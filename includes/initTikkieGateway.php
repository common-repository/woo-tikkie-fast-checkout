<?php 

/**
*  initialize the Tikkie Payment Gateway
*  
*  @return void
**/

/**
*  Tikke gateway class (extends WC_Payment_Gateway)
**/
class WC_Gateway_Tikkie extends WC_Payment_Gateway {

	public $domain;
	
	/**
	* Constructor for the gateway.
	**/
	public function __construct() {
		
		$this->domain = 'tkfc';

		$this->id                 = 'tikkie';
		$this->icon               = apply_filters('woocommerce_tikkie_gateway_icon', '');
		$this->has_fields         = false;
		$this->method_title       = __( 'Tikkie Fast Checkout', $this->domain );
		$this->method_description = __( 'Allows payment through Tikkie Fast Checkout.', $this->domain );

		// Load the settings.
		if ( current_user_can( 'manage_options' ) ) {  
			$this->_update_option();             
			$this->init_form_fields();
			$this->init_settings();
		} else {
			$this->method_description = '<div class="alert alert-warning">'.__( 'Sorry, only administrators are allowed to change settings for this payment method.', $this->domain ).'</div>';
			if( isset($_GET["page"]) && $_GET["page"] == "wc-settings" && isset($_GET["section"]) && $_GET["section"] == "tikkie") {
				echo '<script>document.addEventListener("DOMContentLoaded", function(event) {var x = document.getElementsByClassName("woocommerce-save-button");x[0].disabled = true;});</script>';
			}
		}

		// Define user set variables
		$this->title                	= __('Tikkie Fast Checkout','tkfc');
		$this->live         		 	= $this->get_option( 'tkfc_live' );
		$this->api_key         	 		= $this->get_option( 'tkfc_api_key' );
		$this->test_api_key         	= $this->get_option( 'tkfc_test_api_key' );
		$this->merchant_token  	 		= $this->get_option( 'tkfc_merchant_token' );
		$this->test_merchant_token  	= $this->get_option( 'tkfc_test_merchant_token' );
		$this->expiration  		 		= $this->get_option( 'tkfc_expiration' );
		$this->description          	= $this->get_option( 'tkfc_description' );
		$this->proddetail_enabled   	= $this->get_option( 'tkfc_proddetail_enabled' );
		$this->cart_enabled         	= $this->get_option( 'tkfc_cart_enabled' );
		$this->register_on_thankyou 	= $this->get_option( 'tkfc_register_on_thankyou' );
		$this->instructions         	= $this->get_option( 'tkfc_instructions', $this->description );
		$this->order_status         	= $this->get_option( 'tkfc_order_status', 'completed' );
		// $this->shipping_cost 		 	= $this->get_option( 'tkfc_shipping_cost' );
		// $this->free_shipping_from		= $this->get_option( 'tkfc_free_shipping_from' );

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_tikkie', array( $this, 'thankyou_page' ) );

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_tikkie', array( $this, 'thankyou_page' ) );
	}		

	/**
	* Function to get last updated logfile-entries
	**/
	protected function _update_option() {
		$tkfc_options = get_option("woocommerce_tikkie_settings");
		//Alter the options array appropriately
		$tkfc_options['tkfc_log'] = tk_get_log();
		//Update entire array
		update_option("woocommerce_tikkie_settings", $tkfc_options);
	}

	/**
	* Returns the test or live API key based on the live/test-setting
	**/
	public function get_api_key() {
		return $this->live == 'yes' ? $this->api_key : $this->test_api_key;
	}
	
	/**
	* Returns the test or live Merchant-token based on the live/test-setting
	**/
	public function get_merchant_token() {
		return $this->live == 'yes' ? $this->merchant_token : $this->test_merchant_token;
	}

