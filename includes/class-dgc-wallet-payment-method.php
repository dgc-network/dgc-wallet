<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class dgc_Wallet_Payment_Gateway extends WC_Payment_Gateway {

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
    }

    /**
     * Setup general properties for the gateway.
     */
    protected function setup_properties() {
        $this->id = 'wallet';
        $this->method_title = __( 'Wallet', 'dgc-wallet' );
        $this->method_description = __( 'Have your customers pay with wallet.', 'dgc-wallet' );
        $this->has_fields = false;
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __( 'Enable/Disable', 'dgc-wallet' ),
                'label' => __( 'Enable wallet payments', 'dgc-wallet' ),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no',
            ),
            'title' => array(
                'title' => __( 'Title', 'dgc-wallet' ),
                'type' => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'dgc-wallet' ),
                'default' => __( 'Wallet payment', 'dgc-wallet' ),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __( 'Description', 'dgc-wallet' ),
                'type' => 'textarea',
                'description' => __( 'Payment method description that the customer will see on your checkout.', 'dgc-wallet' ),
                'default' => __( 'Pay with wallet.', 'dgc-wallet' ),
                'desc_tip' => true,
            ),
            'instructions' => array(
                'title' => __( 'Instructions', 'dgc-wallet' ),
                'type' => 'textarea',
                'description' => __( 'Instructions that will be added to the thank you page.', 'dgc-wallet' ),
                'default' => __( 'Pay with wallet.', 'dgc-wallet' ),
                'desc_tip' => true,
            )
        );
    }

    /**
     * Is gateway available
     * @return boolean
     */
    public function is_available() {
        return apply_filters( 'dgc_wallet_payment_is_available', (parent::is_available() && is_full_payment_through_wallet() && is_user_logged_in() && ! is_enable_wallet_partial_payment() ) );
    }

    public function get_icon() {
        $current_balance = dgc_wallet()->wallet->get_wallet_balance( get_current_user_id() );
        return apply_filters( 'dgc_wallet_gateway_icon', sprintf( __( ' | Current Balance: <strong>%s</strong>', 'dgc-wallet' ), $current_balance), $this->id );
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
     * Process wallet payment
     * @param int $order_id
     * @return array
     */
    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ( $order->get_total( 'edit' ) > dgc_wallet()->wallet->get_wallet_balance( get_current_user_id(), 'edit' ) ) && apply_filters( 'dgc_wallet_disallow_negative_transaction', (dgc_wallet()->wallet->get_wallet_balance( get_current_user_id(), 'edit' ) <= 0 || $order->get_total( 'edit' ) > dgc_wallet()->wallet->get_wallet_balance( get_current_user_id(), 'edit' ) ), $order->get_total( 'edit' ), dgc_wallet()->wallet->get_wallet_balance( get_current_user_id(), 'edit' ) ) ) {
            wc_add_notice( __( 'Payment error: ', 'dgc-wallet' ) . sprintf( __( 'Your wallet balance is low. Please add %s to proceed with this transaction.', 'dgc-wallet' ), wc_price( $order->get_total( 'edit' ) - dgc_wallet()->wallet->get_wallet_balance( get_current_user_id(), 'edit' ), dgc_wallet_wc_price_args($order->get_customer_id()) ) ), 'error' );
            return;
        }
        $wallet_response = dgc_wallet()->wallet->debit( get_current_user_id(), $order->get_total( 'edit' ), apply_filters('dgc_wallet_order_payment_description', __( 'For order payment #', 'dgc-wallet' ) . $order->get_order_number(), $order) );

        // Reduce stock levels
        wc_reduce_stock_levels( $order_id );

        // Remove cart
        WC()->cart->empty_cart();

        if ( $wallet_response) {
            $order->payment_complete( $wallet_response);
            do_action( 'dgc_wallet_payment_processed', $order_id, $wallet_response);
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
        $transaction_id = dgc_wallet()->wallet->credit( $order->get_customer_id(), $amount, __( 'Wallet refund #', 'dgc-wallet' ) . $order->get_order_number() );
        if ( !$transaction_id ) {
            throw new Exception( __( 'Refund not credited to customer', 'dgc-wallet' ) );
        }
        do_action( 'dgc_wallet_order_refunded', $order, $amount, $transaction_id );
        return true;
    }

    /**
     * Process renewal payment for subscription order
     * @param int $amount_to_charge
     * @param WC_Order $order
     * @return void
     */
    public function scheduled_subscription_payment( $amount_to_charge, $order ) {
        if ( get_post_meta( $order->get_id(), '_wallet_scheduled_subscription_payment_processed', true ) ) {
            return;
        }
        $wallet_response = dgc_wallet()->wallet->debit( $order->get_customer_id(), $amount_to_charge, __( 'For order payment #', 'dgc-wallet' ) . $order->get_order_number() );
        if ( $wallet_response) {
            $order->payment_complete();
        } else {
            $order->add_order_note( __( 'Insufficient funds in customer wallet', 'dgc-wallet' ) );
        }
        update_post_meta( $order->get_id(), '_wallet_scheduled_subscription_payment_processed', true );
    }

}
