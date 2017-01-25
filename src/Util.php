<?php

/**
 * Title: WordPress pay extension Gravity Forms extension
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.6.0
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_GravityForms_Util {
	/**
	 * Check if the iDEAL condition is true
	 *
	 * @param mixed $form
	 * @param mixed $feed
	 */
	public static function is_condition_true( $form, $feed ) {
		if ( is_array( $form ) ) {
			$form = RGFormsModel::get_form_meta( $form['id'] );
		}

		if ( is_array( $feed ) ) {
			$feed = new Pronamic_WP_Pay_Extensions_GravityForms_PayFeed( $feed['ID'] );
		}

		if ( ! $feed->condition_enabled ) {
			return true;
		}

		$field = RGFormsModel::get_field( $form, $feed->condition_field_id );

		// Unknown field
		if ( empty( $field ) ) {
			return true;
		}

		$is_hidden = RGFormsModel::is_field_hidden( $form, $field, array() );

		// Ignore condition if the field is hidden
		if ( $is_hidden ) {
			return false;
		}

		$value = RGFormsModel::get_field_value( $field, array() );

		$is_match = RGFormsModel::is_value_match( $value, $feed->condition_value );

		switch ( $feed->condition_operator ) {
			case Pronamic_WP_Pay_Extensions_GravityForms_GravityForms::OPERATOR_IS :
				$result = $is_match;

				break;
			case Pronamic_WP_Pay_Extensions_GravityForms_GravityForms::OPERATOR_IS_NOT :
				$result = ! $is_match;

				break;
			default :
				$result = true;
		}

		return $result;
	}
}
