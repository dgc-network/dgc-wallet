<?php

/**
 * Don't forget to include composer autoloader by uncommenting line below
 * if you're not already done it anywhere else in your project.
 **/
/*
use Denpa\Bitcoin\Client as BitcoinClient;

function bitcoin_client_shortcode() {
	$bitcoind = new BitcoinClient('http://DiGitalCoin:dIgITALcOIN@165.232.130.97:7998/');

}
add_shortcode( 'dgc-getinfo', 'bitcoin_client_shortcode' );
*/

use SimpleJsonRpcClient\Client\HttpPostClient as Client;

use SimpleJsonRpcClient\Request\Request;
use SimpleJsonRpcClient\Exception\BaseException;
use SimpleJsonRpcClient\Response\Response;

function json_rpc_shortcode() {
    // Initialize the client. Credentials are optional.
    //$client = new Client('localhost', 'username', 'password');
    $client = new Client('165.232.130.97:7998', 'DiGitalCoin', 'dIgITALcOIN');

    try {
	    $request = new Request('getinfo');
	    //$response = $client->sendRequest($request);
		echo 'I am here!';
        //return $response;
/*
	// Send a request without parameters. The "id" will be added automatically unless supplied.
	// Request objects return their JSON representation when treated as strings.
	$request = new Request('method');
	$response = $client->sendRequest($request);
	
	// Send a request with parameters specified as an array
	$request = new Request('method', array('param1'=>'value1'));
	$response = $client->sendRequest($request);
	
	// Send a request with parameters specified as an object
	$params = new stdClass();
	$params->param1 = 'value1';
	$request = new Request('method', $params);
	$response = $client->sendRequest($request);
	
	// Send a parameter-less request with specific "id"
	$request = new Request('method', null, 2);
	$response = $client->sendRequest($request);
*/    
    }
    catch (BaseException $e) {
	    echo $e->getMessage();
    }
}
add_shortcode( 'dgc-getinfo', 'json_rpc_shortcode' );

