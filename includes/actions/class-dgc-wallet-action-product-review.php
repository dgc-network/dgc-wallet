<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Action_Product_Review extends dgc_Wallet_Action {

    public function __construct() {
        $this->id = 'product_review';
        $this->action_title = __( 'WooCommerce product review', 'text-domain' );
        $this->description = __( 'Set credit for WooCommerce product review', 'text-domain' );
        $this->init_form_fields();
        $this->init_settings();
        // Actions.
        add_action( 'comment_post', array( $this, 'new_product_review' ), 10, 3);
        add_action( 'transition_comment_status', array( $this, 'dgc_wallet_product_review_credit' ), 10, 3);
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields() {

        $this->form_fields = array(
            'enabled' => array(
                'title'   => __( 'Enable/Disable', 'text-domain' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable credit for WooCommerce product review', 'text-domain' ),
                'default' => 'no',
            ),
            'amount' => array(
                'title'       => __( 'Amount', 'text-domain' ),
                'type'        => 'price',
                'description' => __( 'Enter amount which will be credited to the user payment for reviewing a WooCommerce product.', 'text-domain' ),
                'default'     => '10',
                'desc_tip'    => true
            ),
            'description' => array(
                'title'       => __( 'Description', 'text-domain' ),
                'type'        => 'textarea',
                'description' => __( 'Payment transaction description that will display as transaction note.', 'text-domain' ),
                'default'     => __( 'Balance credited for reviewing a WooCommerce product.', 'text-domain' ),
                'desc_tip'    => true,
            )
        );
    }

    public function new_product_review( $comment_ID, $comment_approved, $commentdata ) {
        if ( 'product' === get_post_type(absint( $commentdata['comment_post_ID'] ) ) ) { // WPCS: input var ok, CSRF ok.
            if ( !$this->is_enabled() || $commentdata['comment_approved'] != 1 || get_comment_meta( $comment_ID, 'payment_transaction_id', true ) ) {
                return;
            }
            $product = wc_get_product( $commentdata['comment_post_ID'] );
            if ( $this->settings['amount'] && $product && apply_filters( 'dgc_wallet_product_review_credit', true, $commentdata ) ) {
                $transaction_id = dgc_wallet()->wallet_core->credit( $commentdata['user_id'], $this->settings['amount'], sanitize_textarea_field( $this->settings['description'] ) );
                update_comment_meta( $comment_ID, 'payment_transaction_id', $transaction_id );
            }
        }
    }

    public function dgc_wallet_product_review_credit( $new_status, $old_status, $comment ) {
        if ( !$this->is_enabled() || $new_status != 'approved' || get_comment_meta( $comment->comment_ID, 'payment_transaction_id', true ) ) {
            return;
        }
        $product = wc_get_product( $comment->comment_post_ID );
        if ( $this->settings['amount'] && $product && apply_filters( 'dgc_wallet_product_review_credit', true, $comment ) ) {
            $transaction_id = dgc_wallet()->wallet_core->credit( $comment->user_id, $this->settings['amount'], sanitize_textarea_field( $this->settings['description'] ) );
            update_comment_meta( $comment->comment_ID, 'payment_transaction_id', $transaction_id );
        }
    }

}
