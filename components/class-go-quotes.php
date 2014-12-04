<?php

class GO_Quotes
{
	//Set to TRUE when we are parsing the shortcodes on save.
	public $is_save_post = FALSE;
	//Store $post_ID from save_post action.
	public $post_id      = NULL;
	public $quote_id     = 0;
	public $slug         = 'go-quotes';
	public $post_type_name = 'go-quotes-pullquote';
	public $admin;

	/**
	 * Initialize the plugin and register hooks.
	 */
	public function __construct()
	{
		add_action( 'admin_print_footer_scripts', array( $this, 'custom_quicktags' ) );
		add_action( 'init', array( $this, 'init' ) );
		add_filter( 'quicktags_settings', array( $this, 'quicktag_settings' ), 10, 1 );
		add_filter( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		add_filter( 'post_type_link', array( $this, 'post_type_link' ), 11, 2 );

		if ( is_admin() )
		{
			$this->admin();
		}//end if
	}// end __construct

	/**
	 * Functions and actions to run on init
	 */
	public function init()
	{
		add_shortcode( 'pullquote', array( $this, 'pullquote_shortcode' ) );
		add_shortcode( 'quote', array( $this, 'quote_shortcode' ) );
		add_shortcode( 'blockquote', array( $this, 'blockquote_shortcode' ) );
		$this->register_post_type();
	}//end init

	public function admin()
	{
		if ( ! $this->admin )
		{
			require_once __DIR__ . '/class-go-quotes-admin.php';
			$this->admin = new GO_Quotes_Admin;
		}//end if

		return $this->admin;
	}//end admin

	/**
	 * Register the featured comment post_type
	 */
	public function register_post_type()
	{
		$taxonomies = array();

		$post_type_config = array(
			'labels' => array(
				'name' => 'Pull-quotes',
				'singular_name' => 'Pull-quote',
			),
			'supports' => array(
				'title',
				'excerpt',
			),
			'public' => TRUE,
			'show_in_menu' => TRUE,
			'has_archive' => TRUE,
			'rewrite' => array(
				'slug' => 'pull-quotes',
				'with_front' => FALSE,
			),
			'taxonomies' => array(),
		);

		register_post_type( $this->post_type_name, $post_type_config );
	}// END register_post_type

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
					'taxonomy'    => 'post_tag',
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

	public function custom_quicktags()
	{
		if ( wp_script_is( 'quicktags' ) )
		{
		?>
		<script type="text/javascript">
		QTags.addButton( 'qt-content-block', 'blockquote', '[blockquote person="" attribution=""]', '[/blockquote]', 'q', 'Blockquote' );
		QTags.addButton( 'qt-content-pull', 'pullquote', '[pullquote person="" attribution=""]', '[/pullquote]', 'p', 'Pull quote' );
		QTags.addButton( 'qt-content-quote', 'quote', '[quote person=""]', '[/quote]', null, 'Inline quote' );
		</script>
		<?php
		}//end if
	}//end custom_quicktags

	public function quicktag_settings( $qtInit )
	{
		//disable the block button, we're replacing it.
		$buttons = explode( ',', $qtInit['buttons'] );
		while ( ( $key = array_search( 'block', $buttons ) ) !== false )
		{
			unset( $buttons[ $key ] );
		}//end while
		$qtInit['buttons'] = implode( ',', $buttons );

		return $qtInit;
	}//end quicktag_settings

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
			$atts
		);

		//check if we're saving and add person terms
		if ( isset( $atts[ 'person' ] ) && $this->is_save_post )
		{
			wp_set_post_terms( $this->post_id, $atts[ 'person' ], $this->config( 'taxonomy' ), TRUE );
			return;
		}//end if

		$person = $attributes['person'] ? str_replace( ' ', '-', $attributes['person'] ) : FALSE;
		$attribution = $attributes['attribution'] ? $attributes['attribution'] : FALSE;

		/* Not going to bother escaping it here, since we're going to escape it 6 more times in this function...thanks VIP */
		$cite_link = $person ? get_term_link( $attributes['person'], 'person' ) : '';

