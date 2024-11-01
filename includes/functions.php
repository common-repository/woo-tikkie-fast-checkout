<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
* Adding additional plugin admin css
*
**/
function tk_admin_css_js() {
	wp_enqueue_style( 'tk_admin_styles', TK_PLUGIN_URL.'assets/css/tikkie_admin.css', null, null );
	wp_enqueue_script('tk_admin_js', TK_PLUGIN_URL.'assets/js/tikkie_admin.js');
}
add_action('admin_enqueue_scripts', 'tk_admin_css_js');

/**
*  Add Tikkie Fast Checkout stylesheet
*
*  @return void
**/
function tk_styles() {
	wp_enqueue_style( 'tk_styles', TK_PLUGIN_URL.'assets/css/tikkie.css', null, null );
	wp_register_script( 'tk_script', TK_PLUGIN_URL.'assets/js/tikkie.js', array('jquery'), null );
	// Localize the script
	$translation_array = array(
		'admin_url' => admin_url('admin-ajax.php'),
		'checkout_error' => __('There was an error processing your request','tkfc'),
		'no_variation_selected_error' => __('Please select a variation','tkfc'),
	);
	wp_localize_script( 'tk_script', 'tikkie', $translation_array );
	wp_enqueue_script('tk_script');
}
add_action( 'wp_enqueue_scripts', 'tk_styles' );

/**
*  Outputs the Tikke Fast Checkout Button
*
*  @param bool $echo                  Echo the element here of return the HTML
*  @param string $url                 For overriding: The Button URL
*  @param string $button_label        For overriding: The Button label text
*  @param bool $button_image          For overriding: Show the Tikkie logo in the button or not
*  @param string $button_add_class    For overriding: Add additional classes to the button
*  @return Returns button HTML
**/
function tk_show_button($echo = true, $button_label = '', $button_image = true, $button_add_class = '') {
	// get the gateway settings
	$gateway = new WC_Gateway_Tikkie();

	// if we are on the product or cart page but the settings is turned off - return.
	if(
		( $gateway->enabled 			!= 'yes' ) 					||
		( $gateway->proddetail_enabled	!= 'yes' && is_product() ) 	||
		( $gateway->cart_enabled		!= 'yes' && is_cart() ) 	||
		( empty($gateway->get_api_key()) ) 							||
		( empty($gateway->get_merchant_token()) )
	){
		return;
	}

	// Set button text if empty
	if(empty($button_label)) {
		$button_label = __('Fast checkout with','tkfc');
	}

	//check if function is called by add_action
	if ($echo === '') {
		$echo = true;
	}

	// apply filters
	$button_label 		= apply_filters('tikkie_fast_checkout_button_label_text', $button_label);
	$button_image 		= apply_filters('tikkie_fast_checkout_button_label_image', $button_image);
	$button_add_class 	= apply_filters('tikkie_fast_checkout_button_add_class', $button_add_class);

	$view = '';
	$product_id = '';
	$type = '';
	//set the view
	if (is_product()) {
		$view = 'product';
		$product_id = get_the_id();
		$product = wc_get_product($product_id);
		$type = method_exists($product, 'get_type') ? $product->get_type() : $product->product_type;

		// cancel the button display if this is an external product
		if($type == 'external'){
			return;
		}
	} elseif (is_cart()) {
		$view = 'cart';
	}

	ob_start(); ?>

	<div class="tikkieBtn <?php echo $button_add_class; ?>" data-productid="<?php echo $product_id; ?>" data-type="<?php echo $type; ?>">
		<span class="normal">
			<span class="normalLabel"><?php echo $button_label; ?></span>
			<?php if($button_image){ ?><img src="<?php echo TK_PLUGIN_URL.'assets/img/logo.svg'; ?>" /> <?php } ?>
		</span>
		<span class="anim">
			<div class="dot-1"></div><div class="dot-2"></div>
		</span>
	</div>

	<?php if($gateway->settings['tkfc_termsofservice_enabled'] == 'yes') {
		$text = __($gateway->settings['tkfc_termsofservice_text'], 'tkfc');
		$text = str_replace('[', '<a href="' . $gateway->settings['tkfc_termsofservice_link'] . '" target="_blank">', $text);
		$text = str_replace(']', '</a>', $text);
		?>
		<div class="termsofservice">
			<p><?php echo $text; ?></p>
		</div>
	<?php } ?>

	<?php
	$result = ob_get_clean();
	$result = apply_filters('tikkie_fast_checkout_button_html', $result);

	if($echo === true){
		echo $result;
	} else {
		return $result;
	}
}
add_action('woocommerce_after_add_to_cart_button', 'tk_show_button', 10);
add_action('woocommerce_proceed_to_checkout', 'tk_show_button', 30);

