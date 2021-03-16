<?php

/**
 * Don't forget to include composer autoloader by uncommenting line below
 * if you're not already done it anywhere else in your project.
 **/

use Denpa\Bitcoin\Client as BitcoinClient;

function bitcoin_client_shortcode() {
	$bitcoind = new BitcoinClient('http://DiGitalCoin:dIgITALcOIN@165.232.130.97:7998/');
	$info = $bitcoind->request('getinfo');
	echo $info["version"];
}
add_shortcode( 'dgc-getinfo', 'bitcoin_client_shortcode' );

