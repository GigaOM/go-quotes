<?php

class Go_Quotes
{
	private $_component     = '';
	private $content = '';

	/**
	 * Initialize the plugin and register hooks.
	 */
	public function __construct()
	{
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
		$attribution = ! $attributes['person'] ? FALSE : esc_html( $attributes['attribution'] );

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
							<a href="search.gigaom.com/person/<?php esc_html( $person ); ?>/">
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

				if ( $attributes['attribution'] )
				{
					?>
					<footer>
						<cite>
							<a href="search.gigaom.com/person/<?php esc_html( $person ); ?>/">
								<?php echo esc_html( $attributes['attribution'] ); ?>
							</a>
						</cite>
					</footer>
					<?php
				}//end if
				?>
			</p>
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

}// end class
