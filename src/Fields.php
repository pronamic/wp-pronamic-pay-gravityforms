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

	public function get_payment_method_options( $form_id ) {
		$feed    = get_pronamic_gf_pay_conditioned_feed_by_form_id( $form_id );
		$options = array();

		if ( null !== $feed ) {
			$gateway = Pronamic_WP_Pay_Plugin::get_gateway( $feed->config_id );

			if ( $gateway ) {
				$payment_method_field = $gateway->get_payment_method_field();

				$error = $gateway->get_error();

				if ( is_wp_error( $error ) ) {
					$options = $error;
				} elseif ( $payment_method_field ) {
					$options = $payment_method_field['choices'][0]['options'];
				}
			}
		}

		return $options;
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
