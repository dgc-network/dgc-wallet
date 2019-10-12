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

/**
 * dgc API call
 */
add_action( 'plugins_loaded', 'dgc_API_prefix' );
//add_action( 'user_register', 'dgc_API_create_user_shortcode', 10, 1 );
//add_action( 'edit_user_profile_update', 'dgc_API_update_user_shortcode');
add_shortcode( 'dgc-api-test', 'dgc_API_test_shortcode' );

function dgc_API_test_shortcode() {
	return dgc_API_last_exchange_shortcode();
	return dgc_API_retrieve_exchanges_shortcode();
	//return dgc_API_create_participant_shortcode();
	//return dgc_API_apply_DGC_credit_shortcode();
	return dgc_API_buy_DGC_proposal_shortcode();
	//return dgc_API_sell_DGC_proposal_shortcode();
	return dgc_API_transfer_DGC_proposal_shortcode();
	//return dgc_API_transfer_custodianship_shortcode();
	//return dgc_API_retrieve_proposals_shortcode();
	//return wc_custom_product_tables_activate();
	return dgc_migrate_data_shortcode();
	return dgc_API_create_record_shortcode();
	return dgc_API_retrieve_records_shortcode();
	//return dgc_API_update_records_shortcode();
	//return dgc_API_delete_records_shortcode();
	return dgc_API_retrieve_participants_shortcode();
	//return dgc_API_update_participants_shortcode();
	//return dgc_API_mapsApiKey();
}

function dgc_API_last_exchange_shortcode() {
	$dgc_API_args = array(
		'query'	=> array(
			'currencyIsoCodes'	=> 'TWD',
		),
	);
	$dgc_API_res = dgc_API_call('/lastExchange', 'POST', $dgc_API_args);
	return json_encode($dgc_API_res);
	return $dgc_API_res['body'];
}

function dgc_API_retrieve_exchanges_shortcode() {
	$dgc_API_args = array(
		'query'	=> array(
			'currencyIsoCodes'	=> 'USD',
		),
	);
	$dgc_API_res = dgc_API_call('/retrieveExchanges', 'POST', $dgc_API_args);
	return json_encode($dgc_API_res);
	return $dgc_API_res['body'];
}

function dgc_API_retrieve_proposals_shortcode() {
	$dgc_API_args = array(
		'query'	=> array(
			'role'		=> 'buyDGC',
			'status'	=> 'OPEN',
		),
	);
	$dgc_API_res = dgc_API_call('/retrieveProposals', 'POST', $dgc_API_args);
	//return json_encode($dgc_API_res);
	return $dgc_API_res['body'];
}

function dgc_API_apply_DGC_credit_shortcode() {
	$dgc_API_args = array(
		'data'	=> array(
			'receivingKey'	=> '02f60be85ff7faa45106c2ffefd225deeb6bbd437485ff8b7ad1abdc8cef5d8e09', //receiving_participant_public_key
			'DGC'	=> 10000.00,
		),
	);
	$dgc_API_res = dgc_API_call('/applyDGCoinCredit', 'POST', $dgc_API_args);
	return json_encode($dgc_API_res);
}

function dgc_API_sell_DGC_proposal_shortcode() {
	$dgc_API_args = array(
		'data'	=> array(
			'DGC'	=> 200,
			'TWD'	=> 199.99,  //lowest price to sell
		),
	);
	$dgc_API_res = dgc_API_call('/sellDGCoinProposal', 'POST', $dgc_API_args);
	return json_encode($dgc_API_res);
}

function dgc_API_buy_DGC_proposal_shortcode() {
	$dgc_API_args = array(
		'data'	=> array(
			'DGC'	=> 300,
			'TWD'	=> 301,  //highest price to buy
		),
	);
	$dgc_API_res = dgc_API_call('/buyDGCoinProposal', 'POST', $dgc_API_args);
	return json_encode($dgc_API_res);
}

function dgc_API_transfer_DGC_proposal_shortcode() {
	$dgc_API_args = array(
		'data'	=> array(
			'receivingKey'	=> '02d73ff52fc955d6b6a3bec3453cd2c2aca179317b4bdc144c00433054d223b594', //receiving_participant_public_key
			'DGC'			=> 100,
		),
	);
	$dgc_API_res = dgc_API_call('/transferDGCoinProposal', 'POST', $dgc_API_args);
	return json_encode($dgc_API_res);
}

function dgc_API_answer_DGC_transfer_shortcode() {
	global $wpdb;
	$dgc_API_args = array(
		'query'	=> array(
			'proposalId'	=> 'transferDGC1562575541',
		),
		'data'	=> array(
			'response'	=> 'ACCEPT', //ACCEPT, REJECT, CANCEL,
		),
	);
	$dgc_API_res = dgc_API_call('/answerDGCoinTransfer', 'POST', $dgc_API_args);
	return json_encode($dgc_API_res);
}


function dgc_API_prefix() {
	global $wpdb;
	$array = array();
	$string = '';
	$return = '';
	foreach(str_split($_SERVER['HTTP_HOST']) as $character){
		if ($character == '.') {
			array_push($array, $string);
			$string = '';
		} else {
	    	$string .= $character;
		}
		if ($character == end(str_split($_SERVER['HTTP_HOST']))) {
			array_push($array, $string);
		}
	}
	foreach(array_reverse($array, true) as $item){
		$return .= $item . '_';
	}
	$wpdb->prefix = $return;
}

function dgc_API_call($dgc_API_endpoint, $dgc_API_method = 'GET', $dgc_API_args = []) {

	$wp_request_headers = array(
		'Content-Type' => 'application/json',
		'authorization'=> get_user_meta(get_current_user_id(), "authorization", true ),
    );	

	$dgc_API_args['privateKey'] = get_user_meta(get_current_user_id(), "privateKey", true );
	
	//Populate the correct endpoint for the API request
	//$dgc_API_url = get_option('endpoint_field_option');
	//if ( isset( $dgc_API_url ) ) {
    if ( null !== get_option('endpoint_field_option') ) {
        $dgc_API_url = get_option('endpoint_field_option');
    } else {
		$dgc_API_url = "https://api.scouting.tw/v1";
	}
    $dgc_API_url = "https://api.scouting.tw/v1";
	return wp_remote_request(($dgc_API_url . $dgc_API_endpoint),
        array(
            'method'    => $dgc_API_method,
            'headers'   => $wp_request_headers,
            'body'   	=> json_encode($dgc_API_args),
		));
}

