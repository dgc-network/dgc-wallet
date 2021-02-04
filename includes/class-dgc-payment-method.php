<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class dgc_Payment_Method extends WC_Payment_Gateway {

    /**
     * Class constructor
     */
    public function __construct() {

        $this->setup_properties();
        $this->supports = array(
            'products',
            'refunds',
            'subscriptions',
            'multiple_subscriptions',
            'subscription_cancellation',
            'subscription_suspension',
            'subscription_reactivation',
            'subscription_amount_changes',
            'subscription_date_changes',
            'subscription_payment_method_change'
        );

        // Load the settings
        $this->init_form_fields();
        $this->init_settings();
        // Get settings
        $this->title = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );
        $this->instructions = $this->get_option( 'instructions' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        /* support for woocommerce subscription plugin */
        add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'scheduled_subscription_payment' ), 10, 2 );
		add_filter( 'woocommerce_order_button_text', array( $this, 'custom_order_button_text' ), 1);

        /* Move the below stuffs from class-dgc-payment.php */
        foreach ( apply_filters( 'payment_credit_purchase_order_status', array( 'processing', 'completed' ) ) as $status) {
            add_action( 'woocommerce_order_status_' . $status, array( $this, 'payment_credit_purchase' ) );
        }

        foreach ( apply_filters( 'payment_partial_payment_order_status', array( 'on-hold', 'processing', 'completed' ) ) as $status) {
            add_action( 'woocommerce_order_status_' . $status, array( $this, 'payment_partial_payment' ) );
        }

        foreach ( apply_filters( 'payment_cashback_order_status', dgc_payment()->settings_api->get_option( 'process_cashback_status', '_payment_settings_credit', array( 'processing', 'completed' ) ) ) as $status) {
            add_action( 'woocommerce_order_status_' . $status, array( $this, 'payment_cashback' ), 12 );
        }

        add_action( 'woocommerce_order_status_cancelled', array( $this, 'process_cancelled_order' ) );

    }

	/*
	 * Change Place Order button text on checkout page in woocommerce
	 */
	function custom_order_button_text($order_button_text) {	
		$order_button_text = 'Proceed to dgcPay';	
		return $order_button_text;
	}

    /**
     * Setup general properties for the gateway.
     */
    protected function setup_properties() {
        $this->id = 'dgc-payment';
        $this->method_title = __( 'dgcPay', 'text-domain' );
        $this->method_description = __( 'Have your customers pay with payment.', 'text-domain' );
        $this->has_fields = false;
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __( 'Enable/Disable', 'text-domain' ),
                'label' => __( 'Enable payments', 'text-domain' ),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no',
            ),
            'title' => array(
                'title' => __( 'Title', 'text-domain' ),
                'type' => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'text-domain' ),
                'default' => __( 'dgc Payment', 'text-domain' ),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __( 'Description', 'text-domain' ),
                'type' => 'textarea',
                'description' => __( 'Payment method description that the customer will see on your checkout.', 'text-domain' ),
                'default' => __( 'Pay with dgcPay.', 'text-domain' ),
                'desc_tip' => true,
            ),
            'instructions' => array(
                'title' => __( 'Instructions', 'text-domain' ),
                'type' => 'textarea',
                'description' => __( 'Instructions that will be added to the thank you page.', 'text-domain' ),
                'default' => __( 'Pay with dgcPay.', 'text-domain' ),
                'desc_tip' => true,
            )
        );
    }

    /**
     * Is gateway available
     * @return boolean
     */
    public function is_available() {
        return apply_filters( 'dgc_payment_payment_is_available', ( parent::is_available() && is_full_payment_through_payment() && is_user_logged_in() && ! is_enable_payment_partial_payment() ) );
    }

    public function get_icon() {
        $current_balance = dgc_payment()->payment->get_payment_balance( get_current_user_id() );
        return apply_filters( 'dgc_payment_gateway_icon', sprintf( __( ' | Current Balance: <strong>%s</strong>', 'text-domain' ), $current_balance), $this->id );
    }

    /**
     * Is $order_id a subscription?
     * @param  int  $order_id
     * @return boolean
     */
    protected function is_subscription( $order_id ) {
        return ( function_exists( 'wcs_order_contains_subscription' ) && ( wcs_order_contains_subscription( $order_id ) || wcs_is_subscription( $order_id ) || wcs_order_contains_renewal( $order_id ) ) );
    }

    /**
     * Process payment
     * @param int $order_id
     * @return array
     */
    public function process_payment( $order_id ) {
        
        $order = wc_get_order( $order_id );
        if ( ( $order->get_total( 'edit' ) > dgc_payment()->payment->get_payment_balance( get_current_user_id(), 'edit' ) ) && apply_filters( 'dgc_payment_disallow_negative_transaction', (dgc_payment()->payment->get_payment_balance( get_current_user_id(), 'edit' ) <= 0 || $order->get_total( 'edit' ) > dgc_payment()->payment->get_payment_balance( get_current_user_id(), 'edit' ) ), $order->get_total( 'edit' ), dgc_payment()->payment->get_payment_balance( get_current_user_id(), 'edit' ) ) ) {
            wc_add_notice( __( 'Payment error: ', 'text-domain' ) . sprintf( __( 'Your payment balance is low. Please add %s to proceed with this transaction.', 'text-domain' ), wc_price( $order->get_total( 'edit' ) - dgc_payment()->payment->get_payment_balance( get_current_user_id(), 'edit' ), dgc_payment_wc_price_args($order->get_customer_id()) ) ), 'error' );
            return;
        }
        //$payment_response = dgc_payment()->payment->debit( get_current_user_id(), $order->get_total( 'edit' ), apply_filters('dgc_payment_order_payment_description', __( 'For order payment #', 'text-domain' ) . $order->get_order_number(), $order) );
        if ( $payment_response = dgc_payment()->payment->debit( get_current_user_id(), $order->get_total( 'edit' ), apply_filters('dgc_payment_order_payment_description', __( 'Paid to order #', 'text-domain' ) . $order->get_order_number(), $order) ) ) {
            foreach ( $order->get_items() as $item_id => $item ) {
                $product_id = $item->get_product_id();
                $total = $item->get_total();
                $vendor_id = get_post_field( 'post_author', $product_id );
                dgc_payment()->payment->credit( $vendor_id, $total, apply_filters('dgc_payment_order_payment_description', __( 'Received from order #', 'text-domain' ) . $order->get_order_number(), $order) );
            }
        };
        
        // Reduce stock levels
        wc_reduce_stock_levels( $order_id );

        // Remove cart
        WC()->cart->empty_cart();

        if ( $payment_response) {
            $order->payment_complete( $payment_response );
            do_action( 'dgc_payment_payment_processed', $order_id, $payment_response );
        }

        // Return thankyou redirect
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url( $order ),
        );
    }

    /**
     * Process a refund if supported.
     *
     * @param  int    $order_id Order ID.
     * @param  float  $amount Refund amount.
     * @param  string $reason Refund reason.
     * @return bool|WP_Error
     */
    public function process_refund( $order_id, $amount = null, $reason = '' ) {
        $order = wc_get_order( $order_id );
        $transaction_id = dgc_payment()->payment->credit( $order->get_customer_id(), $amount, __( 'Payment refund #', 'text-domain' ) . $order->get_order_number() );
        if ( !$transaction_id ) {
            throw new Exception( __( 'Refund not credited to customer', 'text-domain' ) );
        }
        do_action( 'dgc_payment_order_refunded', $order, $amount, $transaction_id );
        return true;
    }

    /**
     * Process renewal payment for subscription order
     * @param int $amount_to_charge
     * @param WC_Order $order
     * @return void
     */
    public function scheduled_subscription_payment( $amount_to_charge, $order ) {
        if ( get_post_meta( $order->get_id(), '_payment_scheduled_subscription_payment_processed', true ) ) {
            return;
        }
        $payment_response = dgc_payment()->payment->debit( $order->get_customer_id(), $amount_to_charge, __( 'For order payment #', 'text-domain' ) . $order->get_order_number() );
        if ( $payment_response ) {
            $order->payment_complete();
        } else {
            $order->add_order_note( __( 'Insufficient funds in customer payment', 'text-domain' ) );
        }
        update_post_meta( $order->get_id(), '_payment_scheduled_subscription_payment_processed', true );
    }

        /**
         * Credit payment balance through order payment
         * @param int $order_id
         * @return void
         */
        public function payment_credit_purchase( $order_id ) {
            $payment_product = get_payment_rechargeable_product();
            $charge_amount = 0;
            if ( get_post_meta( $order_id, '_dgc_payment_purchase_credited', true ) || !$payment_product) {
                return;
            }
            $order = wc_get_order( $order_id );
            if ( ! is_payment_rechargeable_order( $order ) ) {
                return;
            }
            $recharge_amount = apply_filters( 'dgc_payment_credit_purchase_amount', $order->get_subtotal( 'edit' ), $order_id );
            if ( 'on' === dgc_payment()->settings_api->get_option( 'is_enable_gateway_charge', '_payment_settings_credit', 'off' ) ) {
                $charge_amount = dgc_payment()->settings_api->get_option( $order->get_payment_method(), '_payment_settings_credit', 0 );
                if ( 'percent' === dgc_payment()->settings_api->get_option( 'gateway_charge_type', '_payment_settings_credit', 'percent' ) ) {
                    $recharge_amount -= $recharge_amount * ( $charge_amount / 100 );
                } else {
                    $recharge_amount -= $charge_amount;
                }
                update_post_meta( $order_id, '_dgc_payment_purchase_gateway_charge', $charge_amount );
            }
            $transaction_id = dgc_payment()->payment->credit( $order->get_customer_id(), $recharge_amount, __( 'Payment credit through purchase #', 'text-domain' ) . $order->get_order_number() );
            if ( $transaction_id ) {
                update_post_meta( $order_id, '_dgc_payment_purchase_credited', true );
                update_post_meta( $order_id, '_payment_payment_transaction_id', $transaction_id );
                update_payment_transaction_meta( $transaction_id, '_dgc_payment_purchase_gateway_charge', $charge_amount, $order->get_customer_id() );
                do_action( 'dgc_payment_credit_purchase_completed', $transaction_id, $order );
            }
        }

        public function payment_cashback( $order_id ) {
            $order = wc_get_order( $order_id );
            /* General Cashback */
            if ( apply_filters( 'process_dgc_payment_general_cashback', !get_post_meta( $order->get_id(), '_general_cashback_transaction_id', true ), $order ) && dgc_payment()->cashback->calculate_cashback(false, $order->get_id()) ) {
                $transaction_id = dgc_payment()->payment->credit( $order->get_customer_id(), dgc_payment()->cashback->calculate_cashback(false, $order->get_id()), __( 'Payment credit through cashback #', 'text-domain' ) . $order->get_order_number() );
                if ( $transaction_id ) {
                    update_payment_transaction_meta( $transaction_id, '_type', 'cashback', $order->get_customer_id() );
                    update_post_meta( $order->get_id(), '_general_cashback_transaction_id', $transaction_id );
                    do_action( 'dgc_payment_general_cashback_credited', $transaction_id );
                }
            }
            /* Coupon Cashback */
            if ( apply_filters( 'process_dgc_payment_coupon_cashback', !get_post_meta( $order->get_id(), '_coupon_cashback_transaction_id', true ), $order ) && get_post_meta( $order->get_id(), '_coupon_cashback_amount', true ) ) {
                $coupon_cashback_amount = apply_filters( 'dgc_payment_coupon_cashback_amount', get_post_meta( $order->get_id(), '_coupon_cashback_amount', true ), $order );
                if ( $coupon_cashback_amount ) {
                    $transaction_id = dgc_payment()->payment->credit( $order->get_customer_id(), $coupon_cashback_amount, __( 'Payment credit through cashback by applying coupon', 'text-domain' ) );
                    if ( $transaction_id ) {
                        update_payment_transaction_meta( $transaction_id, '_type', 'cashback', $order->get_customer_id() );
                        update_post_meta( $order->get_id(), '_coupon_cashback_transaction_id', $transaction_id );
                        do_action( 'dgc_payment_coupon_cashback_credited', $transaction_id );
                    }
                }
            }
        }

        public function payment_partial_payment( $order_id ) {
            $order = wc_get_order( $order_id );
            $partial_payment_amount = get_order_partial_payment_amount( $order_id );
            if ( $partial_payment_amount && !get_post_meta( $order_id, '_partial_pay_through_payment_compleate', true ) ) {
                $transaction_id = dgc_payment()->payment->debit( $order->get_customer_id(), $partial_payment_amount, __( 'For order payment #', 'text-domain' ) . $order->get_order_number() );
                if ( $transaction_id ) {
                    $order->add_order_note(sprintf( __( '%s paid through payment', 'text-domain' ), wc_price( $partial_payment_amount, dgc_payment_wc_price_args($order->get_customer_id()) ) ) );
                    update_payment_transaction_meta( $transaction_id, '_partial_payment', true, $order->get_customer_id() );
                    update_post_meta( $order_id, '_partial_pay_through_payment_compleate', $transaction_id );
                    do_action( 'dgc_payment_partial_payment_completed', $transaction_id, $order );
                }
            }
        }

        public function process_cancelled_order( $order_id ) {
            $order = wc_get_order( $order_id );
            /** credit partial payment amount * */
            $partial_payment_amount = get_order_partial_payment_amount( $order_id );
            if ( $partial_payment_amount && get_post_meta( $order_id, '_partial_pay_through_payment_compleate', true ) ) {
                dgc_payment()->payment->credit( $order->get_customer_id(), $partial_payment_amount, sprintf( __( 'Your order with ID #%s has been cancelled and hence your payment amount has been refunded!', 'text-domain' ), $order->get_order_number() ) );
                $order->add_order_note(sprintf( __( 'Payment amount %s has been credited to customer upon cancellation', 'text-domain' ), $partial_payment_amount ) );
                delete_post_meta( $order_id, '_partial_pay_through_payment_compleate' );
            }

            /** debit cashback amount * */
            if ( apply_filters( 'dgc_payment_debit_cashback_upon_cancellation', get_total_order_cashback_amount( $order_id ) ) ) {
                $total_cashback_amount = get_total_order_cashback_amount( $order_id );
                if ( $total_cashback_amount ) {
                    if ( dgc_payment()->payment->debit( $order->get_customer_id(), $total_cashback_amount, sprintf( __( 'Cashback for #%s has been debited upon cancellation', 'text-domain' ), $order->get_order_number() ) ) ) {
                        delete_post_meta( $order_id, '_general_cashback_transaction_id' );
                        delete_post_meta( $order_id, '_coupon_cashback_transaction_id' );
                    }
                }
            }
        }


}
