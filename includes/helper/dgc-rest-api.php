<?php
/**
 * dgc REST API call
 * 
 * @author dgc.network <admin@dgc.network>
 * @since 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
if ( ! class_exists( 'dgc_REST_API' ) ) {

    class dgc_REST_API {


        public function __construct() {
            // dgc REST API.
            //add_action( 'plugins_loaded', 'dgc_API_prefix' );
            add_action( 'user_register', array( $this, 'dgc_API_participant' ), 10, 1 );
            add_action( 'edit_user_profile_update', array( $this, 'dgc_API_participant' ) );
            add_shortcode( 'dgc-api-participant', array( $this, 'dgc_API_participant' ) );
            add_shortcode( 'dgc-api-test', array( $this, 'dgc_API_test_shortcode' ) );
        }

        function dgc_API_prefix() {
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
        
        /**
         * check the username for query 
         * if the username does NOT exist in users then create a new user
         * if the username existed in users then update the user
         */
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
        
        function dgc_API_test_shortcode() {
            return json_encode(get_transactions());
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
        
                

/**
 * dgc API call
 */


        /**
         * Init WP REST API.
         *
         * @since 1.2.5
         */
        private function rest_api_init() {
            // REST API was included starting WordPress 4.4.
            if ( ! class_exists( 'WP_REST_Server' ) ) {
                return;
            }

            // Init REST API routes.
            add_action( 'rest_api_init', array( $this, 'register_rest_routes' ), 10 );
        }

        /**
         * Include REST API classes.
         *
         * @since 1.2.5
         */
        private function rest_api_includes() {
            include_once dirname( __FILE__) . '/api/class-wc-rest-dgc-wallet-controller.php';
        }

        /**
         * Register REST API routes.
         *
         * @since 1.2.5
         */
        public function register_rest_routes() {
            $this->rest_api_includes();
            $controllers = array(
                // v2 controllers.
                'WC_REST_dgc_Wallet_Controller'
            );
            foreach ( $controllers as $controller) {
                $dgc_wallet_api = new $controller();
                $dgc_wallet_api->register_routes();
            }
        }

    }

}