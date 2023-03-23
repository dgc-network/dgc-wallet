<?php
/**
 * dgc_wallet REST API
 * 
 * @author dgc.network <admin@dgc.network>
 * @since 1.2.5
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
if ( ! class_exists( 'dgc_wallet_API' ) ) {

    class dgc_wallet_API {

        public function __construct() {
            // WP REST API.
            $this->rest_api_init();
        }

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
                'WC_REST_dgc_wallet_Controller'
            );
            foreach ( $controllers as $controller) {
                $dgc_wallet_api = new $controller();
                $dgc_wallet_api->register_routes();
            }
        }

    }

}