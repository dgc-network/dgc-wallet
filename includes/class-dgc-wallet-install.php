<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * dgc_Wallet_Install Class
 */
class dgc_Wallet_Install {

    private static $db_updates = array(
        //'1.0.0' => array(
        //    'dgc_wallet_update_100_db_column'
        //),
        '1.0.8' => array(
            'dgc_wallet_update_108_db_column'
        ),
        '1.1.0' => array(
            'dgc_wallet_update_110_db_column'
        ),
        '1.1.7' => array(
            'dgc_wallet_update_117_db_column'
        ),
        '1.4.0' => array(
            'dgc_wallet_update_140_db_column'
        )
    );
    
    public function __construct() {
        self::update();
    }

    /**
     * Plugin install
     * @return void
     */
    public static function install() {
        if ( ! is_blog_installed() ) {
            return;
        }
        self::create_tables();
        self::cteate_product_if_not_exist();
    }

    /**
     * plugins table creation
     * @global object $wpdb
     */
    private static function create_tables() {
        global $wpdb;
        $wpdb->hide_errors();
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        dbDelta( self::get_schema() );
        dbDelta( self::open_trade_engine_schema() );

    }

    /**
     * Plugin table schema
     * @global object $wpdb
     * @return string
     */
    private static function get_schema() {
        global $wpdb;
        $collate = '';

        if ( $wpdb->has_cap( 'collation' ) ) {
            $collate = $wpdb->get_charset_collate();
        }
        $tables = "CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}dgc_wallet_transactions (
            transaction_id BIGINT UNSIGNED NOT NULL auto_increment,
            blog_id BIGINT UNSIGNED NOT NULL DEFAULT 1,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            type varchar(200 ) NOT NULL,
            amount DECIMAL( 10,2 ) NOT NULL,
            balance DECIMAL( 10,2 ) NOT NULL,
            currency varchar(20 ) NOT NULL,
            details longtext NULL,
            deleted tinyint(1 ) NOT NULL DEFAULT 0,
            date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (transaction_id ),
            KEY user_id (user_id )
        ) $collate;
        CREATE TABLE {$wpdb->base_prefix}dgc_wallet_transaction_meta (
            meta_id BIGINT UNSIGNED NOT NULL auto_increment,
            transaction_id BIGINT UNSIGNED NOT NULL,
            meta_key varchar(255) default NULL,
            meta_value longtext NULL,
            PRIMARY KEY  (meta_id ),
            KEY transaction_id (transaction_id ),
            KEY meta_key (meta_key(32 ) )
        ) $collate;";
        return $tables;
    }

    /**
     * Plugin table schema
     * @global object $wpdb
     * @return string
     */
    private static function open_trade_engine_schema() {
        global $wpdb;
        $collate = '';

        if ( $wpdb->has_cap( 'collation' ) ) {
            $collate = $wpdb->get_charset_collate();
        }
                                                                      
        $tables = "CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}Traders (
            `ID` int(11) NOT NULL AUTO_INCREMENT,
            `TS` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `UserName` varchar(30) NOT NULL,
            `FirstName` varchar(50) NOT NULL,
            `LastName` varchar(50) NOT NULL,
            `PasswordHash` char(60) NOT NULL,
            `BirthDate` date NOT NULL DEFAULT '0000-00-00',
            `PhoneNumber` varchar(22) NOT NULL,
            `SecurityQuestion` varchar(300) NOT NULL,
            `SecurityAnswer` varchar(255) NOT NULL,
            `PIN` char(4) NOT NULL,
            `Email` varchar(255) NOT NULL,
            `AddressLineOne` varchar(255) NOT NULL,
            `AddressLineTwo` varchar(255) NOT NULL,
            `PostCode` varchar(10) NOT NULL,
            `City` varchar(50) NOT NULL,
            `RegisterIP` varchar(45) NOT NULL,
            `Referrer` int(11) NOT NULL,
            `Activated` tinyint(1) unsigned NOT NULL DEFAULT '0',
            `AccountNumber` varchar(255) NOT NULL,
            `Points` decimal(16,8) unsigned NOT NULL DEFAULT '0.00000000',
            `PointsEarned` decimal(16,8) unsigned NOT NULL DEFAULT '0.00000000',
            `PinCount` tinyint(2) unsigned NOT NULL DEFAULT '0',
            `PassCount` tinyint(2) unsigned NOT NULL DEFAULT '0',
            `RecoverCount` tinyint(2) unsigned NOT NULL DEFAULT '0',
            `TransactionCount` int(10) unsigned NOT NULL DEFAULT '0',
            PRIMARY KEY (`ID`),
            UNIQUE KEY `UserName` (`UserName`),
            KEY `Traders_Traders_ID_fk` (`Referrer`),
            CONSTRAINT `Traders_Traders_ID_fk` FOREIGN KEY (`Referrer`) REFERENCES `{$wpdb->base_prefix}Traders` (`ID`)
        ) $collate;
        CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}TraderCurrencies (
            `Currency` int(11) NOT NULL,
            `Balance` decimal(16,8) NOT NULL DEFAULT '0.00000000',
            `Trader` int(11) NOT NULL,
            `HeldBalance` decimal(16,8) NOT NULL DEFAULT '0.00000000',
            `PendingBalance` decimal(16,8) NOT NULL DEFAULT '0.00000000',
            `Completed` decimal(16,8) NOT NULL DEFAULT '0.00000000',
            UNIQUE KEY `TraderCurrencies_Currency_Trader_pk` (`Currency`,`Trader`),
            KEY `TraderCurrencies_Traders__fk` (`Trader`),
            CONSTRAINT `TraderCurrencies_Currencies.ID__fk` FOREIGN KEY (`Currency`) REFERENCES `Currencies` (`ID`),
            CONSTRAINT `TraderCurrencies_Traders__fk` FOREIGN KEY (`Trader`) REFERENCES `{$wpdb->base_prefix}Traders` (`ID`)
        ) $collate;
        CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}Symbols (
            `ID` int(11) NOT NULL AUTO_INCREMENT,
            `Symbol` char(20) NOT NULL,
            `LeftCurrency` int(11) NOT NULL,
            `RightCurrency` int(11) NOT NULL,
            `MakerFee` decimal(16,8) NOT NULL DEFAULT '0.00000000',
            `TakerFee` decimal(16,8) NOT NULL DEFAULT '0.00000000',
            PRIMARY KEY (`ID`),
            UNIQUE KEY `Symbols_ID_uindex` (`ID`),
            UNIQUE KEY `Symbols_code_uindex` (`Symbol`),
            KEY `Symbols_Currencies_ID_fk` (`LeftCurrency`),
            KEY `Symbols_Currencies2_ID_fk` (`RightCurrency`),
            CONSTRAINT `Symbols_Currencies2_ID_fk` FOREIGN KEY (`rightCurrency`) REFERENCES `Currencies` (`ID`),
            CONSTRAINT `Symbols_Currencies_ID_fk` FOREIGN KEY (`leftCurrency`) REFERENCES `Currencies` (`ID`)
        ) $collate;
        CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}OrderErrors (
            `ID` int(11) NOT NULL AUTO_INCREMENT,
            `TS` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `Error` varchar(255) NOT NULL,
            PRIMARY KEY (`ID`)
        ) $collate;
        CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}FeeTotals (
            Currency int(11) NOT NULL,
            Total decimal(32,8) NOT NULL DEFAULT '0.00000000',
            PRIMARY KEY (Currency),
            UNIQUE KEY FeeTotals_Currency_uindex (Currency),
            CONSTRAINT FeeTotals_Currencies_ID_fk FOREIGN KEY (Currency) REFERENCES Currencies (ID)
        ) $collate;
        CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}Currencies (
            ID BIGINT UNSIGNED NOT NULL auto_increment,
            Symbol char(10) NOT NULL,
            Name varchar(255) NOT NULL,
            PRIMARY KEY (ID),
            UNIQUE KEY Currencies_ID_uindex (ID),
            UNIQUE KEY Currencies_Symbol_uindex (Symbol),
            UNIQUE KEY Currencies_Name_uindex (Name)
        ) $collate;";
        return $tables;
    }
    
    /**
     * Create rechargeable product if not exist
     */
    public static function cteate_product_if_not_exist(){
        if ( !wc_get_product( get_option( '_dgc_wallet_recharge_product' ) ) ) {
            self::create_product();
        }
    }

    /**
     * create rechargeable product
     */
    private static function create_product() {
        $product_args = array(
            'post_title' => wc_clean( 'Wallet Topup product' ),
            'post_status' => 'private',
            'post_type' => 'product',
            'post_excerpt' => '',
            'post_content' => stripslashes(html_entity_decode( 'Auto generated product for payment recharge please do not delete or update.', ENT_QUOTES, 'UTF-8' ) ),
            'post_author' => 1
        );
        $product_id = wp_insert_post( $product_args );
        if ( ! is_wp_error( $product_id ) ) {
            $product = wc_get_product( $product_id );
            wp_set_object_terms( $product_id, 'simple', 'product_type' );
            update_post_meta( $product_id, '_stock_status', 'instock' );
            update_post_meta( $product_id, 'total_sales', '0' );
            update_post_meta( $product_id, '_downloadable', 'no' );
            update_post_meta( $product_id, '_virtual', 'yes' );
            update_post_meta( $product_id, '_regular_price', '' );
            update_post_meta( $product_id, '_sale_price', '' );
            update_post_meta( $product_id, '_purchase_note', '' );
            update_post_meta( $product_id, '_featured', 'no' );
            update_post_meta( $product_id, '_weight', '' );
            update_post_meta( $product_id, '_length', '' );
            update_post_meta( $product_id, '_width', '' );
            update_post_meta( $product_id, '_height', '' );
            update_post_meta( $product_id, '_sku', '' );
            update_post_meta( $product_id, '_product_attributes', array() );
            update_post_meta( $product_id, '_sale_price_dates_from', '' );
            update_post_meta( $product_id, '_sale_price_dates_to', '' );
            update_post_meta( $product_id, '_price', '' );
            update_post_meta( $product_id, '_sold_individually', 'yes' );
            update_post_meta( $product_id, '_manage_stock', 'no' );
            update_post_meta( $product_id, '_backorders', 'no' );
            update_post_meta( $product_id, '_stock', '' );
            if (version_compare(WC_VERSION, '3.0', '>=' ) ) {
                $product->set_reviews_allowed( false );
                $product->set_catalog_visibility( 'hidden' );
                $product->save();
            }

            update_option( '_dgc_wallet_recharge_product', $product_id );
        }
    }

    /**
     * Get list of DB update callbacks.
     *
     * @since  1.0.8
     * @return array
     */
    public static function get_db_update_callbacks() {
        return self::$db_updates;
    }
    
    /**
     * Update plugin
     */
    private static function update() {
        $current_db_version = get_option( 'dgc_wallet_db_version' );
        if (version_compare(DGC_WALLET_PLUGIN_VERSION, $current_db_version, '=' ) ){
            return;
        }
        foreach ( self::get_db_update_callbacks() as $version => $update_callbacks ) {
            if (version_compare( $current_db_version, $version, '<' ) ) {
                foreach ( $update_callbacks as $update_callback ) {
                    call_user_func( $update_callback );
                }
            }
        }
        self::update_db_version();
    }

    /**
     * Update DB version to current.
     *
     * @param string|null $version New WooCommerce DB version or null.
     */
    public static function update_db_version( $version = null ) {
        delete_option( 'dgc_wallet_db_version' );
        add_option( 'dgc_wallet_db_version', is_null( $version) ? DGC_WALLET_PLUGIN_VERSION : $version );
    }

}

new dgc_Wallet_Install();