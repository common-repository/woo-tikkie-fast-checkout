<?php
/**
* Plugin Name:  Woocommerce Tikkie Fast Checkout
* Plugin URI:   https://tikkie.me
* Description:  This plugin enables the Tikkie Fast Checkout payment method for your WooCommerce shop
* Version:      1.2.1
* Author:       Tikkie
* License:      GPL2
* License URI:  https://www.gnu.org/licenses/gpl-2.0.html
* Text Domain:  tkfc
* Domain Path:  /languages
**/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

define('TK_ADMIN_BASE', admin_url() .'admin.php?page=wc-settings&amp;tab=checkout&amp;section=tikkie');
define('TK_PLUGIN_SETUP_URL', admin_url() .'admin.php?page=woocommerce_tikkie_fast_checkout');
define('TK_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TK_PLUGIN_NAME', plugin_basename(__FILE__));
define('TK_API_NOTIFY_URL', admin_url('admin-ajax.php').'?action=tikkie-notify');
define('TK_LOG_PATH', wp_upload_dir( null, false )['basedir'].'/tikkie-fast-checkout');
define('TK_LOG_LIMIT', 1000);

function tk_fast_checkout_init() {
	// Check if WooCommerce is active
	if( class_exists('woocommerce') ){
		
		// Load the text domain
		load_plugin_textdomain( 'tkfc', false, basename(dirname( __FILE__ )).'/languages/' );
		
		/**
		* Initialize the gateway class
		*/
		require_once('includes/initTikkieGateway.php');
		require_once('includes/tikkieAPI.php');

		/**
		* Additional functions
		*/
		require_once('includes/functions.php');
		
	} else {
		
		/**
		*  Display admin error if woocommerce is inactive
		*  
		*  @return void
		**/
		function tk_inactive_wc_error(){ ?>
			<div class="error">
				<p><?php _e( 'Tikkie Fast Checkout is disabled because Woocommerce is not installed or inactive.', 'tkfc' ); ?></p>
			</div>
		<?php }
		add_action( 'admin_notices', 'tk_inactive_wc_error' );
	}
}

add_action( 'plugins_loaded', 'tk_fast_checkout_init' );

// Below extra for Wizard functionality
function tk_admin_menu() {
	add_menu_page('', '', 'manage_options', 'woocommerce_tikkie_fast_checkout', 'tk_options_page');
}
add_action( 'admin_menu', 'tk_admin_menu' );

function tk_redirect( $plugin ) {
	if( $plugin == plugin_basename( __FILE__ ) && basename($plugin) == basename(plugins_url('woocommerce-tikkie-fast-checkout.php'), __FILE__) ) {
		exit( wp_redirect( TK_PLUGIN_SETUP_URL ) );
	}
}
add_action( 'activated_plugin', 'tk_redirect' );

