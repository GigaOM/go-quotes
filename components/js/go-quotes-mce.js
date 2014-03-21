( function($){
	tinymce.create('tinymce.plugins.go_quotes', {
		init: function(ed, url) {
			$.each( go_quote_types, function( quote_type ) {
				var mceCommand = 'mce' + quote_type,
					shortcode_open = '[' + quote_type
					if ( quote_type != 'quote' ) {
						shortcode_open += ' attribution=""';
					}//end if
					shortcode_open += ' person=""]',
					shortcode_close = '[/' + quote_type + ']';

				ed.addCommand(mceCommand, function(){
					var content = tinyMCE.activeEditor.selection.getContent();

					if( content ) {
						tinyMCE.activeEditor.selection.setContent( shortcode_open + content + shortcode_close );
					} else { //end if
						tinyMCE.activeEditor.selection.setContent( shortcode_open );
					}//end else
				});

				ed.addButton( quote_type, {
					title: quote_type,
					cmd: mceCommand
				});
			});
		}
	});

	tinymce.PluginManager.add( 'go_quotes', tinymce.plugins.go_quotes );
} )(jQuery);