<?php

/**
 * Title: WordPress pay extension Gravity Forms payment add-on
 * Description:
 * Copyright: Copyright (c) 2005 - 2016
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.4.7
 * @since 1.1.0
 */
class Pronamic_WP_Pay_Extensions_GravityForms_PaymentAddOn extends GFPaymentAddOn {
	/**
	 * Construct and initialize an Gravity Forms payment add-on
	 *
	 * @see https://github.com/wp-premium/gravityforms/blob/1.9.10.15/includes/addon/class-gf-payment-addon.php
	 *
	 * @since 1.3.0
	 */
	public function __construct() {
		parent::__construct();

		/*
		 * Slug
		 *
		 * @var string URL-friendly identifier used for form settings, add-on settings, text domain localization...
		 * @see https://github.com/wp-premium/gravityforms/blob/1.9.10.15/includes/addon/class-gf-addon.php#L24-L27
		 */
		$this->_slug = 'pronamic_pay';

		/*
	 	 * Title
	 	 *
	 	 * @var string Title of the plugin to be used on the settings page, form settings and plugins page. Example: 'Gravity Forms MailChimp Add-On'
		 * @see https://github.com/wp-premium/gravityforms/blob/1.9.10.15/includes/addon/class-gf-addon.php#L40-L43
		 */
		$this->_title = __( 'WordPress Pay Add-On', 'pronamic_ideal' );

		/*
		 * Short title
		 *
		 * @var string Short version of the plugin title to be used on menus and other places where a less verbose string is useful. Example: 'MailChimp'
		 * @see https://github.com/wp-premium/gravityforms/blob/1.9.10.15/includes/addon/class-gf-addon.php#L44-L47
		 */
		$this->_short_title = __( 'Pay', 'pronamic_ideal' );
	}

	/**
	 * Form settings page
	 *
	 * @since 1.3.0
	 */
	public function form_settings_page() {
		if ( ! filter_has_var( INPUT_GET, 'id' ) ) {
			return;
		}

		$form_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_STRING );

		if ( filter_has_var( INPUT_POST, 'pronamic_pay_nonce' ) ) {
			$nonce = filter_input( INPUT_POST, 'pronamic_pay_nonce', FILTER_SANITIZE_STRING );

			// Verify that the nonce is valid.
			if ( wp_verify_nonce( $nonce, 'pronamic_pay_save_pay_gf' ) ) {
				global $wpdb;

				if ( ! filter_has_var( INPUT_GET, 'fid' ) ) {
					return;
				}

				$post_id = filter_input( INPUT_GET, 'fid', FILTER_SANITIZE_STRING );

				$post = get_post( $post_id );

				if ( ! $post ) {
					wp_insert_post( array(
						'post_type'			=> 'pronamic_pay_gf',
						'post_name'			=> 'pronamic-pay-gf-' . $form_id,
						'post_title'		=> 'Pronamic iDEAL for Gravity Forms #' . $form_id,
						'post_status'		=> 'publish',
						'comment_status'	=> 'closed',
						'ping_status'		=> 'closed',
					) );

					$post_id = $wpdb->insert_id;
				}

				wp_update_post( array(
					'ID' => $post_id,
				) );
			}
		}

		GFFormSettings::page_header();

		$view = 'list';

		if ( filter_has_var( INPUT_GET, 'fid' ) ) {
			$view = 'settings';
		}

		require( dirname( __FILE__ ) . '/../views/html-admin-form-feeds-' . $view . '.php' );

		GFFormSettings::page_footer();
	}

	/**
	 * Add supported notification events.
	 *
	 * @param array $form The form currently being processed.
	 *
	 * @return array
	 */
	public function supported_notification_events( $form ) {
		$form = (array) $form;

		$query = new WP_Query( array(
			'post_type'			=> 'pronamic_pay_gf',
			'posts_per_page'	=> -1,
			'meta_query'		=> array(
				array(
					'key'   => '_pronamic_pay_gf_form_id',
					'value' => $form['id'],
				),
			),
		) );

		if ( ! $query->have_posts() ) {
			return false;
		}

		$events = array(
			'complete_payment'          => esc_html__( 'Payment Completed', 'pronamic_ideal' ),
			'fail_payment'              => esc_html__( 'Payment Failed', 'pronamic_ideal' ),
			'add_pending_payment'       => esc_html__( 'Payment Pending', 'pronamic_ideal' ),
		);

		foreach ( $query->posts as $post ) {
			// Get the config ID from the pay feed
			$config_id = get_post_meta( $post->ID, '_pronamic_pay_gf_config_id', true );

			// Get the gateway from the configuration
			$gateway = Pronamic_WP_Pay_Plugin::get_gateway( $config_id );

			if ( $gateway && $gateway->supports( 'recurring' ) ) {
				$subscription_events = array(
					'create_subscription'       => esc_html__( 'Subscription Created', 'pronamic_ideal' ),
					'cancel_subscription'       => esc_html__( 'Subscription Canceled', 'pronamic_ideal' ),
					'expire_subscription'       => esc_html__( 'Subscription Expired', 'pronamic_ideal' ),
					'renew_subscription'        => esc_html__( 'Subscription Renewal Notice', 'pronamic_ideal' ),
					'add_subscription_payment'  => esc_html__( 'Subscription Payment Added', 'pronamic_ideal' ),
					'fail_subscription_payment' => esc_html__( 'Subscription Payment Failed', 'pronamic_ideal' ),
				);

				$events = array_merge( $events, $subscription_events );

				return $events;
			}
		}

		return $events;
	}
}
