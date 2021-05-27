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
    <form class="dashed-slug-wallets api-key" onsubmit="return false;" data-bind="css: { 'wallets-ready': ! noncesDirty() && ajaxSemaphore() < 1 }">
	<?php
		do_action( 'wallets_ui_before' );
		do_action( 'wallets_ui_before_wallets_api_key' );
	?>
	<span
		class="wallets-reload-button"
		title="<?php echo apply_filters( 'wallets_ui_text_reload', esc_attr__( 'Reload data from server', 'wallets-front' ) ); ?>"
		data-bind="click: function() { noncesDirty( false ); ko.tasks.runEarly(); noncesDirty( true ); }">
	</span>

	<label class="apikey"><?php echo apply_filters( 'wallets_ui_text_apikey', esc_html__( 'Wallets API key', 'wallets-front' ) ); ?>:
		<span class="wallets-clipboard-copy" onClick="jQuery(this).next()[0].select();document.execCommand('copy');" title="<?php echo apply_filters( 'wallets_ui_text_copy_to_clipboard', esc_html__( 'Copy to clipboard', 'wallets-front' ) ); ?>">&#x1F4CB;</span>
		<input type="text" readonly="readonly" onClick="this.select();" data-bind="value: nonces().api_key" />
	</label>

	<input
		type="button"
		data-bind="click: doResetApikey, disable: noncesDirty() || ajaxSemaphore() > 0"
		value="&#8635; <?php echo apply_filters( 'wallets_ui_text_renew', esc_attr__( 'Renew', 'wallets-front' ) ); ?>" />

	<?php
		do_action( 'wallets_ui_after_wallets_api_key' );
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

