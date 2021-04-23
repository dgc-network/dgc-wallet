<?php

class WPBW_Widget {
	private $jsonrpc;
	private $account;

	public function register() {
		require_once('jsonRPCClient.php');
		$options = get_option('wpbw_plugin_options');
		//$user = $options['bitcoind_rpc_username'];
		//$pass = $options['bitcoind_rpc_password'];
		//$host = $options['bitcoind_rpc_host'];
		//$port = $options['bitcoind_rpc_port'];
		$rpc_host = dgc_payment()->settings_api->get_option( 'bitcoind_rpc_host', '_payment_settings_digitalcoin' );
		$rpc_port = dgc_payment()->settings_api->get_option( 'bitcoind_rpc_port', '_payment_settings_digitalcoin' );
		$rpc_user = dgc_payment()->settings_api->get_option( 'bitcoind_rpc_username', '_payment_settings_digitalcoin' );
		$rpc_pass = dgc_payment()->settings_api->get_option( 'bitcoind_rpc_password', '_payment_settings_digitalcoin' );
		$passphrase = dgc_payment()->settings_api->get_option( 'wallet_passphrase', '_payment_settings_digitalcoin' );
		$wp_user = wp_get_current_user();

		if($wp_user != 0) {
			$this->account = $options['bitcoind_account_prefix'].hash("sha256", $wp_user->user_login);
			//$this->jsonrpc = new jsonRPCClient('http://'.$user.':'.$pass.'@'.$host.':'.$port.'/');
			$this->jsonrpc = new jsonRPCClient('http://'.$rpc_user.':'.$rpc_pass.'@'.$rpc_host.':'.$rpc_port.'/');
			//$this->jsonrpc = new Client($rpc_host, $rpc_port, $rpc_user, $rpc_pass);

			wp_add_dashboard_widget('wpbw_widget', 'Wallet', array($this, 'display'));
		} else {
			// We shouldn't ever get here, since only logged-in users can access the dashboard.
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
	}

	public function display() {
		$this->handle_post();

		?>
		<label>Block 0 Hash:</label>
		<pre><?php echo $this->jsonrpc->getblockhash(0); ?></pre>
		</br>

		<label>Wallet Info:</label>
		<?php 
		$result = $this->jsonrpc->getwalletinfo(); 
    	$o = '<pre>{<br>'; 
		foreach ($result as $key=>$value) {
        	$o .= '  "'. $key . '": ' . $value . '<br>';
    	}
    	$o .= '}</pre>'; 
		echo $o;
		?>
		</br>

		<label>Receiving address:</label>
		<pre><?php echo $this->jsonrpc->getaccountaddress($this->account); ?></pre>
		</br>

		<label>Balance:</label>
		<pre><?php echo $this->jsonrpc->getbalance($this->account); ?></pre>
		</br>
		</br>

		<label>Balance:</label>
		<pre><?php echo $this->getbalance($this->account); ?></pre>
		</br>
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

	function getBalance()
	{
		return $this->jsonrpc->getbalance();
		//return 21;
	}
}

$wpbw_widget = new WPBW_Widget();

add_action('wp_dashboard_setup', array($wpbw_widget, 'register'));

?>
