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
