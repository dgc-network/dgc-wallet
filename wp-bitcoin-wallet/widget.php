<?php

class WPBW_Widget {
	private $jsonrpc;
	private $account;
	private $dgc_client;
	private $receive_address;
	private $change_address;

	public function register() {
		//require_once('jsonRPCClient.php');
		$options = get_option('wpbw_plugin_options');
		//$user = $options['bitcoind_rpc_username'];
		//$pass = $options['bitcoind_rpc_password'];
		//$host = $options['bitcoind_rpc_host'];
		//$port = $options['bitcoind_rpc_port'];
		$rpc_host = dgc_wallet()->settings_api->get_option( 'bitcoind_rpc_host', '_wallet_settings_conf' );
		$rpc_port = dgc_wallet()->settings_api->get_option( 'bitcoind_rpc_port', '_wallet_settings_conf' );
		$rpc_user = dgc_wallet()->settings_api->get_option( 'bitcoind_rpc_username', '_wallet_settings_conf' );
		$rpc_pass = dgc_wallet()->settings_api->get_option( 'bitcoind_rpc_password', '_wallet_settings_conf' );
		$passphrase = dgc_wallet()->settings_api->get_option( 'wallet_passphrase', '_wallet_settings_conf' );
		$wp_user = wp_get_current_user();
		$current_user_id = get_current_user_id();

		if($wp_user != 0) {
			//$this->jsonrpc = new jsonRPCClient('http://'.$rpc_user.':'.$rpc_pass.'@'.$rpc_host.':'.$rpc_port.'/');
/*			
			$this->account = $options['bitcoind_account_prefix'].hash("sha256", $wp_user->user_login);
			//$this->jsonrpc = new jsonRPCClient('http://'.$user.':'.$pass.'@'.$host.':'.$port.'/');
			//$this->dgc_client = new dgcClient($rpc_host, $rpc_port, $rpc_user, $rpc_pass);
			$this->receive_address = get_user_meta( $current_user_id, 'receive_address' , true );
			$this->change_address = get_user_meta( $current_user_id, 'change_address' , true );
			if ($this->receive_address=='') {
				$this->receive_address = $this->jsonrpc->getnewaddress();
				//$this->receive_address = $this->dgc_client->getnewaddress();
				update_user_meta( $current_user_id, 'receive_address' , $this->receive_address );
			}
			if ($this->change_address=='') {
				$this->change_address = $this->jsonrpc->getrawchangeaddress();
				//$this->change_address = $this->dgc_client->getrawchangeaddress();
				update_user_meta( $current_user_id, 'change_address' , $this->change_address );
			}
*/
			wp_add_dashboard_widget('wpbw_widget', 'Wallet', array($this, 'display'));
		} else {
			// We shouldn't ever get here, since only logged-in users can access the dashboard.
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
	}

	public function display() {
		$this->handle_post();

		?>
		<label>Balance:</label>		
		<?php
		$current_user_id = get_current_user_id();
		$first_name = get_user_meta( $current_user_id, 'first_name' , true );
		$last_name = get_user_meta( $current_user_id, 'last_name' , true );
		$balance = dgc_wallet()->wallet_core->get_wallet_balance($current_user_id);
		$output = '<pre>';
		$output .= $first_name . ' ' . $last_name . ': ' . $balance;
		$output .= '</pre><br>';
		echo $output;
		?>

		<strong>Send Coins:</strong>
		<br />
		<br />
		<form action="" method="post">
		<?php wp_nonce_field('wpbw_widget_nonce'); ?>
		<label>Number of coins:</label>
		<input name="wpbw_send_numcoins" type="text" size="10" />
		<br />
		<label>Destination address:</label>
		<input name="wpbw_send_address" type="text" size="40" />
		<br />
		<input name="wpbw_widget_send" type="submit" value="Send" />
		</form>
		<br />
		<br />
		<strong>Last 10 Transactions:</strong>
		<br />
		<br />
		<ul>
		<?php
/*		
		$transactions = array_reverse($this->jsonrpc->listtransactions($this->account));

		foreach($transactions as $t) {
			?>
			<li><?php echo $t['txid']; ?></li>
			<?php
		}
*/		
		?>
		</ul>

		<label>List Transactions:</label>
		<?php 
        $result = dgc_wallet()->wallet_core->list_transactions($current_user_id);
    	$o = '<pre>[<br>'; 
		foreach ($result as $array_value) {
			$o .= '{<br>'; 
			foreach ($array_value as $key=>$value) {
				$o .= '  "'. $key . '": ' . $value . '<br>';
			}
			$o .= '}<br>'; 
    	}
    	$o .= ']</pre>'; 
		echo $o;
		?>
		</br>

		<?php
	}

	public function handle_post() {
		if(isset($_REQUEST['wpbw_widget_send'])) {
			check_admin_referer('wpbw_widget_nonce');
			//TODO: Sanitize inputs!
			//$transaction = $this->jsonrpc->sendfrom($this->account, $_REQUEST['wpbw_send_address'], (float)$_REQUEST['wpbw_send_numcoins']);

            $txid = 'test';
			$recipient= $_REQUEST['wpbw_send_address'];
            $send_amount = (float)$_REQUEST['wpbw_send_numcoins'];
			dgc_wallet()->wallet_core->init_rpc();
            $passphrase = dgc_wallet()->settings_api->get_option( 'wallet_passphrase', '_wallet_settings_conf' );
			$addresses = array();
            $current_user_id = get_current_user_id();
			$sender = get_user_meta( $current_user_id, 'receive_address' , true );
			$sender_change = get_user_meta( $current_user_id, 'change_address' , true );
			array_push($addresses, $sender);
			$top1_address = 'DQMLne3GZHo4uiu5nWsxdFsTrrmxYJnubS';
			array_push($addresses, $top1_address);

			$result = dgc_wallet()->wallet_core->jsonrpc->listunspent(6, 9999999, $addresses);
			dgc_wallet()->wallet_core->jsonrpc->walletpassphrase($passphrase, 60);
			$send_amount_balance = (float)$_REQUEST['wpbw_send_numcoins'];
			$transactions = array();
			foreach ($result as $array_value) {
				$utxo_object->txid = $array_value["txid"];
				$utxo_object->vout = (int)$array_value["vout"];
				$utxo_amount = (float)$array_value["amount"];
				array_push($transactions, $utxo_object);
				if ( $utxo_amount >= $send_amount_balance ) {
					$outputs->$recipient = $send_amount;
					$outputs->$sender_change = $utxo_amount - $send_amount;
					try {
						$rawtxhex = dgc_wallet()->wallet_core->jsonrpc->createrawtransaction($transactions, $outputs);
						//$fundtx = dgc_wallet()->wallet_core->jsonrpc->fundrawtransaction($rawtxhex);
						//$txid = dgc_wallet()->wallet_core->jsonrpc->sendrawtransaction($fundtx->hex);
						$txid = $passphrase;
						echo "createrawtransaction:".$rawtxhex."<br>";
						//echo "send_amount:".$send_amount."<br>";
						//echo "send_amount_balance:".$send_amount_balance."<br>";
						//echo "txid:".$utxo_object->txid."<br>";
						//echo "vout:".$utxo_object->vout."<br>";
						//echo "utxo_amount:".$utxo_amount."<br>";
					}
                    catch(Exception $e) {
                        //echo 'Message: ' .$e->getMessage();
                        throw new Exception('Message: ' .$e->getMessage());
                    }
					return "end of transaction";
				} else {
					$send_amount_balance = $send_amount_balance - $send_amount;
				}
			}

?>
			<label>Sent, transaction ID is:</label>
			<pre><?php echo $txid; ?>.</pre>
			<br />
			<br />
			<?php
		}
	}

}

$wpbw_widget = new WPBW_Widget();

add_action('wp_dashboard_setup', array($wpbw_widget, 'register'));

?>
