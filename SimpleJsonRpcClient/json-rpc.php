<?php
/**
 * Class to call remote methods via protocol JSON-RPC 2.0
 * Includes server and client functionality
 *
 * According to official JSON-RPC 2.0 specification
 * http://groups.google.com/group/json-rpc/web/json-rpc-2-0
 * Excluding "notifications" and "batch mode"
 *
 * Usage example:
 *
 * 1. Server
 *
 * class JsonRpcServer
 * {
 * public function add( $a, $b )
 * {
 * return $a + $b;
 * }
 * }
 *
 * $server = new JsonRpc( new JsonRpcServer() );
 * $server->process();
 *
 * 2. Client
 *
 * $client = new JsonRpc( 'http://[SERVER]/json_rpc_server.php' );
 * $result = $client->add( 2, 2 ); // returns 4
 *
 * @author ptrofimov
 */
class JsonRpc
{
	const JSON_RPC_VERSION = '2.0';

	private $_server_url, $_server_object;

	public function __construct( $pServerUrlOrObject )
	{
		if ( is_string( $pServerUrlOrObject ) )
		{
			if ( !$pServerUrlOrObject )
			{
				throw new Exception( 'URL string can\'t be empty' );
			}
			$this->_server_url = $pServerUrlOrObject;
		}
		elseif ( is_object( $pServerUrlOrObject ) )
		{
			$this->_server_object = $pServerUrlOrObject;
		}
		else
		{
			throw new Exception( 'Input parameter must be URL string or server class object' );
		}
	}

	public function __call( $pMethod, array $pParams )
	{
		if ( is_null( $this->_server_url ) )
		{
			throw new Exception( 'This is server JSON-RPC object: you can\'t call remote methods' );
		}
		$request = new stdClass();
		$request->jsonrpc = self::JSON_RPC_VERSION;
		$request->method = $pMethod;
		$request->params = $pParams;
		$request->id = md5( uniqid( microtime( true ), true ) );
		$request_json = json_encode( $request );
		$ch = curl_init();
		curl_setopt_array( $ch,
			array( CURLOPT_URL => $this->_server_url, CURLOPT_HEADER => 0, CURLOPT_POST => 1,
				CURLOPT_POSTFIELDS => $request_json, CURLOPT_RETURNTRANSFER => 1 ) );
		$response_json = curl_exec( $ch );
		if ( curl_errno( $ch ) )
		{
			throw new Exception( curl_error( $ch ), curl_errno( $ch ) );
		}
		if ( curl_getinfo( $ch, CURLINFO_HTTP_CODE ) != 200 )
		{
			throw new Exception( sprintf( 'Curl response http error code "%s"',
				curl_getinfo( $ch, CURLINFO_HTTP_CODE ) ) );
		}
		curl_close( $ch );
		$response = $this->_parseJson( $response_json );
		$this->_checkResponse( $response, $request );
		return $response->result;
	}

	public function process()
	{
		if ( is_null( $this->_server_object ) )
		{
			throw new Exception( 'This is client JSON-RPC object: you can\'t process request' );
		}
		ob_start();
		$request_json = file_get_contents( 'php://input' );
		$response = new stdClass();
		$response->jsonrpc = self::JSON_RPC_VERSION;
		try
		{
			$request = $this->_parseJson( $request_json );
			$this->_checkRequest( $request );
			$response->result = call_user_func_array(
				array( $this->_server_object, $request->method ), $request->params );
			$response->id = $request->id;
		}
		catch ( Exception $ex )
		{
			$response->error = new stdClass();
			$response->error->code = $ex->getCode();
			$response->error->message = $ex->getMessage();
			$response->id = null;
		}
		ob_clean();
		echo json_encode( $response );
	}

	private function _parseJson( $pData )
	{
		$data = json_decode( $pData, false, 32 );
		if ( is_null( $data ) )
		{
			throw new Exception( 'Parse error', -32700 );
		}
		return $data;
	}

	private function _checkRequest( $pObject )
	{
		if ( !is_object( $pObject ) || !isset( $pObject->jsonrpc ) || $pObject->jsonrpc !== self::JSON_RPC_VERSION || !isset(
			$pObject->method ) || !is_string( $pObject->method ) || !$pObject->method || ( isset(
			$pObject->params ) && !is_array( $pObject->params ) ) || !isset( $pObject->id ) )
		{
			throw new Exception( 'Invalid Request', -32600 );
		}
		if ( !is_callable( array( $this->_server_object, $pObject->method ) ) )
		{
			throw new Exception( 'Method not found', -32601 );
		}
		if ( is_null( $pObject->params ) )
		{
			$pObject->params = array();
		}
	}

	private function _checkResponse( $pObject, $pRequest )
	{
		if ( !is_object( $pObject ) || !isset( $pObject->jsonrpc ) || $pObject->jsonrpc !== self::JSON_RPC_VERSION || ( !isset(
			$pObject->result ) && !isset( $pObject->error ) ) || ( isset( $pObject->result ) && ( !isset(
			$pObject->id ) || $pObject->id !== $pRequest->id ) ) || ( isset( $pObject->error ) && ( !is_object(
			$pObject->error ) || !isset( $pObject->error->code ) || !isset( $pObject->error->message ) ) ) )
		{
			throw new Exception( 'Invalid Response', -32600 );
		}
		if ( isset( $pObject->error ) )
		{
			throw new Exception( $pObject->error->message, $pObject->error->code );
		}
	}
}