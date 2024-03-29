<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
if ( ! class_exists( 'dgc_wallet_Ajax' ) ) {

    class dgc_wallet_Ajax {

        /**
         * The single instance of the class.
         *
         * @var dgc_wallet_Ajax
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

        /**
         * Class constructor
         */
        public function __construct() {
            add_action( 'wp_ajax_dgc_wallet_order_refund', array( $this, 'dgc_wallet_order_refund' ) );
            add_action( 'wp_ajax_woocommerce_payment_rated', array( $this, 'woocommerce_payment_rated' ) );
            add_action( 'wp_ajax_dgc-wallet-user-search', array( $this, 'dgc_wallet_user_search' ) );
            add_action( 'wp_ajax_dgc_wallet_partial_payment_update_session', array( $this, 'dgc_wallet_partial_payment_update_session' ) );
            add_action( 'wp_ajax_dgc_wallet_refund_partial_payment', array($this, 'dgc_wallet_refund_partial_payment' ) );
            add_action( 'wp_ajax_dgc-wallet-dismiss-promotional-notice', array($this, 'dgc_wallet_dismiss_promotional_notice' ) );
        }
        /**
         * Wallet partial payment refund.
         */
        public function dgc_wallet_refund_partial_payment(){
            if ( !current_user_can( 'edit_shop_orders' ) ) {
                wp_die(-1 );
            }
            $response = array('success' => false);
            $order_id = absint( filter_input(INPUT_POST, 'order_id') );
            $order = wc_get_order($order_id);
            $partial_payment_amount = get_order_partial_payment_amount($order_id);
            $transaction_id = dgc_wallet()->wallet_core->credit( $order->get_customer_id(), $partial_payment_amount, __( 'Payment refund #', 'text-domain' ) . $order->get_order_number() );
            if($transaction_id){
                $response['success'] = true;
                $order->add_order_note(sprintf( __( '%s refunded to customer payment', 'text-domain' ), wc_price( $partial_payment_amount, dgc_wallet_wc_price_args($order->get_customer_id()) ) ));
                update_post_meta($order_id, '_dgc_wallet_partial_payment_refunded', true);
                update_post_meta($order_id, '_partial_payment_refund_id', $transaction_id);
                add_action('dgc_wallet_partial_order_refunded', $order_id, $transaction_id);
            }
            wp_send_json($response);
        }

        /**
         * Process refund through payment
         * @throws exception
         * @throws Exception
         */
        public function dgc_wallet_order_refund() {
            ob_start();
            check_ajax_referer( 'order-item', 'security' );
            if ( !current_user_can( 'edit_shop_orders' ) ) {
                wp_die(-1 );
            }
            $order_id = absint( $_POST['order_id'] );
            $refund_amount = wc_format_decimal(sanitize_text_field( $_POST['refund_amount'] ), wc_get_price_decimals() );
            $refund_reason = sanitize_text_field( $_POST['refund_reason'] );
            $line_item_qtys = json_decode(sanitize_text_field(stripslashes( $_POST['line_item_qtys'] ) ), true );
            $line_item_totals = json_decode(sanitize_text_field(stripslashes( $_POST['line_item_totals'] ) ), true );
            $line_item_tax_totals = json_decode(sanitize_text_field(stripslashes( $_POST['line_item_tax_totals'] ) ), true );
            $api_refund = 'true' === $_POST['api_refund'];
            $restock_refunded_items = 'true' === $_POST['restock_refunded_items'];
            $refund = false;
            $response_data = array();
            try {
                $order = wc_get_order( $order_id );
                $order_items = $order->get_items();
                $max_refund = wc_format_decimal( $order->get_total() - $order->get_total_refunded(), wc_get_price_decimals() );

                if ( !$refund_amount || $max_refund < $refund_amount || 0 > $refund_amount ) {
                    throw new exception( __( 'Invalid refund amount', 'text-domain' ) );
                }
                // Prepare line items which we are refunding
                $line_items = array();
                $item_ids = array_unique( array_merge( array_keys( $line_item_qtys, $line_item_totals) ) );

                foreach ( $item_ids as $item_id ) {
                    $line_items[$item_id] = array( 'qty' => 0, 'refund_total' => 0, 'refund_tax' => array() );
                }
                foreach ( $line_item_qtys as $item_id => $qty) {
                    $line_items[$item_id]['qty'] = max( $qty, 0 );
                }
                foreach ( $line_item_totals as $item_id => $total ) {
                    $line_items[$item_id]['refund_total'] = wc_format_decimal( $total );
                }
                foreach ( $line_item_tax_totals as $item_id => $tax_totals) {
                    $line_items[$item_id]['refund_tax'] = array_filter( array_map( 'wc_format_decimal', $tax_totals) );
                }
                // Create the refund object.
                $refund = wc_create_refund( array(
                    'amount' => $refund_amount,
                    'reason' => $refund_reason,
                    'order_id' => $order_id,
                    'line_items' => $line_items,
                    'refund_payment' => $api_refund,
                    'restock_items' => $restock_refunded_items,
                ) );
                if ( ! is_wp_error( $refund ) ) {
                    $transaction_id = dgc_wallet()->wallet_core->credit( $order->get_customer_id(), $refund_amount, __( 'Payment refund #', 'text-domain' ) . $order->get_order_number() );
                    if ( !$transaction_id ) {
                        throw new Exception( __( 'Refund not credited to customer', 'text-domain' ) );
                    } else {
                        do_action( 'dgc_wallet_order_refunded', $order, $refund, $transaction_id );
                    }
                }

                if ( is_wp_error( $refund ) ) {
                    throw new Exception( $refund->get_error_message() );
                }

                if (did_action( 'woocommerce_order_fully_refunded' ) ) {
                    $response_data['status'] = 'fully_refunded';
                }

                wp_send_json_success( $response_data);
            } catch (Exception $ex) {
                if ( $refund && is_a( $refund, 'WC_Order_Refund' ) ) {
                    wp_delete_post( $refund->get_id(), true );
                }
                wp_send_json_error( array( 'error' => $ex->getMessage() ) );
            }
        }

        /**
         * Mark payment rated.
         */
        public function woocommerce_payment_rated() {
            update_option( 'woocommerce_payment_admin_footer_text_rated', true );
            die;
        }

        /**
         * Search users
         */
        public function dgc_wallet_user_search() {
            $return = array();
            if ( apply_filters( 'dgc_wallet_user_search_exact_match', true ) ) {
                $user = get_user_by( apply_filters( 'dgc_wallet_user_search_by', 'email' ), $_REQUEST['term'] );
                if ( $user && wp_get_current_user()->user_email != $user->user_email) {
                    $return[] = array(
                        /* translators: 1: user_login, 2: user_email */
                        'label' => sprintf(_x( '%1$s (%2$s)', 'user autocomplete result', 'text-domain' ), $user->user_login, $user->user_email),
                        'value' => $user->ID,
                    );
                }
            } else {
                if ( isset( $_REQUEST['site_id'] ) ) {
                    $id = absint( $_REQUEST['site_id'] );
                } else {
                    $id = get_current_blog_id();
                }

                $users = get_users( array(
                    'blog_id' => $id,
                    'search' => '*' . $_REQUEST['term'] . '*',
                    'exclude' => array( get_current_user_id() ),
                    'search_columns' => array( 'user_login', 'user_nicename', 'user_email' ),
                ) );

                foreach ( $users as $user) {
                    $return[] = array(
                        /* translators: 1: user_login, 2: user_email */
                        'label' => sprintf(_x( '%1$s (%2$s)', 'user autocomplete result', 'text-domain' ), $user->user_login, $user->user_email),
                        'value' => $user->ID,
                    );
                }
            }
            wp_send_json( $return);
        }

        public function dgc_wallet_partial_payment_update_session() {
            if ( isset( $_POST['checked'] ) && $_POST['checked'] == 'true' ) {
                update_partial_payment_session(true );
            } else {
                update_partial_payment_session();
            }
            wp_die();
        }
        
        public function dgc_wallet_dismiss_promotional_notice(){
            $post_data = wp_unslash( $_POST );
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_send_json_error( __( 'You have no permission to do that', 'text-domain' ) );
            }

            if ( ! wp_verify_nonce( $post_data['nonce'], 'dgc_wallet_admin' ) ) {
                wp_send_json_error( __( 'Invalid nonce', 'text-domain' ) );
            }
            update_option('_dgc_wallet_promotion_dismissed', true);
            wp_send_json_success();
        }
    }

}
dgc_wallet_Ajax::instance();