/**
* Function to disable Tikkie Gateway for checkout page
**/
function tk_disable_gateway_on_checkoutpage( $available_gateways ) {
	if ( isset( $available_gateways['tikkie']) ) {
		unset( $available_gateways['tikkie'] );
	}
	return $available_gateways;
}
add_filter( 'woocommerce_available_payment_gateways', 'tk_disable_gateway_on_checkoutpage' );

/**
*  Function to log errors to a file
*
*  @param string $message Error to be shown in the error-log
*  @return void
**/
function tk_log($message) {
	// The error line itself
	$error = date('Y-m-d H:i:s').' - '.$message.PHP_EOL;

	// check if the tikkie dir is present
	if(wp_mkdir_p( TK_LOG_PATH )){

		// check if htaccess is present, if not, create it.
		$htaccess = TK_LOG_PATH.'/.htaccess';
		if(!file_exists($htaccess)){
			$handle = fopen($htaccess,'a+');
			fwrite($handle, 'order deny,allow'.PHP_EOL.'deny from all');
			fclose($handle);
		}

		// Set log file path
		$path = TK_LOG_PATH.'/tikkie_fast_checkout.log';
		// Create/Open write and start at the beginning
		$handle = fopen($path, 'a+');
		// read the file
		$filesize = filesize($path);
		$content = @fread($handle, $filesize);
		// insert in array
		$lines = explode(PHP_EOL, $content);
		$count = count($lines);
		$limit = TK_LOG_LIMIT;
		// if more lines than the limit, remove all but the last x lines.
		if($count > $limit){
			// truncate the file
			ftruncate($handle, 0);
			// cut the unwanted lines off the log
			array_splice($lines, 0, 0 - $limit);
			// write back all the lines we want to keep
			fwrite($handle, implode(PHP_EOL, $lines));
		}
		// add the latest error and close
		fwrite($handle, $error);
		fclose($handle);
	}
}

/**
*  Returns contents of the error log
*
*  @return string 	Error log contents
**/
function tk_get_log() {
	$file = TK_LOG_PATH.'/tikkie_fast_checkout.log';
	if(file_exists($file)){
		return @file_get_contents(TK_LOG_PATH.'/tikkie_fast_checkout.log');
	} else {
		return 'No log file present.';
	}
}

/**
*  This is called by Tikkie in the background to set the status of an order.
*
*  @return void
**/
function tk_notification_webhook() {
	// data from Tikkie
	$request_body = json_decode(@file_get_contents('php://input'));

	// check if the request is properly decoded
	if (is_object($request_body)) {
		$ref 	= str_replace('wc_', '', $request_body->referenceId);
		$token 	= $request_body->orderToken;
		$status	= $request_body->status;
	} else {
		tk_log('Notification webhook input is not a json string');
		exit;
	}

	// check if the proper data is set
	if(
		empty($ref) 	||
		empty($token) 	||
		empty($status)
	){
		tk_log('Notification webhook called with invalid arguments');
		exit;
	}

	// init the WC_Order object
	$order = wc_get_order($ref);

	// check if the order is valid
	if(empty($order)){
		tk_log('Notification webhook called an invalid order');
		exit;
	}

	// payment token validation
	$order_payment_token = $order->get_transaction_id();
	if($order_payment_token != $token){
		tk_log('Notification webhook payment token invalid');
		exit;
	}
	
	// get the gateway settings
	$gateway = new WC_Gateway_Tikkie();
	
	// finally, set the order status (if the status is paid, and the WC_Order is not already the correct status)
	if($status == 'PAID' && $order->get_status() !== str_replace('wc-','',$gateway->order_status)){
		// get Tikkie Order
		$tikkie = new TikkieAPI();
		$tOrder = $tikkie->get_order($token);
		
		// add address-data to Woocommerce Order
		tk_set_order_address_data($order, $tOrder);
		
		// set order status to the selected backend status
		$order->update_status($gateway->order_status, sprintf(__('Order status set by Tikkie Fast Checkout (webhook)', 'tkfc'), $gateway->order_status));
	}

	// All done.
	exit;
}
add_action( 'wp_ajax_tikkie-notify', 'tk_notification_webhook' );
add_action( 'wp_ajax_nopriv_tikkie-notify', 'tk_notification_webhook' );

