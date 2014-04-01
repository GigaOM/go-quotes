<?php

class GO_Quotes
{
	public $slug     = 'go-quotes';
	public $content = '';
	public $quote_id = 0;

	/**
	 * Initialize the plugin and register hooks.
	 */
	public function __construct()
	{
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'init', array( $this, 'init' ) );
		add_filter( 'content_save_pre', array( $this, 'content_save_pre' ), 10, 1 );
	}// end __construct

	/**
	 * Functions and actions to run on init
	 */
	public function init()
	{
		add_shortcode( 'pullquote', array( $this, 'pullquote_shortcode' ) );
		add_shortcode( 'quote', array( $this, 'quote_shortcode' ) );
		add_shortcode( 'blockquote', array( $this, 'blockquote_shortcode' ) );
	}

	/**
	 *	Singleton for config data
	 */
	private function config(  $key = NULL )
	{
		if ( ! isset( $this->config ) || ! $this->config )
		{
			$this->config = apply_filters(
				'go_config',
				array(
					'quote_types' => array(
							'blockquote',
							'pullquote',
							'quote',
						),
					'taxonomy'    => 'person',
				),
				$this->slug
			);
		}// end if

		if ( $key )
		{
			return isset( $this->config[ $key ] ) ? $this->config[ $key ] : NULL;
		}// end if

		return $this->config;
	}// end config


	/**
	 * lazy load the script config
	 */
	private function script_config( $key = NULL )
	{
		if ( ! isset( $this->script_config ) )
		{
			$this->script_config = apply_filters( 'go_config', array( 'version' => 1 ), 'go-script-version' );
		}// end if

		if ( $key )
		{
			return isset( $this->script_config[ $key ] ) ? $this->script_config[ $key ] : NULL;
		}// end if

		return $this->script_config;
	}// end script_config

	/**
	 * Load js to add quicktags buttons
	 */
	public function admin_enqueue_scripts()
	{
		//Bail if we're not on an edit page
		if( $hook != 'edit.php' )
		{
			return;
		}// end if

		wp_enqueue_script( 'edit_form_top', plugins_url( 'js/go-quotes-qt.js', __FILE__ ), array('quicktags'), $this->script_config( 'version' ) );

		wp_localize_script(
			'go-quotes-qt',
			'go_quote_types',
			array(
				'types' => $this->config( 'quote_types' )
			)
		);
	}// end admin_enqueue_scripts

	/**
	 * Render the block-level quotes.
	 * @param string $type - the quote type
	 * @param array $atts
	 *              'attribution' adds an attribution block at the bottom of the blockquote
	 *              'person ' adds a person term
	 * @param string $content - the actual quote content
	 * @return string
	 */
	public function render_quote( $type, $atts, $content )
	{
		//bail if no content
		if ( is_null( $content ) )
		{
			return;
		}// end if

		$attributes = shortcode_atts(
			array(
				'attribution' => FALSE,
				'person'      => FALSE,
				),
			$atts );

		$person = $attributes['person'] ? str_replace( ' ', '-', $attributes['person'] ) : FALSE;
		$attribution = $attributes['attribution'] ? $attributes['attribution'] : FALSE;

		ob_start();
		if ( 'pullquote' == $type )
		{
			$quote_block_start = '<aside class="pullquote" id="pullquote-<?php echo ++$this->quote_id; ?>">';
			$quote_block_end = '</aside>';
		}// end if
		elseif ( 'blockquote' == $type )
		{
			$quote_block_start = '<blockquote  id="blockquote-<?php echo ++$this->quote_id; ?>">';
			$quote_block_end = '</blockquote>';
		}// end elseif
		echo  $quote_block_start;
		?>
		<p class='content'>
			<?php
			echo esc_html( $content );
			?>
		</p>
		<?php
		if ( $attribution )
		{
			?>
			<footer>
				<cite>
					<?php
					//if we have a person term, wrap it in a cite link
					if ( $person )
					{
						if ( $cite_link = get_term_link( $person, $this->config( 'taxonomy' ) ) && ! is_wp_error( $cite_link ) )
						{
							?>
							<a href="<?php echo $cite_link; ?>">
							<?php
						}// end if
					}// end if
					echo esc_html( $attribution );
					if ( $person )
					{
						?>
						</a>
						<?php
					}// end if
					?>
				</cite>
			</footer>
			<?php
		}// end if
		echo $quote_block_end;
		return ob_get_clean();
	}// end render_quote

	/**
	 * Pullquote shortcode handler.
	 * @param array $atts
	 *              'attribution' adds an attribution block below the quote
	 *              'person ' adds a person term
	 * @param string $content - the actual quote content
	 * @return string
	 */
	public function pullquote_shortcode( $atts, $content )
	{
		return $this->render_quote( 'pullquote', $atts, $content );
	}// end pullquote_shortcode

	/**
	 * Blockquote shortcode handler.
	 * @param array $atts
	 *              'attribution' adds an attribution block at the bottom of the blockquote
	 *              'person ' adds a person term
	 * @param string $content - the actual quote content
	 * @return string
	 */
	public function blockquote_shortcode( $atts, $content = null )
	{
		return $this->render_quote( 'blockquote', $atts, $content );
	}// end blockquote_shortcode

	/**
	 * Inline quote shortcode handler.
	 * @param array $atts
	 *              'person ' adds a person term, and a cite attribute to the q tag
	 * @param string $content - the actual quote content
	 * @return string
	 */
	public function quote_shortcode( $atts, $content = null )
	{
		//bail if no content
		if ( is_null( $content ) )
		{
			return;
		}// end if

		$attributes = shortcode_atts(
			array(
				'person'      => FALSE
				),
			$atts );

		$term_link = is_wp_error( get_term_link( $attributes['person'], $this->config( 'taxonomy' ) ) ) ?  : get_term_link( $attributes['person'], $this->config( 'taxonomy' ) );

		$cite = $attributes['person'] ? "cite='" . $term_link . "'": '';

		$quote_string = "<q";
		if ( $person )
		{ //if we have a person term, wrap it in a cite link
			if ( $cite_link = get_term_link( $person, $this->config( 'taxonomy' ) ) && ! is_wp_error( $cite_link ) )
			{
				$quote_string .= " cite='" . $cite_link . "'";
			}
		}// end if
		$quote_string .= " id='quote-" . ++$this->quote_id . "'>" . esc_html( $content ) . "</q>";

		return $quote_string;
	}// end quote_shortcode

	public function content_save_pre( $content )
	{
		// check that this isn't an autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		{
			return;
		}//end if

		/*
		* regex out the shortcode args
		* pattern needs three slashes because that's what works:
		* wp escapes quotes for sql prior to this
		*/
		$pattern = '/(?<=person=\\\["\'])(\w+\s?\w+)(?=\\\["\'])/';
		preg_match_all ( $pattern , $content, $matches );

		//remove duplicate terms before we loop through them
		$terms = array_unique( $matches );

		$post_id = get_the_id();

		//append the term(s)
		foreach ( $terms as $term )
		{
			wp_set_post_terms( $post_id, $term, $this->config( 'taxonomy' ), TRUE );
		}// end foreach

		return $content;
	}// end add_person_term

	/* TinyMCE shizzle */

	/**
	 * Check for the rich text editor before adding the filters for our custom buttons
	 * NOTE: this won't work until we have button images
	 */
	public function admin_init()
	{
		//Bail if we're not on an edit page - or using the correct editor
		if( $hook != 'edit.php' || 'true' != get_user_option('rich_editing') )
		{
			return;
		}// end if

		add_filter( 'mce_external_plugins', array( $this, 'mce_external_plugins' ) );
		add_filter( 'mce_buttons', array( $this, 'mce_buttons' ) );
	}// end admin_init

	/**
	 * Load the tinymce pluygin script
	 */
	public function mce_external_plugins( $plugins )
	{
		$plugins['go-quotes'] = plugins_url( 'js/go-quotes-mce.js', __FILE__ );
		return $plugins;
	}// end mce_external_plugins

	/**
	 * Add our custom buttons to the tinymce button array
	 */
	public function mce_buttons( $buttons )
	{
		//remove the default blockquote button - we're going to replace it
		unset($buttons['b-quote']);
		array_push( $buttons, 'separator' );

		foreach( $this->config( 'quote_types' ) as $quote_type )
		{
			array_push( $buttons, $quote_type );
		}// end foreach

		return $buttons;
	}// end mce_buttons

}// end class
