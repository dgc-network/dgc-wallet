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
add_action( 'plugins_loaded', 'dgc_API_init' );
function dgc_API_init() {
	/**
	 * update $wpdb->prefix for namespace 
	 */
	global $wpdb;
	$array = array();
	$string = '';
	$wpdb->prefix = '';
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
		$wpdb->prefix .= $item . '_';
	}
}

//add_action( 'plugins_loaded', 'dgc_API_login', 10, 1 );
//add_action( 'user_register', 'dgc_API_login', 10, 1 );
//add_action( 'edit_user_profile_update', 'dgc_API_login');
//add_shortcode( 'dgc-api-login', 'dgc_API_login' );
//add_action( 'wp_login', 'dgc_API_login' );
function dgc_API_login() {
    if ( null == get_user_meta(get_current_user_id(), "privateKey", true ) ) {
        dgc_API_participant();
    }
}

add_action( 'user_register', 'dgc_API_participant', 10, 1 );
add_action( 'edit_user_profile_update', 'dgc_API_participant');
add_shortcode( 'dgc-api-login', 'dgc_API_participant' );
function dgc_API_participant() {
	/**
	 * check the username for query 
	 * if the username does NOT exist in users then create a new user
	 * if the username existed in users then update the user
	 */

	$dgc_API_args = array(
		'query'	=> array(
			//'email'	=> get_userdata(get_current_user_id())->user_email,
			'username'	=> get_userdata(get_current_user_id())->user_login,
		),
		'data'	=> array(
			'username'		=> get_userdata(get_current_user_id())->user_login,
			//'password'		=> get_userdata(get_current_user_id())->user_login,
			'publicKey'		=> get_user_meta(get_current_user_id(), "publicKey", true ),
			'email'			=> get_userdata(get_current_user_id())->user_email,
			'name'			=> get_userdata(get_current_user_id())->display_name,
			'privateKey'	=> get_user_meta(get_current_user_id(), "privateKey", true ),
			//'encryptedKey'	=> get_user_meta(get_current_user_id(), "encryptedKey", true ),
			'hashedPassword'=> get_userdata(get_current_user_id())->user_pass,
		)
	);
	$dgc_API_res = dgc_API_call('/retrieveUsers', 'POST', $dgc_API_args);
	//return json_encode($dgc_API_res);
	if (json_decode($dgc_API_res['body']) == []){		
		dgc_API_make_privateKey();
		$dgc_API_res = dgc_API_call('/createParticipant', 'POST', $dgc_API_args);
	} else {
		//$dgc_API_res = dgc_API_call('/updateParticipants', 'POST', $dgc_API_args);
	}
	dgc_API_authorization();
	//return json_encode($dgc_API_res);
}

function dgc_API_make_privateKey() {
	if (null == get_user_meta(get_current_user_id(), "privateKey", true ) ) {
		$dgc_API_res = dgc_API_call('/makePrivateKey', 'POST');
		update_user_meta(get_current_user_id(), 'privateKey', json_decode($dgc_API_res['body'])->privateKey);
		update_user_meta(get_current_user_id(), 'publicKey', json_decode($dgc_API_res['body'])->publicKey);
		return json_encode($dgc_API_res);
	} else {
		return 'privateKey: ' . get_user_meta(get_current_user_id(), "privateKey", true );
	}
}

function dgc_API_authorization() {
	$dgc_API_args = array(
		'username'	=> get_userdata(get_current_user_id())->user_login,
		'password'	=> get_userdata(get_current_user_id())->user_pass,
	);
	$dgc_API_res = dgc_API_call('/authorization', 'POST', $dgc_API_args);
	update_user_meta(get_current_user_id(), 'authorization', json_decode($dgc_API_res['body'])->authorization);
	return json_encode($dgc_API_res);
}

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

function dgc_API_call($dgc_API_endpoint, $dgc_API_method = 'GET', $dgc_API_args = []) {

    $dgc_API_args['privateKey'] = get_user_meta(get_current_user_id(), "privateKey", true );
    
    $wp_request_headers = array(
		'Content-Type' => 'application/json',
		'authorization'=> get_user_meta(get_current_user_id(), "authorization", true ),
    );
	
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

