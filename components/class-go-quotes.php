<?php

class Go_Quotes
{
	public $slug     = 'go-contact';
	public $content = '';
	//counter variables
	public $blockquote_id = 0;
	public $inlinequote_id = 0;
	public $pullquote_id = 0;

	/**
	 * Initialize the plugin and register hooks.
	 */
	public function __construct()
	{
		add_action( 'admin_init', array( $this, 'add_buttons' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'action_enqueue_scripts' ) );

		add_action( 'init', array( $this, 'init' ) );
	} // end __construct

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
	public function config()
	{
		if ( ! isset( $this->config ) || ! $this->config )
		{
			$this->config = (object) apply_filters(
				'go_config',
				array(
					'quote_types' => array(
						'blockquote',
						'pullquote',
						'quote',
					),
					'taxonomy'    => 'person',
					'default_url' => 'http://search.local.gostage.it/?s=',
				),
				$this->slug
			);
		} // END if

		return $this->config;
	} // END config

	/** 
	* Load js to add quicktags buttons
	*/
	public function action_enqueue_scripts()
	{
		wp_enqueue_script( 'go-quotes-qt', plugins_url( 'js/go-quotes-qt.js', __FILE__ ), array('quicktags') );
		wp_localize_script(
			'go-quotes-qt',
			'go_quote_types',
			array(
				'types' => $this->config()->quote_types
			)
		);
	}

	/**
	 * Pullquote shortcode handler.
	 * @param array $atts
	 *              'attribution' adds an attribution block below the quote
	 *              'person ' adds a person term
	 * @param string $content
	 * @return string
	 */
	public function pullquote_shortcode( $atts, $content = null )
	{
		//bail if no content
		if ( is_null( $content ) )
		{
			return;
		}//end if

		$attributes = shortcode_atts(
			array(
				'attribution' => FALSE,
				'person'      => FALSE,
				),
			$atts );

		$person = $attributes['person'] ? str_replace( ' ', '-', $attributes['person'] ) : FALSE;
		$attribution = $attributes['attribution'] ? $attributes['attribution'] : FALSE;

		ob_start();
		?>
		<aside class="pullquote right" id="pullquote-<?php echo ++$this->pullquote_id; ?>">
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
						if ( $person )
						{ //if we have a person term, wrap it in a cite link
							$term_link = is_wp_error( get_term_link( $person, $this->config()->taxonomy ) ) ? $this->config()->default_url . str_replace( ' ', '+', $attributes['person'] ) : get_term_link( $person, $this->config()->taxonomy );
							?>
							<a href="<?php echo $term_link ?>">
							<?php
						}//end if
						echo esc_html( $attribution ); ?>
						<?php
						if ( $person )
						{ 
							?>
							</a>
							<?php
						}//end if
						?>
					</cite>
				</footer>
				<?php 
			}//end if
			?>
			</aside>
		<?php
		return ob_get_clean();
	} // end pullquote_shortcode

	/**
	 * Blockquote shortcode handler.
	 * @param array $atts
	 *              'attribution' adds an attribution block at the bottom of the blockquote
	 *              'person ' adds a person term
	 * @param string $content
	 * @return string
	 */
	public function blockquote_shortcode( $atts, $content = null )
	{
		//bail if no content
		if ( is_null( $content ) )
		{
			return;
		}//end if

		$attributes = shortcode_atts(
			array(
				'attribution' => FALSE,
				'person'      => FALSE,
				),
			$atts );

		$person = $attributes['person'] ? str_replace( ' ', '-', $attributes['person'] ) : FALSE;
		$attribution = $attributes['attribution'] ? $attributes['attribution'] : FALSE;

		ob_start();
		?>
		<blockquote  id="blockquote-<?php echo ++$this->blockquote_id; ?>">
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
						if ( $person )
						{ //if we have a person term, wrap it in a cite link
							$term_link = is_wp_error( get_term_link( $person, $this->config()->taxonomy ) ) ? $this->config()->default_url . str_replace( ' ', '+', $attributes['person'] ) : get_term_link( $person, $this->config()->taxonomy );
							?>
							<a href="<?php echo $term_link ?>">
							<?php
						}//end if
						echo esc_html( $attribution ); ?>
						<?php
						if ( $person )
						{ 
							?>
							</a>
							<?php
						}//end if
						?>
					</cite>
				</footer>
				<?php
			}//end if
			?>
		</blockquote>
		<?php
		return ob_get_clean();
	} // end blockquote_shortcode

	/**
	 * Inline quote shortcode handler.
	 * @param array $atts
	 *              'person ' adds a person term, and a cite attribute to the q tag
	 * @param string $content
	 * @return string
	 */
	public function quote_shortcode( $atts, $content = null )
	{
		error_log('inline');
		//bail if no content
		if ( is_null( $content ) )
		{
			return;
		}//end if

		$attributes = shortcode_atts(
			array(
				'person'      => FALSE
				),
			$atts );

		$term_link = is_wp_error( get_term_link( $attributes['person'], $this->config()->taxonomy ) ) ? $this->config()->default_url . str_replace( ' ', '+', $attributes['person'] ) : get_term_link( $attributes['person'], $this->config()->taxonomy );

		$cite = $attributes['person'] ? "cite='" . $term_link . "'": '';

		$quote_string = "<q " . esc_html( $cite ) . "  id='quote-" . ++$this->inlinequote_id . "'>" . esc_html( $content ) . "</q>";
		
		return $quote_string;
	} // end quote_shortcode

	// TinyMCE shizzle
	
	/**
	 * Check for the rich text editor and user permissions
	 * before adding the filters for our custom buttons
	 */
	public function add_buttons()
	{
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) && 'true' != get_user_option('rich_editing') )
		{
			return;
		}//end if

		add_filter( 'mce_external_plugins', array( $this, 'tinymce_plugins' ) );
		add_filter( 'mce_buttons', array( $this, 'tinymce_buttons' ) );
	}//end add_buttons

	/**
	* Load the tinymce pluygin script
	*/
	public function tinymce_plugins( $plugins )
	{
		$plugins['go-quotes'] = plugins_url( 'js/go-quotes-mce.js', __FILE__ );
		return $plugins;
	}//end tinymce_plugin

	/**
	 * Add our custom buttons to the tinymce button array
	 */
	public function tinymce_buttons( $buttons )
	{
		//remove the default blockquote button - we're going to replace it
		unset($buttons['b-quote']);

		array_push( $buttons, 'separator' );

		foreach( $this->config()->quote_types as $quote_type )
		{
			array_push( $buttons, $quote_type );
		}//end foreach

		return $buttons;
	}//end tinymce_buttons

}// end class
