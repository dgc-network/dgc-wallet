<?php

/**
 * dgc Payment settings
 *
 * @author dgc.network
 */
if ( ! class_exists( 'dgc_Payment_Extensions_Settings' ) ):

    class dgc_Payment_Extensions_Settings {
        /* setting api object */

        private $settings_api;

        /**
         * Class constructor
         * @param object $settings_api
         */
        public function __construct( $settings_api) {
            $this->settings_api = $settings_api;
            add_action( 'admin_init', array( $this, 'plugin_settings_page_init' ) );
            add_action( 'admin_menu', array( $this, 'admin_menu' ), 65);
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
            add_action( 'dgc_payment_form_bottom__payment_settings_extensions_general', array( $this, 'display_extensions' ) );
        }

        /**
         * wc payment menu
         */
        public function admin_menu() {
            //add_submenu_page( 'dgc_payment', __( 'Extensions', 'text-domain' ), __( 'Extensions', 'text-domain' ), 'manage_woocommerce', 'dgc-payment-extensions', array( $this, 'plugin_page' ) );
        }

        /**
         * admin init 
         */
        public function plugin_settings_page_init() {
            //set the settings
            $this->settings_api->set_sections( $this->get_settings_sections() );
            foreach ( $this->get_settings_sections() as $section) {
                if (method_exists( $this, "update_option_{$section['id']}_callback" ) ) {
                    add_action( "update_option_{$section['id']}", array( $this, "update_option_{$section['id']}_callback" ), 10, 3);
                }
            }
            $this->settings_api->set_fields( $this->get_settings_fields() );
            //initialize settings
            $this->settings_api->admin_init();
        }

        /**
         * Enqueue scripts and styles
         */
        public function admin_enqueue_scripts() {
            $screen = get_current_screen();
            $screen_id = $screen ? $screen->id : '';
            $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
            //wp_register_script( 'dgc-payment-admin-settings', dgc_payment()->plugin_url() . '/assets/js/admin/admin-settings' . $suffix . '.js', array( 'jquery' ), DGC_PAYMENT_PLUGIN_VERSION);
            wp_register_script( 'dgc-payment-admin-settings', dgc_payment()->plugin_url() . '/assets/js/admin/admin-settings.js', array( 'jquery' ), DGC_PAYMENT_PLUGIN_VERSION);
            if (in_array( $screen_id, array( 'dgc_payment_page_dgc-payment-extensions', 'dgc_payment_page_dgc-payment-extensions' ) ) ) {
                wp_enqueue_style( 'dashicons' );
                wp_enqueue_style( 'wp-color-picker' );
                wp_enqueue_style( 'dgc_payment_admin_styles' );
                wp_add_inline_style( 'dgc_payment_admin_styles', 'tr.licence_key_nonce{ display:none; }' );
                wp_enqueue_media();
                wp_enqueue_script( 'wp-color-picker' );
                wp_enqueue_script( 'jquery' );
                wp_enqueue_script( 'dgc-payment-admin-settings' );
                $localize_param = array(
                    'screen_id' => $screen_id
                );
                wp_localize_script( 'dgc-payment-admin-settings', 'dgc_payment_admin_settings_param', $localize_param);
            }
        }

        /**
         * Setting sections
         * @return array
         */
        public function get_settings_sections() {
            $sections = array(
                array(
                    'id' => '_payment_settings_extensions_general',
                    'title' => __( 'Extensions', 'text-domain' ),
                    'icon' => 'dashicons-admin-plugins',
                )
            );
            return apply_filters( 'dgc_payment_extensions_settings_sections', $sections);
        }

        /**
         * Returns all the settings fields
         *
         * @return array settings fields
         */
        public function get_settings_fields() {
            $settings_fields = array(
            );
            return apply_filters( 'dgc_payment_extensions_settings_filds', $settings_fields);
        }

        /**
         * display plugin settings page
         */
        public function plugin_page() {
            echo '<div class="wrap wc_addons_wrap">';
            settings_errors();
            echo '<div class="payment-settings-extensions-wrap">';
            $this->settings_api->show_navigation();
            $this->settings_api->show_forms();
            echo '</div>';
            echo '</div>';
        }

        public function display_extensions() {
            ?>
            <style type="text/css">
                div#_payment_settings_extensions_general h2 {
                    display: none;
                }
                .wc_addons_wrap .addons-column{
                    padding: 0 !important;
                }
            </style>
            <div class="addons-featured">
                <div class="addons-banner-block">
                    <h1>Obtain Superpowers to get the best out of dgcPay </h1>
                    <p>These power boosting extensions can unlock the ultimate potential for your site.</p>
                    <div class="addons-banner-block-items">
                        <div class="addons-banner-block-item">
                            <div class="addons-banner-block-item-icon">
                                <img class="addons-img" src="https://res.cloudinary.com/subrata91/image/upload/v1554208810/dgc_Payment%20Extensions/Payment-Withdrawl_01.jpg">
                            </div>
                            <div class="addons-banner-block-item-content">
                                <h3>Payment Withdrawal</h3>
                                <p>Let your users withdraw their Payment balance to bank and other digital accounts like PayPal with this awesome addon.</p>
                                <a target="_blank" class="addons-button addons-button-solid" href="https://dgc.network/product/dgc-payment-withdrawal/">
                                    From: $49		</a>
                            </div>
                        </div>
                        <div class="addons-banner-block-item">
                            <div class="addons-banner-block-item-icon">
                                <img class="addons-img" src="https://res.cloudinary.com/subrata91/image/upload/v1554208810/dgc_Payment%20Extensions/Payment-Importer_01.jpg">
                            </div>
                            <div class="addons-banner-block-item-content">
                                <h3>Payment Importer</h3>
                                <p>Payment importer addon enables you to modify the Payment balances of multiple or all users with just one CSV import, hassle free.</p>
                                <a target="_blank" class="addons-button addons-button-solid" href="https://dgc.network/product/dgc-payment-importer/">
                                    From: $15		</a>
                            </div>
                        </div>
                        <div class="addons-banner-block-item">
                            <div class="addons-banner-block-item-icon">
                                <img class="addons-img" src="https://res.cloudinary.com/subrata91/image/upload/v1554208812/dgc_Payment%20Extensions/dgcpayment-coupons.png">
                            </div>
                            <div class="addons-banner-block-item-content">
                                <h3>Payment Coupons</h3>
                                <p>Payment Coupons add-on is the coupon system of Payment. Coupons are a great way to offer rewards to your customers, coupons to be automatically redeemed to the customer's payment if its restrictions are met.</p>
                                <a target="_blank" class="addons-button addons-button-solid" href="https://dgc.network/product/dgc-payment-coupons/">
                                    From: $39		</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="addons-column-section">
                    <div class="addons-column">
                        <div class="addons-column-block">
                            <h1>Integrate with third party add-ons.</h1>
                            <div class="addons-column-block-item">
                                <div class="addons-column-block-item-icon">
                                    <img class="addons-img" src="https://res.cloudinary.com/subrata91/image/upload/v1554208814/dgc_Payment%20Extensions/affiliatewp-dgc-payment.png">
                                </div>
                                <div class="addons-column-block-item-content">
                                    <h3>Payment AffiliateWP</h3>
                                    <a class="addons-button addons-button-solid" href="https://dgc.network/product/dgcpayment-affiliatewp/">
                                        From: $15		</a>
                                    <p>Pay AffiliateWP referrals as Payment credit.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="addons-column"></div>
                </div>
            </div>
            <?php

        }

    }

endif;

new dgc_Payment_Extensions_Settings(dgc_payment()->settings_api);