	/**
	* Initialise Gateway Settings Form Fields.
	**/
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', $this->domain ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Tikkie Fast Checkout', $this->domain ),
				'default' => 'yes',
				'priority' => 1,
			),
			'tkfc_live' => array(
				'title'   => __( 'Production mode', $this->domain ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable production mode', $this->domain ),
				'default' => ($this->get_option('tkfc_live')!='' ? $this->get_option('tkfc_live') : 'no' ),
				'description' => __('When this option is enabled, payments will be processed and test-mode wil be disabled', $this->domain),
				'priority' => 2,
			),
			'tkfc_api_key' => array(
				'title'       => __( 'Production API-key', $this->domain ),
				'type'        => 'text',
				'description' => __( 'This API-key is used for authentication in production mode', $this->domain ),
				'default'     => $this->get_option( 'tkfc_api_key'),
				'desc_tip'    => true,
				'priority' => 3,
			),
			'tkfc_merchant_token' => array(
				'title'       => __( 'Merchant token', $this->domain ),
				'type'        => 'text',
				'description' => __( 'The Tikkie Fast Checkout API uses a so called \'merchant token\' for authentication.', $this->domain ),
				'default'     => $this->get_option( 'tkfc_merchant_token'),
				'desc_tip'    => true,
				'priority' => 4,
			),
			'tkfc_test_api_key' => array(
				'title'       => __( 'Test API-key', $this->domain ),
				'type'        => 'text',
				'description' => __( 'This API-key is used for authentication in test mode', $this->domain ),
				'default'     => $this->get_option( 'tkfc_test_api_key'),
				'desc_tip'    => true,
				'priority' => 5,
			),
			'tkfc_test_merchant_token' => array(
				'title'       => __( 'Test Merchant token', $this->domain ),
				'type'        => 'text',
				'description' => __( 'The Tikkie Fast Checkout API uses a so called \'merchant token\' for authentication.', $this->domain ),
				'default'     => $this->get_option( 'tkfc_test_merchant_token'),
				'desc_tip'    => true,
				'priority' => 6,
			),
			'tkfc_proddetail_enabled' => array(
				'title'   => __( 'Show on product-detail page', $this->domain ),
				'type'    => 'checkbox',
				'label'   => __( 'Show on product-detail page', $this->domain ),
				'default' => ($this->get_option( 'tkfc_proddetail_enabled')!='' ? $this->get_option( 'tkfc_proddetail_enabled') : 'yes'),
				'priority' => 7,
			),
			'tkfc_cart_enabled' => array(
				'title'   => __( 'Show on cart page', $this->domain ),
				'type'    => 'checkbox',
				'label'   => __( 'Show on cart page', $this->domain ),
				'default' => ($this->get_option( 'tkfc_cart_enabled')!='' ? $this->get_option( 'tkfc_cart_enabled') : 'yes'),
				'priority' => 8,
			),
			'tkfc_termsofservice_enabled' => array(
				'title'   => __( 'Show terms of service', $this->domain ),
				'type'    => 'checkbox',
				'label'   => __( 'Show terms of service', $this->domain ),
				'default' => ($this->get_option( 'tkfc_termsofservice_enabled')!='' ? $this->get_option( 'tkfc_termsofservice_enabled') : 'yes'),
				'priority' => 9,
			),
			'tkfc_termsofservice_text' => array(
				'title'       => __( 'Terms of service text', $this->domain ),
				'type'        => 'textarea',
				'description' => __( 'This text will be used when the terms of service has been enabled. You can define the link by placing your link text into square brackets ( [link text] )', $this->domain ),
				'default'     => __( 'If you continue, you will agree to our [terms of service]', $this->domain ),
				'desc_tip'    => true,
				'priority' => 10,
			),
			'tkfc_termsofservice_link' => array(
				'title'       => __( 'Terms of service link', $this->domain ),
				'type'        => 'text',
				'description' => __( 'This is the URL where the terms and conditions are located. (including http(s)://)', $this->domain ),
				'default'     => '',
				'desc_tip'    => true,
				'priority' => 11,
			),
			'tkfc_register_on_thankyou' => array(
				'title'       => __( 'Show registration form on thank you page', $this->domain ),
				'type'        => 'checkbox',
				'description' => __( 'Let the user create an account after payment', $this->domain ),
				'default' 	  => ($this->get_option( 'tkfc_register_on_thankyou')!='' ? $this->get_option( 'tkfc_register_on_thankyou') : 'no'),
				'priority' => 12,
			),
			'tkfc_expiration' => array(
				'title'       => __( 'Tikkie Order Expiration time', $this->domain ),
				'type'        => 'text',
				'description' => __( 'Expiration of the order in seconds, e.g. 900 when order expires in 15 minutes. (between 60-1800)', $this->domain ),
				'default'     => 900,
				'desc_tip'    => true,
				'priority' => 13,
			),
			'tkfc_order_status' => array(
				'title'       => __( 'Order Status', $this->domain ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'description' => __( 'Choose whether status you wish after checkout.', $this->domain ),
				'default'     => 'wc-pending',
				'desc_tip'    => true,
				'options'     => tk_get_order_statuses(),
				'priority' => 14,
			),
			'tkfc_instructions' => array(
				'title'       => __( 'Instructions', $this->domain ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page and emails.', $this->domain ),
				'default'     => '',
				'desc_tip'    => true,
				'priority' => 15,
			),
			'tkfc_log' => array(
				'title'       => __( 'Log file', $this->domain ),
				'type'        => 'textarea',
				'description' => __( 'Showing log entries chronologically', $this->domain ),
				'default'     => tk_get_log(),
				'class'       => 'readonly',
				'disabled'    => true,
				'desc_tip'    => true,
				'priority' => 16,
			),
		);
	}

	/**
	* Output for the order received page.
	**/
	public function thankyou_page() {
		if ( $this->instructions ) {
			echo wpautop( wptexturize( $this->instructions ) );
		}

	}


	/**
	* Add content to the WC emails.
	*
	* @access public
	* @param WC_Order $order
	* @param bool $sent_to_admin
	* @param bool $plain_text
	**/
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && 'tikkie' === $order->get_payment_method() && $order->has_status( 'on-hold' ) ) {
			echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
		}
	}
}

/**
*  Brief description
*  
*  @param [in] $methods Description for $methods
*  @return Return description
*/
function tk_add_tikkie_gateway_class( $methods ) {
	$methods[] = 'WC_Gateway_Tikkie'; 
	return $methods;
}
add_filter( 'woocommerce_payment_gateways', 'tk_add_tikkie_gateway_class' );

/**
*  Wrapper function on wc_get_order_statuses 
*  
*  @return Return order statusses in the same format as wc_get_order_statuses
**/
function tk_get_order_statuses() {
	// get wc order statuses
	$statuses = wc_get_order_statuses();
	
	// remove these from the list
	unset($statuses['wc-failed']);
	unset($statuses['wc-refunded']);
	unset($statuses['wc-cancelled']);
	unset($statuses['wc-pending']);
	
	return $statuses;
}