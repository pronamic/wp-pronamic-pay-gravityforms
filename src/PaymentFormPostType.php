<?php

/**
 * Title: WordPress payment form post type
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.6.4
 * @since 1.1.0
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

	/**
	 * Get the show UI flag for the payment form post type.
	 *
	 * @return boolean true if show UI, false otherwise
	 */
	private function get_show_ui() {
		// If Gravity Forms is active and version is lower then 1.7 we show the WordPress UI.
		return Pronamic_WP_Pay_Extensions_GravityForms_GravityForms::version_compare( '1.7', '<' );
	}

	/**
	 * Initialize.
	 */
	public function init() {
		register_post_type( 'pronamic_pay_gf', array(
			'label'              => __( 'Payment Feeds', 'pronamic_ideal' ),
			'labels'             => array(
				'name'                  => __( 'Payment Feeds', 'pronamic_ideal' ),
				'singular_name'         => __( 'Payment Feed', 'pronamic_ideal' ),
				'add_new'               => __( 'Add New', 'pronamic_ideal' ),
				'add_new_item'          => __( 'Add New Payment Feed', 'pronamic_ideal' ),
				'edit_item'             => __( 'Edit Payment Feed', 'pronamic_ideal' ),
				'new_item'              => __( 'New Payment Feed', 'pronamic_ideal' ),
				'all_items'             => __( 'All Payment Feeds', 'pronamic_ideal' ),
				'view_item'             => __( 'View Payment Feed', 'pronamic_ideal' ),
				'search_items'          => __( 'Search Payment Feeds', 'pronamic_ideal' ),
				'not_found'             => __( 'No payment feeds found.', 'pronamic_ideal' ),
				'not_found_in_trash'    => __( 'No payment feeds found in Trash.', 'pronamic_ideal' ),
				'menu_name'             => __( 'Payment Feeds', 'pronamic_ideal' ),
				'filter_items_list'     => __( 'Filter payment feeds list', 'pronamic_ideal' ),
				'items_list_navigation' => __( 'Payment feeds list navigation', 'pronamic_ideal' ),
				'items_list'            => __( 'Payment feeds list', 'pronamic_ideal' ),
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => $this->get_show_ui(),
			'show_in_nav_menus'  => false,
			'show_in_menu'       => false,
			'show_in_admin_bar'  => false,
			'supports'           => array( 'title', 'revisions' ),
			'rewrite'            => false,
			'query_var'          => false,
		) );
	}
}