function tk_set_wizard_settings() {

	if( isset($_GET["action"]) && $_GET["action"] == 'tikkie_wizard_settings') {

		$ret = '';
		$action = $_GET["action"];
		$tkfc_options = get_option("woocommerce_tikkie_settings");
		// errors
		$result = array('errors'=>array());


		switch($_GET["wizard_setting"]) {
			case "step1":
			// to be sure any change/edit will always return true,  it will be created anyway with update_option
			delete_option( 'woocommerce_tikkie_settings' );

			// API-key
			$tkfc_options['tkfc_api_key'] = sanitize_text_field($_POST["woocommerce_tikkie_tkfc_api_key"]);
			if( !update_option("woocommerce_tikkie_settings", $tkfc_options) ) {
				$result['errors'][] = __('Couldn\'t update option API-key in woocommerce_tikkie_settings','tkfc');
			}

			// Test API-key
			$tkfc_options['tkfc_test_api_key'] = sanitize_text_field($_POST["woocommerce_tikkie_tkfc_test_api_key"]);
			if( !update_option("woocommerce_tikkie_settings", $tkfc_options) ) {
				$result['errors'][] = __('Couldn\'t update option Test API-key in woocommerce_tikkie_settings','tkfc');
			}

			delete_option( 'woocommerce_tikkie_settings' );
			// Merchant-token
			$tkfc_options['tkfc_merchant_token'] = sanitize_text_field($_POST["woocommerce_tikkie_tkfc_merchant_token"]);
			if( !update_option("woocommerce_tikkie_settings", $tkfc_options) ) {
				$result['errors'][] = __('Couldn\'t update option Merchant-token in woocommerce_tikkie_settings','tkfc');
			}
			// Test merchant-token
			$tkfc_options['tkfc_test_merchant_token'] = sanitize_text_field($_POST["woocommerce_tikkie_tkfc_test_merchant_token"]);
			if( !update_option("woocommerce_tikkie_settings", $tkfc_options) ) {
				$result['errors'][] = __('Couldn\'t update option Test merchant-token in woocommerce_tikkie_settings','tkfc');
			}


			if( count($result['errors']) > 0 ) {
				$ret = $result['errors'];
			} else {
				$ret = array('result'=>"data processed");
			}

			break;

			case "step2":
			// to be sure any change/edit will always return true,  it will be created anyway with update_option
			delete_option( 'woocommerce_tikkie_settings' );

			$woocommerce_tikkie_proddetail_enabled = (isset($_POST["woocommerce_tikkie_proddetail_enabled"]) && $_POST["woocommerce_tikkie_proddetail_enabled"]=='yes'?'yes':'no');
			$tkfc_options['tkfc_proddetail_enabled'] = $woocommerce_tikkie_proddetail_enabled;
			if( !update_option("woocommerce_tikkie_settings", $tkfc_options) ) {
				$result['errors'][] = __('Couldn\'t update option "Show on product-detail page" in woocommerce_tikkie_settings','tkfc');
			}

			delete_option( 'woocommerce_tikkie_settings' );
			$woocommerce_tikkie_cart_enabled = (isset($_POST["woocommerce_tikkie_cart_enabled"]) && $_POST["woocommerce_tikkie_cart_enabled"]=='yes'?'yes':'no');
			$tkfc_options['tkfc_cart_enabled'] = $woocommerce_tikkie_cart_enabled;
			if( !update_option("woocommerce_tikkie_settings", $tkfc_options) ) {
				$result['errors'][] = __('Couldn\'t update option "Show on cart page" in woocommerce_tikkie_settings','tkfc');
			}

			delete_option( 'woocommerce_tikkie_settings' );
			$woocommerce_tikkie_termsofservice_enabled = (isset($_POST["woocommerce_tikkie_termsofservice_enabled"]) && $_POST["woocommerce_tikkie_termsofservice_enabled"]=='yes'?'yes':'no');
			$tkfc_options['tkfc_termsofservice_enabled'] = $woocommerce_tikkie_termsofservice_enabled;
			if( !update_option("woocommerce_tikkie_settings", $tkfc_options) ) {
				$result['errors'][] = __('Couldn\'t update option "Show terms of service" in woocommerce_tikkie_settings','tkfc');
			}

			delete_option( 'woocommerce_tikkie_settings' );
			$woocommerce_tikkie_termsofservice_link = isset($_POST["woocommerce_tikkie_termsofservice_link"]) ? esc_url_raw($_POST["woocommerce_tikkie_termsofservice_link"]) : '';
			$tkfc_options['tkfc_termsofservice_link'] = $woocommerce_tikkie_termsofservice_link;
			$tkfc_options['tkfc_termsofservice_text'] = __('If you continue, you will agree to our [terms of service]', 'tkfc');
			if( !update_option("woocommerce_tikkie_settings", $tkfc_options) ) {
				$result['errors'][] = __('Couldn\'t update option "Term of service link" in woocommerce_tikkie_settings','tkfc');
			}

			if( count($result['errors']) > 0 ) {
				$ret = $result['errors'];
			} else {
				$ret = array('result'=>"data processed");
			}
			break;

			case "step3":
			// to be sure any change/edit will always return true,  it will be created anyway with update_option
			delete_option( 'woocommerce_tikkie_settings' );

			$tkfc_options['tkfc_shipping_cost'] = sanitize_text_field($_POST["woocommerce_tikkie_tkfc_shipping_cost"]);
			if( !update_option("woocommerce_tikkie_settings", $tkfc_options) ) {
				$result['errors'][] = __('Couldn\'t update option "Shipping costs" in woocommerce_tikkie_settings','tkfc');
			}

			if( count($result['errors']) > 0 ) {
				$ret = $result['errors'];
			} else {
				$ret = array('result'=>"data processed");
			}
			break;

		}
		// output the result as json
		echo json_encode($ret, JSON_UNESCAPED_SLASHES);
		exit;
	}
}
add_action( 'wp_ajax_tikkie_wizard_settings', 'tk_set_wizard_settings' );
add_action( 'wp_ajax_nopriv_tikkie_wizard_settings', 'tk_set_wizard_settings' );

