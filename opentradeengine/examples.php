<?php
/**
 * Open Trade Engine usage examples.
 *
 * Open Trade Engine
 */
include('engine/engine.php');
include('register.php');
//use WooGateWayCoreLib\admin\functions\CsAdminQuery;
use WooGateWayCoreLib\lib\Util;

function dgc_wp_dashboard_setup() {
    $wp_user = wp_get_current_user();

    if($wp_user != 0) {
        wp_add_dashboard_widget('open_trade_widget', 'Open Trade', 'display');
    } else {
        // We shouldn't ever get here, since only logged-in users can access the dashboard.
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
}
add_action('wp_dashboard_setup', 'dgc_wp_dashboard_setup');

function display() {
    handle_post();
    //$sampleTest->handle_post();

    $t='1';
    $type = 1; 
    $side = 'Buy'; 
    
    $symbols = array();
    $current_user = wp_get_current_user();
    $adapters = apply_filters( 'wallets_api_adapters', array() );
    foreach ( $adapters as $adapter ) {
        $symbol = $adapter->get_symbol();
        $symbols[] = $symbol;
    }
    ?>
    <br />
    <br />
    <strong>Trade Coins:</strong>
    <br />
    <br />
    <form action="" method="post">
    <?php wp_nonce_field('wpbw_widget_nonce'); ?>
    <table>
        <tr>
            <td>
            <select name="from_symbol" >
            <?php 
            foreach($symbols as $symbol) {
                echo '<option value="'.$symbol.'" ';
                if($symbol=="BTC") echo 'selected';
                echo '>'.$symbol.'</option>';
            } 
            ?>
            </select>
            </td>
            <td><?php
            echo '<input name="wpbw_widget_send" type="submit" value="'.$side.'" />';
            ?></td>
            <td>
            <select name="to_symbol" >
            <?php 
            foreach($symbols as $symbol) {
                echo '<option value="'.$symbol.'">'.$symbol.'</option>';
            } 
            ?>
            </select>
            </td>
        </tr>
        <tr>
            <td><input name="from_numcoins" type="text" value="1" />
            </td>
            <td><input name="wpbw_widget_send" type="submit" value="Buy" />
            </td>
            <td><?php
            echo '<input name="to_numcoins" type="text" value="'.$_REQUEST["to_numcoins"].'" />';
            ?></td>
        </tr>
    </table>
    </form>
    <br />
    <?php

}

//class SampleTest extends WP_UnitTestCase {
function handle_post() {
    if(isset($_REQUEST['wpbw_widget_send'])) {
        check_admin_referer('wpbw_widget_nonce');

        $from_symbol = $_REQUEST['from_symbol'];
        $to_symbol = $_REQUEST['to_symbol'];
        $from_numcoins = (float)$_REQUEST['from_numcoins'];
        $to_numcoins = (float)$_REQUEST['to_numcoins'];
        $t = $_REQUEST['to_numcoins'];
        $rate = Dashed_Slug_Wallets_Rates::get_exchange_rate( $to_symbol, $from_symbol );
        $market_numcoins = (float)$rate * $from_numcoins;
        echo '<p>'.$from_numcoins.' '.$from_symbol.' = '.$market_numcoins.' '.$to_symbol.'</p>';
    
        //add traders to database, usually after receiving a post request from a registration form
        /*
        $register = new Register();

        $register->insertMember("buyer", "John", "Smith","2010-11-11", "999-999-9999",
            "Question", "Answer", "555", "buyer@example.com", "password", 3);

        $register->insertMember("seller", "Joe", "Smithie", "12-12-1992", "999-888-9999",
            "Question", "Answer", "444", "seller@example.com", "password", 3);
        */

        //TO DO: create new currencies and symbol using them

        $symbols = array();
        $current_user = wp_get_current_user();
        $adapters = apply_filters( 'wallets_api_adapters', array() );
        foreach ( $adapters as $adapter ) {
            $symbol = $adapter->get_symbol();
            $symbols[] = $symbol;
            $deposit_address = apply_filters(
                'wallets_api_deposit_address', '', array(
                    'user_id' => $current_user->ID,
                    'symbol'  => $symbol,
                )
            );
            echo $symbol.': '.$deposit_address.'<br>';

            $data = array(
                'cs_add_new' => array(
                    'coin_name' => $symbol,
                    'coin_address' => $deposit_address,
                    'checkout_type' => 1
                )
            );
            
            add_new_coin( $data );
            //$CsAdminQuery = new CsAdminQuery();
            //$assertEquals = new WP_UnitTestCase();
            //$this->assertEquals( $CsAdminQuery->add_new_coin( $data ), '{"status":true,"title":"Success","text":"Thank you! Coin has been added successfully.","redirect_url":"http:\/\/example.org\/wp-admin\/admin.php?page=cs-woo-altcoin-all-coins"}' );
       
            //$CsAdminQuery->add_new_coin( $data );
            //CsAdminQuery::add_new_coin( $data );
        }
        
        //TO DO: add balances to traders, usually after a deposit is made manually or through an API


        //create an order, usually done after receiving a post request from user's browser
        //function __construct($setPrice, $setQuantity, $setType, $setSide, $setOwner, $setSymbol, $setFeePercent)
        //types are a work in progress
        //$buyOrder = new Order($price = 0.05, $quantity = 1000, $type = 1, $side = 'Buy', $traderID = 4, $symbolID = 1);
        //$sellOrder = new Order(0.05, 1000, 1, 'Sell', 5, $symbolID = 1);
        
        $price = 0.05;
        $quantity = 1000; 
        $traderID = 4; 
        $symbol = 'DGC';
        $newOrder = new Order($from_symbol, $from_numcoins, $type, $side, $to_symbol, $to_numcoins);
        $engine = new Engine($from_symbol, $to_symbol);
        $engine->addOrder($newOrder); //executes or adds order depending on orders already in it

        //$engine = new Engine($symbolID = 1);
        //$engine->addOrder($buyOrder); //executes or adds order depending on orders already in it
        
        //$order = $engine->getOrderByID($ID);
        //$engine->cancelOrder($order);

        //$engine->addOrder($sellOrder);

        //use trader object to retrieve trader information
        //$buyer = new Trader();
        //$buyer->setupTrader(4);
        
        //$seller = new Trader();
        //$seller->setupTrader(5);
        
        //echo "Buyer ID: ".$buyer->getID()." Buyer Balance: ".$buyer->getBalance("USD");
        //echo "Seller ID: ".$seller->getID()." Seller Balance: ".$seller->getBalance("USD");

    }
}
//}
//$sampleTest = new  SampleTest();

	/**
	 * Add new coin to payment gateway
	 *
	 * @global type $wpdb
	 */
	function add_new_coin( $user_data ) {
		global $wpdb, $wapg_tables;
		$coin_info = Util::check_evil_script( $user_data['cs_add_new'] );

		if ( empty( $coin_info['coin_address'] ) || empty( $coin_info['checkout_type'] ) || empty( $coin_info['coin_name'] ) ) {
			wp_send_json(
				array(
					'status' => false,
					'title'  => __( 'Error', 'woo-altcoin-payment-gateway' ),
					'text'   => __( 'One or more required field is empty', 'woo-altcoin-payment-gateway' ),
				)
			);
		}

		// check coin already exists
		$check_coin_exists = $wpdb->get_var( $wpdb->prepare( " select id from {$wapg_tables['coins']} where name = '%s' and checkout_type = %d ", $coin_info['coin_name'], $coin_info['checkout_type'] ) );
		if ( $check_coin_exists ) {
			wp_send_json(
				array(
					'status' => false,
					'title'  => __( 'Error', 'woo-altcoin-payment-gateway' ),
					'text'   => sprintf( __( '"%s" already added. Please check the list from "All Coins" menu.', 'woo-altcoin-payment-gateway' ), $coin_info['coin_name'] ),
				)
			);
		}
/*
		if ( empty( $coin_web_id = get_coin_id( $coin_info['coin_name'], $coin_info['checkout_type'] ) ) ) {
			wp_send_json(
				array(
					'status' => false,
					'title'  => __( 'Error', 'woo-altcoin-payment-gateway' ),
					'text'   => __( 'Coin is not in service. Please make sure you have selected the coin name from the dropdown list when you have typed coin name. Still problem after selecting from dropdown? please contact support@codesolz.net for more information.', 'woo-altcoin-payment-gateway' ),
				)
			);
		}

		$get_coin_info     = array(
			'name'                     => sanitize_text_field( $coin_info['coin_name'] ),
			'coin_web_id'              => $coin_web_id->slug,
			'symbol'                   => $coin_web_id->symbol,
			'coin_type'                => $coin_web_id->is_paid == 1 ? 2 : 1,
			'checkout_type'            => $coin_info['checkout_type'],
			'status'                   => isset( $coin_info['coin_status'] ) ? 1 : 0,
			'transferFeeTextBoxStatus' => isset( $coin_info['transferFeeTextBoxStatus'] ) ? 1 : 0,
			'transferFeeTextBoxText'   => Util::check_evil_script( $coin_info['transferFeeTextBoxText'] ),
		);

		$check_coin_exists = $wpdb->get_var( $wpdb->prepare( " select id from {$wapg_tables['coins']} where coin_web_id = %s ", $coin_web_id->slug ) );
		if ( $check_coin_exists ) {
			$coin_id = $check_coin_exists;
			$wpdb->update( "{$wapg_tables['coins']}", $get_coin_info, array( 'id' => $coin_id ) );
		} else {
			$wpdb->insert( "{$wapg_tables['coins']}", $get_coin_info );
			$coin_id = $wpdb->insert_id;
		}

		// add coin address
		$coin_info['cid'] = $coin_id;
		if ( $coin_info['checkout_type'] == 2 ) {
			$more_address_fields   = Util::check_evil_script( $user_data['more_coin_address'] );
			$more_address_fields[] = $coin_info['coin_address'];
			for ( $i = 0; $i < count( $more_address_fields ); $i++ ) {
				$coin_info['aid']          = '';
				$coin_info['coin_address'] = $more_address_fields[ $i ];
				coin_address_update( $coin_info );
			}
		} else {
			coin_address_update( $coin_info );
		}
*/
		$get_offer_info          = array(
			'coin_id'                    => $coin_id,
			'offer_amount'               => isset( $coin_info['offer_amount'] ) ? $coin_info['offer_amount'] : 0,
			'offer_type'                 => isset( $coin_info['offer_type'] ) ? $coin_info['offer_type'] : 0,
			'offer_status'               => isset( $coin_info['offer_status'] ) ? 1 : 0,
			'offer_show_on_product_page' => isset( $coin_info['offer_show_on_product_page'] ) ? 1 : 0,
			'offer_start'                => isset( $coin_info['offer_start_date'] ) ? Util::get_formated_datetime( $coin_info['offer_start_date'] ) : '',
			'offer_end'                  => isset( $coin_info['offer_end_date'] ) ? Util::get_formated_datetime( $coin_info['offer_end_date'] ) : '',
		);
		$check_coin_offer_exists = $wpdb->get_var( $wpdb->prepare( " select id from {$wapg_tables['offers']} where coin_id = %d ", $coin_id ) );

		if ( $check_coin_offer_exists ) {
			$wpdb->update( "{$wapg_tables['offers']}", $get_offer_info, array( 'id' => $check_coin_offer_exists ) );
		} else {
			$wpdb->insert( "{$wapg_tables['offers']}", $get_offer_info );
		}

		wp_send_json(
			array(
				'status'       => true,
				'title'        => __( 'Success', 'woo-altcoin-payment-gateway' ),
				'text'         => __( 'Thank you! Coin has been added successfully.', 'woo-altcoin-payment-gateway' ),
				'redirect_url' => admin_url( 'admin.php?page=cs-woo-altcoin-all-coins' ),
			)
		);

	}

    /**
	 * coin address update
	 */
	function coin_address_update( $coin_info ) {
		global $wpdb, $wapg_tables;
		$get_address_info = array(
			'coin_id'     => isset( $coin_info['cid'] ) ? $coin_info['cid'] : '',
			'address'     => isset( $coin_info['coin_address'] ) ? $coin_info['coin_address'] : '',
			'lock_status' => 0,
		);

		if ( isset( $coin_info['aid'] ) && ! empty( $coin_info['aid'] ) ) {
			$wpdb->update( "{$wapg_tables['addresses']}", $get_address_info, array( 'id' => $coin_info['aid'] ) );
		} else {
			$wpdb->insert( "{$wapg_tables['addresses']}", $get_address_info );
		}

		return true;
	}
    
    /**
	 * get coin id
	 */
	function get_coin_id( $coin_name, $checkout_type ) {
        
		$currencies = get_all_coins_list(
			array(
				'ticker' => $coin_name,
			)
		);

		if ( isset( $currencies['success'] ) && $currencies['success'] == true && $currencies['data'][0]->name == $coin_name ) {
			if ( $checkout_type == 2 && $currencies['data'][0]->is_automatic_order_paid == 1 ) {
				return $currencies['data'][0];
			} elseif ( $checkout_type == 1 ) {
				return $currencies['data'][0];
			}
		}

		return false;
	}

    /**
	 * Get list of all coins
	 *
	 * @return array
	 */
	function get_all_coins_list( $slug = array() ) {

		$request_params = empty( $slug ) ? '' : '?' . http_build_query( $slug );

        $all_listed_coins_url = 'https://myportal.coinmarketstats.online/api/all-listed-coins';
		$api_status = Util::remote_call(
			$all_listed_coins_url . $request_params
		);

		if ( isset( $api_status['error'] ) ) {
			return array(
				'success'  => false,
				'response' => $api_status['response'],
			);
		} else {
			$api_status = json_decode( $api_status );
			if ( isset( $api_status->status ) && $api_status->status == 200 ) {
				return array(
					'success' => true,
					'data'    => $api_status->data,
				);
			} else {
				return array(
					'success'  => false,
					'response' => $api_status->response,
				);
			}
		}
	}