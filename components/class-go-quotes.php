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

		add_shortcode( 'pullquote', array( $this, 'pullquote_shortcode' ) );
		add_shortcode( 'quote', array( $this, 'quote_shortcode' ) );
		add_shortcode( 'blockquote', array( $this, 'blockquote_shortcode' ) );
	} // end __construct

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

		$person = ! $attributes['person'] ? FALSE : str_replace( ' ', '-', $attributes['person'] );
		$attribution = ! $attributes['attribution'] ? FALSE : esc_html( $attributes['attribution'] );

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
							<a href="http://search.gigaom.com/person/<?php echo esc_html( $person ); ?>/">
							<?php
						}//end if
						echo $attribution; ?>
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

		$person = ! $attributes['person'] ? FALSE : str_replace( ' ', '-', $attributes['person'] );
		$attribution = ! $attributes['person'] ? FALSE : esc_html( $attributes['attribution'] );

		ob_start();
		?>
		<blockquote>
			<p class='content'>
				<?php
				echo esc_html( $content );
				?>
				</p>
				<?php
				if ( $attributes['attribution'] )
				{
					?>
					<footer>
						<cite>
							<a href="http://search.gigaom.com/person/<?php echo esc_html( $person ); ?>/">
								<?php echo esc_html( $attributes['attribution'] ); ?>
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

		$cite = ( ! $attributes['person'] ) ? '' : "cite='http://search.gigaom.com/person/" . str_replace( ' ', '-', $attributes['person'] ) . "'";

		ob_start();
		?>
		<q <?php echo $cite; ?>><?php echo esc_html( $content ); ?></q>
		<?php
		return ob_get_clean();
	} // end quote_shortcode

	// TinyMCE shizzle
	
	public function add_buttons()
	{
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) && 'true' != get_user_option('rich_editing') )
		{
			return;
		}//end if

		add_filter( 'mce_external_plugins', array( $this, 'tinymce_plugin' ) );
		add_filter( 'mce_buttons', array( $this, 'tinymce_buttons' ) );
	}//end add_buttons

	public function tinymce_plugin( $plugins )
	{
		$plugins['go-quotes'] = plugins_url( 'js/go-quotes-mce.js', __FILE__ );
		return $plugins;
	}//end tinymce_plugin

	public function tinymce_buttons( $buttons )
	{
		array_push( $buttons, 'separator' );

		foreach( $this->quote_types as $quote_type )
		{
			array_push( $buttons, $quote_type );
		}//end foreach

		return $buttons;
	}//end tinymce_buttons

	// init process for button control
	

}// end class