/**
*  Function that returns an array with the product-data needed for the create order API call
*
*  @param int $id product ID
*  @param int $variation_id variation ID (leave empty for simple product)
*  @param int $quantity Quantity of product in cart
*  @param int $priceOverride Manually set price in cents
*  @return Returns array with product data
*/
function tk_populate_tikkie_product_data($item, $id, $variation_id = 0, $quantity = 1, $priceOverride = null) {

	// init product
	if ($variation_id) {
		$product = wc_get_product($variation_id);
	} else {
		$product = wc_get_product($id);
	}

	if($priceOverride !== null){
		$price = $priceOverride;
	} else {
		$price = round($item['data']->get_price() * 100);
	}

	$return = array(
		'itemName' => $product->get_title(),
		'priceInCents' => $price,
		'quantity' => $quantity,
	);

	return $return;
}

/**
*  Creates a quote in the Tikkie API
*
*  @return void
*/
function tk_create_order() {
	// init cart/gateway
	$cart = WC()->cart;			
	$gateway = new WC_Gateway_Tikkie;

	$attributes = array();
	// add the single product if there is only 1 product
	if (!empty($id = isset($_POST['product_id']) ? $_POST['product_id'] : '')) {

		// get the selected attributes of the product
		$attributes = array();
		foreach($_POST as $key => $var) {
			$key = sanitize_text_field($key);
			if (strpos($key, 'attribute') !== false) {
				$attributes[$key] = sanitize_text_field($var);
			}
		}

		// check if product is variable product
		if (!empty($varid = isset($_POST['variation_id'])?$_POST['variation_id']:'') && !empty($attributes)) {
			$cart->add_to_cart($id, sanitize_text_field($_POST['quantity']), $varid, $attributes); 
		} else {
			$cart->add_to_cart($id, sanitize_text_field($_POST['quantity']));
		}
		
		// manually trigger the Yith Woocommerce Dynamic Pricing cart_discount functions when a product is added manually
		if ( function_exists( 'YITH_WC_Dynamic_Pricing_Frontend' ) ) {
			YITH_WC_Dynamic_Pricing_Frontend()->cart_process_discounts();
		}
		
		// recalculate totals
		$cart->calculate_totals();
	}

	if (empty($cart->get_cart_contents())) {
		echo json_encode( array('errors' => array(__('Tikkie Fast Checkout is not available because there are no items in your cart','tkfc'))) );
		exit();
	}
	
	// get the WC_Checkout object
	$checkout = WC()->checkout();
	
	// set default customer country to Netherlands
	WC()->customer->set_shipping_country('NL');
	WC()->customer->save();
	
	// update cart totals to make sure the new default customer country is used
	$cart->calculate_totals();
	
	// create the order
	$order_id = $checkout->create_order($checkout->get_posted_data());
	$order = wc_get_order($order_id);

	// set a session variable for thank you page security
	session_start();
	$_SESSION['tk_order_id'] = $order->get_id();
	
	// loop over all cart contents and populate the $items array
	$items = array();
	$cart_contents = WC()->cart->get_cart_contents();
	foreach($cart_contents as $hash => $item) {
		$items[] = tk_populate_tikkie_product_data($item, $item['product_id'], $item['variation_id'], $item['quantity']);	
	}
	
	// set tikkie FC as payment method
	$order->set_payment_method(new WC_Gateway_Tikkie);
	
	// get the shipping costs from WC_Order
	$shipping_cost = $order->get_shipping_total() + $order->get_shipping_tax();
	$shipping_cost = $shipping_cost * 100;
	
	// API values
	$discount = $cart->get_discount_total();
	$discount_tax = $discount + $cart->get_discount_tax(); //add tax to the discount-amount

	// expiration option validation
	$expiration = intval($gateway->expiration);
	if ($expiration < 180) {
		$expiration = 180;
	} elseif ($expiration > 1800) {
		$expiration = 1800;
	} elseif (!$expiration) {
		$expiration = 900; //default
	}

	// init API class and request
	$tikkie = new TikkieAPI();
	
	$request = array(
		'referenceId' => 'wc_'.$order->get_id(),
		'shippingCostsInCents' => $shipping_cost,
		'discountInCents' => $discount_tax * 100,
		'currency' => 'EUR',
		'expiration' => $expiration,
		'redirectUrl' => $order->get_checkout_order_received_url(),
		'notificationUrl' => TK_API_NOTIFY_URL,
		'addressRequired' => true, //this parameter is not supported by the API yet (true is default)
		'items' => $items,
	);

	// do the API call
	$tOrder = $tikkie->create_order($request);
	
	// check if there are errors
	$result = array('errors'=>array());
	if (!empty($tOrder->errors)) {
		foreach($tOrder->errors as $error) {
			$result['errors'][] = $error->message;
			tk_log("API error (create_order): {$order->get_id()}: {$error->message}");
		}
	} else if(is_null($tOrder)){
		tk_log("API error (create_order): SSL certificate error");
	} else {
		// otherwise the call was successful
		$result = $tOrder;
		$order->set_transaction_id( $tOrder->orderToken );
		$order->save();

		// create the cron that checks/updates the order payment status every 3 minutes
		wp_schedule_event(time() + 180, 'tk_threeminutes', 'tk_update_order_status_cron', array($order->get_id(), $tOrder->orderToken));
		
		// create cron that removes the tk_update_order_status_cron after the expiration-time has passed (and add 15 seconds to try to not have both cronjobs run at the same time (this can cause issues))
		wp_schedule_single_event(time() + $expiration + 15, 'tk_unschedule_order_status_cron', array($order->get_id(), $tOrder->orderToken));
	}

	// output the result as json
	echo json_encode($result, JSON_UNESCAPED_SLASHES);
	exit;
}
add_action( 'wp_ajax_tikkie-create-order', 'tk_create_order' );
add_action( 'wp_ajax_nopriv_tikkie-create-order', 'tk_create_order' );


