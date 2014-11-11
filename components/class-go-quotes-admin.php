<?php

class GO_Quotes_Admin
{
	public $post_type_name = 'go-quotes-pullquote';

	public function __construct()
	{
		add_action( 'go_waterfall_options_meta_box', array( $this, 'go_waterfall_options_meta_box' ) );
		add_filter( 'go_guestpost_post_types', array( $this, 'go_guestpost_post_types' ) );
		add_filter( 'save_post', array( $this, 'save_post' ), 10, 2 );
	}// END __construct

	/**
	 * hooked to the go_guestpost_post_types filter to add the guest post meta box
	 */
	public function go_guestpost_post_types( $post_types )
	{
		$post_types[] = $this->post_type_name;
		return $post_types;
	}//end go_guestpost_post_types

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
		// @TODO: add a nonce
		//if ( ! wp_verify_nonce( $_POST[ $this->post_type_name . '-save-post' ], plugin_basename( __FILE__ ) ) )
		//{
			//return;
		//}// end if

		// Check the permissions
		if ( ! current_user_can( 'edit_post', $post->ID  ) )
		{
			return;
		}// end if

		$this->is_save_post = TRUE;
		$this->post_id = $post_id;

		$post = get_post( $post_id );

		$content = $post->post_content;
		do_shortcode( $content );

		$this->is_save_post = FALSE;
		$this->post_id = NULL;

		$pullquotes = $this->find_pullquotes( $content, $post_id );

		foreach ( $pullquotes as $pullquote )
		{
			if ( ! $pullquote['id'] )
			{
				$this->create_pullquote( $pullquote, $post );
			}//end if
			else
			{
				$this->update_pullquote( $pullquote );
			}//end else
		}//end foreach
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

		while ( $query->have_posts() )
		{
			$query->the_post();
			echo $post->post_title . '<br>';
		}//end while

		wp_reset_postdata();
	}// end go_waterfall_options_meta_box

	/**
	 * find all pullquotes in the contents of provided text
	 */
	public function find_pullquotes( $content, $post_id = NULL )
	{
		preg_match_all( '#\[pullquote([^\]]*?)\]([^\[]*)\[/pullquote\]#', $content, $all_quote_matches, PREG_SET_ORDER );

		$pullquotes = array();

		foreach ( $all_quote_matches as $match )
		{
			$pullquote = array(
				'id' => NULL,
				'shortcode' => $match[0],
				'attributes' => $match[1],
				'quote' => $match[2],
				'post' => NULL,
			);

			// grab the pullquote post id from the pullquote attributes if there is one
			preg_match( '#id="([\d]+)#', $pullquote['attributes'], $matches );

			if ( $matches[1] )
			{
				$pullquote['id'] = $matches[1];

				// if the post for this quote doesn't exist, null the id
				if ( ! ( $pullquote['post'] = get_post( $pullquote['id'] ) ) )
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
			'post_title' => 75 <= sizeof( $pullquote['quote'] ) ? $pullquote['quote'] : substr( $pullquote['quote'], 0, 72 ) . '…',
			'post_type' => $this->post_type_name,
			'post_parent' => $post_id,
		);

		return $pullquote_data;
	}//end setup_pullquote_post_data

	/**
	 * creates a pullquote post object
	 */
	private function create_pullquote( $pullquote, $post )
	{
		$pullquote_data = $this->setup_pullquote_post_data( $pullquote, $post->ID );

		// if we are in here, there's no ID set on the pullquote
		$pullquote_data['post_excerpt'] = $pullquote_data['post_content'];

		// create the pullquote object
		$pullquote['id'] = wp_insert_post( $pullquote_data );

		// let's remove any id from the shortcode so we can make sure the current one in there is accurate
		$content = preg_replace( '#(\[pullquote[^\]]*?) id="[\d]+"([^\]]*\]' . preg_quote( $pullquote['quote'], '#' ) . '\[/pullquote\])#', '$1$2', $post->post_content );

		// add the id to the shortcode
		$content = preg_replace( '#(\[pullquote[^\]]*?)(\]' . preg_quote( $pullquote['quote'], '#' ) . '\[/pullquote\])#', '$1 id="' . $pullquote['id'] . '"$2', $content );

		// update the content
		remove_action( 'save_post', array( $this, 'save_post' ) );
		wp_update_post( array(
			'ID' => $post->ID,
			'post_content' => $content,
		) );
		add_action( 'save_post', array( $this, 'save_post' ) );
	}//end create_pullquote

	/**
	 * update a pullquote post object
	 */
	private function update_pullquote( $pullquote )
	{
		// the pullquote already has a post id
		$pullquote_data['ID'] = $pullquote['id'];

		// if the pullquote's excerpt is unmolested, we are free to continue to update it
		if ( $pullquote['post']->post_excerpt == $pullquote['post']->post_content )
		{
			$pullquote_data['post_excerpt'] = $pullquote_data['post_content'];
		}//end if

		remove_action( 'save_post', array( $this, 'save_post' ) );
		wp_update_post( $pullquote );
		add_action( 'save_post', array( $this, 'save_post' ) );
	}//end update_pullquote
}//end class
