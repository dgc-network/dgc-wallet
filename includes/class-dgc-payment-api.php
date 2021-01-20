<?php
/**
 * dgc_Payment REST API
 * 
 * @author dgc.network <admin@dgc.network>
 * @since 1.2.5
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
if ( ! class_exists( 'dgc_Payment_API' ) ) {

    class dgc_Payment_API {

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
            include_once dirname( __FILE__) . '/api/class-wc-rest-dgc-payment-controller.php';
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
                'WC_REST_dgc_Payment_Controller'
            );
            foreach ( $controllers as $controller) {
                $dgc_payment_api = new $controller();
                $dgc_payment_api->register_routes();
            }
        }

    }

}