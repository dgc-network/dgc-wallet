<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Payment actions.
 *
 * @author subrata
 */
class dgc_Payment_Actions {

    /** @var array Array of action classes. */
    public $actions;

    /**
     * @var dgc_Payment_Actions The single instance of the class
     * @since 1.0.0
     */
    protected static $_instance = null;

    /**
     * Main dgc_Payment_Actions Instance.
     *
     * Ensures only one instance of dgc_Payment_Actions is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @return dgc_Payment_Actions Main instance
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Class Constructor
     */
    public function __construct() {
        $this->load_actions();
        $this->init();
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
    }

    public function init() {
        $load_actions = apply_filters('dgc_payment_actions', array(
            'Action_New_Registration',
            'Action_Product_Review',
            'Action_Daily_Visits',
            'Action_Referrals'
                ));
        foreach ($load_actions as $action) {
            $load_action = is_string($action) ? new $action() : $action;
            $this->actions[$load_action->id] = $load_action;
        }
    }

    public function load_actions() {
        require_once(DGC_PAYMENT_ABSPATH . 'includes/actions/class-dgc-payment-action-new-registration.php' );
        require_once(DGC_PAYMENT_ABSPATH . 'includes/actions/class-dgc-payment-action-product-review.php' );
        require_once(DGC_PAYMENT_ABSPATH . 'includes/actions/class-dgc-payment-action-daily-visits.php' );
        require_once(DGC_PAYMENT_ABSPATH . 'includes/actions/class-dgc-payment-action-referrals.php' );
        do_action('dgc_payment_load_actions');
    }

    public function get_available_actions() {
        $actions = array();
        foreach ($this->actions as $action) {
            if ($action->is_enabled()) {
                $actions[] = $action;
            }
        }
        return $actions;
    }

    public function admin_scripts() {
        $screen = get_current_screen();
        $screen_id = $screen ? $screen->id : '';
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        // Register scripts
        //wp_register_script('dgc_payment_admin_actions', dgc_payment()->plugin_url() . '/assets/js/admin/admin-actions' . $suffix . '.js', array('jquery'), DGC_PAYMENT_PLUGIN_VERSION);
        wp_register_script('dgc_payment_admin_actions', plugin_dir_url( __FILE__ ) . 'assets/js/admin/admin-actions' . $suffix . '.js', array('jquery'), DGC_PAYMENT_PLUGIN_VERSION);        
        if (in_array( $screen_id, array( 'dgc_payment_page_dgc-payment-actions', 'dgc_payment_page_dgc-payment-actions' ) ) ) {
            wp_enqueue_script('dgc_payment_admin_actions');
        }
    }

}

dgc_Payment_Actions::instance();
