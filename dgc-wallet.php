<?php

/**
 * Plugin Name: dgc-wallet
 * Plugin URI: https://wordpress.org/plugins/dgc-wallet/
 * Description: The leading payment plugin for WooCommerce with partial payment, refunds, cashbacks and what not!
 * Author: dgc.network
 * Author URI: https://dgc.network/
 * Version: 1.0.0
 * Requires at least: 4.4
 * Tested up to: 5.2
 * WC requires at least: 3.0
 * WC tested up to: 3.6
 * 
 * Text Domain: dgc-wallet
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * Define DGC_WALLET_PLUGIN_FILE.
 */
if ( ! defined( 'DGC_WALLET_PLUGIN_FILE' ) ) {
    define( 'DGC_WALLET_PLUGIN_FILE', __FILE__ );
}

/*
 * Include wp-bitcoin-wallet
 */
//include_once dirname( __FILE__ ) . '/wp-bitcoin-wallet/wp-bitcoin-wallet.php';
include_once dirname( __FILE__ ) . '/wp-bitcoin-wallet/widget.php';

/*
 * Include dependencies file.
 */
if ( ! class_exists( 'dgc_Wallet_Dependencies' ) ){
    include_once dirname( __FILE__ ) . '/includes/class-dgc-wallet-dependencies.php';
}

/*
 * Include the main class.
 */
if ( ! class_exists( 'dgc_Wallet' ) ) {
    include_once dirname( __FILE__ ) . '/includes/class-dgc-wallet.php';
}

function dgc_wallet(){
    return dgc_Wallet::instance();
}

$GLOBALS['dgc-wallet'] = dgc_wallet();

/*
Plugin Name: XYZ Coin Adapter
Plugin URI: https://www.dashed-slug.net/bitcoin-altcoin-wallets-wordpress-plugin/full-node-multi-coin-adapter-extension/
Description:  Example of how to add an RPC-compatible wallet to the Full Node Multi Coin Adapter for Bitcoin and Altcoin Wallets
Version: 0.1.0
Author: Alex Georgiou <alexgeorgiou@gmail.com>
Author URI: http://alexgeorgiou.gr
*/

function wallets_multiadapter_coins_filter( $coins ) {
	$coins['XYZ'] = array( // replace XYZ with the coin's ticker symbol in this line

		// Coin symbol (again)
		'symbol' => 'XYZ',

		// Coin name
		'name' => 'XYZ coin',

		// Default withdrawal fee (coin adapter settings override this)
		'wd fee' => '0.005',

		// Default internal transaction fee (coin adapter settings override this)
		'move fee' => '0.0005',

		// Default min confirmation count required for deposits (coin adapter settings override this)
		'minconf' => 12,

		// Default RPC port (coin adapter settings override this)
		'port number' => 12345,

		// Whether the wallet supports -walletnotify
		'tx notify' => 1,

		// Whether the wallet supports -blocknotify
		'block notify' => 1,

		// Whether the wallet supports -alertnotify (some wallets have deprecated this)
		'alert notify' => 0,

		// Comma separated list of hex bytes, needed for frontend validation of withdraw addresses. Leave blank for no validation.
		'versions' => '',

		// An sprintf() pattern for deposit address QR Code URI. If unsure, set to '%s'.
		'qr pattern' => 'xyzcoin:%s',

		// An sprintf() pattern for displaying amounts. If unsure, leave to '%01.8f'.
		'amount pattern' => '%01.8f',

		// Default sprintf() pattern for URI to block explorer transaction page. Can be overriden with WordPress filter.
		'explorer tx uri' => 'https://blockexplorer.example.com/tx/%s/',

		// Default sprintf() pattern for URI to block explorer address page. Can be overriden with WordPress filter.
		'explorer address uri' => 'https://blockexplorer.example.com/address/%s/',

		// URL to an 64x64 icon for the coin. Or leave empty to pull the icon from 'assets/sprites/SYMBOL.png'.
		'icon url' => 'http://www.example.com/xyz-coin-icon-64x64.png',
	);

	return $coins;
 }

add_filter( 'wallets_multiadapter_coins', 'wallets_multiadapter_coins_filter' );

?>
