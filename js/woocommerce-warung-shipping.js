jQuery(function($) {


	if(typeof jne_version !== 'undefined') {
		var trigger_chosen = 'liszt:updated';
	} else {
		var trigger_chosen = 'chosen:updated';
	}

	$( 'body' ).bind( 'state_to_city_changed', function() {
		if($().chosen)
			$( 'select#billing_city, select#shipping_city' ).chosen().trigger( trigger_chosen );
		else if($().select2)
			$( 'select#billing_city, select#shipping_city' ).select2();
	});
	$( 'body' ).bind( 'country_to_city_changed', function() {
		if($().chosen)
			$( 'select#billing_city, select#shipping_city' ).chosen().trigger( trigger_chosen );
		else if($().select2)
			$( 'select#billing_city, select#shipping_city' ).select2();
	});
	$( 'body' ).bind( 'load_billing_city', function() {
		if($().chosen)
			$( 'select#billing_city' ).chosen().trigger( trigger_chosen );
		else if($().select2)
			$( 'select#billing_city' ).select2();
	});
	$( 'body' ).bind( 'load_shipping_city', function() {
		if($().chosen)
			$( 'select#shipping_city' ).chosen().trigger( trigger_chosen );
		else if($().select2)
			$( 'select#shipping_city' ).select2();
	});	

	// wc_checkout_params is required to continue, ensure the object exists
	if (typeof wc_checkout_params === "undefined")
		return false;

	if($().chosen) {
		$("select#billing_city, select#shipping_city").chosen({search_contains: true});
	} else if($().select2) {
		$("select#billing_city, select#shipping_city").select2(
			{
				ajax: {
					method: 'POST',
					url: ajax_object.ajax_url,
					dataType: 'json',
					delay: 250,
					data: function (params) {
						return {
							q: params.term, // search term
							action: 'ongkir',
							page: params.page
						};
					},
					processResults: function (data, page) {
						// parse the results into the format expected by Select2.
						// since we are using custom formatting functions we do not need to
						// alter the remote JSON data
						return {
							results: data.results
						};
					},
					cache: true
				},
				escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
				minimumInputLength: 3,
			}
		);
	}

});