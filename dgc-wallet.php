<?php

/**
 * Plugin Name: dgcWallet
 * Plugin URI: https://wordpress.org/plugins/dgc-wallet/
 * Description: The leading wallet plugin for WooCommerce with partial payment, refunds, cashbacks and what not!
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
 *
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define DGC_WALLET_PLUGIN_FILE.
if ( ! defined( 'DGC_WALLET_PLUGIN_FILE' ) ) {
    define( 'DGC_WALLET_PLUGIN_FILE', __FILE__);
}
// include dependencies file
if ( ! class_exists( 'dgc_Wallet_Dependencies' ) ){
    include_once dirname( __FILE__) . '/includes/class-dgc-wallet-dependencies.php';
}

// Include the main class.
if ( ! class_exists( 'dgc_Wallet' ) ) {
    include_once dirname( __FILE__) . '/includes/class-dgc-wallet.php';
}

function dgc_wallet(){
    return dgc_Wallet::instance();
}

$GLOBALS['dgc_wallet'] = dgc_wallet();
