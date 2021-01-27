<?php
/**
 * The Template for displaying payment recharge form
 *
 * This template can be overridden by copying it to yourtheme/dgc-payment/dgc-payment-endpoint.php.
 *
 * HOWEVER, on occasion we will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @author 	dgc.network
 * @version     1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

global $wp;
do_action( 'dgc_payment_before_my_payment_content' );
$is_rendred_from_myaccount = wc_post_content_has_shortcode( 'dgc-payment' ) ? false : is_account_page();
$menu_items = apply_filters('dgc_payment_nav_menu_items', array(
    'top_up' => array(
        'title' => apply_filters( 'dgc_payment_account_topup_menu_title', __( 'exchange dgc', 'text-domain' ) ),
        'url' => $is_rendred_from_myaccount ? esc_url(wc_get_endpoint_url(get_option('woocommerce_dgc_payment_endpoint', 'text-domain'), 'add', wc_get_page_permalink('myaccount'))) : add_query_arg('payment_action', 'add', get_permalink()),
        'icon' => 'dashicons dashicons-plus-alt'
    ),
    'transfer' => array(
        'title' => apply_filters('dgc_payment_account_transfer_amount_menu_title', __('dgc transfer', 'text-domain')),
        'url' => $is_rendred_from_myaccount ? esc_url(wc_get_endpoint_url(get_option('woocommerce_dgc_payment_endpoint', 'text-domain'), 'transfer', wc_get_page_permalink('myaccount'))) : add_query_arg('payment_action', 'transfer', get_permalink()),
        'icon' => 'dashicons dashicons-randomize'
    ),
    'transaction_details' => array(
        'title' => apply_filters('dgc_payment_account_transaction_menu_title', __('Transactions', 'text-domain')),
        'url' => $is_rendred_from_myaccount ? esc_url(wc_get_account_endpoint_url(get_option('woocommerce_dgc_payment_transactions_endpoint', 'dgc-payment-transactions'))) : add_query_arg('payment_action', 'view_transactions', get_permalink()),
        'icon' => 'dashicons dashicons-list-view'
    )
), $is_rendred_from_myaccount);
?>

<div class="dgc-payment-my-payment-container">
    <div class="dgc-payment-sidebar">
        <h3 class="dgc-payment-sidebar-heading"><a href="<?php echo $is_rendred_from_myaccount ? esc_url( wc_get_account_endpoint_url( get_option( 'woocommerce_dgc_payment_endpoint', 'text-domain' ) ) ) : get_permalink(); ?>"><?php echo apply_filters( 'dgc_payment_account_menu_title', __( 'dgcPay', 'text-domain' ) ); ?></a></h3>
        <ul>
            <?php foreach ($menu_items as $item => $menu_item) : ?>
                <?php if (apply_filters('dgc_payment_is_enable_' . $item, true)) : ?>
                    <li class="card"><a href="<?php echo $menu_item['url']; ?>" ><span class="<?php echo $menu_item['icon'] ?>"></span><p><?php echo $menu_item['title']; ?></p></a></li>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php do_action('dgc_payment_menu_items'); ?>
        </ul>
    </div>
    <div class="dgc-payment-content">
        <div class="dgc-payment-content-heading">
            <h3 class="dgc-payment-content-h3"><?php _e( 'Balance', 'text-domain' ); ?></h3>
            <p class="dgc-payment-price"><?php echo dgc_payment()->payment->get_payment_balance( get_current_user_id() ); ?></p>
        </div>
        <div style="clear: both"></div>
        <hr/>
        <?php if ( ( isset( $wp->query_vars['dgc-payment'] ) && ! empty( $wp->query_vars['dgc-payment'] ) ) || isset( $_GET['payment_action'] ) ) { ?>
            <?php if ( apply_filters( 'dgc_payment_is_enable_top_up', true ) && ( ( isset( $wp->query_vars['dgc-payment'] ) && 'add' === $wp->query_vars['dgc-payment'] ) || ( isset( $_GET['payment_action'] ) && 'add' === $_GET['payment_action'] ) ) ) { ?>
                <form method="post" action="">
                    <div class="dgc-payment-add-amount">
                        <label for="dgc_payment_balance_to_add"><?php _e( 'Enter amount', 'text-domain' ); ?></label>
                        <?php
                        $min_amount = dgc_payment()->settings_api->get_option( 'min_topup_amount', '_payment_settings_general', 0 );
                        $max_amount = dgc_payment()->settings_api->get_option( 'max_topup_amount', '_payment_settings_general', '' );
                        ?>
                        <input type="number" step="0.01" min="<?php echo $min_amount; ?>" max="<?php echo $max_amount; ?>" name="dgc_payment_balance_to_add" id="dgc_payment_balance_to_add" class="dgc-payment-balance-to-add" required="" />
                        <?php wp_nonce_field( 'dgc_payment_topup', 'dgc_payment_topup' ); ?>
                        <input type="submit" name="woo_add_to_payment" class="woo-add-to-payment" value="<?php _e( 'Add', 'text-domain' ); ?>" />
                    </div>
                </form>
            <?php } else if ( apply_filters( 'dgc_payment_is_enable_transfer', 'on' === dgc_payment()->settings_api->get_option( 'is_enable_payment_transfer', '_payment_settings_general', 'on' ) ) && ( ( isset( $wp->query_vars['dgc-payment'] ) && 'transfer' === $wp->query_vars['dgc-payment'] ) || ( isset( $_GET['payment_action'] ) && 'transfer' === $_GET['payment_action'] ) ) ) { ?> 
                <form method="post" action="">
                    <p class="dgc-payment-field-container form-row form-row-wide">
                        <label for="dgc_payment_transfer_user_id"><?php _e( 'Select whom to transfer', 'text-domain' ); ?> <?php
                            if ( apply_filters( 'dgc_payment_user_search_exact_match', true ) ) {
                                _e( '(Email)', 'text-domain' );
                            }
                            ?></label>
                        <select name="dgc_payment_transfer_user_id" class="dgc-payment-select2" required=""></select>
                    </p>
                    <p class="dgc-payment-field-container form-row form-row-wide">
                        <label for="dgc_payment_transfer_amount"><?php _e( 'Amount', 'text-domain' ); ?></label>
                        <input type="number" step="0.01" min="<?php echo dgc_payment()->settings_api->get_option('min_transfer_amount', '_payment_settings_general', 0); ?>" name="dgc_payment_transfer_amount" required=""/>
                    </p>
                    <p class="dgc-payment-field-container form-row form-row-wide">
                        <label for="dgc_payment_transfer_note"><?php _e( 'What\'s this for', 'text-domain' ); ?></label>
                        <textarea name="dgc_payment_transfer_note"></textarea>
                    </p>
                    <p class="dgc-payment-field-container form-row">
                        <?php wp_nonce_field( 'dgc_payment_transfer', 'dgc_payment_transfer' ); ?>
                        <input type="submit" class="button" name="dgc_payment_transfer_fund" value="<?php _e( 'Proceed to transfer', 'text-domain' ); ?>" />
                    </p>
                </form>
            <?php } ?> 
            <?php do_action( 'dgc_payment_menu_content' ); ?>
        <?php } else if ( apply_filters( 'dgc_payment_is_enable_transaction_details', true ) ) { ?>
            <?php $transactions = get_payment_transactions( array( 'limit' => apply_filters( 'dgc_payment_transactions_count', 10 ) ) ); ?>
            <?php if ( ! empty( $transactions ) ) { ?>
                <ul class="dgc-payment-transactions-items">
                    <?php foreach ( $transactions as $transaction ) : ?> 
                        <li>
                            <div>
                                <p><?php echo $transaction->details; ?></p>
                                <small><?php echo wc_string_to_datetime( $transaction->date )->date_i18n( wc_date_format() ); ?></small>
                            </div>
                            <div class="dgc-payment-transaction-type-<?php echo $transaction->type; ?>"><?php
                                echo $transaction->type == 'credit' ? '+' : '-';
                                echo wc_price( apply_filters( 'dgc_payment_amount', $transaction->amount, $transaction->currency, $transaction->user_id ), dgc_payment_wc_price_args($transaction->user_id) );
                                ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php
            } else {
                _e( 'No transactions found', 'text-domain' );
            }
        }
        ?>
    </div>
</div>
<?php do_action( 'dgc_payment_after_my_payment_content' );
