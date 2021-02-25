<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

final class dgc_Payment {
    /**
     * The single instance of the class.
     *
     * @var dgc_Payment
     * @since 1.0.0
     */
    protected static $_instance = null;

    /**
     * Setting API instance
     * @var dgc_Payment_Settings_API 
     */
    public $settings_api = null;

    /**
     * payment instance.
     * @var dgc_Payment_Wallet 
     */
    public $payment = null;

    /**
     * Cashback instance.
     * @var dgc_Payment_Cashback 
     */
    public $cashback = null;

    /**
     * Payment REST API
     * @var dgc_Payment_API 
     */
    public $rest_api = null;

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
        if ( dgc_Payment_Dependencies::is_woocommerce_active() ) {
            $this->define_constants();
            $this->includes();
            $this->init_hooks();
            do_action( 'dgc_payment_loaded' );
        } else {
            add_action( 'admin_notices', array( $this, 'admin_notices' ), 15);
        }
    }

    /**
     * Constants define
     */
    private function define_constants() {
        $this->define( 'DGC_PAYMENT_ABSPATH', dirname(DGC_PAYMENT_PLUGIN_FILE) . '/' );
        $this->define( 'DGC_PAYMENT_PLUGIN_FILE', plugin_basename(DGC_PAYMENT_PLUGIN_FILE) );
        $this->define( 'DGC_PAYMENT_PLUGIN_VERSION', '1.0.0' );
    }

    /**
     * 
     * @param string $name
     * @param mixed $value
     */
    private function define( $name, $value ) {
        if ( ! defined( $name) ) {
            define( $name, $value );
        }
    }

    /**
     * Check request
     * @param string $type
     * @return bool
     */
    private function is_request( $type ) {
        switch ( $type ) {
            case 'admin' :
                return is_admin();
            case 'ajax' :
                return defined( 'DOING_AJAX' );
            case 'cron' :
                return defined( 'DOING_CRON' );
            case 'frontend' :
                return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
        }
    }

    /**
     * load plugin files
     */
    public function includes() {
        include_once( DGC_PAYMENT_ABSPATH . 'includes/helper/dgc-payment-util.php' );
        include_once( DGC_PAYMENT_ABSPATH . 'includes/helper/dgc-payment-update-functions.php' );
        include_once( DGC_PAYMENT_ABSPATH . 'includes/class-dgc-payment-install.php' );
        
        include_once( DGC_PAYMENT_ABSPATH . 'includes/class-dgc-payment-settings-api.php' );
        $this->settings_api = new dgc_Payment_Settings_API();
        
        include_once( DGC_PAYMENT_ABSPATH . 'includes/class-dgc-payment-wallet.php' );
        $this->payment = new dgc_Payment_Wallet();
        
        include_once( DGC_PAYMENT_ABSPATH . 'includes/class-dgc-payment-cashback.php' );
        $this->cashback = new dgc_Payment_Cashback();
        
        include_once( DGC_PAYMENT_ABSPATH . 'includes/class-dgc-payment-widgets.php' );
        
        if ( $this->is_request( 'admin' ) ) {
            include_once( DGC_PAYMENT_ABSPATH . 'includes/class-dgc-payment-settings.php' );
            include_once( DGC_PAYMENT_ABSPATH . 'includes/class-dgc-payment-extensions.php' );
            include_once( DGC_PAYMENT_ABSPATH . 'includes/class-dgc-payment-admin.php' );
        }
        if ( $this->is_request( 'frontend' ) ) {
            include_once( DGC_PAYMENT_ABSPATH . 'includes/class-dgc-payment-frontend.php' );
        }
        if ( $this->is_request( 'ajax' ) ) {
            include_once( DGC_PAYMENT_ABSPATH . 'includes/class-dgc-payment-ajax.php' );
        }
    }

    /**
     * Plugin url
     * @return string path
     */
    public function plugin_url() {
        return untrailingslashit( plugins_url( '/', DGC_PAYMENT_PLUGIN_FILE ) );
    }

    /**
     * Plugin init
     */
    private function init_hooks() {
        register_activation_hook(DGC_PAYMENT_PLUGIN_FILE, array( 'dgc_Payment_Install', 'install' ) );
        add_filter( 'plugin_action_links_' . plugin_basename(DGC_PAYMENT_PLUGIN_FILE), array( $this, 'plugin_action_links' ) );
        add_action( 'init', array( $this, 'init' ), 5);
        add_action( 'widgets_init', array($this, 'dgc_payment_widget_init') );
        add_action( 'woocommerce_loaded', array( $this, 'woocommerce_loaded_callback' ) );
        add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
        do_action( 'dgc_payment_init' );
    }

    /**
     * Plugin init
     */
    public function init() {
        $this->load_plugin_textdomain();
        include_once( DGC_PAYMENT_ABSPATH . 'includes/class-dgc-payment-method.php' );
        include_once( DGC_PAYMENT_ABSPATH . 'includes/class-dgc-payment-gateway.php' );
        $this->add_marketplace_support();
        add_filter( 'woocommerce_email_classes', array( $this, 'woocommerce_email_classes' ) );
        add_filter( 'woocommerce_payment_gateways', array( $this, 'load_gateway' ) );

        /** Move the below to class-dgc-payment-method */
/*
        foreach ( apply_filters( 'payment_credit_purchase_order_status', array( 'processing', 'completed' ) ) as $status) {
            add_action( 'woocommerce_order_status_' . $status, array( $this->payment, 'payment_credit_purchase' ) );
        }

        foreach ( apply_filters( 'payment_partial_payment_order_status', array( 'on-hold', 'processing', 'completed' ) ) as $status) {
            add_action( 'woocommerce_order_status_' . $status, array( $this->payment, 'payment_partial_payment' ) );
        }

        foreach ( apply_filters( 'payment_cashback_order_status', $this->settings_api->get_option( 'process_cashback_status', '_payment_settings_credit', array( 'processing', 'completed' ) ) ) as $status) {
            add_action( 'woocommerce_order_status_' . $status, array( $this->payment, 'payment_cashback' ), 12 );
        }

        add_action( 'woocommerce_order_status_cancelled', array( $this->payment, 'process_cancelled_order' ) );
*/
        add_filter( 'woocommerce_reports_get_order_report_query', array( $this, 'woocommerce_reports_get_order_report_query' ) );

        add_action( 'woocommerce_new_order_item', array( $this, 'woocommerce_new_order_item' ), 10, 2 );

        add_rewrite_endpoint( get_option( 'woocommerce_dgc_payment_endpoint', 'text-domain' ), EP_PAGES);
        add_rewrite_endpoint( get_option( 'woocommerce_dgc_payment_transactions_endpoint', 'dgc-payment-transactions' ), EP_PAGES);
        if ( !get_option( '_payment_enpoint_added' ) ) {
            flush_rewrite_rules();
            update_option( '_payment_enpoint_added', true );
        }
        
        add_action( 'deleted_user', array($this, 'delete_user_transaction_records' ) );

        add_filter( 'woocommerce_currencies', array($this, 'add_dgc_currency' ) );
        add_filter( 'woocommerce_currency_symbol', array($this, 'add_dgc_currency_symbol' ), 10, 2);
    }
    
    /**
     * Custom currency and currency symbol
     */
    //add_filter( 'woocommerce_currencies', 'add_my_currency' );

    function add_dgc_currency( $currencies ) {
        $currencies['DGC'] = __( 'dg Cedit', 'text-domain' );
        return $currencies;
    }

    //add_filter('woocommerce_currency_symbol', 'add_my_currency_symbol', 10, 2);

    function add_dgc_currency_symbol( $currency_symbol, $currency ) {
        switch( $currency ) {
            case 'DGC': $currency_symbol = '$'; break;
        }
        return $currency_symbol;
    }

    /**
     * dgc_Payment init widget
     */
    public function dgc_payment_widget_init(){
        register_widget('dgc_Payment_Topup');
    }

    /**
     * Load WooCommerce dependent class file.
     */
    public function woocommerce_loaded_callback() {
        include_once DGC_PAYMENT_ABSPATH . 'includes/abstracts/abstract-dgc-payment-actions.php';
        require_once DGC_PAYMENT_ABSPATH . 'includes/class-dgc-payment-actions.php';
        include_once DGC_PAYMENT_ABSPATH . 'includes/class-dgc-payment-api.php';
        $this->rest_api = new dgc_Payment_API();
    }

    /**
     * WP REST API init.
     */
    public function rest_api_init() {
        include_once( DGC_PAYMENT_ABSPATH . 'includes/api/class-wp-rest-dgc-payment-controller.php' );
        $rest_controller = new WP_REST_dgc_Payment_Controller();
        $rest_controller->register_routes();
    }
    /**
     * Add settings link to plugin list.
     * @param array $links
     * @return array
     */
    public function plugin_action_links( $links ) {
        $action_links = array(
            'settings' => '<a href="' . admin_url( 'admin.php?page=dgc-payment-settings' ) . '" aria-label="' . esc_attr__( 'View Payment settings', 'text-domain' ) . '">' . esc_html__( 'Settings', 'text-domain' ) . '</a>',
        );

        return array_merge( $action_links, $links );
    }

    /**
     * Text Domain loader
     */
    public function load_plugin_textdomain() {
        $locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
        $locale = apply_filters( 'plugin_locale', $locale, 'text-domain' );

        unload_textdomain( 'text-domain' );
        load_textdomain( 'text-domain', WP_LANG_DIR . '/dgc-payment/dgc-payment-' . $locale . '.mo' );
        load_plugin_textdomain( 'text-domain', false, plugin_basename(dirname(DGC_PAYMENT_PLUGIN_FILE) ) . '/languages' );
    }

    /**
     * dgc Payment for WooCommerce payment gateway loader
     * @param array $load_gateways
     * @return array
     */
    public function load_gateway( $load_gateways ) {
        $load_gateways[] = 'dgc_Payment_Method';
        //$load_gateways[] = 'dgc_Payment_Gateway';
        return $load_gateways;
    }

    /**
     * WooCommerce email loader
     * @param array $emails
     * @return array
     */
    public function woocommerce_email_classes( $emails ) {
        $emails['dgc_Payment_Email_New_Transaction'] = include DGC_PAYMENT_ABSPATH . 'includes/emails/class-dgc-payment-email-new-transaction.php';
        return $emails;
    }

    /**
     * Exclude rechargable orders from admin report
     * @param array $query
     * @return array
     */
    public function woocommerce_reports_get_order_report_query( $query ) {
        $rechargable_orders = get_payment_rechargeable_orders();
        if ( ! empty( $rechargable_orders) && apply_filters('dgc_payment_exclude_payment_rechargeable_orders_from_report', true) ) {
            $exclude_orders = implode( ', ', $rechargable_orders);
            $query['where'] .= " AND posts.ID NOT IN ({$exclude_orders})";
        }
        return $query;
    }
    /**
     * Load marketplace supported file.
     */
    public function add_marketplace_support() {
        if (class_exists( 'WCMp' ) ) {
            include_once( DGC_PAYMENT_ABSPATH . 'includes/marketplace/wc-merketplace/class-dgc-payment-wcmp-gateway.php' );
            include_once( DGC_PAYMENT_ABSPATH . 'includes/marketplace/wc-merketplace/class-dgc-payment-wcmp.php' );
        }
        if (class_exists( 'WeDevs_Dokan' ) ) {
            include_once( DGC_PAYMENT_ABSPATH . 'includes/marketplace/dokan/class-dgc-payment-dokan.php' );
        }
        if(class_exists('WCFMmp')){
            include_once( DGC_PAYMENT_ABSPATH . 'includes/marketplace/wcfmmp/class-dgc-payment-wcfmmp.php' );
        }
    }
    /**
     * Store fee key to order item meta.
     * @param Int $item_id
     * @param WC_Order_Item_Fee $item
     */
    public function woocommerce_new_order_item($item_id, $item){
        if ( $item->get_type() == 'fee' ) {
            update_metadata( 'order_item', $item_id, '_legacy_fee_key', $item->legacy_fee_key );
        }
    }
    
    public function delete_user_transaction_records($id){
        global $wpdb;
        if( apply_filters('dgc_payment_delete_transaction_records', true) ){
            $wpdb->query($wpdb->prepare( "DELETE t.*, tm.* FROM {$wpdb->base_prefix}dgc_payment_transactions t JOIN {$wpdb->base_prefix}dgc_payment_transaction_meta tm ON t.transaction_id = tm.transaction_id WHERE t.user_id = %d", $id ));
/*            
            $wpdb->query($wpdb->prepare( "DELETE * FROM {$wpdb->base_prefix}dgc_payment_transaction_meta WHERE user_id = %d", $id ));
			// dgc-API-call:begin: /deleteRecords
			$dgc_API_args = array(
				'table'		=> $wpdb->prefix . 'dgc_payment_transactions',
				'query'		=> array(
					//'user_id'  => $id,
                    'publicKey'	=> get_user_meta($id, "publicKey", true ),
				),
			);
			return dgc_API_call('/deleteRecords', 'POST', $dgc_API_args);
            // dgc-API-call:end: /deleteRecords
*/            
        }
    }

    /**
     * Load template
     * @param string $template_name
     * @param array $args
     * @param string $template_path
     * @param string $default_path
     */
    public function get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
        if ( $args && is_array( $args ) ) {
            extract( $args );
        }
        $located = $this->locate_template( $template_name, $template_path, $default_path);
        include ( $located);
    }

    /**
     * Locate template file
     * @param string $template_name
     * @param string $template_path
     * @param string $default_path
     * @return string
     */
    public function locate_template( $template_name, $template_path = '', $default_path = '' ) {
        $default_path = apply_filters( 'dgc_payment_template_path', $default_path);
        if ( !$template_path) {
            $template_path = 'text-domain';
        }
        if ( !$default_path) {
            $default_path = DGC_PAYMENT_ABSPATH . 'templates/';
        }
        // Look within passed path within the theme - this is priority
        $template = locate_template( array(trailingslashit( $template_path) . $template_name, $template_name) );
        // Add support of third perty plugin
        $template = apply_filters( 'dgc_payment_locate_template', $template, $template_name, $template_path, $default_path);
        // Get default template
        if ( !$template) {
            $template = $default_path . $template_name;
        }
        return $template;
    }

    /**
     * Display admin notice
     */
    public function admin_notices() {
        echo '<div class="error"><p>';
        _e( 'dgc Payment for WooCommerce plugin requires <a href="https://wordpress.org/plugins/woocommerce/">WooCommerce</a> plugins to be active!', 'text-domain' );
        echo '</p></div>';
    }

}
