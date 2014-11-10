<?php

class GO_Quotes_Admin
{

	public function __construct()
	{
		add_action( 'init', array( $this, 'init' ), 11 );
		add_action( 'go_waterfall_options_meta_box', array( $this, 'go_waterfall_options_meta_box' ) );
		add_filter( 'save_post', array( $this, 'save_post' ), 10, 2 );
	}// END __construct

	/**
	 * Functions and actions to run on init
	 */
	public function init()
	{
	}//end init

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

		if ( ! preg_match( '/\[pullquote[^\]]*]*\]/', $content ) )
		{
			return;
		}//end if

		preg_match_all( '#\[pullquote([^\]]*?)\]([^\[]*)\[/pullquote\]#', $content, $all_quote_matches, PREG_SET_ORDER );

		foreach ( $all_quote_matches as $match )
		{
			$pullquote_shortcode = $match[0];
			$pullquote_attributes = $match[1];
			$pullquote_quote = $match[2];

			// grab the pullquote post id from the pullquote attributes if there is one
			preg_match( '#id="([\d]+)#', $pullquote_attributes, $matches );

			$pullquote_post_id = NULL;
			$pullquote = NULL;
			$pullquote_meta = array();

			// is there an ID associated with the pullquote?
			if ( $matches[1] )
			{
				$pullquote_post_id = $matches[1];
				if ( $pullquote = get_post( $pullquote_post_id ) )
				{
					$pullquote_meta = get_post_meta( $pullquote_post_id, 'go-quotes-pullquote', TRUE );

					// if the post parent doesn't match the post
					if ( $pullquote->post_parent != $post_id )
					{
						$pullquote = NULL;
						$pullquote_post_id = NULL;
					}//end if
				}//end if
				else
				{
					$pullquote_post_id = NULL;
				}//end else
			}//end if

			$pullquote_data = array(
				'post_content' => $pullquote_quote,
				'post_title' => 75 <= sizeof( $pullquote_quote ) ? $pullquote_quote : substr( $pullquote_quote, 0, 72 ) . '…',
				'post_type' => $this->post_type_name,
				'post_parent' => $post_id,
			);

			if ( ! $pullquote_post_id )
			{
				// if we are in here, there's no ID set on the pullquote
				$pullquote_data['post_excerpt'] = $pullquote_data['post_content'];

				// create the pullquote object
				$pullquote_post_id = wp_insert_post( $pullquote_data );

				// let's remove any id from the shortcode so we can make sure the current one in there is accurate
				$content = preg_replace( '#(\[pullquote[^\]]*?) id="[\d]+"([^\]]*\]' . preg_quote( $pullquote_quote, '#' ) . '\[/pullquote\])#', '$1$2', $content );

				// add the id to the shortcode
				$content = preg_replace( '#(\[pullquote[^\]]*?)(\]' . preg_quote( $pullquote_quote, '#' ) . '\[/pullquote\])#', '$1 id="' . $pullquote_post_id . '"$2', $content );

				// update the content
				remove_action( 'save_post', array( $this, 'save_post' ) );
				wp_update_post( array(
					'ID' => $post_id,
					'post_content' => $content,
				) );
				add_action( 'save_post', array( $this, 'save_post' ) );
			}//end if
			else
			{
				// the pullquote already has a post id
				$pullquote_data['ID'] = $pullquote_post_id;

				// if the pullquote's excerpt is unmolested, we are free to continue to update it
				if ( $pullquote->post_excerpt == $pullquote->post_content )
				{
					$pullquote_data['post_excerpt'] = $pullquote_data['post_content'];
				}//end if

				remove_action( 'save_post', array( $this, 'save_post' ) );
				wp_update_post( $pullquote );
				add_action( 'save_post', array( $this, 'save_post' ) );
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

		while ( $query->have_posts() )
		{
			$query->the_post();
			echo $post->post_title . '<br>';
			?>
			<?php
		}//end while

		wp_reset_postdata();
	}// end go_waterfall_options_meta_box
}//end class
