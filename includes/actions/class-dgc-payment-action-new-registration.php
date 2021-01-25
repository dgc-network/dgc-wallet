<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Action_New_Registration extends dgc_PaymentAction {

    public function __construct() {
        $this->id = 'new_registration';
        $this->action_title = __( 'New user registration', 'dgc-payment' );
        $this->description = __( 'Set credit upon new user registration', 'dgc-payment' );
        $this->init_form_fields();
        $this->init_settings();
        // Actions.
        add_action( 'user_register', array( $this, 'dgc_payment_new_user_registration_credit' ) );
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields() {

        $this->form_fields = apply_filters('dgc_payment_action_new_registration_form_fields', array(
            'enabled' => array(
                'title'   => __( 'Enable/Disable', 'dgc-payment' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable auto credit upon user registration', 'dgc-payment' ),
                'default' => 'no',
            ),
            'amount' => array(
                'title'       => __( 'Amount', 'dgc-payment' ),
                'type'        => 'price',
                'description' => __( 'Enter amount which will be credited to the user payment after registration.', 'dgc-payment' ),
                'default'     => '10',
                'desc_tip'    => true
            ),
            'description' => array(
                'title'       => __( 'Description', 'dgc-payment' ),
                'type'        => 'textarea',
                'description' => __( 'Payment transaction description that will display as transaction note.', 'dgc-payment' ),
                'default'     => __( 'Balance credited for becoming a member.', 'dgc-payment' ),
                'desc_tip'    => true,
            )
        ));
    }
    
    public function dgc_payment_new_user_registration_credit( $user_id ){
        if ( $this->is_enabled() && $this->settings['amount'] && apply_filters( 'dgc_payment_new_user_registration_credit', true, $user_id ) ){
            $amount = apply_filters( 'dgc_payment_new_user_registration_credit_amount', $this->settings['amount'], $user_id );
            $transaction_id = dgc_payment()->payment->credit( $user_id, $amount, sanitize_textarea_field( $this->settings['description'] ) );
            if($transaction_id){
                do_action('dgc_payment_action_new_registration_credited', $transaction_id, $user_id, $this);
            }
        }
    }

}