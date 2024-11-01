<?php
/**
* Runs on Uninstall of the Woocommerce Tikkie Fast Checkout plugin
*
* @package   Woocommerce Tikkie Fast Checkout
* Author:       Tikkie
* Author URI:   https://tikkie.me 
**/

// Check that we should be doing this
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit; // Exit if accessed directly
}

$tkfc_options = get_option("woocommerce_gateway_order");
//Remove from the options array appropriately
unset($tkfc_options['tikkie']);
//Update entire array
update_option("woocommerce_gateway_order", $tkfc_options);

global $wpdb;
$wpdb->query( 
	$wpdb->prepare( 
		"
		DELETE FROM `".$wpdb->prefix."options` 
		WHERE `option_name` LIKE %s 
		",
		'woocommerce_tikkie_settings'
	)
);

/**
* Remove the directory and its content (all files and subdirectories).
* @param string $dir the directory name
**/
function rmrf(  $dir ) {
	$files = array_diff(scandir($dir), array('.','..'));
	foreach ($files as $file) {
		(is_dir("$dir/$file")) ? rmrf("$dir/$file") : unlink("$dir/$file");
	}
	return rmdir($dir);
}

$log_base = wp_upload_dir( null, false );
$logfile_dir = $log_base['basedir'].'/tikkie-fast-checkout/';

// Clean up by removing dir
rmrf( $logfile_dir );


