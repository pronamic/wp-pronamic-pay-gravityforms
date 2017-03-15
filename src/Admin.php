<?php

/**
 * Title: WordPress pay extension Gravity Forms admin
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.6.4
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_GravityForms_Admin {
	/**
	 * Bootstrap
	 */
	public static function bootstrap() {
		// Actions
		add_action( 'admin_init',                                 array( __CLASS__, 'admin_init' ) );
		add_action( 'admin_init',                                 array( __CLASS__, 'maybe_redirect_to_entry' ) );

		// Filters
		add_filter( 'gform_addon_navigation',                     array( __CLASS__, 'addon_navigation' ) );

		add_filter( 'gform_entry_info',                           array( __CLASS__, 'entry_info' ), 10, 2 );

		add_filter( 'gform_custom_merge_tags',                    array( __CLASS__, 'custom_merge_tags' ), 10 );

		// Actions - AJAX
		add_action( 'wp_ajax_gf_get_form_data',                   array( __CLASS__, 'ajax_get_form_data' ) );
		add_action( 'wp_ajax_gf_dismiss_pronamic_pay_feeds_menu', array( __CLASS__, 'ajax_dismiss_feeds_menu' ) );
	}

	//////////////////////////////////////////////////

	/**
	 * Admin initialize
	 */
	public static function admin_init() {
		new Pronamic_WP_Pay_Extensions_GravityForms_AdminPaymentFormPostType();
	}

	//////////////////////////////////////////////////

	/**
	 * Gravity Forms addon navigation
	 *
	 * @param $menus array with addon menu items
	 * @return array
	 */
	public static function addon_navigation( $menus ) {
		if ( Pronamic_WP_Pay_Extensions_GravityForms_GravityForms::version_compare( '1.7', '<' ) ) {
			$menus[] = array(
				'name'       => 'edit.php?post_type=pronamic_pay_gf',
				'label'      => __( 'Payment Feeds', 'pronamic_ideal' ),
				'callback'   => null,
				'permission' => 'manage_options',
			);

			return $menus;
		}

		if ( '1' === get_user_meta( get_current_user_id(), '_pronamic_pay_gf_dismiss_feeds_menu', true ) ) {
			return $menus;
		}

		$menus[] = array(
			'name'       => Pronamic_WP_Pay_Extensions_GravityForms_PaymentAddOn::SLUG,
			'label'      => __( 'Payment Feeds', 'pronamic_ideal' ),
			'callback'   => array( __CLASS__, 'temporary_feeds_page' ),
			'permission' => 'manage_options',
		);

		return $menus;
	}

	/**
	 * Temporary feeds page
	 *
	 * @since unreleased
	 */
	public static function temporary_feeds_page() {
		require dirname( __FILE__ ) . '/../views/html-admin-temporary-feeds-page.php';
	}

	public function ajax_dismiss_feeds_menu() {
		$current_user = wp_get_current_user();

		update_user_meta( $current_user->ID, '_pronamic_pay_gf_dismiss_feeds_menu', 1 );
	}

	//////////////////////////////////////////////////

	/**
	 * Add menu item to form settings
	 *
	 * @param $menu_items array with form settings menu items
	 * @return array
	 */
	public static function form_settings_menu_item( $menu_items ) {
		$menu_items[] = array(
			'name' => 'pronamic_pay',
			'label' => __( 'Pay', 'pronamic_ideal' ),
			'query' => array( 'fid' => null ),
		);

		return $menu_items;
	}

	//////////////////////////////////////////////////

	/**
	 * Render entry info of the specified form and lead
	 *
	 * @param string $form_id
	 * @param array $lead
	 */
	public static function entry_info( $form_id, $lead ) {
		$payment_id = gform_get_meta( $lead['id'], 'pronamic_payment_id' );

		if ( $payment_id ) {
			printf(
				'<a href="%s">%s</a>',
				esc_attr( get_edit_post_link( $payment_id ) ),
				esc_html( get_the_title( $payment_id ) )
			);
		}
	}

	//////////////////////////////////////////////////

	/**
	 * Custom merge tags
	 */
	public static function custom_merge_tags( $merge_tags ) {
		$merge_tags[] = array(
			'label' => __( 'Payment Status', 'pronamic_ideal' ),
			'tag'   => '{payment_status}',
		);

		$merge_tags[] = array(
			'label' => __( 'Payment Date', 'pronamic_ideal' ),
			'tag'   => '{payment_date}',
		);

		$merge_tags[] = array(
			'label' => __( 'Transaction Id', 'pronamic_ideal' ),
			'tag'   => '{transaction_id}',
		);

		$merge_tags[] = array(
			'label' => __( 'Payment Amount', 'pronamic_ideal' ),
			'tag'   => '{payment_amount}',
		);

		$merge_tags[] = array(
			'label' => __( 'Pronamic Payment ID', 'pronamic_ideal' ),
			'tag'   => '{pronamic_payment_id}',
		);

		$merge_tags[] = array(
			'label' => __( 'Pronamic Subscription Cancel URL', 'pronamic_ideal' ),
			'tag'   => '{pronamic_subscription_cancel_url}',
		);

		$merge_tags[] = array(
			'label' => __( 'Pronamic Subscription Renew URL', 'pronamic_ideal' ),
			'tag'   => '{pronamic_subscription_renew_url}',
		);

		$merge_tags[] = array(
			'label' => __( 'Pronamic Subscription Renewal Date', 'pronamic_ideal' ),
			'tag'   => '{pronamic_subscription_renewal_date}',
		);

		return $merge_tags;
	}

	//////////////////////////////////////////////////

	/**
	 * Maybed redirect to Gravity Forms entry
	 */
	public static function maybe_redirect_to_entry() {
		if ( filter_has_var( INPUT_GET, 'pronamic_gf_lid' ) ) {
			$lead_id = filter_input( INPUT_GET, 'pronamic_gf_lid', FILTER_SANITIZE_STRING );

			$lead = RGFormsModel::get_lead( $lead_id );

			if ( ! empty( $lead ) ) {
				$url = add_query_arg( array(
					'page' => 'gf_entries',
					'view' => 'entry',
					'id'   => $lead['form_id'],
					'lid'  => $lead_id,
				), admin_url( 'admin.php' ) );

				wp_redirect( $url );

				exit;
			}
		}
	}

	//////////////////////////////////////////////////

	/**
	 * Handle AJAX request get form data
	 */
	public static function ajax_get_form_data() {
		$form_id = filter_input( INPUT_GET, 'formId', FILTER_SANITIZE_STRING );

		$result = new stdClass();
		$result->success = true;
		$result->data    = RGFormsModel::get_form_meta( $form_id );

		// Output
		header( 'Content-Type: application/json' );

		echo json_encode( $result );

		die();
	}

	/**
	 * Get new feed URL.
	 *
	 * @since 1.6.3
	 * @param string $form_id
	 * @return string
	 */
	public static function get_new_feed_url( $form_id ) {
		if ( Pronamic_WP_Pay_Extensions_GravityForms_GravityForms::version_compare( '1.7', '<' ) ) {
			return add_query_arg( 'post_type', 'pronamic_pay_gf', admin_url( 'post-new.php' ) );
		}

		return add_query_arg( array(
			'page'    => 'gf_edit_forms',
			'view'    => 'settings',
			'subview' => 'pronamic_pay',
			'id'      => $form_id,
			'fid'     => 0,
		), admin_url( 'admin.php' ) );
	}
}
