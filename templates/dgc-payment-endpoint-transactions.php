<?php
/**
 * The Template for displaying transaction history
 *
 * This template can be overridden by copying it to yourtheme/dgc-payment/dgc-payment-endpoint-transactions.php.
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

$transactions = get_payment_transactions();
do_action( 'dgc_payment_before_transaction_details_content' );
?>
<p><?php _e( 'Current balance :', 'text-domain' ); ?> <?php echo dgc_payment()->payment->get_payment_balance( get_current_user_id() ); ?> <a href="<?php echo is_account_page() ? esc_url( wc_get_account_endpoint_url( get_option( 'woocommerce_dgc_payment_endpoint', 'text-domain' ) ) ) : get_permalink(); ?>"><span class="dashicons dashicons-editor-break"></span></a></p>
<table id="dgc-payment-transaction-details" class="table">
    <thead>
        <tr>
            <?php do_action('dgc_payment_before_transaction_table_th'); ?>
            <th><?php _e( 'ID', 'text-domain' ); ?></th>
            <th><?php _e( 'Credit', 'text-domain' ); ?></th>
            <th><?php _e( 'Debit', 'text-domain' ); ?></th>
            <th><?php _e( 'Details', 'text-domain' ); ?></th>
            <th><?php _e( 'Date', 'text-domain' ); ?></th>
            <?php do_action('dgc_payment_after_transaction_table_th'); ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( $transactions as $key => $transaction ) : ?>
        <tr>
            <?php do_action('dgc_payment_before_transaction_table_items', $transaction); ?>
            <td><?php echo $transaction->transaction_id; ?></td>
            <td><?php echo $transaction->type == 'credit' ? wc_price( apply_filters( 'dgc_payment_amount', $transaction->amount, $transaction->currency, $transaction->user_id ), dgc_payment_wc_price_args($transaction->user_id) ) : ' - '; ?></td>
            <td><?php echo $transaction->type == 'debit' ? wc_price( apply_filters( 'dgc_payment_amount', $transaction->amount, $transaction->currency, $transaction->user_id ), dgc_payment_wc_price_args($transaction->user_id) ) : ' - '; ?></td>
            <td><?php echo $transaction->details; ?></td>
            <td><?php echo wc_string_to_datetime( $transaction->date )->date_i18n( wc_date_format() ); ?></td>
            <?php do_action('dgc_payment_after_transaction_table_items', $transaction); ?>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php do_action( 'dgc_payment_after_transaction_details_content' );
