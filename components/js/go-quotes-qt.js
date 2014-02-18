(function(){
	jQuery.each( go_quote_types, function( key, quote_type ) {
		var shortcode_open = '[' + quote_type,		
			shortcode_close = '[/' + quote_type + ']';
			if ( quote_type != 'quote' ) {
				shortcode_open += ' attribution=""';
			}//end if
			shortcode_open += ' person=""]',

		QTags.addButton( 'go-quotes-' + quote_type, quote_type, shortcode_open, shortcode_close );
	});
})();
