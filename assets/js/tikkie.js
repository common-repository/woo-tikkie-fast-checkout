jQuery( document ).ready(function($) {
	$('body').on('click', '.tikkieBtn', function(e){
		//define the clicked button
		elem = $(this);
		//check if button is disabled
		if (!$(this).hasClass('disabled')) {
			// mark the button as disabled
			$(this).addClass('disabled');

			//get the product-id if on product detail page
			var productid = $(this).data('productid');
			var type = $(this).data('type');

			var variationid = '';

			// get woocommerce variation-form data
			var data = $('form.cart :input[name!=add-to-cart]').serialize(); //ignore the add-to-cart variable, otherwise woocommerce will trigger and add the product to the cart

			//handle variable products
			if (type == 'variable') {
				variationid = $('input[name="variation_id"]').val();

				//check if a variation is selected
				if (variationid == 0) {
					//clear lock and show error message
					tk_reenable_button();
					$(this).after('<p class="tkfc_error">'+ tikkie.no_variation_selected_error +'</p>');
					return;
				}
			} else if (type == 'simple') {
				data += '&product_id=' + productid;
			} else if (type == 'bundle') {
				data += '&product_id=' + productid;
			}

			//do ajax call to create the order in tikkie API
			$.ajax({
				method: "POST",
				url: tikkie.admin_url + '?action=tikkie-create-order',
				data: data,
				success: function(result, status, xhr) {
					// try json parsing (and catch the errors)
					try {
						result = JSON.parse(result);
						jsonValid = true;
					} catch (err) {
						jsonValid = false;
						jsonError = err.message;
					}
					
					tk_reenable_button();
					//check if there are errors in the API call
					if (result.errors != undefined ) {
						elem.after('<p class="tkfc_error">'+ result['errors'][0] +'</p>');
					} else if (!jsonValid) {
						elem.after('<p class="tkfc_error">'+ jsonError +'</p>');
					} else {
						// if there are no errors, redirect
						window.location.href = result.checkoutUrl;
					}
				},
				error: function(xhr, status, error) {
					tk_reenable_button();
					elem.after('<p class="tkfc_error">'+ tikkie.checkout_error +'</p>');
				}
			});

		}
	});
	// reenables the button and removed the loadingscreen
	function tk_reenable_button() {
		$('.tikkieBtn').removeClass('disabled');
	}
});
