<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Action_Referrals extends dgc_PaymentAction {

    /**
     * Referral base.
     * @var string 
     */
    public $referral_handel = null;

    public function __construct() {
        $this->id = 'referrals';
        $this->action_title = __('Referrals', 'dgc-payment');
        $this->description = __('Set credit ruls for referrals', 'dgc-payment');
        $this->init_form_fields();
        $this->init_settings();
        // Actions.
        add_action('wp_loaded', array($this, 'load_dgc_payment_referral'));
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields() {

        $this->form_fields = apply_filters('dgc_payment_action_referrals_form_fields', array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'dgc-payment'),
                'type' => 'checkbox',
                'label' => __('Enable credit for referrals.', 'dgc-payment'),
                'default' => 'no',
            ),
            array(
                'title' => __('Referring Visitors', 'dgc-payment'),
                'type' => 'title',
                'desc' => '',
                'id' => 'referring_visitors',
            ),
            'referring_visitors_amount' => array(
                'title' => __('Amount', 'dgc-payment'),
                'type' => 'price',
                'description' => __('Enter amount which will be credited to the user payment for daily visits.', 'dgc-payment'),
                'default' => '10',
                'desc_tip' => true
            ),
            'referring_visitors_limit_duration' => array(
                'title' => __('Limit', 'dgc-payment'),
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'css' => 'min-width: 350px;',
                'options' => array('0' => __('No Limit', 'dgc-payment'), 'day' => __('Per Day', 'dgc-payment'), 'week' => __('Per Week', 'dgc-payment'), 'month' => __('Per Month', 'dgc-payment'))
            ),
            'referring_visitors_limit' => array(
                'type' => 'number',
                'default' => 0,
            ),
            'referring_visitors_description' => array(
                'title' => __('Description', 'dgc-payment'),
                'type' => 'textarea',
                'description' => __('Payment transaction description that will display as transaction note.', 'dgc-payment'),
                'default' => __('Balance credited for referring a visitor', 'dgc-payment'),
                'desc_tip' => true,
            ),
            array(
                'title' => __('Referring Signups', 'dgc-payment'),
                'type' => 'title',
                'desc' => '',
                'id' => 'referring_signups',
            ),
            'referring_signups_amount' => array(
                'title' => __('Amount', 'dgc-payment'),
                'type' => 'price',
                'description' => __('Enter amount which will be credited to the user payment for daily visits.', 'dgc-payment'),
                'default' => '10',
                'desc_tip' => true
            ),
            'referring_signups_limit_duration' => array(
                'title' => __('Limit', 'dgc-payment'),
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'css' => 'min-width: 350px;',
                'options' => array('0' => __('No Limit', 'dgc-payment'), 'day' => __('Per Day', 'dgc-payment'), 'week' => __('Per Week', 'dgc-payment'), 'month' => __('Per Month', 'dgc-payment'))
            ),
            'referring_signups_limit' => array(
                'type' => 'number',
                'default' => 0,
            ),
            'referring_signups_description' => array(
                'title' => __('Description', 'dgc-payment'),
                'type' => 'textarea',
                'description' => __('Payment transaction description that will display as transaction note.', 'dgc-payment'),
                'default' => __('Balance credited for referring a new member', 'dgc-payment'),
                'desc_tip' => true,
            ),
            array(
                'title' => __('Referral Links', 'dgc-payment'),
                'type' => 'title',
                'desc' => '',
                'id' => 'referring_links',
            ),
            'referal_link' => array(
                'title' => __('Referral Format', 'dgc-payment'),
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'css' => 'min-width: 350px;',
                'options' => array('id' => __('Numeric referral ID', 'dgc-payment'), 'username' => __('Usernames as referral ID', 'dgc-payment'))
            )
        ));
    }

    public function load_dgc_payment_referral() {
        if ($this->is_enabled()) {
            $this->referral_handel = apply_filters('dgc_payment_referral_handel', 'wwref');
            add_filter('dgc_payment_nav_menu_items', array($this, 'add_referral_nav_menu'), 10, 2);
            add_action('dgc_payment_menu_content', array($this, 'referral_content'));
            add_filter('dgc_payment_endpoint_actions', array($this, 'dgc_payment_endpoint_actions'));
            $this->init_referrals();
            add_action( 'wp', array( $this, 'init_referral_visit' ), 105 );
            add_action('user_register', array($this, 'dgc_payment_referring_signup'));
        }
    }

    public function add_referral_nav_menu($nav_menu, $is_rendred_from_myaccount) {
        $nav_menu['referrals'] = array(
            'title' => apply_filters('dgc_payment_account_referrals_menu_title', __('Referrals', 'dgc-payment')),
            'url' => $is_rendred_from_myaccount ? esc_url(wc_get_endpoint_url(get_option('woocommerce_dgc_payment_endpoint', 'dgc-payment'), 'referrals', wc_get_page_permalink('myaccount'))) : add_query_arg('payment_action', 'referrals', get_permalink()),
            'icon' => 'dashicons dashicons-groups'
        );
        return $nav_menu;
    }

    public function dgc_payment_endpoint_actions($actions) {
        $actions[] = 'referrals';
        return $actions;
    }

    public function referral_content() {
        global $wp;
        if (apply_filters('dgc_payment_is_enable_referrals', true) && ( ( isset($wp->query_vars['dgc-payment']) && 'referrals' === $wp->query_vars['dgc-payment'] ) || ( isset($_GET['payment_action']) && 'referrals' === $_GET['payment_action'] ) )) {
            dgc_payment()->get_template('dgc-payment-referrals.php', array('settings' => $this->settings, 'referral' => $this));
        }
    }

    public function init_referrals() {
        if (isset($_GET[$this->referral_handel]) && !empty($_GET[$this->referral_handel])) {
            if (!headers_sent() && did_action('wp_loaded') ) {
                wc_setcookie('dgc_payment_referral', $_GET[$this->referral_handel], time() + DAY_IN_SECONDS);
            }
        }
    }

    public function get_referral_user() {
        if (isset($_COOKIE['dgc_payment_referral'])) {
            $dgc_payment_referral = $_COOKIE['dgc_payment_referral'];
            if ('id' === $this->settings['referal_link']) {
                $user = get_user_by('ID', $dgc_payment_referral);
            } else {
                $user = get_user_by('login', $dgc_payment_referral);
            }
            if ($user->ID === get_current_user_id()) {
                return false;
            }
            return apply_filters('dgc_payment_referral_user', $user, $this);
        }
        return false;
    }

    public function init_referral_visit() {
        $referral_visit_amount = $this->settings['referring_visitors_amount'];
        if ($referral_visit_amount && $this->get_referral_user()) {
            $referral_user = $this->get_referral_user();
            if(isset($_COOKIE['dgc_payment_referral_visit_credited_'. $referral_user->ID])){
                return;
            }
            $limit = $this->settings['referring_visitors_limit_duration'];
            $referral_visitor_count = get_user_meta($referral_user->ID, '_dgc_payment_referring_visitor', true) ? get_user_meta($referral_user->ID, '_dgc_payment_referring_visitor', true) : 0;
            $dgc_payment_referring_earning = get_user_meta($referral_user->ID, '_dgc_payment_referring_earning', true) ? get_user_meta($referral_user->ID, '_dgc_payment_referring_earning', true) : 0;
            if ($limit) {
                $dgc_payment_referral_visit_count = get_transient('dgc_payment_referral_visit_' . $referral_user->ID) ? get_transient('dgc_payment_referral_visit_' . $referral_user->ID) : 0;
                if($dgc_payment_referral_visit_count <= $this->settings['referring_visitors_limit']){
                    if (!headers_sent() && did_action('wp_loaded')) {
                        $transiant_duration = DAY_IN_SECONDS;
                        if('week' === $limit){
                            $transiant_duration = WEEK_IN_SECONDS;
                        } else if('month' === $limit){
                            $transiant_duration = MONTH_IN_SECONDS;
                        }
                        set_transient('dgc_payment_referral_visit_' . $referral_user->ID, $dgc_payment_referral_visit_count + 1, $transiant_duration);
                        $transaction_id = dgc_payment()->payment->credit($referral_user->ID, $referral_visit_amount, $this->settings['referring_visitors_description']);
                        update_user_meta($referral_user->ID, '_dgc_payment_referring_visitor', $referral_visitor_count + 1);
                        update_user_meta($referral_user->ID, '_dgc_payment_referring_earning', $dgc_payment_referring_earning + $referral_visit_amount);
                        do_action('dgc_payment_after_referral_visit', $transaction_id, $this);
                    }
                }
            } else{
                $transaction_id = dgc_payment()->payment->credit($referral_user->ID, $referral_visit_amount, $this->settings['referring_visitors_description']);
                update_user_meta($referral_user->ID, '_dgc_payment_referring_visitor', $referral_visitor_count + 1);
                update_user_meta($referral_user->ID, '_dgc_payment_referring_earning', $dgc_payment_referring_earning + $referral_visit_amount);
                do_action('dgc_payment_after_referral_visit', $transaction_id, $this);
            }
            wc_setcookie('dgc_payment_referral_visit_credited_' . $referral_user->ID, true, time() + DAY_IN_SECONDS);
        }
    }
    
    public function dgc_payment_referring_signup($user_id){
        $referral_signup_amount = $this->settings['referring_signups_amount'];
        if ($referral_signup_amount && $this->get_referral_user()) {
            $referral_user = $this->get_referral_user();
            $limit = $this->settings['referring_signups_limit_duration'];
            $referral_signup_count = get_user_meta($referral_user->ID, '_dgc_payment_referring_signup', true) ? get_user_meta($referral_user->ID, '_dgc_payment_referring_signup', true) : 0;
            $dgc_payment_referring_earning = get_user_meta($referral_user->ID, '_dgc_payment_referring_earning', true) ? get_user_meta($referral_user->ID, '_dgc_payment_referring_earning', true) : 0;
            if ($limit) {
                $dgc_payment_referral_signup_count = get_transient('dgc_payment_referral_signup_' . $referral_user->ID) ? get_transient('dgc_payment_referral_signup_' . $referral_user->ID) : 0;
                if($dgc_payment_referral_signup_count <= $this->settings['referring_signups_limit']){
                    if (!headers_sent() && did_action('wp_loaded')) {
                        $transiant_duration = DAY_IN_SECONDS;
                        if('week' === $limit){
                            $transiant_duration = WEEK_IN_SECONDS;
                        } else if('month' === $limit){
                            $transiant_duration = MONTH_IN_SECONDS;
                        }
                        set_transient('dgc_payment_referral_signup_' . $referral_user->ID, $dgc_payment_referral_signup_count + 1, $transiant_duration);
                        $transaction_id = dgc_payment()->payment->credit($referral_user->ID, $referral_signup_amount, $this->settings['referring_signups_description']);
                        update_user_meta($referral_user->ID, '_dgc_payment_referring_signup', $referral_signup_count + 1);
                        update_user_meta($referral_user->ID, '_dgc_payment_referring_earning', $dgc_payment_referring_earning + $referral_signup_amount);
                        do_action('dgc_payment_after_referral_signup', $transaction_id, $user_id, $this);
                    }
                }
            } else{
                $transaction_id = dgc_payment()->payment->credit($referral_user->ID, $referral_signup_amount, $this->settings['referring_signups_description']);
                update_user_meta($referral_user->ID, '_dgc_payment_referring_signup', $referral_signup_count + 1);
                update_user_meta($referral_user->ID, '_dgc_payment_referring_earning', $dgc_payment_referring_earning + $referral_signup_amount);
                do_action('dgc_payment_after_referral_signup', $transaction_id, $user_id, $this);
            }
        }
    }
    
}
