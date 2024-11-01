<?php

if (!class_exists('TikkieAPI')) {
	/**
	*  Tikke gateway class (extends WC_Payment_Gateway)
	**/
	class TikkieAPI {

		protected $sandboxURL = 'https://api-sandbox.abnamro.com/v1/tikkie/fastcheckout/';
		protected $liveURL = 'https://api.abnamro.com/v1/tikkie/fastcheckout/';
		protected $apiKey;
		protected $merchantId;
		protected $live;

		/**
		* Constructor
		**/
		public function __construct() {
			$gateway = new WC_Gateway_Tikkie;
			$this->apiKey = $gateway->get_api_key();
			$this->merchantId = $gateway->get_merchant_token();
			$this->live = $gateway->live;
		}
		/**
		*  Does the actual request
		*
		*  @param string $endpoint the API endpoint
		*  @param array $data regular array (will be json_encoded in function)
		*  @param array $type Type of request (GET/POST)
		*  @return The request response (will be json_decoded)
		*/
		protected function call($endpoint, $data = array(), $type = 'GET') {
			//set the correct environment (test/live)
			if ($this->live == 'yes') {
				$baseUrl = $this->liveURL;
			} else {
				$baseUrl = $this->sandboxURL;
			}

			//set url
			$url = $baseUrl.$endpoint;

			//set headers
			$jData = json_encode($data);
			$headers = array();
			$headers['API-Key'] = $this->apiKey;
			$headers['X-Merchant-Token'] = $this->merchantId;

			if ($type == 'GET') {
				$headers['Content-Type'] = 'application/json';
			}

			if ($type == 'POST') {
				$headers['Content-Type'] = 'application/json';
				$headers['Content-Length'] = strlen($jData);
				$headers['Accept'] = 'application/json';
			}

			$args = array(
				'method' => $type,
				'headers' => $headers, 
				'body' => $jData
			);
			$response = wp_remote_post($url, $args);
			$result = wp_remote_retrieve_body( $response );
			
			// check if the request has been succesful (20x HTTP code)
			$valid_http_codes = array(200,201);
			if( isset($response['response']['code']) ) {
				if (!in_array($response['response']['code'], $valid_http_codes)) {
					//some error occured with the API call
					tk_log('API error: for url: '.$url.' http_code: '.$response['response']['code']);
				}
			}
			$result = json_decode($result);

			return $result;
		}

		public function create_order($data = array()) {
			return $this->call('orders', $data, 'POST');
		}

		public function get_order($orderToken) {
			return $this->call('orders/'.$orderToken);
		}

		public function create_dummy_merchant($data = array()) {
			return $this->call('merchant', $data, 'POST');
		}
	}
}
