<?php

class Go_Quotes
{
	private $_component     = '';
	private $content = '';
	private $quote_types = array(
		'blockquote',
		'pullquote',
		'quote',
		);

	/**
	 * Initialize the plugin and register hooks.
	 */
	public function __construct()
	{

		add_action( 'init', array( $this, 'add_buttons' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'action_enqueue_scripts' ) );
		add_action( 'admin_print_scripts', array( $this, 'action_print_scripts' ), 1 );

		add_shortcode( 'pullquote', array( $this, 'pullquote_shortcode' ) );
		add_shortcode( 'quote', array( $this, 'quote_shortcode' ) );
		add_shortcode( 'blockquote', array( $this, 'blockquote_shortcode' ) );
	} // end __construct

	/** 
	* Make quotes array available to javascript functions
	*/
	public function action_print_scripts()
	{
		?>
		<script type="text/javascript">
			window.go_quote_types = <?php echo json_encode( $this->quote_types ); ?>;
		</script>
		<?php
	}

	/** 
	* Load js to add quicktags buttons
	*/
	public function action_enqueue_scripts()
	{
		wp_enqueue_script( 'go-quotes-qt', plugins_url( 'js/go-quotes-qt.js', __FILE__ ), array('quicktags') );
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
		<aside class="pullquote right">
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
						<?php if ( $person )
						{ //if we have a person term, wrap it in a cite link
							?>
							<a href="<?php echo esc_url('http://search.gigaom.com/person/' . $person .'/' ); ?>">
							<?php
						}//end if
						echo esc_html( $attribution ); ?>
						<?php if ( $person )
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
		$attribution = $attributes['person'] ? $attributes['attribution'] : FALSE;

		ob_start();
		?>
		<blockquote>
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
						<a href="<?php echo esc_url('http://search.gigaom.com/person/' . $person . '/' ); ?>">
							<?php echo esc_html( $attribution ); ?>
						</a>
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

		$cite = $attributes['person'] ? "cite='http://search.gigaom.com/person/" . str_replace( ' ', '-', $attributes['person'] ) . "'": '';

		$quote_string = "<q " . esc_html( $cite ) . ">" . esc_html( $content ) . "</q>";
		
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

		foreach( $this->quote_types as $quote_type )
		{
			array_push( $buttons, $quote_type );
		}//end foreach

		return $buttons;
	}//end tinymce_buttons

}// end class
