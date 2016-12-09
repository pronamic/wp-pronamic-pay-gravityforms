<?php

/**
 * Title: WordPress pay extension Gravity Forms pay feed list table
 * Description:
 * Copyright: Copyright (c) 2005 - 2016
 * Company: Pronamic
 *
 * @author Reüel van der Steege
 * @version 1.0.0
 * @since unreleased
 */

class Pronamic_WP_Pay_Extensions_GravityForms_PayFeed_List_Table extends WP_List_Table {
	/**
	 * Our Gravity Form array
	 *
	 * @var array
	 *
	 * @since unreleased
	 */
	public $form;

	private $row_class = '';

	public function __construct( $form_id ) {
		$this->form = GFAPI::get_form( $form_id );

		/* Cache column header internally so we don't have to work with the global get_column_headers() function */
		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			array(),
		);

		parent::__construct();
	}

	/**
	 * Return the columns that should be used in the list table
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'                      => '',
			'name'                    => esc_html__( 'Name', 'pronamic_ideal' ),
			'transaction_description' => esc_html__( 'Transaction Description', 'pronamic_ideal' ),
			'configuration'           => esc_html__( 'Configuration', 'pronamic_ideal' ),
		);
	}

	/**
	 * Get the name of the default primary column.
	 *
	 * @return string Name of the default primary column.
	 *
	 * @since unreleased
	 */
	protected function get_default_primary_column_name() {
		return 'name';
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @since unreleased
	 */
	public function prepare_items() {
		$query = new WP_Query( array(
			'post_type'      => 'pronamic_pay_gf',
			'posts_per_page' => - 1,
			'meta_query'     => array(
				array(
					'key'   => '_pronamic_pay_gf_form_id',
					'value' => $this->form['id'],
				),
			),
		) );

		$this->items = $query->posts;
	}

	/**
	 * Display the table.
	 *
	 * @since unreleased
	 */
	public function display() {
		if ( empty( $this->items ) ) {
			$this->prepare_items();
		}

		$singular = rgar( $this->_args, 'singular' );

		?>

		<table class="wp-list-table <?php echo esc_attr( implode( ' ', $this->get_table_classes() ) ); ?>" cellspacing="0">
			<thead>
				<tr>
					<?php $this->print_column_headers(); ?>
				</tr>
			</thead>

			<tbody id="the-list" <?php if ( $singular ) { echo esc_attr( " class='list:$singular'" ); } ?>>
				<?php $this->display_rows_or_placeholder(); ?>
			</tbody>

			<tfoot>
				<tr>
					<?php $this->print_column_headers( false ); ?>
				</tr>
			</tfoot>

		</table>

		<?php
	}

	/**
	 * Output the single table row
	 *
	 * @param  object $post The WP_Post object item.
	 *
	 * @since unreleased
	 */
	public function single_row( $post ) {
		echo '<tr id="pronamic-pay-gf-feed-' . esc_attr( $post->ID ) . '">';

		$this->single_row_columns( $post );

		echo '</tr>';
	}

	/**
	 * Custom public function for displaying the 'cb' column
	 * Used to handle active / inactive PDFs
	 *
	 * @param  array $item The table row being processed
	 *
	 * @since unreleased
	 */
	public function column_cb( $post ) {
		return;

		$is_active   = get_post_meta( $post->ID, '_pronamic_pay_gf_feed_active', true );
		$state_nonce = wp_create_nonce( sprintf( 'pronamic_pay_gf_state_nonce_%s_%s', $this->form['id'], $post->ID ) );

		printf(
			'<img data-id="%s" data-nonce="%s" data-form-id="%s" src="%s" style="cursor: pointer;margin:-1px 0 0 8px;" alt="%s" title="%5$s" />',
			$post->ID,
			$state_nonce,
			$this->form['id'],
			GFCommon::get_base_url() . '/images/active' . intval( $is_active ) . '.png',
			$is_active ? esc_attr__( 'Active', 'pronamic_ideal' ) : esc_attr__( 'Inactive', 'pronamic_ideal' )
		);
	}

	/**
	 * Column feed name with actions to allow edit, duplication and deletion.
	 *
	 * @param  array $post The WP_Post object for pay feed post.
	 *
	 * @since unreleased
	 */
	public function column_name( $post ) {
		$edit_url = add_query_arg( array( 'fid' => $post->ID ) );
		$title    = get_the_title( $post->ID );

		if ( empty( $title ) ) {
			$title = __( 'Default pay feed', 'pronamic_ideal' );
		}

		$actions = array(
			'edit' => sprintf(
				'<a title="%s" href="%s">%s</a>',
				esc_attr__( 'Edit this feed', 'pronamic_ideal' ),
				esc_url( $edit_url ),
				esc_html__( 'Edit', 'pronamic_ideal' )
			),

			'duplicate' => sprintf(
				'<a title="%s" data-id="%s" class="submitduplicate" data-nonce="%s" data-fid="%s">%s</a>',
				esc_attr__( 'Duplicate this feed', 'pronamic_ideal' ),
				esc_attr( $post->ID ),
				wp_create_nonce( sprintf( 'pronamic_pay_gf_duplicate_nonce_%s_%s', $this->form['id'], $post->ID ) ),
				esc_attr( $this->form['id'] ),
				esc_html__( 'Duplicate', 'pronamic_ideal' )
			),

			'delete' => sprintf(
				'<a title="%s" class="submitdelete" data-id="%s" data-nonce="%s" data-fid="%s">%s</a>',
				esc_attr__( 'Delete this feed', 'pronamic_ideal' ),
				esc_attr( $post->ID ),
				wp_create_nonce( sprintf( 'pronamic_pay_gf_delete_nonce_%s_%s', $this->form['id'], $post->ID ) ),
				esc_attr( $this->form['id'] ),
				esc_html__( 'Delete', 'pronamic_ideal' )
			),
		);

		?>

		<a href="<?php echo esc_url( $edit_url ); ?>"><strong><?php echo esc_html( $title ); ?></strong></a>

		<div class="row-actions">

			<?php

			$keys     = array_keys( $actions );
			$last_key = array_pop( $keys );

			foreach ( $actions as $key => $html ) {
				printf(
					'<span class="%s">%s%s</span>',
					esc_attr( $key ),
					$html, // WPCS: XSS ok
					esc_html( $key === $last_key ? '' : ' | ' )
				);
			}

			?>

		</div>

		<?php
	}

	/**
	 * Column transaction description.
	 *
	 * @param  array $post The WP_Post object for pay feed post.
	 *
	 * @since unreleased
	 */
	public function column_transaction_description( $post ) {
		$description = get_post_meta( $post->ID, '_pronamic_pay_gf_transaction_description', true );

		echo esc_html( $description );
	}

	/**
	 * Column configuration.
	 *
	 * @param  array $post The WP_Post object for pay feed post.
	 *
	 * @since unreleased
	 */
	public function column_configuration( $post ) {
		$config_id = get_post_meta( $post->ID, '_pronamic_pay_gf_config_id', true );
		$title = get_the_title( $config_id );

		if ( empty( $config_id ) || empty( $title ) ) {
			$title = '-';
		}

		echo esc_html( $title );
	}

	/**
	 * Display text if no pay feeds exist yet.
	 *
	 * @since unreleased
	 */
	public function no_items() {
		printf( // WPCS: XSS ok
			__( 'This form doesn\'t have any pay feeds. Let\'s go %1$screate one%2$s.', 'pronamic_ideal' ),
			'<a href="' . esc_url( add_query_arg( array( 'fid' => 0 ) ) ) . '">',
			'</a>'
		);
	}
}
