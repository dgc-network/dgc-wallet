<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
if (!class_exists('dgc_Wallet_Dependencies')) {

    class dgc_Wallet_Dependencies {

        private static $active_plugins;

        public static function init() {
            self::$active_plugins = (array) get_option('active_plugins', array());
            if (is_multisite()) {
                self::$active_plugins = array_merge(self::$active_plugins, get_site_option('active_sitewide_plugins', array()));
            }
        }

        /**
         * Check woocommerce exist
         * @return Boolean
         */
        public static function woocommerce_active_check() {
            if (!self::$active_plugins) {
                self::init();
            }
            return in_array('woocommerce/woocommerce.php', self::$active_plugins) || array_key_exists('woocommerce/woocommerce.php', self::$active_plugins);
        }

        /**
         * Check if woocommerce active
         * @return Boolean
         */
        public static function is_woocommerce_active() {
            return self::woocommerce_active_check();
        }
        
        /**
         * Check woocommerce exist
         * @return Boolean
         */
        public static function dgc_wallet_active_check() {
            if (!self::$active_plugins) {
                self::init();
            }
            return in_array('dgc-wallet/dgc-wallet.php', self::$active_plugins) || array_key_exists('dgc-wallet/dgc-wallet.php', self::$active_plugins);
        }

        /**
         * Check if dgc Wallet for WooCommerce active
         * @return Boolean
         */
        public static function is_dgc_wallet_active() {
            return self::dgc_wallet_active_check();
        }

    }

}