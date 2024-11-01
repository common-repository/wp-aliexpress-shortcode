<?php
/*
Plugin Name: 	WP AliExpress Shortcode
Plugin URI: 	https://eryk.io/wp-aliexpress-shortcode
Description: 	Add shortcodes to your posts and pages to include a product listed on AliExpress. Enriched with image, title, actual price and option to directly order on AliExpress for your visitors.
Version: 		1.0
Author: 		Eryk
Author URI: 	https://eryk.io
License: 		GPL2

{Plugin Name} is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
{Plugin Name} is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with {Plugin Name}. If not, see {URI to Plugin License}.

*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

// [aliexpress id="193823"]
// [aliexpress url="http://www.aliexpres.com/item"]
function wp_ae_func( $atts ) {
	$a = shortcode_atts( array(
			'id' => 0,
			'url' => ""
	), $atts );
	
	require_once( dirname(__file__).'/includes/wp_ae_frame.php' );
	
	$frameObj = new WP_AE_Frame();
	$html_frame = $frameObj->get_frame($a['id'], $a['url']);
	
	wp_enqueue_style( 'wp_ae_style', plugins_url( 'css/styles.css', __FILE__ ) );
	wp_enqueue_style( 'dashicons' );
	
	return $html_frame;
}
add_shortcode( 'aliexpress', 'wp_ae_func' );

if ( is_admin() ) {
	
	add_action('admin_menu', 'wp_ae_admin_add_page');
	function wp_ae_admin_add_page() {
		add_options_page('WP AliExpress Shortcode', 'WP AliExpress Shortcode', 'manage_options', 'wp_ae', 'wp_ae_options_page');
	}	
	
	add_action( 'admin_init', 'wp_ae_admin_init' );
	function wp_ae_admin_init() {
		add_settings_section( 'wp_ae_section_usage', 'Usage', 'wp_ae_section_usage_callback', 'wp_ae' );
		
		register_setting( 'wp_ae_my-settings-group', 'wp_ae_apikey' );
		register_setting( 'wp_ae_my-settings-group', 'wp_ae_trackingid' );
		
		add_settings_section( 'wp_ae_section_settings', 'Settings', 'wp_ae_section_settings_callback', 'wp_ae' );

		add_settings_field( 'wp_ae_field_apikey', 'API Key', 'wp_ae_field_apikey_callback', 'wp_ae', 'wp_ae_section_settings' );
		add_settings_field( 'wp_ae_field_trackingid', 'Tracking ID', 'wp_ae_field_trackingid_callback', 'wp_ae', 'wp_ae_section_settings' );
	}

	function wp_ae_section_usage_callback() {
		echo 'This plugin creates a shortcode that can be used in posts or pages. The shortcode can be used in two ways:<br/>';
		echo '<br/>';
		echo '1. Use <b>[aliexpress id="123456"]</b> where the ID is an AliExpress item ID which can be found in the URL.<br/>';
		echo '2. Use <b>[aliexpress url="http://www.aliexpress.com/item/This-is-an-item/123456.html"]</b> where the URL is a direct link to an AliExpress item.';
	}
	
	function wp_ae_section_settings_callback() {
		echo 'By default, this plugin communicates with the AliExpres API through a free webservice provided by the developer, <a href="https://eryk.io/wp-aliexpress-shortcode" target="_blank">eryk.io</a>. Additionaly, it is possible to use your own AliExpress API key and tracking ID by changing the settings below.';
	}
	
	function wp_ae_field_apikey_callback() {
		$setting = esc_attr( get_option( 'wp_ae_apikey' ) );
		echo "<input type='text' name='wp_ae_apikey' value='$setting' /> <i>(optional)</i>";
	}
	
	function wp_ae_field_trackingid_callback() {
		$setting = esc_attr( get_option( 'wp_ae_trackingid' ) );
		echo "<input type='text' name='wp_ae_trackingid' value='$setting' /> <i>(optional)</i>";
	}
	
	function wp_ae_options_page() {
		?>
	    <div class="wrap">
	        <h2>WP AliExpress Shortcode</h2>
	        <form action="options.php" method="POST">
	            <?php settings_fields( 'wp_ae_my-settings-group' ); ?>
	            <?php do_settings_sections('wp_ae'); ?>
	            <?php submit_button(); ?>
	        </form>
	    </div>
	    <?php
	}
	
	function wp_ae_settings_link($links) {
		$settings_link = '<a href="options-general.php?page=wp_ae">Settings</a>';
		array_unshift($links, $settings_link);
		return $links;
	}
	
	$plugin = plugin_basename(__FILE__);
	add_filter("plugin_action_links_$plugin", 'wp_ae_settings_link' );
	
}
