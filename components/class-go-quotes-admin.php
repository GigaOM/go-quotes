<?php

class GO_Quotes_Admin
{
	public $post_type_name = 'go-quotes-pullquote';

	public function __construct()
	{
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'go_waterfall_options_meta_box', array( $this, 'go_waterfall_options_meta_box' ) );
		add_action( 'post_submitbox_start', array( $this, 'post_submitbox_start' ) );

		add_action( 'current_screen', array( $this, 'current_screen' ) );

		add_filter( 'go_guestpost_post_types', array( $this, 'go_guestpost_post_types' ) );
		add_filter( 'save_post', array( $this, 'save_post' ), 10, 2 );
		add_filter( 'go_waterfall_options_post_types', array( $this, 'go_waterfall_options_post_types' ) );
	}// END __construct

	public function current_screen( $screen )
	{
		if ( ! $screen )
		{
			return;
		}//end if

		if ( 'edit-go-quotes-pullquote' != $screen->id )
		{
			return;
		}//end if

		add_action( 'manage_posts_custom_column', array( $this, 'manage_posts_custom_column' ), 10, 2 );
		add_filter( 'manage_posts_columns', array( $this, 'manage_posts_columns' ) );
	}//end current_screen

	/**
	 * hooked to the admin_enqueue_scripts
	 */
	public function admin_enqueue_scripts()
	{
		if ( ! function_exists( 'go_ui' ) )
		{
			return;
		}//end if

		go_ui();

		$script_config = apply_filters( 'go_config', array( 'version' => 1 ), 'go-script-version' );

		wp_register_script(
			'go-quotes-pullquote',
			plugins_url( 'js/lib/go-quotes-pullquote.js', __FILE__ ),
			array(
				'jquery',
				'blockui',
			),
			$script_config['version'],
			TRUE
		);

		wp_enqueue_script( 'go-quotes-pullquote' );
	}//end admin_enqueue_scripts

	/**
	 * hooked to manage_posts_columns to add an article column
	 */
	public function manage_posts_columns( $columns )
	{
		if ( ! empty( $columns['syndication'] ) )
		{
			unset( $columns['syndication'] );
		}//end if

		$offset = array_search( 'date', array_keys( $columns ) );
		$offset++;

		$columns = array_merge(
			array_slice( $columns, 0, $offset ),
			array( 'article' => 'Article' ),
			array_slice( $columns, $offset, NULL )
		);

		return $columns;
	}//end manage_posts_columns

	/**
	 * hooked to manage_posts_custom_column to add an article column
	 */
	public function manage_posts_custom_column( $column, $post_id )
	{
		if ( 'article' != $column )
		{
			return;
		}//end if

		$quote_post = get_post( $post_id );
		$parent = get_post( $quote_post->post_parent );

		$date_format = get_option( 'date_format' );

		?>
		<p>
			<a href="<?php echo esc_url( get_edit_post_link( $parent->ID ) ); ?>"><?php echo esc_html( get_the_title( $parent->ID ) ); ?></a>
		</p>
		<div class="publish-date">
			<strong>Article publish date</strong>: <?php echo esc_html( get_the_date( $date_format, $parent->ID ) ); ?>
			<?php echo esc_html( get_the_time( 'h:i a', $parent->ID ) ); ?>
		</div>
		<?php
	}//end manage_posts_custom_column

	/**
	 * hooked to the go_guestpost_post_types filter to add the guest post meta box
	 */
	public function go_guestpost_post_types( $post_types )
	{
		$post_types[] = $this->post_type_name;
		return $post_types;
	}//end go_guestpost_post_types

	public function post_submitbox_start()
	{
		echo wp_nonce_field( 'go-quotes-featured-pullquote', '_go_quotes_featured_save' );
	}//end post_submitbox_start

	/**
	 * hooked to go_waterfall_options_post_types to add support for waterfall settings on go-quotes
	 */
	public function go_waterfall_options_post_types( $post_types )
	{
		$post_types[] = $this->post_type_name;

		return $post_types;
	}//end go_waterfall_options_post_types

	/**
	 * Hooks to the save_post action and looks though the content for
	 * person attributes ( specifically person="NAME")
	 * then adds the name to the post as a person term
	 */
	public function save_post( $post_id )
	{
		// check that this isn't an autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		{
			return;
		}//end if

		$post = get_post( $post_id );

		if ( ! is_object( $post ) )
		{
			return;
		}// end if

		// Don't run on post revisions (almost always happens just before the real post is saved)
		if ( wp_is_post_revision( $post->ID ) )
		{
			return;
		}// end if

		// Check the nonce
		if ( empty( $_POST[ '_go_quotes_featured_save' ] ) || ! wp_verify_nonce( $_POST[ '_go_quotes_featured_save' ], 'go-quotes-featured-pullquote' ) )
		{
			return;
		}// end if

		$whitelisted_post_types = array( 'post' );
		if ( ! in_array( $post->post_type, $whitelisted_post_types ) )
		{
			return;
		}//end if

		// Check the permissions
		if ( ! current_user_can( 'edit_post', $post->ID  ) )
		{
			return;
		}// end if

		$this->is_save_post = TRUE;
		$this->post_id = $post_id;

		$original_post_content = $content = $_POST['post_content'];
		do_shortcode( $content );

		$this->is_save_post = FALSE;
		$this->post_id = NULL;

		$pullquotes = $this->find_pullquotes( $content, $post_id );

		$update_post_content = FALSE;

		foreach ( $pullquotes as $pullquote )
		{
			if ( ! $pullquote['id'] )
			{
				$content = $this->create_pullquote( $pullquote, $post, $content );

				if ( $content != $original_post_content )
				{
					$update_post_content = TRUE;
				}//end if
			}//end if
			else
			{
				$this->update_pullquote( $pullquote );
			}//end else
		}//end foreach

		// if we updated the content with a new ID for a pullquote, let's update the post
		if ( $update_post_content )
		{
			// update the content
			remove_action( 'save_post', array( $this, 'save_post' ) );
			wp_update_post( array(
				'ID' => $post->ID,
				'post_content' => $content,
			) );
			add_action( 'save_post', array( $this, 'save_post' ) );
		}//end if
	}// end save_post

	/**
	 * meta box for controlling injectable units
	 */
	public function go_waterfall_options_meta_box( $parent_post )
	{
		global $post;

		$args = array(
			'post_type' => $this->post_type_name,
			'post_parent' => $parent_post->ID,
		);

		$query = new WP_Query( $args );

		if ( ! $query->have_posts() )
		{
			return;
		}//end if
		?>
		<h4 id="go-quotes-pullquotes">Featured Pull-quotes</h4>
		<?php

		include_once __DIR__ . '/class-go-quotes-pullquote-table.php';

		$featured_table = new GO_Quotes_Pullquote_Table( $post, $query );
		$featured_table->prepare_items();
		$featured_table->display();

		wp_reset_postdata();
	}// end go_waterfall_options_meta_box

	/**
	 * find all pullquotes in the contents of provided text
	 */
	public function find_pullquotes( $content, $post_id = NULL )
	{
		preg_match_all( '#\[pullquote([^\]]*?)\]([^\[]*)\[/pullquote\]#', stripslashes( $content ), $all_quote_matches, PREG_SET_ORDER );

		$pullquotes = array();

		foreach ( $all_quote_matches as $match )
		{
			$pullquote = array(
				'id' => NULL,
				'shortcode' => $match[0],
				'attributes' => $match[1],
				'quote' => $match[2],
				'post' => NULL,
				'person' => NULL,
				'attribution' => NULL,
				'content' => NULL,
			);

			// grab the person from the pullquote attributes if there is one
			preg_match( '#person="([^"]+)"#', $pullquote['attributes'], $matches );

			if ( $matches[1] )
			{
				$pullquote['person'] = $matches[1];
			}//end if

			// grab the attribution from the pullquote attributes if there is one
			preg_match( '#attribution="([^"]+)"#', $pullquote['attributes'], $matches );

			if ( $matches[1] )
			{
				$pullquote['attribution'] = $matches[1];
			}//end if

			// grab the pullquote post id from the pullquote attributes if there is one
			preg_match( '#id="([\d]+)"#', $pullquote['attributes'], $matches );

			if ( $matches[1] )
			{
				$pullquote['id'] = $matches[1];

				// if the post for this quote doesn't exist or the post is in the trash, null the id
				if (
					! ( $pullquote['post'] = get_post( $pullquote['id'] ) )
					|| 'trash' === $pullquote['post']->post_status
				)
				{
					$pullquote['id'] = NULL;
				}//end if

				// if the post parent doesn't match the post, null the id
				if (
					$post_id
					&& $pullquote['id']
					&& $pullquote['post']->post_parent != $post_id
				)
				{
					$pullquote['id'] = NULL;
				}//end if
			}//end if

			$pullquotes[] = $pullquote;
		}//end foreach

		return $pullquotes;
	}//end find_pullquotes

	/**
	 * sets up the pullquote data
	 */
	private function setup_pullquote_post_data( $pullquote, $post_id )
	{
		$pullquote_data = array(
			'post_content' => $pullquote['quote'],
			'post_excerpt' => $pullquote['quote'],
			'post_title' => 75 <= count( $pullquote['quote'] ) ? $pullquote['quote'] : substr( $pullquote['quote'], 0, 72 ) . '…',
			'post_type' => $this->post_type_name,
			'post_parent' => $post_id,
		);

		return $pullquote_data;
	}//end setup_pullquote_post_data

	/**
	 * creates a pullquote post object
	 */
	private function create_pullquote( $pullquote, $post, $content )
	{
		$pullquote_data = $this->setup_pullquote_post_data( $pullquote, $post->ID );

		// if we are in here, there's no ID set on the pullquote
		$pullquote_data['post_excerpt'] = $pullquote_data['post_content'];

		// create the pullquote object
		$pullquote['id'] = wp_insert_post( $pullquote_data );

		$this->update_attribution( $pullquote );

		$content = stripslashes( $content );

		// let's remove any id from the shortcode so we can make sure the current one in there is accurate
		$content = preg_replace( '#(\[pullquote[^\]]*?) id="[\d]+"([^\]]*\]' . preg_quote( $pullquote['quote'], '#' ) . '\[/pullquote\])#', '$1$2', $content );

		// add the id to the shortcode
		$content = preg_replace( '#(\[pullquote[^\]]*?)(\]' . preg_quote( $pullquote['quote'], '#' ) . '\[/pullquote\])#', '$1 id="' . $pullquote['id'] . '"$2', $content );

		$content = addslashes( $content );

		return $content;
	}//end create_pullquote

	/**
	 * update a pullquote post object
	 */
	private function update_pullquote( $pullquote )
	{
		$pullquote_data = array();

		// the pullquote already has a post id
		$pullquote_data['ID'] = $pullquote['id'];
		$pullquote_data['post_title'] = 75 <= count( $pullquote['quote'] ) ? $pullquote['quote'] : substr( $pullquote['quote'], 0, 72 ) . '…';

		// if the pullquote's excerpt is unmolested, we are free to continue to update it
		if ( $pullquote['post']->post_excerpt == $pullquote['post']->post_content )
		{
			$pullquote_data['post_excerpt'] = $pullquote['quote'];
		}//end if

		remove_action( 'save_post', array( $this, 'save_post' ) );
		wp_update_post( $pullquote_data );
		add_action( 'save_post', array( $this, 'save_post' ) );

		$this->update_attribution( $pullquote );
	}//end update_pullquote

	/**
	 * updates a pullquote's attribution info
	 */
	private function update_attribution( $pullquote )
	{
		$pullquote['person'] = empty( $pullquote['person'] ) ? '' : sanitize_text_field( $pullquote['person'] );
		$pullquote['attribution'] = empty( $pullquote['attribution'] ) ? '' : sanitize_text_field( $pullquote['attribution'] );

		update_post_meta( $pullquote['id'], 'go-quotes', array(
			'attribution' => $pullquote['attribution'],
			'person' => $pullquote['person'],
		) );
	}//end update_attribution
}//end class