/**
*  Handles the cronjob for expired orders
*
*  @param int $order_id Woocommerce order ID
*  @param string $order_token Tikkie order token
*  @return void
*/
function tk_update_order_status_cron($order_id, $order_token) {
	$tikkie = new TikkieAPI();
	$tOrder = $tikkie->get_order($order_token);
	
	//check if the order matches the tikkie referenceId 
	if (str_replace('wc_', '', $tOrder->referenceId) == $order_id) {
		// get the WC_Order
		$order = wc_get_order( $order_id );
		if($order === false){
			tk_unschedule_order_status_cron($order_id, $order_token);
			tk_log('Tikkie Fast Checkout tried to change the status of an unexisting order (#'.$order_id.')');
		} else {
			if($order->get_status() == 'trash'){
				tk_unschedule_order_status_cron($order_id, $order_token);
				tk_log('Tikkie Fast Checkout tried to change the status of a trashed order (#'.$order_id.')');
			} else {
				// check the payment status and update the WC_Order status
				if ($tOrder->status == 'EXPIRED') {
					$order->update_status('wc-failed', __('Tikkie Fast Checkout payment expired', 'tkfc'));
				} elseif ($tOrder->status == 'PAID') {
					// get the gateway settings
					$gateway = new WC_Gateway_Tikkie;
					
					// add address-data to Woocommerce Order
					tk_set_order_address_data($order, $tOrder);
					
					$order->update_status($gateway->order_status, __('Order status set by Tikkie Fast Checkout (cronjob)', 'tkfc'));
					
					// unschedule the cron
					tk_unschedule_order_status_cron($order_id, $order_token);
					
				} elseif ($tOrder->status == 'NEW') {
					// keep doing the cronjob (nothing actually has to be done)
					// tk_log('Cronjob: order status is still NEW, proceeding to run cronjob (#'.$order_id.')');
				} else {
					tk_log('Cronjob error: invalid payment status '.$tOrder->status.' (#'.$order_id.')');
				}
			}
		}
	} else {
		tk_log('Cronjob error: order_id does not match (#'.$order_id.')');
	}
}
add_action('tk_update_order_status_cron', 'tk_update_order_status_cron', 10, 2);

/**
*  Unschedules the cronjob that is set in tk_create_order
*
*  @param int $order_id Woocommerce order ID
*  @param string $order_token Tikkie order token
*  @return void
*/
function tk_unschedule_order_status_cron($order_id, $order_token) {
	// check if the tk_update_order_status_cron is scheduled (should always be true, but we check it just in case)
	if ($next = wp_next_scheduled('tk_update_order_status_cron', array($order_id, $order_token))) {
		// unschedule the event
		wp_unschedule_event($next, 'tk_update_order_status_cron', array($order_id, $order_token));
		
		// also unschedule the tk_unschedule_order_status_cron
		$next_unschedule = wp_next_scheduled('tk_unschedule_order_status_cron', array($order_id, $order_token));
		wp_unschedule_event($next_unschedule, 'tk_unschedule_order_status_cron', array($order_id, $order_token));
	} else {
		tk_log('Cronjob error: Tried unscheduling update-order-status-cronjob but the event is no longer scheduled (#'.$order_id.')');
	}
}
add_action('tk_unschedule_order_status_cron', 'tk_unschedule_order_status_cron', 10, 2);

