=== Tikkie Fast Checkout ===
Contributors: Tikkie
Tags: tikkie,woocommerce,payment,fast checkout,psp
Requires at least: 4.9.8
Tested up to: 5.3.2
Requires PHP: 7.0
Stable tag: 1.2.1
License URI: https://tikkie.me

This plugin enables the Tikkie Fast Checkout payment method for your WooCommerce shop

== Description ==

The Tikkie Fast Checkout plugin allows customers to quickly purchase your products with only three clicks. Add the Tikkie Fast Checkout button to your product detail page and/or cart overview and customers can purchase your products without having to create an account.

= Features & Compatibility =

* Add Tikkie Fast Checkout button to the product detail page
* Add Tikkie Fast Checkout button to the cart overview
* Compatible with WooCommerce Product Bundles
* Compatible with YITH WooCommerce Dynamic Pricing and Discounts Premium

To sign up for Tikkie Fast Checkout, visit the [registration page (in Dutch)](https://www.tikkie.me/aanvragen-supersnel-betalen)

For more information about Fast Checkout or pricing, visit the [information page (in Dutch)](https://www.tikkie.me/tikkie-voor-woocommerce)

== Installation ==

1. Install the plugin via the Wordpress repository or FTP
1. Fill out the application to receive a test API Key and Merchant Token
1. Enter them and configure the plugin
1. Try the plugin!

= How to go Live =
1. Become a Tikkie Business User
1. Receive your production API Key and Merchant Token
1. Enter them and configure the plugin
1. Activate production mode

== Screenshots ==

1. Fast Checkout button on a product detail page
2. Fast Checkout button on a cart overview
3. Fast Checkout page
4. Cart overview in Fast Checkout
5. Settings overview

== Changelog ==

= 1.2.1 =
Fixed status update cron on deleted orders

= 1.2.0 =
Tikkie Fast Checkout WordPress plugin launch

= 1.1.11 =
Fixed woocommerce_available_payment_gateways filter

= 1.1.10 =
Added test api key field in wizard

= 1.1.9 =
Added terms of service functionality
Fixed get_type error on the single detail page (Woocommerce < version 3.0.0)
Changed cURL functionallity to the wp_remote_post function
Added Sanitation POST/GET fields
Fixed cronjobs interference (tk_update_order_status_cron should now be properly removed with tk_unschedule_order_status_cron)
Increased minimum Expiration time to 180 seconds to prevent interference with the tk_update_order_status_cron

= 1.1.8 =
Fixed Tikkie-button not showing when only test-keys are entered, now correctly checks based on the chosen mode (test/live)

= 1.1.7 =
Added retrieving correct merchant-token for test-mode

= 1.1.6 =
Added test-merchant token to settings
Reordered API/Merchant settings

= 1.1.5 =
Fixed issue where step 2 was not being saved correctly in the settings wizard (and fixed related PHP-warning)
Added option for saving a seperate test API-key
Renamed test-mode to live-mode and swapped the checkbox (checked = live mode, unchecked = test-mode)
Reordered options in plugin settings
Renamed generic .label class to .normalLabel to prevent styling conflicts

= 1.1.4 =
Fixed tk_notification_webhook() to also set address-data
Fixed tk_update_order_status_cron() to set address-data before updating order-status
Updated logging to show how the order-status is updated

= 1.1.3 =
Fixed tk_get_order priority to make sure it runs before outputting the customer address-details
Renamed tk_get_order() to tk_thankyou_page_get_order()

= 1.1.2 =
Added error handling for parsing the tikkie-create-order AJAX-request
Rewrote cronjob for checking order-status, now checks every 3 minutes, until expiration is passed
Fixed bug where default Wordpress permalink-settings would result in an incorrect redirectUrl

= 1.1.1 =
Correctly packaged tikkie.css for fixing v1.0.7
We also now save customer-address data to Woocommerce in the 'expired-order' cronjob

= 1.1.0 =
Rewrote shipping-cost handling to use default Woocommerce-logic
Rewrote populating product-data sent to Tikkie to use actual cart-prices (should improve compatibility with discount plugins)
Added compatibility with 'YITH WooCommerce Dynamic Pricing and Discounts Premium' plugin

= 1.0.7 =
Fixed some styling issues that could occur with the tikkieBtn

= 1.0.6 =
Added compatibility for the Woocommerce Product Bundles plugin

= 1.0.5 =
Fixed VAT-calculations on shipping-costs

= 1.0.4 =
Removed pr() function which could conflict with other plugins (and is obviously not needed for production)

= 1.0.3 =
Fixed compatibility with the WooCommerce Chained Products plugin
Removed some unwanted order statuses from the settings page

= 1.0.2 =
Fixed bug where coupons were not applied in the Woocommerce Order
Fixed bug where order not paid with Tikkie were also making API-requests
Fixed order-statusses being changed twice
Fixed styling issue with the Fast Checkout button

= 1.0.1 =
Added support for Virtual Products
Added option to set minimum order value for Free Shipping

= 1.0.0 =
Initial release: November 16th, 2018

== Developer Documentation ==

The Fast Checkout button can be placed with the function tk_show_button(). It has a few parameters to customize how the button looks. 
The button can be placed anywhere you'd like in your template (for example in the header/footer), and according to the plugin settings it will be hidden/shown on the product detailpages and shopping cart page.

== FAQ ==

= How does the Tikkie Fast Checkout work? =

The integration of Tikkie Fast Checkout on your website enables your clients to experience a more smooth and fast checkout. Your client can skip the shopping cart and place an order without having to log in or fill in his/her address details. The Fast Checkout button can de added to the PDP (Product Detail Page) and/or placed in the shopping cart before the checkout page.

= How do I get started with Tikkie Fast Checkout? =

In this store you are able to download the plug-in. In order to receive your test keys, please fill in the [following form (in Dutch)](https://www.tikkie.me/aanvragen-supersnel-betalen). After having applied for the keys you will also receive instructions on how to configure the plug-in and finally go into production with the live keys.

= What are the costs? =

For Tikkie we have the following cost structure per transaction. Every month will be individually calculated:
| Transactions             | Price per Transaction |
|--------------------------|-----------------------|
| <101 Transactions        | € 0,25                |
| 101 - 500 Transactions   | € 0,20                |
| >500 Transactions        | € 0,15                |

= Where can I create my API Key/Merchant Token? =

In order to obtain the test Key/Merchant Token you will need to fill in the application form via the [following link (in Dutch)](https://www.tikkie.me/aanvragen-supersnel-betalen). Depending on whether you are already have a Tikkie account you will also receive the live Key/Merchant Token.

= Why does the plug-in not work when going to production? =

When going to production you have to make sure that you check the box that says ‘enable production mode’. A warning will appear in red to inform you that test-mode is disabled and that real payments will be processed from now on.

= How can I customize the appearance of the Fast Checkout button? =

Custom styling can either be done with custom CSS or by adding a class in the `tk_show_button` function.

= Why is my Fast Checkout button not shown? =

There could be a few reasons why the Fast Checkout button is not shown:
1. The payment method has not been activated
2. The show on cart/production detail page button is not checked
3. The API-Key and Merchant Token are not entered for the mode (test/production) you are in
4. The product is external


== Limitations ==

It is not possible to pay by Tikkie Fast Checkout on the Woocommerce checkout-page.
Tikkie Fast Checkout can currently only be used for orders shipped within The Netherlands.
Discounts not applied via coupons are currently not supported