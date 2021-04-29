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
			$this->account = $options['bitcoind_account_prefix'].hash("sha256", $wp_user->user_login);
			//$this->jsonrpc = new jsonRPCClient('http://'.$user.':'.$pass.'@'.$host.':'.$port.'/');
			$this->jsonrpc = new jsonRPCClient('http://'.$rpc_user.':'.$rpc_pass.'@'.$rpc_host.':'.$rpc_port.'/');
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

			wp_add_dashboard_widget('wpbw_widget', 'Wallet', array($this, 'display'));
		} else {
			// We shouldn't ever get here, since only logged-in users can access the dashboard.
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
	}

	public function display() {
		$this->handle_post();

		?>
		<label>Top1 Balance:</label>
		<?php $this->account = 'DQMLne3GZHo4uiu5nWsxdFsTrrmxYJnubS'; ?>
		<?php $output = $this->account . ': '; ?>
		<?php //$output .= $this->dgc_client->getbalance($this->account); ?>
		<pre><?php echo $output; ?></pre>
		</br>

		<label>Balance:</label>		
		<?php
		$current_user_id = get_current_user_id();
		$first_name = get_user_meta( $current_user_id, 'first_name' , true );
		$last_name = get_user_meta( $current_user_id, 'last_name' , true );
		//$balance = $this->jsonrpc->getbalance();
        //$array = dgc_wallet()->wallet_core->listtransactions($user_id, 50, 100);
		$balance = $this->wallet_core->getbalance($current_user_id);
		$output = '<pre>';
		$output .= $first_name . ' ' . $last_name . ': ' . $balance;
		$output .= '</pre><br>';
		echo $output;
		?>

		<label>Wallet Info:</label>
		<?php 
        $result = dgc_wallet()->wallet_core->listtransactions($user_id, 50, 100);
		//$result = $this->jsonrpc->getwalletinfo(); 
    	$o = '<pre>{<br>'; 
		foreach ($result as $key=>$value) {
        	$o .= '  "'. $key . '": ' . $value . '<br>';
    	}
    	$o .= '}</pre>'; 
		echo $o;
		?>
		</br>

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
		$transactions = array_reverse($this->jsonrpc->listtransactions($this->account));

		foreach($transactions as $t) {
			?>
			<li><?php echo $t['txid']; ?></li>
			<?php
		}
		?>
		</ul>
		<?php
	}

	public function handle_post() {
		if(isset($_REQUEST['wpbw_widget_send'])) {
			check_admin_referer('wpbw_widget_nonce');
			//TODO: Sanitize inputs!
			$transaction = $this->jsonrpc->sendfrom($this->account, $_REQUEST['wpbw_send_address'], (float)$_REQUEST['wpbw_send_numcoins']);
			?>
			<label>Sent, transaction ID is:</label>
			<pre><?php echo $transaction; ?>.</pre>
			<br />
			<br />
			<?php
		}
	}

}

$wpbw_widget = new WPBW_Widget();

add_action('wp_dashboard_setup', array($wpbw_widget, 'register'));

?>
