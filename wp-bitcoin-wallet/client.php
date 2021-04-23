<?php
//To enable developer mode (no need for an RPC server, replace this file with the snipet at https://gist.github.com/d3e148deb5969c0e4b60 

class dgcClient {
	private $uri;
	private $jsonrpc;

	function __construct($host, $port, $user, $pass) {
		$this->jsonrpc = new jsonRPCClient('http://'.$user.':'.$pass.'@'.$host.':'.$port.'/');
		//$this->uri = "http://" . $user . ":" . $pass . "@" . $host . ":" . $port . "/";
		//$this->jsonrpc = new jsonRPCClient($this->uri);
	}

	function getBalance($address='') {
		$amount = 0;
		$result = $this->jsonrpc->listunspent();
		foreach ($result as $array_value) {
			if ( $array_value["address"] == $address ) {
				$amount = $amount + $array_value["amount"];
			}
    	}
		return $amount;
	}

    function getNewAddress() {
        return $this->jsonrpc->getnewaddress();
	}

    function getRawChangeAddress() {
        return $this->jsonrpc->getrawchangeaddress();
	}

	function getAddressList($user_session)
	{
		return $this->jsonrpc->getaddressesbyaccount("zelles(" . $user_session . ")");
		//return array("1test", "1test");
	}

	function getTransactionList($user_session)
	{
		return $this->jsonrpc->listtransactions("zelles(" . $user_session . ")", 10);
	}

	function getNewAddress($user_session)
	{
		return $this->jsonrpc->getnewaddress("zelles(" . $user_session . ")");
		//return "1test";
	}

	function withdraw($user_session, $address, $amount)
	{
		return $this->jsonrpc->sendfrom("zelles(" . $user_session . ")", $address, (float)$amount, 6);
		//return "ok wow";
	}
}
?>