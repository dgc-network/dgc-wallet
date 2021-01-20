<?php

/**
 * dgc Payment for WooCommerce Uninstall
 *
 * Uninstalling dgc Payment for WooCommerce product, tables, and options.
 *
 * @author  dgc.network
 * @version 1.0.1
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb, $wp_version;

// Remove rechargable product 
wp_delete_post( get_option( '_dgc_payment_recharge_product' ), true );
delete_option( '_dgc_payment_recharge_product' );

/*
 * Only remove ALL plugins data if WALLET_REMOVE_ALL_DATA constant is set to true in user's
 * wp-config.php. This is to prevent data loss when deleting the plugin from the backend
 * and to ensure only the site owner can perform this action.
 */
if ( defined( 'WALLET_REMOVE_ALL_DATA' ) && true === WALLET_REMOVE_ALL_DATA ) {
    // Tables.
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->base_prefix}dgc_payment_transactions" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->base_prefix}dgc_payment_transaction_meta" );

    // Delete options.
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_payment\_%';" );
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_dgc_payment\_%';" );

    // Clear any cached data that has been removed
    wp_cache_flush();
}
