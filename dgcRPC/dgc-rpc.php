<?php

use Denpa\Bitcoin\Client as BitcoinClient;

/**
 * The [wporg] shortcode.
 *
 * Accepts a title and will display a box.
 *
 * @param array  $atts    Shortcode attributes. Default empty.
 * @param string $content Shortcode content. Default null.
 * @param string $tag     Shortcode tag (name). Default empty.
 * @return string Shortcode output.
 */
function dgc_shortcode( $atts = [], $content = null, $tag = '' ) {
    // normalize attribute keys, lowercase
    $atts = array_change_key_case( (array) $atts, CASE_LOWER );
 
    // override default attributes with user attributes
    $wporg_atts = shortcode_atts(
        array(
            'title' => 'WordPress.org',
            'params' => '',
        ), $atts, $tag
    );

	//$bitcoind = new BitcoinClient('http://DiGitalCoin:dIgITALcOIN@165.232.130.97:7998/');
	$bitcoind = new BitcoinClient('http://digitalcoinrpc:56c735f3910a53eeda0357670bc6a02f@1.163.27.45:7998/');
	if ( $wporg_atts['params'] == '' ) {
		$info = $bitcoind->request( $tag );
	} else {
		$info = $bitcoind->request( $tag, $wporg_atts['params'] );
	}
	$result = json_decode($info);
/*
	echo '<h2>' . $tag . '</h2>';
    if ($result === FALSE) {
        // JSON is invalid
        echo $info . '<br>';
    }
	foreach ($result as $key=>$value) {
      
        if ( is_array($value) ) {
            foreach ($value as $sub_value) {
                foreach ($sub_value as $sub_key=>$sub_value) {
                    echo $sub_key . ' : ' . $sub_value . '<br>';
                }
            }
        } else {
            echo $key . ' : ' . $value . '<br>';
        }
 
        echo $key . ' : ' . $value . '<br>';
    }
	echo '<br>';
*/

    // start box
    $o = '<div class="wporg-box">';
 
    // title
    $o .= '<h2>' . $tag . '</h2>';
 
    if ( is_object($info === FALSE) ) {
        // JSON is invalid
        $o .= $info . '<br>';
    }
	foreach ($result as $key=>$value) {
        $o .= $key . ' : ' . $value . '<br>';
    }
	$o .= '<br>';

    // enclosing tags
    if ( ! is_null( $content ) ) {
        // secure output by executing the_content filter hook on $content
        $o .= apply_filters( 'the_content', $content );
 
        // run shortcode parser recursively
        $o .= do_shortcode( $content );
    }
 
    // end box
    $o .= '</div>';
 
    // return output
    return $o;

}
 
/**
 * Central location to create all shortcodes.
 */
function wporg_shortcodes_init() {
    add_shortcode( 'getinfo', 'dgc_shortcode' );
    add_shortcode( 'getpeerinfo', 'dgc_shortcode' );
    add_shortcode( 'getblockchaininfo', 'dgc_shortcode' );
    add_shortcode( 'getdifficulty', 'dgc_shortcode' );
    add_shortcode( 'getwalletinfo', 'dgc_shortcode' );
	
}
 
add_action( 'init', 'wporg_shortcodes_init' );
