<?php

/**
 * The Template for mini payment
 *
 * This template can be overridden by copying it to yourtheme/dgc-payment/dgc-payment-mini.php.
 *
 * HOWEVER, on occasion we will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @author 	dgc.network
 * @version     1.0.8
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

$title      = __( 'Current payment balance', 'text-domain' );
$menu_item  = '<li class="right"><a class="dgc-payment-menu-contents" href="' . esc_url( wc_get_account_endpoint_url( get_option( 'woocommerce_dgc_payment_endpoint', 'text-domain' ) ) ) . '" title="' . $title . '">';
$menu_item .= '<span class="dgc-payment-icon-payment"></span>&nbsp;';
$menu_item .= dgc_payment()->payment->get_payment_balance( get_current_user_id() );
$menu_item .= '</a></li>';

echo $menu_item;
