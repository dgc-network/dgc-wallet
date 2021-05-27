<?php
/**
 * Open Trade Engine usage examples.
 *
 * Open Trade Engine
 */
include('engine/engine.php');
include('register.php');

add_action('wp_dashboard_setup', 'dgc_wp_dashboard_setup');
function dgc_wp_dashboard_setup() {
    $wp_user = wp_get_current_user();

    if($wp_user != 0) {
        wp_add_dashboard_widget('open_trade_widget', 'Open Trade', 'display');
    } else {
        // We shouldn't ever get here, since only logged-in users can access the dashboard.
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
}

function display() {
    handle_post();
    //tab_deposits_cb();
    $email_address = 'rover.k.chen@gmail.com';
    //address_exporter( $email_address, $page = 1 );
    $export_value = apply_filters( 'wallets_address_exporter', $email_address);
/*
    foreach ($export_value['data'] as $key=>$value) {
        echo $key.':'.$value.'<br>';
    }
*/    
//    foreach ($export_value as $key=>$value) {
        foreach ($export_value['data'] as $array_value) {
            foreach ($array_value as $key=>$value) {
                foreach ($array_value['data'] as $sub_array_value) {
                    foreach ($sub_array_value as $key=>$value) {
                        echo $key.':'.$value.'<br>';
                    }
                }
            }
        }
//    }

    wp_enqueue_script( 'wallets_ko' );
?>

<div class="dashed-slug-wallets balance" data-bind="css: { 'wallets-ready': !coinsDirty(), 'fiat-coin': selectedCoin() && coins()[ selectedCoin() ].is_fiat, 'crypto-coin': selectedCoin() && coins()[ selectedCoin() ].is_crypto }">
	<?php
		do_action( 'wallets_ui_before' );
		do_action( 'wallets_ui_before_balance' );
	?>
	<!-- ko if: ( Object.keys( coins() ).length > 0 ) -->
	<span class="wallets-reload-button" title="<?php echo apply_filters( 'wallets_ui_text_reload', esc_attr__( 'Reload data from server', 'wallets-front' ) ); ?>" data-bind="click: function() { coinsDirty( false ); if ( 'object' == typeof ko.tasks ) ko.tasks.runEarly(); coinsDirty( true ); }"></span>
	<label class="coin"><?php echo apply_filters( 'wallets_ui_text_coin', esc_html__( 'Coin', 'wallets-front' ) ); ?>: <select data-bind="options: Object.keys( coins() ).map(function(o){return coins()[o]}), optionsText: 'name', optionsValue: 'symbol', value: selectedCoin, valueUpdate: ['afterkeydown', 'input'], style: { 'background-image': 'url(' + $root.getCoinIconUrl( selectedCoin() ) + ')' }"></select></label>
	<label class="balance"><?php echo apply_filters( 'wallets_ui_text_balance', esc_html__( 'Balance', 'wallets-front' ) ); ?>: <span data-bind="text: currentCoinBalance">-</span><span class="fiat-amount" data-bind="text: currentCoinFiatBalance" ></span></label>
	<label class="available_balance" data-bind="if: currentCoinBalance() != currentCoinAvailableBalance()"><?php echo apply_filters( 'wallets_ui_text_available_balance', esc_html__( 'Available balance', 'wallets-front' ) ); ?>: <span data-bind="text: currentCoinAvailableBalance">-</span><span class="fiat-amount" data-bind="text: currentCoinFiatAvailableBalance" ></span></label>
	<!-- /ko -->
	<!-- ko ifnot: ( Object.keys( coins() ).length > 0 ) -->
	<p class="no-coins-message"><?php echo apply_filters( 'wallets_ui_text_no_coins', esc_html__( 'No currencies are currently enabled.', 'wallets-front' ) );?></p>
	<!-- /ko -->
	<?php
		do_action( 'wallets_ui_after_balance' );
		do_action( 'wallets_ui_after' );
	?>
</div>

    <strong>Buy Coins:</strong>
    <br />
    <br />
    <form action="" method="post">
    <?php wp_nonce_field('wpbw_widget_nonce'); ?>
    <label>Number of coins:</label>
    <input name="wpbw_send_numcoins" type="text" size="10" />
    <br />
    <label>Coin Type:</label>
    <select name="wpbw_send_address" >
        <option value="BTC" > BTC </option>
        <option value="DGC"> DGC </option>
    </select>
    <br />
    <input name="wpbw_widget_send" type="submit" value="Send" />
    </form>
    <br />
    <?php

}

function handle_post() {
    if(isset($_REQUEST['wpbw_widget_send'])) {
        check_admin_referer('wpbw_widget_nonce');

//add traders to database, usually after receiving a post request from a registration form
/*
$register = new Register();

$register->insertMember("buyer", "John", "Smith","2010-11-11", "999-999-9999",
    "Question", "Answer", "555", "buyer@example.com", "password", 3);

$register->insertMember("seller", "Joe", "Smithie", "12-12-1992", "999-888-9999",
   "Question", "Answer", "444", "seller@example.com", "password", 3);
*/

//TO DO: create new currencies and symbol using them


//TO DO: add balances to traders, usually after a deposit is made manually or through an API


//create an order, usually done after receiving a post request from user's browser
//function __construct($setPrice, $setQuantity, $setType, $setSide, $setOwner, $setSymbol, $setFeePercent)
//types are a work in progress
$buyOrder = new Order($price = 0.05, $quantity = 1000, $type = 1, $side = 'Buy', $traderID = 4, $symbolID = 1);
$sellOrder = new Order(0.05, 1000, 1, 'Sell', 5, $symbolID = 1);

$engine = new Engine($symbolID = 1);
$engine->addOrder($buyOrder); //executes or adds order depending on orders already in it

//$order = $engine->getOrderByID($ID);
//$engine->cancelOrder($order);

$engine->addOrder($sellOrder);

//use trader object to retrieve trader information
$buyer = new Trader();
$buyer->setupTrader(4);

$seller = new Trader();
$seller->setupTrader(5);

echo "Buyer ID: ".$buyer->getID()." Buyer Balance: ".$buyer->getBalance("USD");
echo "Seller ID: ".$seller->getID()." Seller Balance: ".$seller->getBalance("USD");



    }
}

function tab_deposits_cb() {
    global $wpdb;

    $prefix = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
    $table_name_txs  = "{$prefix}wallets_txs";
    $table_name_adds = "{$prefix}wallets_adds";

    $intervals = array(
        'DAYOFYEAR' => __( 'Today',      'wallets' ),
        'WEEK'      => __( 'This week',  'wallets' ),
        'MONTH'     => __( 'This month', 'wallets' ),
        'YEAR'      => __( 'This year',  'wallets' ),
    );

    $data = array();

    foreach ( $intervals as $interval => $interval_text ) {
        $data[ $interval ] = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT
                    symbol,
                    SUM( amount - fee ) as amount,
                    COUNT( 1 ) as count
                FROM
                    {$table_name_txs}
                WHERE
                    status = 'done'
                    AND category = 'deposit'
                    AND amount > 0
                    AND $interval(created_time) = $interval( NOW() )
                    AND YEAR(created_time) = YEAR( NOW() )
                    AND ( blog_id = %d )
                GROUP BY
                    symbol
                ORDER BY
                    symbol",
                get_current_blog_id()
            ),
            OBJECT_K
        );

        $totals = new stdClass();
        $totals->amount = 0;
        $totals->count  = 0;
        foreach ( $data[ $interval ] as $symbol => $fields ) {
            $totals->amount += $data[ $interval ][ $symbol ]->amount;
            $totals->count  += $data[ $interval ][ $symbol ]->count;
            echo 'symbol:'.$symbol;
        }
        $data[ $interval ]['totals'] = $totals;

    }
    //$this->render_table( $data, __( 'Amounts received as deposits', 'wallets' ), 'wallets_deposits_amounts', 'amount' );
    //$this->render_table( $data, __( 'Deposits count',               'wallets' ), 'wallets_deposits_count',   'count'  );
}

