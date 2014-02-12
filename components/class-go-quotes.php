<?php

class Go_Pullquote
{
	private $_component     = '';
	private $content = '';

	/**
	 * Initialize the plugin and register hooks.
	 */
	public function __construct()
	{
		add_shortcode( 'pullquote', array( $this, 'shortcode' ) );
	} // end __construct

	/**
	 * WordPress shortcode handler.  Populate class variables with attributes
	 * @param array $atts
	 *              'side' which can be left, right or full
	 *              'quotes' turns on or off enclosing quotes
	 *              'attribution' adds an attribution block below the quote 
	 * @param string $content
	 * @return string
	 */
	public function shortcode( $atts, $content = null )
	{
		if ( is_null( $content ) )
		{
			//bail if no content
			return;
		}//end if

		$attributes =  shortcode_atts( array(
					'attribution' => FALSE,
					'quotes'      => FALSE,
					'side'        => 'full',
				), 
		$atts );

		$side = 'pullquote ' . esc_attr( $attributes['side'] );

		$class = ( ! $attributes['quotes'] ) ? 'content' : 'content quotes';

		ob_start();
		?>
		<div class="<?php echo $side; ?>">
			<p class='<?php echo $class; ?>'>
				<?php
				echo esc_html( $content );
				if ( $attributes['attribution'] && ! $attributes['quotes'] )
				{
					?>
					<cite>- 
						<?php
						echo esc_html( $attributes['attribution'] );
						?>
					</cite>
					<?php
				}//end if
			?>
			</p>
			<?php
			if ( $attributes['attribution'] && $attributes['quotes'] )
			{
				?>
				<cite>- 
					<?php
					echo esc_html( $attributes['attribution'] );
					?>
				</cite>
				<?php
			}//end if
			?>
		</div>
		<?php
		return ob_get_clean();
	} // end shortcode
}// end class