		ob_start();
		if ( 'pullquote' == $type || 'blockquote' == $type )
		{
			//set some defaults
			$wrapped_content               = '<p class="content">' . wp_kses_post( $content ) . '</p>';
			$attribution_start     = '<footer><cite>';
			$attribution_end       = '</cite></footer>';
			$wrapper_start         = '';
			$wrapper_end           = '';

			switch ( $type )
			{
				case 'pullquote':
					$quote_block_start     = '<aside class="pullquote" id="quote-' . absint( ++$this->quote_id ) . '">';
					$quote_block_end       = '</aside>';
					$wrapper_start         = '<div class="boxed">';
					$wrapper_end           = '</div>';
					break;

				case 'blockquote':
					$quote_block_start     = '<blockquote id="quote-' . absint( ++$this->quote_id ) . '">';
					$quote_block_end       = '</blockquote>';
					break;

				default:
					$quote_block_start     = '<q id="quote-' . absint( ++$this->quote_id );
					$wrapped_content       = wp_kses_post( $content );
					$quote_block_end       = '</q>';
					break;
			}//end switch

			echo $quote_block_start;

			echo $wrapper_start;

			echo $wrapped_content;

			if ( $attribution )
			{
				echo $attribution_start;

				//if we have a person term, wrap it in a cite link
				if ( $person )
				{
					if ( ! is_wp_error( $cite_link ) )
					{
						?>
						<a href='<?php echo esc_url( $cite_link ); ?>'>
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
				echo $attribution_end;
			}// end if

			echo $wrapper_end;


			$quote_permalink = get_permalink( $post->ID ) . '#quote-' . $this->quote_id;
			//permalink
			$links = '<a href="' . esc_url( $quote_permalink ) . '" class="goicon icon-link-circled"></a>';
			$links = apply_filters( 'go_quotes_links', $links, $quote_permalink, $post->ID );

			echo '<div class="social">';
			echo wp_kses_post( $links );
			echo '</div>';

			echo $quote_block_end;
		}//end if
		else
		{
			// $type = 'quote'
			$quote_string = '<q';

			if ( $attributes['person'] )
			{
				//if we have a person term, wrap it in a cite link
				if ( ! is_wp_error( $cite_link ) )
				{
					$quote_string .= ' cite="' . esc_url( $cite_link ) . '"';
				}
			}// end if

			$quote_string .= ' id="quote-' . ++$this->quote_id . '">' . wp_kses_post( $content ) . '</q>';

			echo $quote_string;
		}//end else
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
		if ( is_feed() )
		{
			return '';
		}//end if

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
	public function blockquote_shortcode( $atts, $content = NULL )
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
	public function quote_shortcode( $atts, $content = NULL )
	{
		return $this->render_quote( 'quote', $atts, $content );
	}// end quote_shortcode

	/**
	 * Include pullquotes in the post_types of the main query
	 */
	public function pre_get_posts( $query )
	{
		if (
			! is_admin()
			&& $query->is_main_query()
			// @TODO: remove theme_preview when waterfall goes live
			&& go_theme()->theme_preview()
		)
		{
			$post_types = array_merge(
				(array) $query->query_vars['post_type'],
				array( is_singular() && isset( $query->queried_object->post_type ) ? $query->queried_object->post_type : 'post' ),
				array( $this->post_type_name )
			);

			$query->set( 'post_type', $post_types );
		}// END if

		return $query;
	}// END pre_get_posts


	/**
	 * Return the link to the original post for pullquote posts
	 */
	public function post_type_link( $permalink, $post )
	{
		if ( $post->post_type == $this->post_type_name )
		{
			//avoid recursion
			remove_filter( 'post_type_link', array( $this, 'post_type_link' ), 11, 2 );

			$permalink = get_permalink( $post->post_parent );

			add_filter( 'post_type_link', array( $this, 'post_type_link' ), 11, 2 );
		}//end if

		return $permalink;
	}//end post_type_link

}// end class


/**
 * GO_Alerts Singleton
 */
function go_quotes()
{
	global $go_quotes;

	if ( ! $go_quotes )
	{
		$go_quotes = new GO_Quotes;
	}//end if

	return $go_quotes;
}//end go_quotes
