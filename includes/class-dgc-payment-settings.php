<?php

/**
 * dgc Payment settings
 *
 * @author dgc.network
 */
if ( ! class_exists( 'dgc_Payment_Settings' ) ):

    class dgc_Payment_Settings {
        /* setting api object */

        private $settings_api;

        /**
         * Class constructor
         * @param object $settings_api
         */
        public function __construct( $settings_api) {
            $this->settings_api = $settings_api;
            add_action( 'admin_init', array( $this, 'plugin_settings_page_init' ) );
            add_action( 'admin_menu', array( $this, 'admin_menu' ), 60 );
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        }

        /**
         * wc payment menu
         */
        public function admin_menu() {
            add_submenu_page( 'dgc-payment', __( 'Settings', 'text-domain' ), __( 'Settings', 'text-domain' ), 'manage_woocommerce', 'dgc-payment-settings', array( $this, 'plugin_page' ) );
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
            //if (in_array( $screen_id, array( 'dgc_payment_page_dgc-payment-settings', 'dgc_payment_page_dgc-payment-settings' ) ) ) {
                wp_enqueue_style( 'dashicons' );
                wp_enqueue_style( 'wp-color-picker' );
                wp_enqueue_style( 'dgc_payment_admin_styles' );
                wp_enqueue_media();
                wp_enqueue_script( 'wp-color-picker' );
                wp_enqueue_script( 'jquery' );
                wp_enqueue_script( 'dgc-payment-admin-settings' );
                $localize_param = array(
                    'screen_id' => $screen_id,
                    'gateways' => $this->get_wc_payment_gateways( 'id' )
                );
                wp_localize_script( 'dgc-payment-admin-settings', 'dgc_payment_admin_settings_param', $localize_param);
            //}
        }

        /**
         * Setting sections
         * @return array
         */
        public function get_settings_sections() {
            $sections = array(
                array(
                    'id' => '_payment_settings_general',
                    'title' => __( 'General', 'text-domain' ),
                    'icon' => 'dashicons-admin-generic',
                ),
                array(
                    'id' => '_payment_settings_credit',
                    'title' => __( 'Credit Options', 'text-domain' ),
                    'icon' => 'dashicons-money'
                ),
                array(
                    'id' => '_payment_settings_conf',
                    'title' => __( 'Configuration', 'text-domain' ),
                    'icon' => 'dashicons-money'
                )
            );
            return apply_filters( 'dgc_payment_settings_sections', $sections);
        }

        /**
         * Returns all the settings fields
         *
         * @return array settings fields
         */
        public function get_settings_fields() {
            $settings_fields = array(
                '_payment_settings_general' => array_merge( 
                    array(
                        array(
                            'name' => 'product_title',
                            'label' => __( 'Rechargeable Product Title', 'text-domain' ),
                            'desc' => __( 'Enter payment rechargeable product title', 'text-domain' ),
                            'type' => 'text',
                            'default' => $this->get_rechargeable_product_title()
                        ),
                        array(
                            'name' => 'product_image',
                            'label' => __( 'Rechargeable Product Image', 'text-domain' ),
                            'desc' => __( 'Choose payment rechargeable product image', 'text-domain' ),
                            'type' => 'attachment',
                            'options' => array(
                                'button_label' => __( 'Set product image', 'text-domain' ),
                                'uploader_title' => __( 'Product image', 'text-domain' ),
                                'uploader_button_text' => __( 'Set product image', 'text-domain' )
                            )
                        ) 
                    ), 
                    $this->get_wc_tax_options(), 
                    array(
                        array(
                            'name' => 'min_topup_amount',
                            'label' => __( 'Minimum Topup Amount', 'text-domain' ),
                            'desc' => __( 'The minimum amount needed for payment top up', 'text-domain' ),
                            'type' => 'number',
                            'step' => '0.01'
                        ),
                        array(
                            'name' => 'max_topup_amount',
                            'label' => __( 'Maximum Topup Amount', 'text-domain' ),
                            'desc' => __( 'The maximum amount needed for payment top up', 'text-domain' ),
                            'type' => 'number',
                            'step' => '0.01'
                        ) 
                    ), 
                    $this->wp_menu_locations(), 
                    array(
                        array(
                            'name' => 'is_auto_deduct_for_partial_payment',
                            'label' => __( 'Auto deduct payment balance for partial payment', 'text-domain' ),
                            'desc' => __( 'If a purchase requires more balance than you have in your payment, then if checked the payment balance will be deduct first and the rest of the amount will need to be paid.', 'text-domain' ),
                            'type' => 'checkbox',
                        ),
                        array(
                            'name' => 'is_enable_payment_transfer',
                            'label' => __( 'Allow Payment Transfer', 'text-domain' ),
                            'desc' => __( 'If checked user will be able to transfer fund to another user.', 'text-domain' ),
                            'type' => 'checkbox',
                            'default' => 'on'
                        ),
                        array(
                            'name' => 'min_transfer_amount',
                            'label' => __( 'Minimum Transfer Amount', 'text-domain' ),
                            'desc' => __( 'Enter minimum transfer amount', 'text-domain' ),
                            'type' => 'number',
                            'step' => '0.01'
                        ),
                        array(
                            'name' => 'transfer_charge_type',
                            'label' => __( 'Transfer charge type', 'text-domain' ),
                            'desc' => __( 'Select transfer charge type percentage or fixed', 'text-domain' ),
                            'type' => 'select',
                            'options' => array( 'percent' => __( 'Percentage', 'text-domain' ), 'fixed' => __( 'Fixed', 'text-domain' ) ),
                            'size' => 'regular-text wc-enhanced-select'
                        ),
                        array(
                            'name' => 'transfer_charge_amount',
                            'label' => __( 'Transfer charge Amount', 'text-domain' ),
                            'desc' => __( 'Enter transfer charge amount', 'text-domain' ),
                            'type' => 'number',
                            'step' => '0.01'
                        ) 
                    ), 
                    $this->get_wc_payment_allowed_gateways()
                ),

                '_payment_settings_credit' => array_merge( 
                    array(
                        array(
                            'name' => 'is_enable_cashback_reward_program',
                            'label' => __( 'Cashback Reward Program', 'text-domain' ),
                            'desc' => __( 'Run cashback reward program on your store', 'text-domain' ),
                            'type' => 'checkbox',
                        ),
                        array(
                            'name' => 'process_cashback_status',
                            'label' => __( 'Process cashback', 'text-domain' ),
                            'desc' => __( 'Select order status to process cashback', 'text-domain' ),
                            'type' => 'select',
                            'options' => apply_filters( 'dgc_payment_process_cashback_status', array( 'pending' => __( 'Pending payment', 'text-domain' ), 'on-hold' => __( 'On hold', 'text-domain' ), 'processing' => __( 'Processing', 'text-domain' ), 'completed' => __( 'Completed', 'text-domain' ) ) ),
                            'default' => array( 'processing', 'completed' ),
                            'size' => 'regular-text wc-enhanced-select',
                            'multiple' => true
                        ),
                        array(
                            'name' => 'cashback_rule',
                            'label' => __( 'Cashback Rule', 'text-domain' ),
                            'desc' => __( 'Select Cashback Rule cart or product wise', 'text-domain' ),
                            'type' => 'select',
                            'options' => array( 'cart' => __( 'Cart wise', 'text-domain' ), 'product' => __( 'Product wise', 'text-domain' ), 'product_cat' => __( 'Product category wise', 'text-domain' ) ),
                            'size' => 'regular-text wc-enhanced-select'
                        ),
                        array(
                            'name' => 'cashback_type',
                            'label' => __( 'Cashback type', 'text-domain' ),
                            'desc' => __( 'Select cashback type percentage or fixed', 'text-domain' ),
                            'type' => 'select',
                            'options' => array( 'percent' => __( 'Percentage', 'text-domain' ), 'fixed' => __( 'Fixed', 'text-domain' ) ),
                            'size' => 'regular-text wc-enhanced-select'
                        ),
                        array(
                            'name' => 'cashback_amount',
                            'label' => __( 'Cashback Amount', 'text-domain' ),
                            'desc' => __( 'Enter cashback amount', 'text-domain' ),
                            'type' => 'number',
                            'step' => '0.01'
                        ),
                        array(
                            'name' => 'min_cart_amount',
                            'label' => __( 'Minimum Cart Amount', 'text-domain' ),
                            'desc' => __( 'Enter applicable minimum cart amount for cashback', 'text-domain' ),
                            'type' => 'number',
                            'step' => '0.01'
                        ),
                        array(
                            'name' => 'max_cashback_amount',
                            'label' => __( 'Maximum Cashback Amount', 'text-domain' ),
                            'desc' => __( 'Enter maximum cashback amount', 'text-domain' ),
                            'type' => 'number',
                            'step' => '0.01'
                        ),
                        array(
                            'name' => 'allow_min_cashback',
                            'label' => __( 'Allow Minimum cashback', 'text-domain' ),
                            'desc' => __( 'If checked minimum cashback amount will be applied on product category cashback calculation.', 'text-domain' ),
                            'type' => 'checkbox',
                            'default' => 'on'
                        ),
                        array(
                            'name' => 'is_enable_gateway_charge',
                            'label' => __( 'Payment gateway charge', 'text-domain' ),
                            'desc' => __( 'Charge customer when they add balance to their payment?', 'text-domain' ),
                            'type' => 'checkbox',
                        ),
                        array(
                            'name' => 'gateway_charge_type',
                            'label' => __( 'Charge type', 'text-domain' ),
                            'desc' => __( 'Select gateway charge type percentage or fixed', 'text-domain' ),
                            'type' => 'select',
                            'options' => array( 'percent' => __( 'Percentage', 'text-domain' ), 'fixed' => __( 'Fixed', 'text-domain' ) ),
                            'size' => 'regular-text wc-enhanced-select'
                        ) 
                    ), 
                    $this->get_wc_payment_gateways(), 
                    array()
                ),

                '_payment_settings_conf' => array_merge( 
                    array(
                        array(
                            'name' => 'bitcoind_rpc_host',
                            'label' => __( 'RPC Host', 'text-domain' ),
                            'desc' => __( 'Enter RPC Host address', 'text-domain' ),
                            'type' => 'text'
                        ),
                        array(
                            'name' => 'bitcoind_rpc_port',
                            'label' => __( 'RPC Port', 'text-domain' ),
                            'desc' => __( 'Enter RPC Port', 'text-domain' ),
                            'type' => 'text'
                        ),
                        array(
                            'name' => 'bitcoind_rpc_username',
                            'label' => __( 'RPC Username', 'text-domain' ),
                            'desc' => __( 'Enter RPC Username', 'text-domain' ),
                            'type' => 'text'
                        ),
                        array(
                            'name' => 'bitcoind_rpc_password',
                            'label' => __( 'RPC Password', 'text-domain' ),
                            'desc' => __( 'Enter RPC Password', 'text-domain' ),
                            'type' => 'password'
                        ),
                        array(
                            'name' => 'wallet_passphrase',
                            'label' => __( 'Wallet passphrase', 'text-domain' ),
                            'desc' => __( 'Enter wallet passphrase', 'text-domain' ),
                            'type' => 'password'
                        ) 
                    ), 
                    array()
                )
            );
            return apply_filters( 'dgc_payment_settings_filds', $settings_fields);
        }

        /**
         * Fetch rechargeable product title
         * @return string title
         */
        public function get_rechargeable_product_title() {
            $product_title = '';
            $payment_product = get_payment_rechargeable_product();
            if ( $payment_product) {
                $product_title = $payment_product->get_title();
            }
            return $product_title;
        }

        /**
         * display plugin settings page
         */
        public function plugin_page() {
            echo '<div class="wrap">';
            echo '<h2 style="margin-bottom: 15px;">' . __( 'Settings', 'text-domain' ) . '</h2>';
            settings_errors();
            echo '<div class="payment-settings-wrap">';
            $this->settings_api->show_navigation();
            $this->settings_api->show_forms();
            echo '</div>';
            echo '</div>';
        }

        /**
         * Chargeable payment gateways
         * @param string $context
         * @return array
         */
        public function get_wc_payment_gateways( $context = 'field' ) {
            $gateways = array();
            foreach (WC()->payment_gateways()->payment_gateways as $gateway) {
                if ( 'yes' === $gateway->enabled && $gateway->id != 'payment' ) {
                    $method_title = $gateway->get_title() ? $gateway->get_title() : __( '(no title)', 'text-domain' );
                    if ( $context == 'field' ) {
                        $gateways[] = array(
                            'name' => $gateway->id,
                            'label' => $method_title,
                            'desc' => __( 'Enter gateway charge amount for ', 'text-domain' ) . $method_title,
                            'type' => 'number',
                            'step' => '0.01',
                        );
                    } else {
                        $gateways[] = $gateway->id;
                    }
                }
            }
            return $gateways;
        }

        /**
         * allowed payment gateways
         * @param string $context
         * @return array
         */
        public function get_wc_payment_allowed_gateways( $context = 'field' ) {
            $gateways = array();
            foreach (WC()->payment_gateways()->payment_gateways as $gateway) {
                if ( 'yes' === $gateway->enabled && $gateway->id != 'payment' ) {
                    $method_title = $gateway->get_title() ? $gateway->get_title() : __( '(no title)', 'text-domain' );
                    if ( $context == 'field' ) {
                        $gateways[] = array(
                            'name' => $gateway->id,
                            'label' => $method_title,
                            'desc' => __( 'Allow this gateway for recharge payment', 'text-domain' ),
                            'type' => 'checkbox',
                            'default' => 'on'
                        );
                    }
                }
            }
            return $gateways;
        }

        /**
         * get tax options
         * @param string $context
         * @return array
         */
        public function get_wc_tax_options( $context = 'field' ) {
            $tax_options = array();
            if ( wc_tax_enabled() ) {
                $tax_options[] = array(
                    'name' => '_tax_status',
                    'label' => __( 'Rechargeable Product Tax status', 'text-domain' ),
                    'desc' => __( 'Define whether or not the rechargeable Product is taxable.', 'text-domain' ),
                    'type' => 'select',
                    'options' => array(
                        'taxable' => __( 'Taxable', 'text-domain' ),
                        'none' => _x( 'None', 'Tax status', 'text-domain' ),
                    ),
                    'size' => 'regular-text wc-enhanced-select',
                );
                $tax_options[] = array(
                    'name' => '_tax_class',
                    'label' => __( 'Rechargeable Product Tax class', 'text-domain' ),
                    'desc' => __( 'Define whether or not the rechargeable Product is taxable.', 'text-domain' ),
                    'type' => 'select',
                    'options' => wc_get_product_tax_class_options(),
                    'desc' => __( 'Choose a tax class for rechargeable product. Tax classes are used to apply different tax rates specific to certain types of product.', 'text-domain' ),
                    'size' => 'regular-text wc-enhanced-select',
                );
            }
            return $tax_options;
        }

        /**
         * get all registered nav menu locations settings
         * @return array
         */
        public function wp_menu_locations() {
            $menu_locations = array();
            if (current_theme_supports( 'menus' ) ) {
                $locations = get_registered_nav_menus();
                if ( $locations) {
                    foreach ( $locations as $location => $title) {
                        $menu_locations[] = array(
                            'name' => $location,
                            'label' => (current( $locations) == $title) ? __( 'Mini payment display location', 'text-domain' ) : '',
                            'desc' => $title,
                            'type' => 'checkbox'
                        );
                    }
                }
            }
            return $menu_locations;
        }

        /**
         * Callback fuction of all option after save
         * @param array $old_value
         * @param array $value
         * @param string $option
         */
        public function update_option__payment_settings_general_callback( $old_value, $value, $option) {
            /**
             * save product title on option change
             */
            if ( $old_value['product_title'] != $value['product_title'] ) {
                $this->set_rechargeable_product_title( $value['product_title'] );
            }

            /**
             * Save tax status
             */
            if ( $old_value['_tax_status'] != $value['_tax_status'] || $old_value['_tax_class'] != $value['_tax_class'] ) {
                $this->set_rechargeable_tax_status( $value['_tax_status'], $value['_tax_class'] );
            }

            /**
             * Save product image
             */
            if ( $old_value['product_image'] != $value['product_image'] ) {
                $this->set_rechargeable_product_image( $value['product_image'] );
            }
        }

        /**
         * Set rechargeable product title
         * @param string $title
         * @return boolean | int 
         */
        public function set_rechargeable_product_title( $title) {
            $payment_product = get_payment_rechargeable_product();
            if ( $payment_product) {
                $payment_product->set_name( $title);
                return $payment_product->save();
            }
            return false;
        }

        /**
         * Set rechargeable tax status
         * @param string $_tax_status, $_tax_class
         * @return boolean | int 
         */
        public function set_rechargeable_tax_status( $_tax_status, $_tax_class) {
            $payment_product = get_payment_rechargeable_product();
            if ( $payment_product) {
                $payment_product->set_tax_status( $_tax_status);
                $payment_product->set_tax_class( $_tax_class);
                return $payment_product->save();
            }
            return false;
        }
        
        /**
         * Set rechargeable product image
         * @param int $attachment_id
         * @return boolean | int 
         */
        public function set_rechargeable_product_image( $attachment_id ) {
            $payment_product = get_payment_rechargeable_product();
            if ( $payment_product) {
                $payment_product->set_image_id( $attachment_id );
                return $payment_product->save();
            }
            return false;
        }

    }

endif;

new dgc_Payment_Settings(dgc_payment()->settings_api);
