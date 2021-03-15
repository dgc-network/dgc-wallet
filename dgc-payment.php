<?php

/**
 * Plugin Name: dgc-payment
 * Plugin URI: https://wordpress.org/plugins/dgc-payment/
 * Description: The leading payment plugin for WooCommerce with partial payment, refunds, cashbacks and what not!
 * Author: dgc.network
 * Author URI: https://dgc.network/
 * Version: 1.0.0
 * Requires at least: 4.4
 * Tested up to: 5.2
 * WC requires at least: 3.0
 * WC tested up to: 3.6
 * 
 * Text Domain: dgc-payment
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * Define DGC_PAYMENT_PLUGIN_FILE.
 */
if ( ! defined( 'DGC_PAYMENT_PLUGIN_FILE' ) ) {
    define( 'DGC_PAYMENT_PLUGIN_FILE', __FILE__ );
}

/*
 * Include JsonRpc file.
 */
if ( ! class_exists( 'JsonRpc' ) ){
    include_once dirname( __FILE__ ) . '/SimpleJsonRpcClient/json-rpc.php';
	//$client = new JsonRpc( 'http://[SERVER]/json_rpc_server.php' );
	//$result = $client->add( 2, 2 ); // returns 4
	$client = new JsonRpc( 'http://DiGiCoin:dIgIcOIN@165.232.130.97:7998' );
	$result = $client->__call( 'getinfo' );
}


/*
 * Include dependencies file.
 */
if ( ! class_exists( 'dgc_Payment_Dependencies' ) ){
    include_once dirname( __FILE__ ) . '/includes/class-dgc-payment-dependencies.php';
}

/*
 * Include the main class.
 */
if ( ! class_exists( 'dgc_Payment' ) ) {
    include_once dirname( __FILE__ ) . '/includes/class-dgc-payment.php';
}

function dgc_payment(){
    return dgc_Payment::instance();
}

$GLOBALS['dgc-payment'] = dgc_payment();

/**
 * dgc Payment Gateway
 */
/*
$active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
if(dgc_payment_is_woocommerce_active()){
	add_filter('woocommerce_payment_gateways', 'add_dgc_payment_gateway');
	function add_dgc_payment_gateway( $gateways ){
		$gateways[] = 'dgc_Payment_Gateway';
		return $gateways; 
	}

	add_action('plugins_loaded', 'init_dgc_payment_gateway');
	function init_dgc_payment_gateway(){
		require dirname( __FILE__ ) . '/includes/class-dgc-payment-gateway.php';
	}
}
*/
/**
 * @return bool
 */
/*
function dgc_payment_is_woocommerce_active()
{
	$active_plugins = (array) get_option('active_plugins', array());
	if (is_multisite()) {
		$active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
	}
	return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
}
*/
/**
 * dgc API call
 */

/**
 * update $wpdb->prefix for namespace 
 */
/*
//add_action( 'plugins_loaded', 'dgc_API_prefix' );
function dgc_API_prefix() {
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

add_action( 'user_register', 'dgc_API_participant', 10, 1 );
add_action( 'edit_user_profile_update', 'dgc_API_participant');
add_shortcode( 'dgc-api-participant', 'dgc_API_participant' );
*/
/**
 * check the username for query 
 * if the username does NOT exist in users then create a new user
 * if the username existed in users then update the user
 */
/*
function dgc_API_participant() {

	$dgc_API_args = array(
		'query'	=> array(
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
	$dgc_API_res = dgc_API_call('/isUsernameExists', 'POST', $dgc_API_args);
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
	}
}

function dgc_API_authorization() {
	$dgc_API_args = array(
		'username'	=> get_userdata(get_current_user_id())->user_login,
		'password'	=> get_userdata(get_current_user_id())->user_pass,
	);
	$dgc_API_res = dgc_API_call('/authorization', 'POST', $dgc_API_args);
	update_user_meta(get_current_user_id(), 'authorization', json_decode($dgc_API_res['body'])->authorization);
}

add_shortcode( 'dgc-api-test', 'dgc_API_test_shortcode' );
function dgc_API_test_shortcode() {
	return json_encode(get_payment_transactions());
	return dgc_API_last_exchange_shortcode();
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
*/
