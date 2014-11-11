var go_quotes_pullquote = {};

(function( $ ) {
	go_quotes_pullquote.init = function() {
		$( document ).on( 'click', '.wp-list-table.pullquotes .pullquote .submitdelete', function( e ) {
			e.preventDefault();

			var $link = $( this );
			var $row = $link.closest( 'tr' );

			$row.block({
				message: '<span>Please wait...</span>'
			});

			var jqxhr = $.get( $link.attr( 'href' ) );

			jqxhr.done( function() {
				$row.fadeOut( 'fast' );
			});
		});
	};

	$( function() {
		go_quotes_pullquote.init();
	});
})( jQuery );
