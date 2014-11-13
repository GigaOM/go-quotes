<?php

class GO_Quotes_Pullquote_Table extends WP_List_Table
{
	public $user_can;
	public $parent_post;
	public $query;
	public $content_pullquotes = array();
	public $content_pullquote_ids = array();

	/**
	 * constructor!
	 */
	public function __construct( $parent_post, $query )
	{
		$this->query = $query;
		$this->parent_post = $parent_post;

		//Set parent defaults
		parent::__construct(
			array(
				'singular' => 'pullquote',  //singular name of the listed records
				'plural'   => 'pullquotes', //plural name of the listed records
				'ajax'     => FALSE,             //does this table support ajax?
			)
		);

		$this->content_pullquotes = go_quotes()->admin()->find_pullquotes( $parent_post->post_content, $parent_post->ID );
		$this->content_pullquote_ids = wp_list_pluck( $this->content_pullquotes, 'id' );
	} // END __construct

	/**
	 * Custom column to render item date
	 *
	 * @param Object $item Current item
	 */
	public function column_date( $item )
	{
		if ( 'draft' === $item->post_status )
		{
			return 'TBD';
		}//end if

		return esc_html( get_the_date() . ' ' . get_the_time() );
	} // END column_default

	/**
	 * Custom column to output info on the pullquote
	 *
	 * @param Object $comment Current comment
	 */
	public function column_pullquote( $item )
	{
		$edit_url    = admin_url( 'post.php?post=' . $item->ID . '&amp;action=edit' );
		$trash_url   = get_delete_post_link( $item->ID );

		$actions = array(
			'post-edit' => sprintf( '<a href="%1$s">Edit</a>', esc_url( $edit_url ) ),
			'post-trash' => sprintf( '<span class="delete"><a class="submitdelete" href="%1$s">Trash</a></span>', esc_url( $trash_url ) ),
		);

		$state = '';
		if ( 'draft' === $item->post_status )
		{
			$state = '- <span class="post-state">Draft</span>';
		}//end if

		$orphan = '';
		if ( ! in_array( $item->ID, $this->content_pullquote_ids ) )
		{
			$orphan = '- <span class="post-state">This pull-quote is no longer in this post!</span>';
		}//end if

		return sprintf(
			'<strong><a href="%1$s">%2$s</a></strong> %3$s %4$s %5$s',
			esc_url( $edit_url ),
			get_the_title( $item->ID ),
			$state,
			$orphan,
			$this->row_actions( $actions )
		);
	} // END column_pullquote

	/**
	 * Returns an associative array of columns
	 */
	public function get_columns()
	{
		$columns = array(
			'pullquote' => 'Pull-quote',
			'date'  => 'Publish date',
		);

		return $columns;
	} // END get_columns

	/**
	 * This echos a single item (from the items property) to the page.
	 *
	 * @param Object $comment Current comment
	 */
	public function single_row( $post )
	{
		// Prep some stuff so the functions have what they need
		$this->user_can = current_user_can( 'edit_post', $post->ID );

		echo '<tr id="go-quotes-pullquote-' . absint( $post->ID ) . '" class="' . esc_attr( $row_class ) . '">';
		echo $this->single_row_columns( $post );
		echo '</tr>';
	} // END single_row

	/**
	 * We don't want to diplsay anything with the tablenav so we simply do nothing. We include this
	 * because we don't want the defaults.
	 */
	protected function display_tablenav( $unused_which )
	{
	}//end display_tablenav

	/**
	 * prepares the comments for rendering
	 */
	public function prepare_items()
	{
		$this->_column_headers = array( $this->get_columns() );
		$this->items = $this->query->get_posts();
	} // END prepare_items

	/**
	 * Outputs the list table the way we want for feedback info!
	 */
	public function custom_display()
	{
		?>
		<table class="<?php echo esc_attr( implode( ' ', $this->get_table_classes() ) ); ?>">
			<?php
			if ( 0 == count( $this->items ) )
			{
				?>
				<tbody class="none">
					<tr>
						<td>
							There are currently no pullquotes on this post.
						</td>
					</tr>
				</tbody>
				<?php
			} // END if
			else
			{
				?>
				<tbody>
					<?php $this->display_rows_or_placeholder(); ?>
				</tbody>
				<?php
			} // END else
			?>
		</table>
		<?php
	} // END custom_display
}// END class