function address_exporter_callback( $email_address, $page = 1 ) {
    $user = get_user_by( 'email', $email_address );

    global $wpdb;
    $prefix = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
    $table_name_txs  = "{$prefix}wallets_txs";
    $table_name_adds = "{$prefix}wallets_adds";

    $export_items = array();
    $count        = 500;
    //$output = 'Test->';

    if ( $user ) {
        $from = ( $page - 1 ) * $count;

        $addresses = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT
                    id,
                    symbol,
                    address,
                    extra
                FROM
                    {$table_name_adds}
                WHERE
                    account = %d
                LIMIT
                    %d, %d
                ",
                $user->ID,
                $from,
                $count
            )
        );
        if ( $addresses ) {
            foreach ( $addresses as $add ) {
                //$output .= $add->symbol;

                $data = array(
                    array(
                        'name'  => __( 'Coin symbol', 'wallets' ),
                        'value' => $add->symbol,
                    ),
                    array(
                        'name'  => __( 'Address', 'wallets' ),
                        'value' => $add->address,
                    ),
                );

                if ( $add->extra ) {
                    $data[] = array(
                        'name'  => __( 'Address extra field', 'wallets' ),
                        'value' => $add->extra,
                    );
                }

                $export_items[] = array(
                    'item_id'     => "wallets-address-{$add->id}",
                    'group_id'    => 'wallets-addresses',
                    'group_label' => __( 'Bitcoin and Altcoin Wallets blockchain deposit addresses', 'wallets' ),
                    'data'        => $data,
                );
            } // end foreach address
        } // end if addresses
    } // end if user
    //echo $output;
    //return;        

    return array(
        'data' => $export_items,
        'done' => count( $export_items ) != $count,
    );
} // end function address_exporter
add_filter( 'wallets_address_exporter', 'address_exporter_callback');
