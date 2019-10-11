<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Action_New_Registration extends dgc_WalletAction {

    public function __construct() {
        $this->id = 'new_registration';
        $this->action_title = __( 'New user registration', 'dgc-wallet' );
        $this->description = __( 'Set credit upon new user registration', 'dgc-wallet' );
        $this->init_form_fields();
        $this->init_settings();
        // Actions.
        add_action( 'user_register', array( $this, 'dgc_wallet_new_user_registration_credit' ) );
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields() {

        $this->form_fields = apply_filters('dgc_wallet_action_new_registration_form_fields', array(
            'enabled' => array(
                'title'   => __( 'Enable/Disable', 'dgc-wallet' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable auto credit upon user registration', 'dgc-wallet' ),
                'default' => 'no',
            ),
            'amount' => array(
                'title'       => __( 'Amount', 'dgc-wallet' ),
                'type'        => 'price',
                'description' => __( 'Enter amount which will be credited to the user wallet after registration.', 'dgc-wallet' ),
                'default'     => '10',
                'desc_tip'    => true
            ),
            'description' => array(
                'title'       => __( 'Description', 'dgc-wallet' ),
                'type'        => 'textarea',
                'description' => __( 'Wallet transaction description that will display as transaction note.', 'dgc-wallet' ),
                'default'     => __( 'Balance credited for becoming a member.', 'dgc-wallet' ),
                'desc_tip'    => true,
            )
        ));
    }
    
    public function dgc_wallet_new_user_registration_credit( $user_id ){
        if ( $this->is_enabled() && $this->settings['amount'] && apply_filters( 'dgc_wallet_new_user_registration_credit', true, $user_id ) ){
            $amount = apply_filters( 'dgc_wallet_new_user_registration_credit_amount', $this->settings['amount'], $user_id );
            $transaction_id = dgc_wallet()->wallet->credit( $user_id, $amount, sanitize_textarea_field( $this->settings['description'] ) );
            if($transaction_id){
                do_action('dgc_wallet_action_new_registration_credited', $transaction_id, $user_id, $this);
            }
        }
    }

}
