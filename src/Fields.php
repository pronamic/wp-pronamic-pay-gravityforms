<?php

/**
 * Title: WordPress pay extension Gravity Forms fields
 * Description:
 * Copyright: Copyright (c) 2005 - 2016
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.4.7
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_GravityForms_Fields {
	/**
	 * Construct and intialize custom Gravity Forms fields.
	 */
	public function __construct() {
		add_filter( 'gform_enable_credit_card_field', '__return_true' );

		if ( Pronamic_WP_Pay_Class::method_exists( 'GF_Fields', 'register' ) ) {
			GF_Fields::register( new Pronamic_WP_Pay_Extensions_GravityForms_PaymentMethodsField() );
			GF_Fields::register( new Pronamic_WP_Pay_Extensions_GravityForms_IssuersField() );
		}
	}

	/**
	 * Add pay field group to the Gravity Forms field groups.
	 *
	 * @see https://github.com/wp-premium/gravityforms/blob/1.9.19/form_detail.php#L2353-L2368
	 * @since 1.4.7
	 * @param array $field_groups
	 * @return array
	 */
	public static function add_pay_field_group( $field_groups ) {
		if ( ! isset( $field_groups['pronamic_pay_fields'] ) ) {
			$field_groups['pronamic_pay_fields'] = array(
				'name'   => 'pronamic_pay_fields',
				'label'  => __( 'Payment Fields', 'pronamic_ideal' ),
				'fields' => array(),
			);
		}

		return $field_groups;
	}
}
