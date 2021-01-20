<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Action_Daily_Visits extends dgc_PaymentAction {

    public function __construct() {
        $this->id = 'daily_visits';
        $this->action_title = __( 'Daily visits', 'dgc-payment' );
        $this->description  = __( 'Set credit for daily visits', 'dgc-payment' );
        $this->init_form_fields();
        $this->init_settings();
        // Actions.
        add_action( 'wp', array( $this, 'dgc_payment_site_visit_credit' ), 100 );
        
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields() {

        $this->form_fields = array(
            'enabled' => array(
                'title'   => __( 'Enable/Disable', 'dgc-payment' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable credit for daily visits.', 'dgc-payment' ),
                'default' => 'no',
            ),
            'amount' => array(
                'title'       => __( 'Amount', 'dgc-payment' ),
                'type'        => 'price',
                'description' => __( 'Enter amount which will be credited to the user payment for daily visits.', 'dgc-payment' ),
                'default'     => '10',
                'desc_tip'    => true
            ),
            'exclude_role' => array(
                'title'       => __( 'Exclude user role', 'dgc-payment' ),
                'description' => __( 'This option lets you limit which user role you want to exclude.', 'dgc-payment' ),
                'type'        => 'multiselect',
                'class'       => 'wc-enhanced-select',
                'css'         => 'min-width: 350px;',
                'desc_tip'    => true,
                'options'     => $this->get_editable_role_options()
            ),
            'description' => array(
                'title'       => __( 'Description', 'dgc-payment' ),
                'type'        => 'textarea',
                'description' => __( 'Payment transaction description that will display as transaction note.', 'dgc-payment' ),
                'default'     => __( 'Balance credited visiting site.', 'dgc-payment' ),
                'desc_tip'    => true,
            )
        );
    }
    
    public function get_editable_role_options(){
        $role_options = array();
        $editable_roles = array_reverse( wp_roles()->roles );
        foreach ( $editable_roles as $role => $details ) {
            $name = translate_user_role($details['name'] );
            $role_options[$role] = $name;
	}
        return $role_options;
    }
    
    public function dgc_payment_site_visit_credit() {
        if ( !$this->is_enabled() || ! is_user_logged_in() ) {
            return;
        }
        $user_id = get_current_user_id();
        $user = new WP_User($user_id);
        if( isset( $this->settings['exclude_role'] ) && !array_diff( $user->roles, (array) $this->settings['exclude_role'] ) ){
            return;
        }
        if ( get_transient('dgc_payment_site_visit_' . $user_id ) ) {
            return;
        }
        
        if ( ! headers_sent() && did_action( 'wp_loaded' ) ) {
            set_transient('dgc_payment_site_visit_' . $user_id, true, DAY_IN_SECONDS);
        }
        
        if ( $this->settings['amount'] && apply_filters( 'dgc_payment_site_visit_credit', true ) ) {
            dgc_payment()->payment->credit( $user_id, $this->settings['amount'], sanitize_textarea_field( $this->settings['description'] ) );
        }
    }

}

