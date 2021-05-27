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
?>

<form class="dashed-slug-wallets balance balance-list onsubmit="return false;" data-bind="css: { 'wallets-ready': !coinsDirty() }">
	<?php
		do_action( 'wallets_ui_before' );
		do_action( 'wallets_ui_before_balance' );
	?>
	<!-- ko ifnot: ( Object.keys( coins() ).length > 0 ) -->
	<p class="no-coins-message"><?php echo apply_filters( 'wallets_ui_text_no_coins', esc_html__( 'No currencies are currently enabled.', 'wallets-front' ) );?></p>
	<!-- /ko -->

	<!-- ko if: ( Object.keys( coins() ).length > 0 ) -->
	<span class="wallets-reload-button" title="<?php echo apply_filters( 'wallets_ui_text_reload', esc_attr__( 'Reload data from server', 'wallets-front' ) ); ?>" data-bind="click: function() { coinsDirty( false ); if ( 'object' == typeof ko.tasks ) ko.tasks.runEarly(); coinsDirty( true ); }"></span>

	<label class="zero-balances">
		<?php echo apply_filters( 'wallets_ui_text_show_zero_balances', esc_html__( 'Show zero balances: ', 'wallets-front' ) ); ?>
		<input type="checkbox" data-bind="checked: showZeroBalances" />
	</label>

	<table>
		<thead>
			<tr>
				<th class="coin" colspan="2"><?php echo apply_filters( 'wallets_ui_text_coin', esc_html__( 'Coin', 'wallets-front' ) ); ?></th>
				<th class="balance"><?php echo apply_filters( 'wallets_ui_text_balance', esc_html__( 'Balance', 'wallets-front' ) ); ?></th>
				<th class="available_balance"><?php echo apply_filters( 'wallets_ui_text_available_balance', esc_html__( 'Available balance', 'wallets-front' ) ); ?></th>
			</tr>
		</thead>

		<tbody data-bind="foreach: jQuery.map( coins(), function( v, i ) { var copy = jQuery.extend({},v); copy.sprintf_pattern = copy.sprintf; delete copy.sprintf; return copy; } )">
			<!--  ko if: ( $root.showZeroBalances() || balance ) -->
			<tr data-bind="css: { 'fiat-coin': is_fiat, 'crypto-coin': is_crypto }">
				<td class="icon">
					<img data-bind="attr: { src: icon_url, alt: name }" />
				</td>
				<td class="coin" data-bind="text: name"></td>
				<td class="balance">
					<span data-bind="text: sprintf( sprintf_pattern, balance )"></span>
					<span class="fiat-amount" data-bind="text: rate ? sprintf( '%s %01.2f', walletsUserData.fiatSymbol, balance * rate ) : '';" ></span>
				</td>
				<td class="available_balance">
					<span data-bind="text: sprintf( sprintf_pattern, available_balance )"></span>
					<span class="fiat-amount" data-bind="text: rate ? sprintf( '%s %01.2f', walletsUserData.fiatSymbol, available_balance * rate ) : '';" ></span>
				</td>
			</tr>
			<!-- /ko -->
		</tbody>
	</table>
	<!-- /ko -->
	<?php
		do_action( 'wallets_ui_after_balance' );
		do_action( 'wallets_ui_after' );
	?>
</form>

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

