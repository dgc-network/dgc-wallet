<?php
//To enable developer mode (no need for an RPC server, replace this file with the snipet at https://gist.github.com/d3e148deb5969c0e4b60 

class dgcClient {
	private $jsonrpc;

	function __construct($host, $port, $user, $pass) {
		$this->jsonrpc = new jsonRPCClient('http://'.$user.':'.$pass.'@'.$host.':'.$port.'/');
	}

	function getbalance( $user_id = '' ) {
		$amount = 0;
		if ( $user_id != '') {
			$addresses = array();
			$receive_address = get_user_meta( $user_id, 'receive_address' , true );
			$change_address = get_user_meta( $user_id, 'change_address' , true );
			array_push($addresses, $receive_address, $change_address);
			$result = $this->jsonrpc->listunspent(6, 9999999, $addresses);
			$result = $this->jsonrpc->listunspent();
			foreach ($result as $array_value) {
				if (( $array_value["address"] == $receive_address ) || ( $array_value["address"] == $change_address )) {
					$amount = $amount + $array_value["amount"];
				}
			}
		}
		return $amount;
	}

	function sendtoaddress( $user_id = '', $amount = 0 ) {
		$txid = '';
		$balance_amount = $amount;
		if ( $user_id != '') {
			$current_user_id = get_current_user_id();
			$addresses = array();
			$send_address = get_user_meta( $current_user_id, 'receive_address' , true );
			$change_address = get_user_meta( $current_user_id, 'change_address' , true );
			$recipient = get_user_meta( $user_id, 'receive_address' , true );
			array_push($addresses, $send_address);
			$result = $this->jsonrpc->listunspent(6, 9999999, $addresses);
			$transactions = array();
			foreach ($result as $array_value) {
				$utxo_object->txid = $array_value["txid"];
				$utxo_object->vout = $array_value["vout"];
				array_push($transactions, $utxo_object);
				if ( $array_value["amount"] >= $balance_amount ) {
					$outputs->$recipient = $amount;
					$outputs->$change_address = $amount - $array_value["amount"];
					$rawtxhex = $this->jsonrpc->createrawtransaction($transactions, $outputs);
					$result = $this->jsonrpc->fundrawtransaction($rawtxhex);
					//$result = $this->jsonrpc->signrawtransaction($rawtxhex);
					//if ($result->complete) {
						$txid = $this->jsonrpc->sendrawtransaction($result->hex);
						return $txid;
					//}					
				} else {
					$balance_amount = $balance_amount - $array_value["amount"];
				}
			}
		}
		return $txid;
	}
}
?>