;(function($) {
	
	// Gateway select change event
	$('.hide_class').hide();
	$('#satosms_gateway\\[sms_gateway\\]').on( 'change', function() {
		var self = $(this),
			value = self.val();
		$('.hide_class').hide();
		$('.'+value+'_wrapper').fadeIn();
	});

	// Trigger when a change occurs in gateway select box 
	$('#satosms_gateway\\[sms_gateway\\]').trigger('change');

	// handle send sms from order page in admin panale
	var w = $('.satosms_send_sms').width(),
		h = $('.satosms_send_sms').height(),
		block = $('#satosms_send_sms_overlay_block').css({
					'width' : w+'px',
					'height' : h+'px',
				});


	$( 'input#satosms_send_sms_button' ).on( 'click', function(e) {
		e.preventDefault();
		var self = $(this),
			textareaValue = $('#satosms_sms_to_buyer').val(),
			smsNonce = $('#satosms_send_sms_nonce').val(),
			orderId = $('input[name=order_id][type=hidden]').val(),
			data = {
				action : 'satosms_send_sms_to_buyer',
				textareavalue: textareaValue,
				sms_nonce: smsNonce,
				order_id: orderId
			};

		if( !textareaValue ) {
			return;
		}
		self.attr( 'disabled', true );
		block.show();
		$.post( satosms.ajaxurl, data , function( res ) {
			if ( res.success ) {
				$('div.satosms_send_sms_result').html( res.data.message ).show();
				$('#satosms_sms_to_buyer').val('');
				block.hide();
				self.attr( 'disabled', false );
			} else {
				$('div.satosms_send_sms_result').html( res.data.message ).show();	
				block.hide();
				self.attr( 'disabled', false );
			}
		});
	});


})(jQuery);