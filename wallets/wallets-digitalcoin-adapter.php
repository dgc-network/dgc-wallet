<?php
// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

if ( class_exists( 'Dashed_Slug_Wallets_Coin_Adapter_RPC' ) && ! class_exists( 'Dashed_Slug_Wallets_Litecoin_RPC_Adapter' ) ) {

	final class Dashed_Slug_Wallets_Litecoin_RPC_Adapter extends Dashed_Slug_Wallets_Coin_Adapter_RPC {

		// helpers

		// settings api

		// section callbacks

		/** @internal */
		public function section_fees_cb() {
			if ( ! current_user_can( 'manage_wallets' ) )  {
				wp_die( __( 'You do not have sufficient permissions to access this page.', 'wallets-litecoin' ) );
			}

			?><p><?php esc_html_e( 'You can set two types of fees:', 'wallets-litecoin'); ?></p>
				<ul>
					<li>
						<strong><?php esc_html_e( 'Transaction fees', 'wallets-litecoin' )?></strong> &mdash;
						<?php esc_html_e( 'These are the fees a user pays when they send funds to other users.', 'wallets-litecoin' )?>
					</li><li>
						<p><strong><?php esc_html_e( 'Withdrawal fees', 'wallets-litecoin' )?></strong> &mdash;
						<?php esc_html_e( 'This the amount that is subtracted from a user\'s account in addition to the amount that they send to another address on the blockchain.', 'wallets-litecoin' )?></p>
						<p><?php echo __( 'Fees are calculated as: <i>total_fees = fixed_fees + amount * proportional_fees</i>.', 'wallets-litecoin' ); ?></p>
						<p class="card"><?php esc_html_e( 'This withdrawal fee is NOT the network fee, and you are advised to set the withdrawal fee to an amount that will cover the network fee of a typical transaction, possibly with some slack that will generate profit. To control network fees use the paytxfee setting in litecoin.conf', 'wallets-litecoin' ) ?>
						<a href="https://litecoin.info/Litecoin.conf" target="_blank"><?php esc_html_e( 'Refer to the documentation for details.', 'wallets-litecoin' )?></a></p>
					</li>
				</ul><?php
		}

		// input field callbacks

		// API

		public function get_adapter_name() {
			return 'Digitalcoin core node';
		}

		public function get_name() {
			return 'Digitalcoin';
		}

		public function get_sprintf() {
			return mb_convert_encoding('&#x0141;', 'UTF-8', 'HTML-ENTITIES') . '%01.8f';
		}

		public function get_symbol() {
			return 'DGC';
		}

		public function get_icon_url() {
			return plugins_url( '../assets/digitalcoin.png', __FILE__ );
		}
	}
}
