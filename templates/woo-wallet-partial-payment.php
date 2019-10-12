<?php
/**
 * The Template for displaying partial payment html at checkout page
 *
 * This template can be overridden by copying it to yourtheme/dgc-wallet/dgc-wallet-partial-payment.php.
 *
 * HOWEVER, on occasion we will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @author 	dgc.network
 * @version     1.1.4
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
$current_wallet_amount = apply_filters( 'dgc_wallet_partial_payment_amount', dgc_wallet()->wallet->get_wallet_balance( get_current_user_id(), 'edit' ) );
if ( $current_wallet_amount <= 0 ) {
    return;
}
$rest_amount = get_dgc_wallet_cart_total() - $current_wallet_amount;
if ( 'on' === dgc_wallet()->settings_api->get_option( 'is_auto_deduct_for_partial_payment', '_wallet_settings_general' ) ) {
    ?>
    <tr class="wallet-pay-partial">
        <th colspan="2"><label><?php echo sprintf( __( '%s will be debited from your wallet and %s will be paid through other payment method', 'dgc-wallet' ), wc_price( $current_wallet_amount, dgc_wallet_wc_price_args() ), wc_price( $rest_amount, dgc_wallet_wc_price_args() ) ); ?></label></th>
    </tr>

<?php } else { ?>
    <tr class="wallet-pay-partial">
        <th><?php _e( 'Pay by wallet', 'dgc-wallet' ); ?> <span id="partial_wallet_payment_tooltip" style="vertical-align: middle;" title="<?php echo esc_html(sprintf( __( 'If checked %s will be debited from your wallet and %s will be paid through other payment method', 'dgc-wallet' ), wc_price( $current_wallet_amount, dgc_wallet_wc_price_args() ), wc_price( $rest_amount, dgc_wallet_wc_price_args() ) ) ); ?>" class="dashicons dashicons-info"></span></th>
        <td data-title="<?php esc_attr_e( 'Pay by wallet', 'dgc-wallet' ); ?>"><input type="checkbox" <?php checked( is_enable_wallet_partial_payment(), true, true ) ?> style="vertical-align: middle;" name="partial_pay_through_wallet" class="partial_pay_through_wallet" /></td>
    </tr>

    <script type="text/javascript">
        jQuery(function ($) {
            $('#partial_wallet_payment_tooltip').tooltip({
                content: function () {
                    return $(this).prop('title');
                }
            });
            $(document).on('change', '.partial_pay_through_wallet', function (event) {
                event.stopImmediatePropagation();
                var data = {
                    action: 'dgc_wallet_partial_payment_update_session',
                    checked: $(this).is(':checked')
                };
                $.post('<?php echo admin_url( 'admin-ajax.php' ); ?>', data, function () {
                    $(document.body).trigger('update_checkout');
                });
            });
        });
    </script>
<?php }