/**
*  Handles customer address and order status on the thankyou page
*
*  @param int $order_id Woocommerce order ID
*  @return void
*/
function tk_thankyou_page_get_order($order_id) {
	// Get an instance of the WC_Order object
	$order = wc_get_order( $order_id );
	
	// start the session so we can use the _SESSION variable
	session_start();
	
	// only execute this when the order is paid with Tikkie Fast Checkout
	if ($order->get_payment_method() == 'tikkie') {
		$transaction_id = $order->get_transaction_id();

		// init API class and request
		$tikkie = new TikkieAPI();
		
		$rOrder = $tikkie->get_order( $transaction_id );

		// if the user is logged in, connect the order to the account
		if ($user_id = get_current_user_id()) {
			// connect order to user
			$order->set_customer_id($user_id);
			$order->save();
		}
		
		// check if there are errors
		$result = array('errors'=>array());
		if (!empty($rOrder->errors)) {
			foreach($rOrder->errors as $error) {
				$result['errors'][] = $error->message;
				tk_log("API error (get_order): {$order->get_id()}: {$error->message}");
			}
		} else { //otherwise the retrieval was successful

			// set address for the order
			tk_set_order_address_data($order, $rOrder);

			// get the gateway settings
			$gateway = new WC_Gateway_Tikkie;
			
			// only update order status on the browser where the session was started
			if (isset($_SESSION['tk_order_id']) && $_SESSION['tk_order_id'] == $order_id) {
				
				// now, set the order status if the order is already paid and not already the correct status
				if($rOrder->status == 'PAID' && $order->get_status() !== str_replace('wc-','',$gateway->order_status)) {
					$order->update_status($gateway->order_status, __('Order status set by Tikkie Fast Checkout (order received page)', 'tkfc'));
				}
			}
		}
	}
}
add_action('woocommerce_thankyou', 'tk_thankyou_page_get_order', 1, 1);

/**
*  @brief saves the address-data that is received from TK_API->get_order into the WC_Order
*  
*  @param object $wc_order WC_Order instance
*  @param array $tk_order array with the Tikkie Order
*  @return void
*  
**/
function tk_set_order_address_data($wc_order, $tk_order) {
	$address = array(
		'first_name' => $tk_order->payer->firstName,
		'last_name'  => $tk_order->payer->lastName,
		'company'    => $tk_order->payer->companyName,
		'email'      => $tk_order->payer->email,
		'phone'      => $tk_order->payer->phoneNumber,
		'address_1'  => $tk_order->payer->shippingAddress->street ." " .$tk_order->payer->shippingAddress->houseNumber.$tk_order->payer->shippingAddress->addition,
		'address_2'  => '',
		'city'       => $tk_order->payer->shippingAddress->city,
		'postcode'   => $tk_order->payer->shippingAddress->postalCode,
		'country'    => $tk_order->payer->shippingAddress->country
	);
	$wc_order->set_address( $address, 'billing' );
	$wc_order->set_address( $address, 'shipping' );
}

/**
*  Adds URL to plugin overview page linking to the plugin settings
*
*  @param array $links array with links
*  @return returns the new array with links
*/
function add_action_links($links) {
	$mylinks = array(
		'<a href="' . TK_ADMIN_BASE . '">Settings</a>',
	);
	return array_merge( $links, $mylinks );
}
add_filter( 'plugin_action_links_' . TK_PLUGIN_NAME, 'add_action_links' );

