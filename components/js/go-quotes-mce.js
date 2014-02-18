( function($){
	tinymce.create('tinymce.plugins.go_quotes', {
		init: function(ed, url) {
			$.each( go_quotes, function( quote_type ) {
				console_log(quote_type);
				var mceCommand = 'mce' + quote_type,
					shortcode_open = '[' + quote_type + ' attribution="" person=""]',
					shortcode_close = '[/' + quote_type + ']';

				ed.addCommand(mceCommand, function(){
					var content = tinyMCE.activeEditor.selection.getContent();

					if( content ) {
						tinyMCE.activeEditor.selection.setContent( shortcode_open + content + shortcode_close );
					} else {
						tinyMCE.activeEditor.selection.setContent( shortcode_open );
					}
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