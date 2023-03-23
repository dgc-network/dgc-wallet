<?php 
// Copyright (c) The dgc.network
// SPDX-License-Identifier: Apache-2.0

class dgc_wallet_Payment_Gateway extends WC_Payment_Gateway{

    private $order_status;

	public function __construct(){
		$this->id = 'dgc-wallet';
		$this->method_title = __('dgcWallet','text-domain');
		$this->title = __('dgcWallet','text-domain');
		$this->has_fields = true;
		$this->init_form_fields();
		$this->init_settings();

		$this->enabled = $this->get_option('enabled');
		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->hide_text_box = $this->get_option('hide_text_box');
		$this->order_status = $this->get_option('order_status');

		add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));
		add_filter('woocommerce_order_button_text', array( $this, 'custom_order_button_text' ), 1);
	}

	/*
	 * Change Place Order button text on checkout page in woocommerce
	 */

	//add_filter('woocommerce_order_button_text','custom_order_button_text',1);
	function custom_order_button_text($order_button_text) {	
		$order_button_text = 'Proceed to dgcWallet';	
		return $order_button_text;
	}

	public function init_form_fields(){
		$this->form_fields = array(
			'enabled' => array(
				'title' 		=> __( 'Enable/Disable', 'text-domain' ),
				'type' 			=> 'checkbox',
				'label' 		=> __( 'Enable dgcWallet', 'text-domain' ),
				'default' 		=> 'yes'
			),
			'title' => array(
				'title' 		=> __( 'Method Title', 'text-domain' ),
				'type' 			=> 'text',
				'description' 	=> __( 'This controls the title', 'text-domain' ),
				'default'		=> __( 'dgcWallet', 'text-domain' ),
				'desc_tip'		=> true,
			),
/*			
			'description' => array(
				'title' 		=> __( 'Customer Message', 'text-domain' ),
				'type' 			=> 'textarea',
				'css' 			=> 'width:500px;',
				'default' 		=> 'None of the dgc payment options are suitable for you? please drop us a note about your favourable payment option and we will contact you as soon as possible.',
				'description' 	=> __( 'The message which you want it to appear to the customer in the checkout page.', 'text-domain' ),
			),
*/			
			'hide_text_box' => array(
				'title' 		=> __( 'Hide The Payment Field', 'text-domain' ),
				'type' 			=> 'checkbox',
				'label' 		=> __( 'Hide', 'text-domain' ),
				'default' 		=> 'no',
				'description' 	=> __( 'If you do not need to show the text box for customers at all, enable this option.', 'text-domain' ),
			),
		
			'order_status' => array(
				'title' 		=> __( 'Order Status After The Checkout', 'text-domain' ),
				'type' 			=> 'select',
				'options' 		=> wc_get_order_statuses(),
				'default' 		=> 'wc-on-hold',
				'description' 	=> __( 'The default order status if this gateway used in payment.', 'text-domain' ),
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
	public function admin_options_backup() {
		?>
		<h3><?php _e( 'dgcWallet Settings', 'text-domain' ); ?></h3>
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
									<a href="https://dgc.network/plugin/text-domain-pro/" class="button dgc_button" target="_blank"><span class="dashicons dashicons-star-filled"></span> Upgrade Now</a> 
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
										<li>» Please leave us a <a target="_blank" href="https://wordpress.org/support/view/plugin-reviews/text-domain?filter=5#postform">★★★★★</a> rating.</li>
	                                	<li>» <a href="https://dgc.network/submit-ticket/" target="_blank">Support Request</a></li>
	                                	<li>» <a href="https://dgc.network/knowledgebase_category/text-domain-pro/" target="_blank">Documentation and Common issues.</a></li>
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

		$order = new WC_Order( $order_id );

		global $woocommerce;
		// Mark as on-hold (we're awaiting the cheque)
		$order->update_status($this->order_status, __( 'Awaiting payment', 'text-domain' ));
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

	/**
	 * Process the payment and return the result
     *
	 * @access public
	 * @param int $order_id
	 * @return array
	 */
	function process_payment_bitcoin ($order_id) {

		$order = new WC_Order ($order_id);

		//-----------------------------------
		// Save bitcoin payment info together with the order.
		// Note: this code must be on top here, as other filters will be called from here and will use these values ...
		//
		// Calculate realtime bitcoin price (if exchange is necessary)

		$exchange_rate = BWWC__get_exchange_rate_per_bitcoin (get_woocommerce_currency(), 'getfirst', $this->exchange_rate_type);
		/// $exchange_rate = BWWC__get_exchange_rate_per_bitcoin (get_woocommerce_currency(), $this->exchange_rate_retrieval_method, $this->exchange_rate_type);
		if (!$exchange_rate) {
			$msg = 'ERROR: Cannot determine Bitcoin exchange rate. Possible issues: store server does not allow outgoing connections, exchange rate servers are blocking incoming connections or down. ' .
				   'You may avoid that by setting store currency directly to Bitcoin(BTC)';
			BWWC__log_event (__FILE__, __LINE__, $msg);
			exit ('<h2 style="color:red;">' . $msg . '</h2>');
		}

		$order_total_in_btc   = ($order->get_total() / $exchange_rate);
		
		// Apply exchange rate multiplier only for stores with non-bitcoin default currency.
		if (get_woocommerce_currency() != 'BTC')
		$order_total_in_btc = $order_total_in_btc * $this->exchange_multiplier;

		$order_total_in_btc   = sprintf ("%.8f", $order_total_in_btc);

		$bitcoins_address = false;
  
		$order_info = array (
			'order_id'			=> $order_id,
			'order_total'		=> $order_total_in_btc,
			'order_datetime'  	=> date('Y-m-d H:i:s T'),
			'requested_by_ip'	=> @$_SERVER['REMOTE_ADDR'],
		);

		$ret_info_array = array();

		if ($this->service_provider == 'blockchain.info') {
			$bitcoin_addr_merchant = $this->bitcoin_addr_merchant;
			$secret_key = substr(md5(microtime()), 0, 16);	# Generate secret key to be validate upon receiving IPN callback to prevent spoofing.
			$callback_url = trailingslashit (home_url()) . "?wc-api=BWWC_Bitcoin&secret_key={$secret_key}&bitcoinway=1&src=bcinfo&order_id={$order_id}"; // http://www.example.com/?bitcoinway=1&order_id=74&src=bcinfo
		   	BWWC__log_event (__FILE__, __LINE__, "Calling BWWC__generate_temporary_bitcoin_address__blockchain_info(). Payments to be forwarded to: '{$bitcoin_addr_merchant}' with callback URL: '{$callback_url}' ...");

			// This function generates temporary bitcoin address and schedules IPN callback at the same
			$ret_info_array = BWWC__generate_temporary_bitcoin_address__blockchain_info ($bitcoin_addr_merchant, $callback_url);

			/*
			$ret_info_array = array (
		   		'result'                    => 'success', // OR 'error'
		   		'message'					=> '...',
		   		'host_reply_raw'            => '......',
		   		'generated_bitcoin_address' => '1H9uAP3x439YvQDoKNGgSYCg3FmrYRzpD2', // or false
		   	);
			*/
			$bitcoins_address = @$ret_info_array['generated_bitcoin_address'];

		} else if ($this->service_provider == 'electrum-wallet') {
			// Generate bitcoin address for electrum wallet provider.
			/*
			$ret_info_array = array (
		   		'result'                    => 'success', // OR 'error'
		   		'message'					=> '...',
		   		'host_reply_raw'            => '......',
		   		'generated_bitcoin_address' => '1H9uAP3x439YvQDoKNGgSYCg3FmrYRzpD2', // or false
		   	);
			*/
			$ret_info_array = BWWC__get_bitcoin_address_for_payment__electrum ($this->electrum_master_public_key, $order_info);
			$bitcoins_address = @$ret_info_array['generated_bitcoin_address'];
		}

		if (!$bitcoins_address) {
			$msg = "ERROR: cannot generate bitcoin address for the order: '" . @$ret_info_array['message'] . "'";
			BWWC__log_event (__FILE__, __LINE__, $msg);
			exit ('<h2 style="color:red;">' . $msg . '</h2>');
		}

   		BWWC__log_event (__FILE__, __LINE__, "     Generated unique bitcoin address: '{$bitcoins_address}' for order_id " . $order_id);

		if ($this->service_provider == 'blockchain.info') {
	     	update_post_meta (
	     		$order_id, 			// post id ($order_id)
	     		'secret_key', 		// meta key
	     		$secret_key 		// meta value. If array - will be auto-serialized
	     	);
	 	}

     	update_post_meta (
     		$order_id, 				// post id ($order_id)
     		'order_total_in_btc', 	// meta key
     		$order_total_in_btc 	// meta value. If array - will be auto-serialized
     	);
     	update_post_meta (
     		$order_id, 				// post id ($order_id)
     		'bitcoins_address',		// meta key
     		$bitcoins_address 		// meta value. If array - will be auto-serialized
     	);
     	update_post_meta (
     		$order_id, 				// post id ($order_id)
     		'bitcoins_paid_total',	// meta key
     		"0" 					// meta value. If array - will be auto-serialized
     	);
     	update_post_meta (
     		$order_id, 				// post id ($order_id)
     		'bitcoins_refunded',	// meta key
     		"0" 					// meta value. If array - will be auto-serialized
     	);
     	update_post_meta (
     		$order_id, 				// post id ($order_id)
     		'_incoming_payments',	// meta key. Starts with '_' - hidden from UI.
     		array()					// array (array('datetime'=>'', 'from_addr'=>'', 'amount'=>''),)
     	);
     	update_post_meta (
     		$order_id, 				// post id ($order_id)
     		'_payment_completed',	// meta key. Starts with '_' - hidden from UI.
     		0						// array (array('datetime'=>'', 'from_addr'=>'', 'amount'=>''),)
     	);

		// The bitcoin gateway does not take payment immediately, but it does need to change the orders status to on-hold
		// (so the store owner knows that bitcoin payment is pending).
		// We also need to tell WooCommerce that it needs to redirect to the thankyou page – this is done with the returned array
		// and the result being a success.
		//
		global $woocommerce;

		//	Updating the order status:

		// Mark as on-hold (we're awaiting for bitcoins payment to arrive)
		$order->update_status('on-hold', __('Awaiting bitcoin payment to arrive', 'woocommerce'));

		// Remove cart
		$woocommerce->cart->empty_cart();

		// Empty awaiting payment session
		unset($_SESSION['order_awaiting_payment']);

		// Return thankyou redirect
		if (version_compare (WOOCOMMERCE_VERSION, '2.1', '<')) {
			return array(
				'result' 	=> 'success',
				'redirect'	=> add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(woocommerce_get_page_id('thanks'))))
			);
		} else {
			return array(
				'result' 	=> 'success',
				'redirect'	=> add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, $this->get_return_url( $order )))
			);
		}
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