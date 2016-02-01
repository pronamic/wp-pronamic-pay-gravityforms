<?php

/**
 * Title: WordPress pay extension Gravity Forms payment add-on
 * Description:
 * Copyright: Copyright (c) 2005 - 2016
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.3.0
 * @since 1.1.0
 */
class Pronamic_WP_Pay_Extensions_GravityForms_PaymentAddOn extends GFPaymentAddOn {
	/**
	 * Construct and initialize an Gravity Forms payment add-on
	 *
	 * @see https://github.com/gravityforms/gravityforms/blob/1.9.10.15/includes/addon/class-gf-payment-addon.php
	 *
	 * @since 1.3.0
	 */
	public function __construct() {
		parent::__construct();

		/*
		 * Slug
		 *
		 * @var string URL-friendly identifier used for form settings, add-on settings, text domain localization...
		 * @see https://github.com/gravityforms/gravityforms/blob/1.9.10.15/includes/addon/class-gf-addon.php#L24-L27
		 */
		$this->_slug = 'pronamic_pay';

		/*
	 	 * Title
	 	 *
	 	 * @var string Title of the plugin to be used on the settings page, form settings and plugins page. Example: 'Gravity Forms MailChimp Add-On'
		 * @see https://github.com/gravityforms/gravityforms/blob/1.9.10.15/includes/addon/class-gf-addon.php#L40-L43
		 */
		$this->_title = __( 'WordPress Pay Add-On', 'pronamic_ideal' );

		/*
		 * Short title
		 *
		 * @var string Short version of the plugin title to be used on menus and other places where a less verbose string is useful. Example: 'MailChimp'
		 * @see https://github.com/gravityforms/gravityforms/blob/1.9.10.15/includes/addon/class-gf-addon.php#L44-L47
		 */
		$this->_short_title = __( 'Pay', 'pronamic_ideal' );
	}

	/**
	 * Form settings page
	 *
	 * @since 1.3.0
	 */
	public function form_settings_page() {
		GFFormSettings::page_header();

		printf(
			'<h3>%s</h3>',
			esc_html__( 'Pay Feeds', 'pronamic_ideal' )
		);

		printf(
			'<p>%s</p>',
			wp_kses(
				sprintf(
					esc_html__( 'You can find your pay feeds under %s.', 'pronamic_ideal' ),
					sprintf(
						'<a href="%s">%s</a>',
						esc_attr( add_query_arg( 'post_type', 'pronamic_pay_gf', admin_url( 'edit.php' ) ) ),
						esc_html__( 'Forms » iDEAL', 'pronamic_ideal' )
					)
				),
				array(
					'a' => array( 'href' => true ),
				)
			)
		);

		GFFormSettings::page_footer();
	}
}
