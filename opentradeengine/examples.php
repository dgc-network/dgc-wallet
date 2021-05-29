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
    $symbols = array();

    $current_user = wp_get_current_user();
    $email_address = $current_user->user_email;
    $export_value = apply_filters( 'wallets_address_exporter', $email_address);
    foreach ($export_value['data'] as $array_value) {
        foreach ($array_value['data'] as $sub_array_value) {
            if ($sub_array_value['name']=='Coin symbol') {
                echo $sub_array_value['value'].':';
                $symbols[] = $sub_array_value['value'];
            } else if ($sub_array_value['name']=='Address') {
                echo $sub_array_value['value'].'<br>';
            }
        }
    }

    /** new try */
    $current_user_id = get_current_user_id();
    $adapters = apply_filters( 'wallets_api_adapters', array() );
    foreach ( $adapters as $adapter ) {
        $symbol = $adapter->get_symbol();
        $deposit_address = apply_filters(
            'wallets_api_deposit_address', '', array(
                'user_id' => $current_user->ID,
                'symbol'  => $symbol,
            )
        );
        echo $symbol.': '.$current_user.'<br>';
    }

?>

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
        <?php 
        foreach($symbols as $symbol) {
            echo '<option value="'.$symbol.'">'.$symbol.'</option>';
        } 
        ?>
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

function transaction_exporter( $email_address, $page = 1 ) {
    $user = get_user_by( 'email', $email_address );

    global $wpdb;
    //$table_name_txs = Dashed_Slug_Wallets::$table_name_txs;
    $prefix = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
    $table_name_txs  = "{$prefix}wallets_txs";
    $table_name_adds = "{$prefix}wallets_adds";

    $export_items = array();
    $count        = 500;

    if ( $user ) {
        $from = ( $page - 1 ) * $count;

        $txs = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT
                    id,
                    symbol,
                    address,
                    extra,
                    txid
                FROM
                    {$table_name_txs}
                WHERE
                    account = %d
                    AND category IN ( 'deposit', 'withdraw' )
                LIMIT
                    %d, %d
                ",
                $user->ID,
                $from,
                $count
            )
        );

        if ( $txs ) {
            foreach ( $txs as $tx ) {
                $data = array(
                    array(
                        'name'  => __( 'Coin symbol', 'wallets' ),
                        'value' => $tx->symbol,
                    ),
                    array(
                        'name'  => __( 'Blockchain address', 'wallets' ),
                        'value' => $tx->address,
                    ),
                );

                if ( $tx->extra ) {
                    $data[] = array(
                        'name'  => __( 'Address extra field', 'wallets' ),
                        'value' => $tx->extra,
                    );
                }

                $data[] = array(
                    'name'  => __( 'Blockchain TXID', 'wallets' ),
                    'value' => $tx->txid,
                );

                $export_items[] = array(
                    'item_id'     => "wallets-tx-{$tx->id}",
                    'group_id'    => 'wallets-txs',
                    'group_label' => __( 'Bitcoin and Altcoin Wallets blockchain transactions', 'wallets' ),
                    'data'        => $data,
                );

            } // end foreach txs
        } // end if txs
    } // end if user

    return array(
        'data' => $export_items,
        'done' => $count != count( $export_items ),
    );
} // end function transaction_exporter