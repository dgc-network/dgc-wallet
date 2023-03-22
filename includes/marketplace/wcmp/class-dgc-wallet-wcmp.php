<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'dgc_Wallet_WCMp' ) ) {

    class dgc_Wallet_WCMp {
        /**
         * The single instance of the class.
         *
         * @var dgc_Wallet_WCMp
         * @since 1.1.10
         */
        protected static $_instance = null;
        
        /**
         * Main instance
         * @return class object
         */
        public static function instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }
        
        public function __construct() {
            add_filter( 'automatic_payment_method', array( $this, 'add_payment_payment_method' ) );
            add_filter( 'wcmp_vendor_payment_mode', array( $this, 'add_payment_vendor_payment_method' ) );
            add_filter( 'wcmp_payment_gateways', array(&$this, 'add_wcmp_payment_payment_gateway' ) );
        }

        public function add_payment_payment_method( $payment_methods ) {
            return array_merge( $payment_methods, array( 'dgc-wallet' => __( 'Payment', 'text-domain' ) ) );
        }

        public function add_payment_vendor_payment_method( $payment_method ) {
            if ( 'Enable' === get_wcmp_vendor_settings( 'payment_method_dgc_wallet', 'payment' ) ) {
                return array_merge( $payment_method, array( 'dgc-wallet' => __( 'Payment', 'text-domain' ) ) );
            }
            return $payment_method;
        }

        public function add_wcmp_payment_payment_gateway( $load_gateways ) {
            $load_gateways[] = 'dgc_WCMp_Payment_Gateway';
            return $load_gateways;
        }

    }

}
dgc_Wallet_WCMp::instance();
