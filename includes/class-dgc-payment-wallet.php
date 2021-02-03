<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'dgc_Payment_Wallet' ) ) {

    class dgc_Payment_Wallet {
        /**
         * WordPress user ID.
         * @var INT 
         */
        public $user_id = 0;

        /**
         * Payment balance.
         * @var float 
         */
        public $payment_balance = 0;

        /**
         * Current payment balance meta key.
         * @var string 
         */
        public $meta_key = '_current_dgc_payment_balance';

        /*
         * Class constructor
         */
        public function __construct() {
            $this->user_id = get_current_user_id();
        }

        /**
         * setter method
         * @param int $user_id
         */
        private function set_user_id( $user_id = '' ) {
            $this->user_id = $user_id ? $user_id : $this->user_id;
        }

        /**
         * Get user payment balance or display
         * @global object $wpdb
         * @param int $user_id
         * @param string $context
         * @return mixed
         */
        public function get_payment_balance( $user_id = '', $context = 'view' ) {
            global $wpdb;
            if ( empty( $user_id ) ) {
                $user_id = get_current_user_id();
            }
            $this->set_user_id( $user_id );
            $this->payment_balance = 0;
            if ( $this->user_id ) {
                $credit_amount = array_sum(wp_list_pluck( get_payment_transactions( array( 'user_id' => $this->user_id, 'where' => array( array( 'key' => 'type', 'value' => 'credit' ) ) ) ), 'amount' ) );
                $debit_amount = array_sum(wp_list_pluck( get_payment_transactions( array( 'user_id' => $this->user_id, 'where' => array( array( 'key' => 'type', 'value' => 'debit' ) ) ) ), 'amount' ) );
                $balance = $credit_amount - $debit_amount;
                $this->payment_balance = apply_filters( 'dgc_payment_current_balance', $balance, $this->user_id );
            }
            return 'view' === $context ? wc_price( $this->payment_balance, dgc_payment_wc_price_args($this->user_id) ) : number_format( $this->payment_balance, wc_get_price_decimals(), '.', '' );
        }

        /**
         * Create payment credit transaction
         * @param int $user_id
         * @param float $amount
         * @param string $details
         * @return int transaction id
         */
        public function credit( $user_id = '', $amount = 0, $details = '' ) {
            $this->set_user_id( $user_id );
            return $this->record_transaction( $amount, 'credit', $details );
        }

        /**
         * Create payment debit transaction
         * @param int $user_id
         * @param float $amount
         * @param string $details
         * @return int transaction id
         */
        public function debit( $user_id = '', $amount = 0, $details = '' ) {
            $this->set_user_id( $user_id );
            return $this->record_transaction( $amount, 'debit', $details );
        }

        /**
         * Record payment transactions
         * @global object $wpdb
         * @param int $amount
         * @param string $type
         * @param string $details
         * @return boolean | transaction id
         */
        private function record_transaction( $amount, $type, $details ) {
            global $wpdb;
            if(!$this->user_id){
                return;
            }
            if ( $amount < 0 ) {
                $amount = 0;
            }
            $balance = $this->get_payment_balance( $this->user_id, '' );
            if ( $type == 'debit' && apply_filters( 'dgc_payment_disallow_negative_transaction', ( $balance <= 0 || $amount > $balance), $amount, $balance) ) {
                return false;
            }
            if ( $type == 'credit' ) {
                $balance += $amount;
            } else if ( $type == 'debit' ) {
                $balance -= $amount;
            }
            if ( $wpdb->insert( "{$wpdb->base_prefix}dgc_payment_transactions", apply_filters( 'dgc_payment_transactions_args', array( 'blog_id' => $GLOBALS['blog_id'], 'user_id' => $this->user_id, 'type' => $type, 'amount' => $amount, 'balance' => $balance, 'currency' => get_woocommerce_currency(), 'details' => $details, 'date' => current_time('mysql') ), array( '%d', '%d', '%s', '%f', '%f', '%s', '%s', '%s' ) ) ) ) {
                $transaction_id = $wpdb->insert_id;
/*
                // dgc-API-call:begin: /createRecord
                $transaction_id = time();
                $data =  apply_filters( 'dgc_payment_transactions_args', array( 
                    'transaction_id'=> $transaction_id, 
                    'blog_id'       => $GLOBALS['blog_id'], 
                    'user_id'       => $this->user_id, 
                    'publicKey'		=> get_user_meta($this->user_id, "publicKey", true ),
                    'type'          => $type,
                    'amount'        => $amount, 
                    'balance'       => $balance, 
                    'currency'      => get_woocommerce_currency(), 
                    'details'       => $details, 
                    'deleted'     => 0, 
                    'date'          => time() 
                ), array( '%d', '%d', '%d', '%s', '%f', '%f', '%s', '%s', '%d', '%d') ) ;
                $dgc_API_args = array(
                    'table'		=> $wpdb->prefix . 'dgc_payment_transactions',
                    'data'		=> $data,
                );
                $dgc_API_res = dgc_API_call('/createRecord', 'POST', $dgc_API_args);
                // dgc-API-call:end: /createRecord
*/    
                update_user_meta($this->user_id, $this->meta_key, $balance);
                clear_dgc_payment_cache( $this->user_id );
                do_action( 'dgc_payment_transaction_recorded', $transaction_id, $this->user_id, $amount, $type);
                $email_admin = WC()->mailer()->emails['dgc_Payment_Email_New_Transaction'];
                $email_admin->trigger( $transaction_id );
                return $transaction_id;
            }
            return false;
        }


        /** Move the below to class-dgc-payment-method */
        /** Move the below to class-dgc-payment-admin */
        
        /**
         * Credit payment balance through order payment
         * @param int $order_id
         * @return void
         */
/*        
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
            $transaction_id = $this->credit( $order->get_customer_id(), $recharge_amount, __( 'Payment credit through purchase #', 'text-domain' ) . $order->get_order_number() );
            if ( $transaction_id ) {
                update_post_meta( $order_id, '_dgc_payment_purchase_credited', true );
                update_post_meta( $order_id, '_payment_payment_transaction_id', $transaction_id );
                update_payment_transaction_meta( $transaction_id, '_dgc_payment_purchase_gateway_charge', $charge_amount, $order->get_customer_id() );
                do_action( 'dgc_payment_credit_purchase_completed', $transaction_id, $order );
            }
        }

        public function payment_cashback( $order_id ) {
            $order = wc_get_order( $order_id );
            // General Cashback
            if ( apply_filters( 'process_dgc_payment_general_cashback', !get_post_meta( $order->get_id(), '_general_cashback_transaction_id', true ), $order ) && dgc_payment()->cashback->calculate_cashback(false, $order->get_id()) ) {
                $transaction_id = $this->credit( $order->get_customer_id(), dgc_payment()->cashback->calculate_cashback(false, $order->get_id()), __( 'Payment credit through cashback #', 'text-domain' ) . $order->get_order_number() );
                if ( $transaction_id ) {
                    update_payment_transaction_meta( $transaction_id, '_type', 'cashback', $order->get_customer_id() );
                    update_post_meta( $order->get_id(), '_general_cashback_transaction_id', $transaction_id );
                    do_action( 'dgc_payment_general_cashback_credited', $transaction_id );
                }
            }
            // Coupon Cashback
            if ( apply_filters( 'process_dgc_payment_coupon_cashback', !get_post_meta( $order->get_id(), '_coupon_cashback_transaction_id', true ), $order ) && get_post_meta( $order->get_id(), '_coupon_cashback_amount', true ) ) {
                $coupon_cashback_amount = apply_filters( 'dgc_payment_coupon_cashback_amount', get_post_meta( $order->get_id(), '_coupon_cashback_amount', true ), $order );
                if ( $coupon_cashback_amount ) {
                    $transaction_id = $this->credit( $order->get_customer_id(), $coupon_cashback_amount, __( 'Payment credit through cashback by applying coupon', 'text-domain' ) );
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
                $transaction_id = $this->debit( $order->get_customer_id(), $partial_payment_amount, __( 'For order payment #', 'text-domain' ) . $order->get_order_number() );
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
            // credit partial payment amount
            $partial_payment_amount = get_order_partial_payment_amount( $order_id );
            if ( $partial_payment_amount && get_post_meta( $order_id, '_partial_pay_through_payment_compleate', true ) ) {
                $this->credit( $order->get_customer_id(), $partial_payment_amount, sprintf( __( 'Your order with ID #%s has been cancelled and hence your payment amount has been refunded!', 'text-domain' ), $order->get_order_number() ) );
                $order->add_order_note(sprintf( __( 'Payment amount %s has been credited to customer upon cancellation', 'text-domain' ), $partial_payment_amount ) );
                delete_post_meta( $order_id, '_partial_pay_through_payment_compleate' );
            }

            // debit cashback amount
            if ( apply_filters( 'dgc_payment_debit_cashback_upon_cancellation', get_total_order_cashback_amount( $order_id ) ) ) {
                $total_cashback_amount = get_total_order_cashback_amount( $order_id );
                if ( $total_cashback_amount ) {
                    if ( $this->debit( $order->get_customer_id(), $total_cashback_amount, sprintf( __( 'Cashback for #%s has been debited upon cancellation', 'text-domain' ), $order->get_order_number() ) ) ) {
                        delete_post_meta( $order_id, '_general_cashback_transaction_id' );
                        delete_post_meta( $order_id, '_coupon_cashback_transaction_id' );
                    }
                }
            }
        }
*/
    }

}
