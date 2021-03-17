<?php

/**
 * Don't forget to include composer autoloader by uncommenting line below
 * if you're not already done it anywhere else in your project.
 **/

use Denpa\Bitcoin\Client as BitcoinClient;

function bitcoin_client_shortcode() {
	$bitcoind = new BitcoinClient('http://DiGitalCoin:dIgITALcOIN@165.232.130.97:7998/');
	$info = $bitcoind->request( 'getinfo' );
	//echo $info["version"];
}
add_shortcode( 'dgc-getinfo', 'bitcoin_client_shortcode' );

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
            'params' => array(),
        ), $atts, $tag
    );

	$bitcoind = new BitcoinClient('http://DiGitalCoin:dIgITALcOIN@165.232.130.97:7998/');
	$info = $bitcoind->request( $tag, $wporg_atts['params'] );
	//echo $info["version"];
	$info = json_decode($info);
	foreach ($info as $key=>$value) {
		echo $key . ' : ' . $value . '<br>';
	}

/*
    // start box
    $o = '<div class="wporg-box">';
 
    // title
    $o .= '<h2>' . esc_html__( $wporg_atts['title'], 'wporg' ) . '</h2>';
 
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
*/	
}
 
/**
 * Central location to create all shortcodes.
 */
function wporg_shortcodes_init() {
    add_shortcode( 'wporg', 'dgc_shortcode' );
    add_shortcode( 'getinfo', 'dgc_shortcode' );
}
 
add_action( 'init', 'wporg_shortcodes_init' );

/**
 * Another Example.
 */

function recent_posts_function($atts){
	extract(shortcode_atts(array(
	   'posts' => 1,
	), $atts));
 
	$return_string = '<ul>';
	query_posts(array('orderby' => 'date', 'order' => 'DESC' , 'showposts' => $posts));
	if (have_posts()) :
	   while (have_posts()) : the_post();
		  $return_string .= '<li><a href="'.get_permalink().'">'.get_the_title().'</a></li>';
	   endwhile;
	endif;
	$return_string .= '</ul>';
 
	wp_reset_query();
	return $return_string;
 }