/**
*  Display the registration form after a tikkie payment if selected
*
*  @return void
**/
function tk_show_registration_form($order_id) {
	// session is already started in tk_thankyou_page_get_order
	
	// get the gateway settings
	$gateway = new WC_Gateway_Tikkie();

	// if this function is disabled: abort here.
	if( $gateway->register_on_thankyou != 'yes' || is_user_logged_in() || !isset($_SESSION['tk_order_id']) || $_SESSION['tk_order_id'] != $order_id ){
		return;
	}

	if(empty($_POST['email'])){
		$order = wc_get_order($_SESSION['tk_order_id']);
		$_POST['email'] = $order->get_billing_email();
	}

	// Re-set the priority of the default order details table to provide a priority
	remove_action('woocommerce_thankyou', 'woocommerce_order_details_table', 10);
	add_action('woocommerce_thankyou', 'woocommerce_order_details_table', 10);

	ob_start(); ?>

	<section class="tikkieRegister">
		<h2><?php esc_html_e( 'Register', 'woocommerce' ); ?></h2>
		<?php wc_print_notices(); ?>
		<form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?> >

			<?php do_action( 'woocommerce_register_form_start' ); ?>

			<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="reg_username"><?php esc_html_e( 'Username', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
					<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" /><?php // @codingStandardsIgnoreLine ?>
				</p>
			<?php endif; ?>

			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="reg_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
				<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" /><?php // @codingStandardsIgnoreLine ?>
			</p>

			<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>

				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="reg_password"><?php esc_html_e( 'Password', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
					<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" />
				</p>

			<?php endif; ?>

			<?php do_action( 'woocommerce_register_form' ); ?>

			<p class="woocommerce-FormRow form-row">
				<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
				<button type="submit" class="woocommerce-Button button" name="register" value="<?php esc_attr_e( 'Register', 'woocommerce' ); ?>"><?php esc_html_e( 'Register', 'woocommerce' ); ?></button>
			</p>

			<?php do_action( 'woocommerce_register_form_end' ); ?>

		</form>
	</section>

	<?php echo ob_get_clean();
}
add_action('woocommerce_thankyou', 'tk_show_registration_form', 20);

/**
*  Connect a newly registered user to a just made order
*
*  @param int $user_id User ID provided by WordPress just after user registration
*  @return void
**/
function tk_user_reg_after_payment($user_id) {
	session_start();
	if(!empty($_SESSION['tk_order_id'])){
		// connect order to user
		$order = wc_get_order($_SESSION['tk_order_id']);
		$order->set_customer_id($user_id);
		$order->save();

		// copy order addresses to the user
		copy_order_address_to_customer($_SESSION['tk_order_id'], $user_id);

		// add notice to user session
		wc_add_notice(__('Thanks for creating your account, login below to view your account and orders.','tkfc'));

		//redirect to my account page
		wp_redirect( get_permalink( get_option('woocommerce_myaccount_page_id')) );
		exit;
	}
}
add_action( 'user_register', 'tk_user_reg_after_payment', 10, 1 );

/**
*  Returns all shipping methods defined in Woocommerce as key => label
*
*  @return Array with all shipping methods
**/
function tk_get_shipping_methods() {
	$result = array();
	foreach(WC()->shipping->get_shipping_methods() as $key => $method) {
		$result[$key] = $method->method_title;
	}
	return $result;
}

/**
*  Copy the woocommerce order address data to the wordpress user
*
*  @param int $order_id WooCommerce order ID
*  @param int $user_id  WordPress user ID
*  @return void
**/
function copy_order_address_to_customer($order_id, $user_id) {
	$order = new WC_Order($order_id);

	update_user_meta( $user_id, 'billing_first_name', $order->get_billing_first_name() );
	update_user_meta( $user_id, 'billing_last_name', $order->get_billing_last_name() );
	update_user_meta( $user_id, 'billing_company', $order->get_billing_company() );
	update_user_meta( $user_id, 'billing_address_1', $order->get_billing_address_1() );
	update_user_meta( $user_id, 'billing_address_2', $order->get_billing_address_2() );
	update_user_meta( $user_id, 'billing_city', $order->get_billing_city() );
	update_user_meta( $user_id, 'billing_state', $order->get_billing_state() );
	update_user_meta( $user_id, 'billing_postcode', $order->get_billing_postcode() );
	update_user_meta( $user_id, 'billing_country', $order->get_billing_country() );
	update_user_meta( $user_id, 'billing_phone', $order->get_billing_phone() );

	update_user_meta( $user_id, 'shipping_first_name', $order->get_shipping_first_name() );
	update_user_meta( $user_id, 'shipping_last_name', $order->get_shipping_last_name() );
	update_user_meta( $user_id, 'shipping_company', $order->get_shipping_company() );
	update_user_meta( $user_id, 'shipping_address_1', $order->get_shipping_address_1() );
	update_user_meta( $user_id, 'shipping_address_2', $order->get_shipping_address_2() );
	update_user_meta( $user_id, 'shipping_city', $order->get_shipping_city() );
	update_user_meta( $user_id, 'shipping_state', $order->get_shipping_state() );
	update_user_meta( $user_id, 'shipping_postcode', $order->get_shipping_postcode() );
	update_user_meta( $user_id, 'shipping_country', $order->get_shipping_country() );
}

/**
* Shows wizard page
*  @return void
**/
function tk_options_page() {
	?>
	<div class="setup_container">
		<div class="top">
			<p><img src="<?php echo TK_PLUGIN_URL ;?>/assets/img/logo.svg" /></p>
		</div>
		<div class="body clearfix">
			<div class="steps">
				<div class="welcome"><?php echo __('Welcome ', 'tkfc'); ?></div>
				<div class="step1"><?php echo __('Step 1', 'tkfc'); ?></div>
				<div class="step2"><?php echo __('Step 2', 'tkfc'); ?></div>
				<div class="step3"><?php echo __('Step 3', 'tkfc'); ?></div>
			</div>
			<h2><?php echo __('Tikkie Fast Checkout', 'tkfc'); ?></h2>
			<?php if( !isset($_GET["wizard"]) ) {?>
				<p><?php echo __('Enable customers of your webshop to checkout without having the customer entering their details like email and address information.', 'tkfc'); ?></p>
				<p><?php echo __('You can go directly to settings or use the wizard to guide you through the most essential settings.', 'tkfc'); ?></p>
				<div class="buttons_container">
					<div class="midleft"><a class="button" href="<?php echo TK_ADMIN_BASE;?>"><?php echo __('Go directly to settings','tkfc');?></a>
					</div>
					<div class="midright"><a class="button" href="<?php echo TK_PLUGIN_SETUP_URL?>&amp;wizard=1"><?php echo __('Use the wizard','tkfc');?></a>
					</div>
				</div>

			<?php }elseif( isset($_GET["wizard"]) && $_GET["wizard"] == 1)  {?>
				<p><?php echo __('The most important part to actually start working with Tikkie Fast Checkout, is that you need to set your API-key and merchant token.', 'tkfc'); ?></p>
				<form method="post" action="">
					<label for="this"><?php echo __('API-key:','tkfc');?></label>
					<input class="input-text regular-input required" type="text" name="woocommerce_tikkie_tkfc_api_key" id="woocommerce_tikkie_tkfc_api_key" placeholder="">
					<label for="this"><?php echo __('Test API-key:','tkfc');?></label>
					<input class="input-text regular-input required" type="text" name="woocommerce_tikkie_tkfc_test_api_key" id="woocommerce_tikkie_tkfc_test_api_key" placeholder="">
					<p><?php echo __('Tikkie Fast Checkout uses a so called merchant token for authentication.', 'tkfc'); ?></p>
					<label for="this"><?php echo __('Merchant token:','tkfc');?></label>
					<input class="input-text regular-input required" type="text" name="woocommerce_tikkie_tkfc_merchant_token" id="woocommerce_tikkie_tkfc_merchant_token"  placeholder="">
					<label for="this"><?php echo __('Test Merchant token:','tkfc');?></label>
					<input class="input-text regular-input required" type="text" name="woocommerce_tikkie_tkfc_test_merchant_token" id="woocommerce_tikkie_tkfc_test_merchant_token" placeholder="">

					<div class="buttons_container">
						<div class="midleft"><a class="button previous" href="<?php echo TK_PLUGIN_SETUP_URL;?>"><?php echo __('previous','tkfc');?></a>
						</div>
						<div class="midright"><a class="button wizard_next step1" href="<?php echo TK_PLUGIN_SETUP_URL?>&amp;wizard=2"><?php echo __('next','tkfc');?></a>
						</div>
					</div>
				</form>
				<div class="tkfc_form_error"></div>
			<?php }elseif( isset($_GET["wizard"]) && $_GET["wizard"] == 2)  {?>
				<p><?php echo __('Choose whether you want to show the Tikkie Fast Checkout button on the product detail page and/or on the cart page.','tkfc');?></p>

				<form method="post" action="" class="step2">
					<div>
						<label for="this"><?php echo __('Show on product detail page','tkfc');?></label>
						<input class="" type="checkbox" name="woocommerce_tikkie_proddetail_enabled" id="woocommerce_tikkie_proddetail_enabled" value="yes" checked="checked">
					</div>
					<div>
						<label for="this"><?php echo __('Show on cart page','tkfc');?></label>
						<input class="required" type="checkbox" name="woocommerce_tikkie_cart_enabled" id="woocommerce_tikkie_cart_enabled" value="yes" checked="checked">
					</div>
					<hr>
					<div class="termsofservice">
						<label for="this"><?php echo __('Show terms of service','tkfc');?></label>
						<input class="required" type="checkbox" name="woocommerce_tikkie_termsofservice_enabled" id="woocommerce_tikkie_termsofservice_enabled" value="yes" checked="checked">
					</div>
					<div>
						<label for="this"><?php echo __('Terms of service link (including http(s)://)','tkfc');?></label>
						<input class="input-text regular-input" type="text" name="woocommerce_tikkie_termsofservice_link" id="woocommerce_tikkie_termsofservice_link"  placeholder="">
					</div>
					<div class="buttons_container">
						<div class="midleft"><a class="button previous" href="<?php echo TK_PLUGIN_SETUP_URL?>&amp;wizard=1"><?php echo __('previous','tkfc');?></a>
						</div>
						<div class="midright"><a class="button wizard_next step2" href="<?php echo TK_ADMIN_BASE ?> "><?php echo __('finish','tkfc');?></a>
						</div>
					</div>
				</form>
				<div class="tkfc_form_error"></div>
			<?php } ?>

		</div>
	</div>
	<?php
}
/**
 *  @brief Adds new WP_Cron schedules
 *  
 *  @param [array] $schedules contains the current schedules
 *  @return Return the new schedules
 *  
 */
function tk_cron_schedules($schedules) {
	$schedules['tk_threeminutes'] = array(
		'interval' => 180, // 3 * 60
		'display' => __('Every three minutes', 'tkfc'),
	);
	return $schedules;
}
add_filter('cron_schedules','tk_cron_schedules'); 