<?php 
// Copyright (c) The dgc.network
// SPDX-License-Identifier: Apache-2.0

class WC_dgc_Payment_Gateway extends WC_Payment_Gateway{

    private $order_status;

	public function __construct(){
		$this->id = 'dgc_payment';
		$this->method_title = __('dgc Payment','woocommerce-dgc-payment-gateway');
		$this->title = __('dgc Payment','woocommerce-dgc-payment-gateway');
		$this->has_fields = true;
		$this->init_form_fields();
		$this->init_settings();
		$this->enabled = $this->get_option('enabled');
		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->hide_text_box = $this->get_option('hide_text_box');
		$this->order_status = $this->get_option('order_status');

		add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));
	}

	public function init_form_fields(){
		$this->form_fields = array(
			'enabled' => array(
			'title' 		=> __( 'Enable/Disable', 'woocommerce-dgc-payment-gateway' ),
			'type' 			=> 'checkbox',
			'label' 		=> __( 'Enable dgc Payment', 'woocommerce-dgc-payment-gateway' ),
			'default' 		=> 'yes'
			),
			'title' => array(
				'title' 		=> __( 'Method Title', 'woocommerce-dgc-payment-gateway' ),
				'type' 			=> 'text',
				'description' 	=> __( 'This controls the title', 'woocommerce-dgc-payment-gateway' ),
				'default'		=> __( 'dgc Payment', 'woocommerce-dgc-payment-gateway' ),
				'desc_tip'		=> true,
			),
			'description' => array(
				'title' 		=> __( 'Customer Message', 'woocommerce-dgc-payment-gateway' ),
				'type' 			=> 'textarea',
				'css' 			=> 'width:500px;',
				'default' 		=> 'None of the dgc payment options are suitable for you? please drop us a note about your favourable payment option and we will contact you as soon as possible.',
				'description' 	=> __( 'The message which you want it to appear to the customer in the checkout page.', 'woocommerce-dgc-payment-gateway' ),
			),
			'hide_text_box' => array(
				'title' 		=> __( 'Hide The Payment Field', 'woocommerce-dgc-payment-gateway' ),
				'type' 			=> 'checkbox',
				'label' 		=> __( 'Hide', 'woocommerce-dgc-payment-gateway' ),
				'default' 		=> 'no',
				'description' 	=> __( 'If you do not need to show the text box for customers at all, enable this option.', 'woocommerce-dgc-payment-gateway' ),
			),
			'order_status' => array(
				'title' 		=> __( 'Order Status After The Checkout', 'woocommerce-dgc-payment-gateway' ),
				'type' 			=> 'select',
				'options' 		=> wc_get_order_statuses(),
				'default' 		=> 'wc-on-hold',
				'description' 	=> __( 'The default order status if this gateway used in payment.', 'woocommerce-dgc-payment-gateway' ),
			),
		);
	}
	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function admin_options() {
		?>
		<h3><?php _e( 'dgc Payment Settings', 'woocommerce-dgc-payment-gateway' ); ?></h3>
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">
						<table class="form-table">
							<?php $this->generate_settings_html();?>
						</table><!--/.form-table-->
					</div>
					<div id="postbox-container-1" class="postbox-container">
	                        <div id="side-sortables" class="meta-box-sortables ui-sortable"> 
	                           
     							<div class="postbox ">
	                                <div class="handlediv" title="Click to toggle"><br></div>
	                                <h3 class="hndle"><span><i class="dashicons dashicons-update"></i>&nbsp;&nbsp;Upgrade to Pro</span></h3>
	                                <div class="inside">
	                                    <div class="support-widget">
	                                        <ul>
	                                            <li>» Full Form Builder</li>
	                                            <li>» Custom Gateway Icon</li>
	                                            <li>» Order Status After Checkout</li>
	                                            <li>» Custom API Requests</li>
	                                            <li>» Debugging Mode</li>
	                                            <li>» Auto Hassle-Free Updates</li>
	                                            <li>» High Priority Customer Support</li>
	                                        </ul>
											<a href="https://dgc.network/plugin/woocommerce-dgc-payment-gateway-pro/" class="button dgc_button" target="_blank"><span class="dashicons dashicons-star-filled"></span> Upgrade Now</a> 
	                                    </div>
	                                </div>
	                            </div>
	                            <div class="postbox ">
	                                <div class="handlediv" title="Click to toggle"><br></div>
	                                <h3 class="hndle"><span><i class="dashicons dashicons-editor-help"></i>&nbsp;&nbsp;Plugin Support</span></h3>
	                                <div class="inside">
	                                    <div class="support-widget">
	                                        <p>
	                                        <img style="width: 70%;margin: 0 auto;position: relative;display: inherit;" src="https://dgc.network/wp-content/uploads/2016/03/dgc_logo_with_ruby_color-300x88.png">
	                                        <br/>
	                                        Got a Question, Idea, Problem or Praise?</p>
	                                        <ul>
												<li>» Please leave us a <a target="_blank" href="https://wordpress.org/support/view/plugin-reviews/woocommerce-dgc-payment-gateway?filter=5#postform">★★★★★</a> rating.</li>
	                                            <li>» <a href="https://dgc.network/submit-ticket/" target="_blank">Support Request</a></li>
	                                            <li>» <a href="https://dgc.network/knowledgebase_category/woocommerce-dgc-payment-gateway-pro/" target="_blank">Documentation and Common issues.</a></li>
	                                            <li>» <a href="https://dgc.network/plugins/" target="_blank">Our Plugins Shop</a></li>
	                                        </ul>

	                                    </div>
	                                </div>
	                            </div>
	                       
	                            <div class="postbox rss-postbox">
	    							<div class="handlediv" title="Click to toggle"><br></div>
	    								<h3 class="hndle"><span><i class="fa fa-wordpress"></i>&nbsp;&nbsp;dgc Blog</span></h3>
	    								<div class="inside">
											<div class="rss-widget">
												<?php
	    											wp_widget_rss_output(array(
	    													'url' => 'https://dgc.network/feed/',
	    													'title' => 'dgc Blog',
	    													'items' => 3,
	    													'show_summary' => 0,
	    													'show_author' => 0,
	    													'show_date' => 1,
	    											));
	    										?>
	    									</div>
	    								</div>
	    						</div>

	                        </div>
	                    </div>
                    </div>
				</div>
				<div class="clear"></div>
				<style type="text/css">
				.dgc_button{
					background-color:#4CAF50 !important;
					border-color:#4CAF50 !important;
					color:#ffffff !important;
					width:100%;
					padding:5px !important;
					text-align:center;
					height:35px !important;
					font-size:12pt !important;
				}
				</style>
				<?php
	}
	public function process_payment( $order_id ) {
		global $woocommerce;
		$order = new WC_Order( $order_id );
		// Mark as on-hold (we're awaiting the cheque)
		$order->update_status($this->order_status, __( 'Awaiting payment', 'woocommerce-dgc-payment-gateway' ));
		// Reduce stock levels
		wc_reduce_stock_levels( $order_id );
		if(isset($_POST[ $this->id.'-admin-note']) && trim($_POST[ $this->id.'-admin-note'])!=''){
			$order->add_order_note(esc_html($_POST[ $this->id.'-admin-note']),1);
		}
		// Remove cart
		$woocommerce->cart->empty_cart();
		// Return thankyou redirect
		return array(
			'result' => 'success',
			'redirect' => $this->get_return_url( $order )
		);	
	}

	public function payment_fields(){
		if($this->hide_text_box !== 'yes'){
	    ?>

		<fieldset>
			<p class="form-row form-row-wide">
				<label for="<?php echo $this->id; ?>-admin-note"><?php echo ($this->description); ?> <span class="required">*</span></label>
				<textarea id="<?php echo $this->id; ?>-admin-note" class="input-text" type="text" name="<?php echo $this->id; ?>-admin-note"></textarea>
			</p>						
			<div class="clear"></div>
		</fieldset>
		<?php
		}
	}
}