<?php

/**
 * Title: WordPress payment form post type
 * Description:
 * Copyright: Copyright (c) 2005 - 2015
 * Company: Pronamic
 * @author Remco Tolsma
 * @version 1.1.0
 */
class Pronamic_WP_Pay_Extensions_GravityForms_PaymentFormPostType {
	/**
	 * Construct and initialize payment form post type
	 */
	public function __construct() {
		/**
		 * Priotiry of the initial post types function should be set to < 10
		 *
		 * @see https://core.trac.wordpress.org/ticket/28488
		 * @see https://core.trac.wordpress.org/changeset/29318
		 *
		 * @see https://github.com/WordPress/WordPress/blob/4.0/wp-includes/post.php#L167
		 */
		add_action( 'init', array( $this, 'init' ), 0 ); // highest priority
	}

	//////////////////////////////////////////////////

	/**
	 * Initialize
	 */
	public function init() {
		register_post_type( 'pronamic_pay_gf', array(
			'label'              => __( 'Payment Forms', 'pronamic_ideal' ),
			'labels'             => array(
				'name'               => __( 'Payment Forms', 'pronamic_ideal' ),
				'singular_name'      => __( 'Payment Form', 'pronamic_ideal' ),
				'add_new'            => __( 'Add New', 'pronamic_ideal' ),
				'add_new_item'       => __( 'Add New Payment Form', 'pronamic_ideal' ),
				'edit_item'          => __( 'Edit Payment Form', 'pronamic_ideal' ),
				'new_item'           => __( 'New Payment Form', 'pronamic_ideal' ),
				'all_items'          => __( 'All Payment Forms', 'pronamic_ideal' ),
				'view_item'          => __( 'View Payment Form', 'pronamic_ideal' ),
				'search_items'       => __( 'Search Payment Forms', 'pronamic_ideal' ),
				'not_found'          => __( 'No payment forms found', 'pronamic_ideal' ),
				'not_found_in_trash' => __( 'No payment forms found in Trash', 'pronamic_ideal' ),
				'menu_name'          => __( 'Payment Forms', 'pronamic_ideal' )
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_nav_menus'  => false,
			'show_in_menu'       => false,
			'show_in_admin_bar'  => false,
			'supports'           => array( 'title', 'revisions' ),
			'rewrite'            => false,
			'query_var'          => false,
		) );
	}
}
