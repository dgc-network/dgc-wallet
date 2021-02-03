<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
if (!class_exists('dgc_Payment_Frontend')) {

    class dgc_Payment_Frontend {

        /**
         * The single instance of the class.
         *
         * @var dgc_Payment_Frontend
         * @since 1.1.10
         */
        protected static $_instance = null;

        /**
         * Main instance
         * @return class object
         */
        public static function instance() {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         * Class constructor
         */
        public function __construct() {
            add_filter('wp_nav_menu_items', array($this, 'add_payment_nav_menu'), 100, 2);
            add_filter('woocommerce_get_query_vars', array($this, 'add_woocommerce_query_vars'));
            add_filter('woocommerce_endpoint_dgc-payment_title', array($this, 'woocommerce_endpoint_title'), 10, 2);
            add_filter('woocommerce_endpoint_dgc-payment-transactions_title', array($this, 'woocommerce_endpoint_title'), 10, 2);
            add_filter('woocommerce_account_menu_items', array($this, 'dgc_payment_menu_items'), 10, 1);
            add_action('woocommerce_account_dgc-payment_endpoint', array($this, 'dgc_payment_endpoint_content'));
            add_action('woocommerce_account_dgc-payment-transactions_endpoint', array($this, 'dgc_payment_transactions_endpoint_content'));

            add_filter('woocommerce_is_purchasable', array($this, 'make_dgc_payment_recharge_product_purchasable'), 10, 2);
            add_action('wp_loaded', array($this, 'dgc_payment_frontend_loaded'), 20);
            add_action('woocommerce_before_calculate_totals', array($this, 'dgc_payment_set_recharge_product_price'));
            add_filter('woocommerce_add_to_cart_validation', array($this, 'restrict_other_from_add_to_cart'), 20);
            add_action('wp_enqueue_scripts', array(&$this, 'dgc_payment_styles'), 20);
            add_filter('woocommerce_available_payment_gateways', array($this, 'woocommerce_available_payment_gateways'), 30);
            if ('on' === dgc_payment()->settings_api->get_option('is_enable_cashback_reward_program', '_payment_settings_credit', 'on')) {
                add_action('woocommerce_before_cart_table', array($this, 'woocommerce_before_cart_table'));
                add_action('woocommerce_before_checkout_form', array($this, 'woocommerce_before_cart_table'));
                add_action('woocommerce_shop_loop_item_title', array($this, 'display_cashback'), 15);
                add_action('woocommerce_single_product_summary', array($this, 'display_cashback'), 15);
                add_filter('woocommerce_available_variation', array($this, 'woocommerce_available_variation'), 10, 3);
            }
            add_action('woocommerce_checkout_order_processed', array($this, 'woocommerce_checkout_order_processed'), 30, 3);
            add_action('woocommerce_review_order_after_order_total', array($this, 'woocommerce_review_order_after_order_total'));
            add_action('woocommerce_checkout_create_order_coupon_item', array($this, 'convert_coupon_to_cashbak_if'), 10, 4);

            add_filter('woocommerce_coupon_is_valid', array($this, 'dgc_payment_is_valid_cashback_coupon'), 100, 2);
            add_filter('woocommerce_coupon_message', array($this, 'update_woocommerce_coupon_message_as_cashback'), 10, 3);
            add_filter('woocommerce_cart_totals_coupon_label', array($this, 'change_coupon_label'), 10, 2);
            add_filter('woocommerce_cart_get_total', array($this, 'woocommerce_cart_get_total'));
            add_shortcode('dgc-payment', __CLASS__ . '::dgc_payment_shortcode_callback');
            add_action('woocommerce_cart_calculate_fees', array($this, 'dgc_payment_add_partial_payment_fee'));
            add_filter('woocommerce_cart_totals_get_fees_from_cart_taxes', array($this, 'woocommerce_cart_totals_get_fees_from_cart_taxes'), 10, 2);
            add_action('woocommerce_thankyou', array($this, 'restore_woocommerce_cart_items'));
            add_filter('dgc_payment_is_enable_transfer', array($this, 'dgc_payment_is_enable_transfer'));
        }

        /**
         * Add a new item to a menu
         * @param string $menu
         * @param array $args
         * @return string
         */
        public function add_payment_nav_menu($menu, $args) {
            // Check if add a new item to a menu assigned to Primary Navigation Menu location
            if (apply_filters('dgc_payment_hide_nav_menu', false, $menu, $args) || in_array($args->theme_location, apply_filters('dgc_payment_exclude_nav_menu_location', array(), $menu, $args))) {
                return $menu;
            }

            if ('off' === dgc_payment()->settings_api->get_option($args->theme_location, '_payment_settings_general', 'off') || !is_user_logged_in()) {
                return $menu;
            }

            ob_start();
            dgc_payment()->get_template('dgc-payment-mini.php');
            $mini_payment = ob_get_clean();
            return $menu . $mini_payment;
        }

        /**
         * Add WooCommerce query vars.
         * @param type $query_vars
         * @return type
         */
        public function add_woocommerce_query_vars($query_vars) {
            $query_vars['dgc-payment'] = get_option('woocommerce_dgc_payment_endpoint', 'text-domain');
            $query_vars['dgc-payment-transactions'] = get_option('woocommerce_dgc_payment_transactions_endpoint', 'dgc-payment-transactions');
            return $query_vars;
        }

        /**
         * Change WooCommerce endpoint title for payment pages.
         */
        public function woocommerce_endpoint_title($title, $endpoint) {
            switch ($endpoint) {
                case 'dgc-payment' :
                    $title = apply_filters('dgc_payment_account_menu_title', __('dgcPay', 'text-domain'));
                    break;
                case 'dgc-payment-transactions' :
                    $title = apply_filters('dgc_payment_account_transaction_menu_title', __('Payment Transactions', 'text-domain'));
                    break;
                default :
                    $title = '';
                    break;
            }
            return $title;
        }

        /**
         * Register and enqueue frontend styles and scripts
         */
        public function dgc_payment_styles() {
            $wp_scripts = wp_scripts();
            $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
            wp_register_style('dgc-payment-jquery-ui', '//ajax.googleapis.com/ajax/libs/jqueryui/' . $wp_scripts->registered['jquery-ui-core']->ver . '/themes/smoothness/jquery-ui.css', false, $wp_scripts->registered['jquery-ui-core']->ver, false);
            wp_register_style('jquery-datatables-style', '//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css');
            wp_register_style('jquery-datatables-responsive-style', '//cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css');
            wp_register_style('dgc-payment-style', dgc_payment()->plugin_url() . '/assets/css/frontend.css', array(), DGC_PAYMENT_PLUGIN_VERSION);
            // Add RTL support 
            wp_style_add_data('dgc-payment-style', 'rtl', 'replace');
            wp_register_script('jquery-datatables-script', '//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js', array('jquery'));
            wp_register_script('jquery-datatables-responsive-script', '//cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js', array('jquery'));
            wp_register_script('dgc-payment-endpoint', dgc_payment()->plugin_url() . '/assets/js/frontend/dgc-payment-endpoint' . $suffix . '.js', array('jquery', 'jquery-datatables-script'), DGC_PAYMENT_PLUGIN_VERSION);
            $payment_localize_param = array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'search_by_user_email' => apply_filters('dgc_payment_user_search_exact_match', true),
                'i18n' => array(
                    'emptyTable' => __('No transactions available', 'text-domain'),
                    'lengthMenu' => sprintf(__('Show %s entries', 'text-domain'), '_MENU_'),
                    'info' => sprintf(__('Showing %1s to %2s of %3s entries', 'text-domain'), '_START_', '_END_', '_TOTAL_'),
                    'infoEmpty' => __('Showing 0 to 0 of 0 entries', 'text-domain'),
                    'paginate' => array(
                        'first' => __('First', 'text-domain'),
                        'last' => __('Last', 'text-domain'),
                        'next' => __('Next', 'text-domain'),
                        'previous' => __('Previous', 'text-domain')
                    ),
                    'non_valid_email_text' => __('Please enter a valid email address', 'text-domain'),
                    'no_resualt' => __('No results found', 'text-domain'),
                    'inputTooShort' => __('Please enter 3 or more characters', 'text-domain'),
                    'searching' => __('Searching…', 'text-domain')
                )
            );
            wp_localize_script('dgc-payment-endpoint', 'payment_param', $payment_localize_param);
            wp_enqueue_style('dgc-payment-style');
            //if (is_account_page()) {
                wp_enqueue_style('dashicons');
                wp_enqueue_style('select2');
                wp_enqueue_style('jquery-datatables-style');
                wp_enqueue_style('jquery-datatables-responsive-style');
                wp_enqueue_script('selectWoo');
                wp_enqueue_script('jquery-datatables-script');
                wp_enqueue_script('jquery-datatables-responsive-script');
                wp_enqueue_script('dgc-payment-endpoint');
            //}
            $add_to_cart_variation = "jQuery(function ($) { $(document).on('show_variation', function (event, variation, purchasable) { if(variation.cashback_amount) { $('.on-dgc-payment-cashback').show(); $('.on-dgc-payment-cashback').html(variation.cashback_html); } else { $('.on-dgc-payment-cashback').hide(); } }) });";
            wp_add_inline_script('wc-add-to-cart-variation', $add_to_cart_variation);
        }

        /**
         * dgc Payment for WooCommerce menu
         * @param array $items
         * @return array
         */
        public function dgc_payment_menu_items($items) {
            unset($items['edit-account']);
            unset($items['customer-logout']);
            unset($items['downloads'] ); // Disable Downloads
            $items['dgc-payment'] = apply_filters('dgc_payment_account_menu_title', __('dgcPay', 'text-domain'));
            $items['edit-account'] = __('Account details', 'text-domain');
            $items['customer-logout'] = __('Logout', 'text-domain');
            return $items;
        }

        /**
         * WooCommerce endpoint contents for payment 
         */
        public function dgc_payment_endpoint_content() {
            dgc_payment()->get_template('dgc-payment-endpoint.php');
        }

        /**
         * WooCommerce endpoint contents for transaction details
         */
        public function dgc_payment_transactions_endpoint_content() {
            dgc_payment()->get_template('dgc-payment-endpoint-transactions.php');
        }

        /**
         * Do payment frontend load functions.
         */
        public function dgc_payment_frontend_loaded() {
            // reset partial payment session
            if (!is_ajax()) {
                update_payment_partial_payment_session();
            }
            /**
             * Process payment recharge.
             */
            if (isset($_POST['dgc_payment_balance_to_add']) && !empty($_POST['dgc_payment_balance_to_add'])) {
                $is_valid = $this->is_valid_payment_recharge_amount($_POST['dgc_payment_balance_to_add']);
                if ($is_valid['is_valid']) {
                    add_filter('woocommerce_add_cart_item_data', array($this, 'add_dgc_payment_product_price_to_cart_item_data'), 10, 2);
                    $product = get_payment_rechargeable_product();
                    if ($product) {
                        dgc_payment_persistent_cart_update();
                        wc()->cart->empty_cart();
                        wc()->cart->add_to_cart($product->get_id());
                        $redirect_url = apply_filters('dgc_payment_redirect_to_checkout_after_added_amount', true) ? wc_get_checkout_url() : wc_get_cart_url();
                        wp_safe_redirect($redirect_url);
                        exit();
                    }
                } else {
                    wc_add_notice($is_valid['message'], 'error');
                }
            }
            /**
             * Process payment transfer.
             */
            if (isset($_POST['dgc_payment_transfer_fund']) && apply_filters('dgc_payment_is_enable_transfer', 'on' === dgc_payment()->settings_api->get_option('is_enable_payment_transfer', '_payment_settings_general', 'on'))) {
                $response = $this->do_payment_transfer();
                if (!$response['is_valid']) {
                    wc_add_notice($response['message'], 'error');
                } else {
                    wc_add_notice($response['message']);
                    $location = esc_url( wc_get_account_endpoint_url( get_option( 'woocommerce_dgc_payment_endpoint', 'text-domain' ) ) );
                    wp_safe_redirect($location);
                    exit();
                }
            }
        }

        /**
         * Check payment recharge amount.
         * @param float $amount
         * @return array
         */
        public function is_valid_payment_recharge_amount($amount = 0) {
            $response = array('is_valid' => true);
            $min_topup_amount = dgc_payment()->settings_api->get_option('min_topup_amount', '_payment_settings_general', 0);
            $max_topup_amount = dgc_payment()->settings_api->get_option('max_topup_amount', '_payment_settings_general', 0);
            if (isset($_POST['dgc_payment_topup']) && wp_verify_nonce($_POST['dgc_payment_topup'], 'dgc_payment_topup')) {
                if ($min_topup_amount && $amount < $min_topup_amount) {
                    $response = array(
                        'is_valid' => false,
                        'message' => sprintf(__('The minimum amount needed for payment top up is %s', 'text-domain'), wc_price($min_topup_amount, dgc_payment_wc_price_args()))
                    );
                }
                if ($max_topup_amount && $amount > $max_topup_amount) {
                    $response = array(
                        'is_valid' => false,
                        'message' => sprintf(__('Payment top up amount should be less than %s', 'text-domain'), wc_price($max_topup_amount, dgc_payment_wc_price_args()))
                    );
                }
                if ($min_topup_amount && $max_topup_amount && ( $amount < $min_topup_amount || $amount > $max_topup_amount )) {
                    $response = array(
                        'is_valid' => false,
                        'message' => sprintf(__('Payment top up amount should be between %s and %s', 'text-domain'), wc_price($min_topup_amount, dgc_payment_wc_price_args()), wc_price($max_topup_amount, dgc_payment_wc_price_args()))
                    );
                }
            } else {
                $response = array(
                    'is_valid' => false,
                    'message' => __('Cheatin&#8217; huh?', 'text-domain')
                );
            }
            return apply_filters('dgc_payment_is_valid_payment_recharge_amount', $response, $amount);
        }

        /**
         * Do transfer payment amount.
         * @return array
         */
        public function do_payment_transfer() {
            $response = array('is_valid' => true, 'message' => '');
            if (isset($_POST['dgc_payment_transfer']) && wp_verify_nonce($_POST['dgc_payment_transfer'], 'dgc_payment_transfer')) {
                if (isset($_POST['dgc_payment_transfer_user_id'])) {
                    $whom = $_POST['dgc_payment_transfer_user_id'];
                }
                if (isset($_POST['dgc_payment_transfer_amount'])) {
                    $amount = $_POST['dgc_payment_transfer_amount'];
                }
                $whom = apply_filters('dgc_payment_transfer_user_id', $whom);
                $whom = get_userdata($whom);
                $current_user_obj = get_userdata(get_current_user_id());
                $credit_note = isset($_POST['dgc_payment_transfer_note']) && !empty($_POST['dgc_payment_transfer_note']) ? $_POST['dgc_payment_transfer_note'] : sprintf(__('Payment funds received from %s', 'text-domain'), $current_user_obj->user_email);
                $debit_note = sprintf(__('Payment funds transfer to %s', 'text-domain'), $whom->user_email);
                $credit_note = apply_filters('dgc_payment_transfer_credit_transaction_note', $credit_note, $whom, $amount);
                $debit_note = apply_filters('dgc_payment_transfer_debit_transaction_note', $debit_note, $whom, $amount);
                
                $transfer_charge_type = dgc_payment()->settings_api->get_option('transfer_charge_type', '_payment_settings_general', 'percent');
                $transfer_charge_amount = dgc_payment()->settings_api->get_option('transfer_charge_amount', '_payment_settings_general', 0);
                $transfer_charge = 0;
                if ('percent' === $transfer_charge_type) {
                    $transfer_charge = ( $amount * $transfer_charge_amount ) / 100;
                } else {
                    $transfer_charge = $transfer_charge_amount;
                }
                $transfer_charge = apply_filters('dgc_payment_transfer_charge_amount', $transfer_charge, $whom);
                $credit_amount = apply_filters('dgc_payment_transfer_credit_amount', $amount, $whom);
                $debit_amount = apply_filters('dgc_payment_transfer_debit_amount', $amount + $transfer_charge, $whom);
                if ( dgc_payment()->settings_api->get_option( 'min_transfer_amount', '_payment_settings_general', 0 ) ) {
                    if ( dgc_payment()->settings_api->get_option( 'min_transfer_amount', '_payment_settings_general', 0 ) > $amount) {
                        return array(
                            'is_valid' => false,
                            'message' => sprintf( __('Minimum transfer amount is %s', 'text-domain'), wc_price( dgc_payment()->settings_api->get_option( 'min_transfer_amount', '_payment_settings_general', 0 ) ) )
                        );
                    }
                }
                if (!$whom) {
                    return array(
                        'is_valid' => false,
                        'message' => __('Invalid user', 'text-domain')
                    );
                }
                if (floatval($debit_amount) > dgc_payment()->payment->get_payment_balance(get_current_user_id(), 'edit')) {
                    return array(
                        'is_valid' => false,
                        'message' => __('Entered amount is greater than current payment amount.', 'text-domain')
                    );
                }

                if ($credit_transaction_id = dgc_payment()->payment->credit($whom->ID, $credit_amount, $credit_note)) {
                    do_action('dgc_payment_transfer_amount_credited', $credit_transaction_id, $whom->ID, get_current_user_id());
                    $debit_transaction_id = dgc_payment()->payment->debit(get_current_user_id(), $debit_amount, $debit_note);
                    do_action('dgc_payment_transfer_amount_debited', $debit_transaction_id, get_current_user_id(), $whom->ID);
                    update_payment_transaction_meta($debit_transaction_id, '_payment_transfer_charge', $transfer_charge, get_current_user_id());
                    $response = array(
                        'is_valid' => true,
                        'message' => __('Amount transferred successfully!', 'text-domain')
                    );
                }
            } else {
                $response = array(
                    'is_valid' => false,
                    'message' => __('Cheatin&#8217; huh?', 'text-domain')
                );
            }
            return $response;
        }

        /**
         * WooCommerce add cart item data
         * @param array $cart_item_data
         * @param int $product_id
         * @return array
         */
        public function add_dgc_payment_product_price_to_cart_item_data($cart_item_data, $product_id) {
            $product = wc_get_product($product_id);
            if (isset($_POST['dgc_payment_balance_to_add']) && $product) {
                $recharge_amount = apply_filters('dgc_payment_rechargeable_amount', round($_POST['dgc_payment_balance_to_add'], 2));
                $cart_item_data['recharge_amount'] = $recharge_amount;
            }
            return $cart_item_data;
        }

        /**
         * Make rechargeable product purchasable
         * @param boolean $is_purchasable
         * @param WC_Product object $product
         * @return boolean
         */
        public function make_dgc_payment_recharge_product_purchasable($is_purchasable, $product) {
            $payment_product = get_payment_rechargeable_product();
            if ($payment_product) {
                if ($payment_product->get_id() == $product->get_id()) {
                    $is_purchasable = true;
                }
            }
            return $is_purchasable;
        }

        /**
         * Set topup product price at run time
         * @param OBJECT $cart
         * @return NULL
         */
        public function dgc_payment_set_recharge_product_price($cart) {
            $product = get_payment_rechargeable_product();
            if (!$product && empty($cart->cart_contents)) {
                return;
            }
            foreach ($cart->cart_contents as $key => $value) {
                if (isset($value['recharge_amount']) && $value['recharge_amount'] && $product->get_id() == $value['product_id']) {
                    $value['data']->set_price($value['recharge_amount']);
                }
            }
        }

        /**
         * Restrict customer to order other product along with rechargeable product
         * @param boolean $valid
         * @return boolean
         */
        public function restrict_other_from_add_to_cart($valid) {
            $product = get_payment_rechargeable_product();
            if (is_payment_rechargeable_cart()) {
                wc_add_notice(apply_filters('dgc_payment_restrict_other_from_add_to_cart', __('You can not add another product while your cart contains with payment rechargeable product.', 'text-domain')), 'error');
                $valid = false;
            }
            return $valid;
        }

        /**
         * Filter WooCommerce available payment gateway
         * for add balance to payment
         * @param type $_available_gateways
         * @return type
         */
        public function woocommerce_available_payment_gateways($_available_gateways) {
            if (is_payment_rechargeable_cart()) {
                foreach ($_available_gateways as $gateway_id => $gateway) {
                    if (dgc_payment()->settings_api->get_option($gateway_id, '_payment_settings_general', 'on') != 'on') {
                        unset($_available_gateways[$gateway_id]);
                    }
                }
            }
            return $_available_gateways;
        }

        /**
         * Cashback notice
         */
        public function woocommerce_before_cart_table() {
            if (dgc_payment()->cashback->calculate_cashback() && !is_payment_rechargeable_cart() && apply_filters('display_cashback_notice_at_woocommerce_page', true)) :
                ?>
                <div class="woocommerce-Message woocommerce-Message--info woocommerce-info">
                    <?php
                    $cashback_amount = dgc_payment()->cashback->calculate_cashback();
                    if (is_user_logged_in()) {
                        echo apply_filters('dgc_payment_cashback_notice_text', sprintf(__('Upon placing this order a cashback of %s will be credited to your payment.', 'text-domain'), wc_price($cashback_amount, dgc_payment_wc_price_args())), $cashback_amount);
                    } else {
                        echo apply_filters('dgc_payment_cashback_notice_text', sprintf(__('Please <a href="%s">log in</a> to avail %s cashback from this order.', 'text-domain'), esc_url(get_permalink(get_option('woocommerce_myaccount_page_id'))), wc_price($cashback_amount, dgc_payment_wc_price_args())), $cashback_amount);
                    }
                    ?>
                </div>
                <?php
            endif;
        }

        /**
         * Handel cashback and partial payment on order processed hook
         * @param int $order_id
         * @param array $posted_data
         * @param Object $order
         */
        public function woocommerce_checkout_order_processed($order_id, $posted_data, $order) {
            $cashback_amount = dgc_payment()->cashback->calculate_cashback();
            if ($cashback_amount && !is_payment_rechargeable_order(wc_get_order($order_id)) && is_user_logged_in()) {
                update_post_meta($order_id, '_payment_cashback', $cashback_amount);
            }
        }

        /**
         * Sets partial payment amount to cart as negative fee
         * @since 1.2.1
         */
        public function dgc_payment_add_partial_payment_fee() {
            $parial_payment_amount = apply_filters('dgc_payment_partial_payment_amount', dgc_payment()->payment->get_payment_balance(get_current_user_id(), 'edit'));
            if ($parial_payment_amount > 0) {
                $fee = array(
                    'id' => '_via_payment_partial_payment',
                    'name' => __('Via payment', 'text-domain'),
                    'amount' => (float) -1 * $parial_payment_amount,
                    'taxable' => false,
                    'tax_class' => '',
                );
                if (is_enable_payment_partial_payment() && $parial_payment_amount) {
                    wc()->cart->fees_api()->add_fee($fee);
                } else {
                    $all_fees = wc()->cart->fees_api()->get_fees();
                    if (isset($all_fees['_via_partial_payment_payment'])) {
                        unset($all_fees['_via_partial_payment_payment']);
                        wc()->cart->fees_api()->set_fees($all_fees);
                    }
                }
            }
        }

        /**
         * Unset Fee tax for partial amount
         * @param array $fee_taxes
         * @param object $fee
         * @return array
         */
        public function woocommerce_cart_totals_get_fees_from_cart_taxes($fee_taxes, $fee) {
            if ('_via_payment_partial_payment' === $fee->object->id) {
                $fee_taxes = array();
            }
            return $fee_taxes;
        }

        /**
         * Function that display partial payment option
         * @return NULL
         */
        public function woocommerce_review_order_after_order_total() {
            if (apply_filters('dgc_payment_disable_partial_payment', ( is_full_payment_through_payment() || is_payment_rechargeable_cart()))) {
                return;
            }
            wp_enqueue_style('dashicons');
            wp_enqueue_style('dgc-payment-jquery-ui');
            wp_enqueue_script('jquery-ui-tooltip');
            dgc_payment()->get_template('dgc-payment-partial.php');
        }

        /**
         * Convert coupon to cashback.
         * @param array $item
         * @param string $code
         * @param Object $coupon
         * @param Object $order
         * @since 1.0.6
         */
        public function convert_coupon_to_cashbak_if($item, $code, $coupon, $order) {
            $coupon_id = $coupon->get_id();
            $_is_coupon_cashback = get_post_meta($coupon_id, '_is_coupon_cashback', true);
            if ('yes' === $_is_coupon_cashback && is_user_logged_in()) {
                $discount_total = $order->get_discount_total('edit');
                $coupon_amount = WC()->cart->get_coupon_discount_amount($code, WC()->cart->display_cart_ex_tax);
                $discount_total -= $coupon_amount;
                $order->set_discount_total($discount_total);
                $order_id = $order->save();
                $_coupon_cashback_amount = floatval(get_post_meta($order_id, '_coupon_cashback_amount', true));
                update_post_meta($order_id, '_coupon_cashback_amount', ( $_coupon_cashback_amount + $coupon_amount));
            }
        }
        
        public function woocommerce_available_variation($args, $product_class, $variation){
            $cashback_amount = 0;
            if ('product' === dgc_payment()->settings_api->get_option('cashback_rule', '_payment_settings_credit', 'cart')) {
                $cashback_amount = dgc_payment()->cashback->get_product_cashback_amount($variation);
            } else if ('product_cat' === dgc_payment()->settings_api->get_option('cashback_rule', '_payment_settings_credit', 'cart')) {
                $cashback_amount = dgc_payment()->cashback->get_product_category_wise_cashback_amount($variation);
            }
            $cashback_amount = apply_filters('dgc_payment_variation_cashback_amount', $cashback_amount, $variation);
            $args['cashback_amount'] = $cashback_amount;
            $args['cashback_html'] = wc_price($cashback_amount, dgc_payment_wc_price_args()). __(' Cashback', 'text-domain');
            return $args;
        }

        /**
         * Display cashback amount in product
         * 
         * @return void
         */
        public function display_cashback() {
            $product = wc_get_product(get_the_ID());
            if($product->has_child()){
                $product = wc_get_product(current($product->get_children()));
            }
            $cashback_amount = 0;
            if ('product' === dgc_payment()->settings_api->get_option('cashback_rule', '_payment_settings_credit', 'cart')) {
                $cashback_amount = dgc_payment()->cashback->get_product_cashback_amount($product);
            } else if ('product_cat' === dgc_payment()->settings_api->get_option('cashback_rule', '_payment_settings_credit', 'cart')) {
                $cashback_amount = dgc_payment()->cashback->get_product_category_wise_cashback_amount($product);
            }
            $cashback_amount = apply_filters('dgc_payment_product_cashback_amount', $cashback_amount, get_the_ID());
            if($cashback_amount){
                echo '<span class="on-dgc-payment-cashback">' . wc_price($cashback_amount, dgc_payment_wc_price_args()) . __(' Cashback', 'text-domain') . '</span>';
            } else{
                echo '<span class="on-dgc-payment-cashback" style="display:none;"></span>';
            }
        }

        /**
         * Check if user logged in for cashback coupon.
         * @param boolean $is_valid
         * @param WC_Coupon $coupon
         * @return boolean
         */
        public function dgc_payment_is_valid_cashback_coupon($is_valid, $coupon) {
            $_is_coupon_cashback = get_post_meta($coupon->get_id(), '_is_coupon_cashback', true);
            if ('yes' === $_is_coupon_cashback) {
                if (!is_user_logged_in()) {
                    $is_valid = false;
                }
            }
            return $is_valid;
        }

        /**
         * 
         * @param string $msg
         * @param int $msg_code
         * @param Object $coupon
         * @return string
         */
        public function update_woocommerce_coupon_message_as_cashback($msg, $msg_code, $coupon) {
            $coupon_id = $coupon->get_id();
            $_is_coupon_cashback = get_post_meta($coupon_id, '_is_coupon_cashback', true);
            if (is_user_logged_in() && 'yes' === $_is_coupon_cashback && 200 === $msg_code) {
                $msg = __('Coupon code applied successfully as cashback.', 'text-domain');
            }
            return $msg;
        }

        /**
         * Change coupon label in cart and checkout page
         * @param string $label
         * @param Object $coupon
         * @return string
         */
        public function change_coupon_label($label, $coupon) {
            $coupon_id = $coupon->get_id();
            $_is_coupon_cashback = get_post_meta($coupon_id, '_is_coupon_cashback', true);
            if (is_user_logged_in() && 'yes' === $_is_coupon_cashback) {
                $label = sprintf(esc_html__('Cashback: %s', 'text-domain'), $coupon->get_code());
            }
            return $label;
        }

        /**
         * Update WC Cart get_total if cashback coupon applied.
         * @param float $total
         * @return float
         */
        public function woocommerce_cart_get_total($total) {
            if (is_user_logged_in()) {
                $total += get_dgc_payment_coupon_cashback_amount();
            }
            return $total;
        }

        /**
         * Shortcode Wrapper.
         *
         * @param string[] $function Callback function.
         * @param array    $atts     Attributes. Default to empty array.
         *
         * @return string
         */
        public static function shortcode_wrapper($function, $atts = array()) {
            ob_start();
            call_user_func($function, $atts);
            return ob_get_clean();
        }

        /**
         * Payment shortcode callback
         * @param array $atts
         * @return string
         */
        public static function dgc_payment_shortcode_callback($atts) {
            return self::shortcode_wrapper(array('dgc_Payment_Frontend', 'dgc_payment_shortcode_output'), $atts);
        }

        /**
         * Payment shortcode output
         * @param array $atts
         */
        public static function dgc_payment_shortcode_output($atts) {
            if (!is_user_logged_in()) {
                echo '<div class="woocommerce">';
                wc_get_template('myaccount/form-login.php');
                echo '</div>';
            } else {
                wp_enqueue_style('dashicons');
                wp_enqueue_style('select2');
                wp_enqueue_style('jquery-datatables-style');
                wp_enqueue_style('jquery-datatables-responsive-style');
                wp_enqueue_script('jquery-datatables-script');
                wp_enqueue_script('jquery-datatables-responsive-script');
                wp_enqueue_script('selectWoo');
                wp_enqueue_script('dgc-payment-endpoint');
                if (isset($_GET['payment_action']) && !empty($_GET['payment_action'])) {
                    if ('view_transactions' === $_GET['payment_action']) {
                        dgc_payment()->get_template('dgc-payment-endpoint-transactions.php');
                    } else if (in_array($_GET['payment_action'], apply_filters('dgc_payment_endpoint_actions', array('add', 'transfer')))) {
                        dgc_payment()->get_template('dgc-payment-endpoint.php');
                    }
                    do_action('dgc_payment_shortcode_action', $_GET['payment_action']);
                } else {
                    dgc_payment()->get_template('dgc-payment-endpoint.php');
                }
            }
        }
        
        public function restore_woocommerce_cart_items($order_id){
            $saved_cart = dgc_payment_get_saved_cart();
            foreach ($saved_cart as $cart_item_key => $restore_item){
                wc()->cart->add_to_cart($restore_item['product_id'], $restore_item['quantity'], $restore_item['variation_id'], $restore_item['variation']);
            }
            wc()->cart->calculate_totals();
            dgc_payment_persistent_cart_destroy();
        }
        
        public function dgc_payment_is_enable_transfer($is_enable){
            if('on' != dgc_payment()->settings_api->get_option( 'is_enable_payment_transfer', '_payment_settings_general', 'on' )){
                $is_enable = false;
            }
            return $is_enable;
        }
    }

}
dgc_Payment_Frontend::instance();
