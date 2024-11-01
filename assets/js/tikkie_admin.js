jQuery( document ).ready(function($) {

	function checkFormFields(elm) {
		var form = $(elm).closest('form');	
		$('.required', form).each(function() {
			$(this).removeClass('not_filled');
			if($(this).val()=='') {
				$(this).addClass('not_filled');
			}
		});
		if( $('.not_filled').length > 0 ) {
			return false;
		}
		return true;
	}

	$('.buttons_container a.button').not('.previous').on('click', function(e) {
		e.preventDefault();
		if( checkFormFields( $(this) ) ) {
			var href = $(this).attr('href');
			window.location.href = href;
		}
	});

	$('textarea.readonly').attr('readonly','readonly');
	if( $('#woocommerce_tikkie_tkfc_log').length > 0) {
		$('#woocommerce_tikkie_tkfc_log').scrollTop( $('#woocommerce_tikkie_tkfc_log')[0].scrollHeight - $('#woocommerce_tikkie_tkfc_log').height());
	}
	
	// show/hide the test-api-key formfield based on setting
	function update_api_key_fields() {
		live_mode = $('#woocommerce_tikkie_tkfc_live').prop('checked');
		
		// check whether live or testmode is selected and hide the appropriate api-keys/merchant-token fields
		if (live_mode) {
			$('#woocommerce_tikkie_tkfc_test_api_key').parents('tr').hide();
			$('#woocommerce_tikkie_tkfc_test_merchant_token').parents('tr').hide();
			$('#woocommerce_tikkie_tkfc_live').parents('tr').find('.description').show();
		} else {
			$('#woocommerce_tikkie_tkfc_test_api_key').parents('tr').show();
			$('#woocommerce_tikkie_tkfc_test_merchant_token').parents('tr').show();
			$('#woocommerce_tikkie_tkfc_live').parents('tr').find('.description').hide();
		}
	}
	
	// bind it to changing the checkbox
	$('body').on('change','#woocommerce_tikkie_tkfc_live',function(){
		update_api_key_fields();
	});
	
	// and run on page-load
	update_api_key_fields();

	$('a.wizard_next').on('click', function(e) {
		e.preventDefault();
		if( checkFormFields( $(this) ) ) {
			var href = $(this).attr('href');
			//do ajax call 
			var form = $(this).closest('form');			
			data = form.serialize();
			var step;
			if( $(this).hasClass('step1') ) {
				step = 'step1';
			}
			if( $(this).hasClass('step2') ) {
				step = 'step2';
			}
			if( $(this).hasClass('step3') ) {
				step = 'step3';
			}			
			$.ajax({
				method: "POST",
				url: ajaxurl + '?action=tikkie_wizard_settings&wizard_setting=' + step + '',
				data: data,
				success: function(result, status, xhr) {		
					result = JSON.parse(result);					
					//check if there are errors in the API call
					if (result.errors != undefined ) { 
						$('.tkfc_form_error').html('<p class="tkfc_error">'+ result['errors'][0] +'</p>');
					} else {
						// if there are no errors, redirect
						window.location.href = href;						
					}
				},
				error: function(xhr, status, error) { 
					$('.tkfc_form_error').html('<p class="tkfc_error">'+ tikkie.checkout_error +'</p>'); 
				}
			});
			
		}
	});

	// functionality for wizard
	function getParameterByName(name, url) {
		if (!url) {
			url = window.location.href;
		}
		name = name.replace(/[\[\]]/g, "\\$&");
		var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
		results = regex.exec(url);
		if (!results) return null;
		if (!results[2]) return '';
		return decodeURIComponent(results[2].replace(/\+/g, ' '));
	}	

	if( $('.setup_container').length > 0 ) {
		if( getParameterByName('wizard') == null ) { // welcome
			$('.steps .welcome').addClass('purpled active');
		}
		if( getParameterByName('wizard') == '1' ) { 
			$('.steps .welcome').addClass('purpled').removeClass('active');
			$('.steps .step1').addClass('purpled active');
		}
		if( getParameterByName('wizard') == '2' ) { 
			$('.steps .welcome').addClass('purpled').removeClass('active');
			$('.steps .step1').addClass('purpled').removeClass('active');
			$('.steps .step2').addClass('purpled active');
		}
		if( getParameterByName('wizard') == '3' ) { 
			$('.steps .welcome').addClass('purpled').removeClass('active');
			$('.steps .step1').addClass('purpled').removeClass('active');
			$('.steps .step2').addClass('purpled').removeClass('active');
			$('.steps .step3').addClass('purpled active');
		}			
	}

});