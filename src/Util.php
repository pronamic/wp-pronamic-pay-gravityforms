<?php

/**
 * Title: WordPress pay extension Gravity Forms extension
 * Description:
 * Copyright: Copyright (c) 2005 - 2016
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.0.0
 */
class Pronamic_WP_Pay_Extensions_GravityForms_Util {
	/**
	 * Check if the iDEAL condition is true
	 *
	 * @param mixed $form
	 * @param mixed $feed
	 */
	public static function is_condition_true( $form, $feed ) {
		$result = true;

		if ( $feed->condition_enabled ) {
			$field = RGFormsModel::get_field( $form, $feed->condition_field_id );

			if ( empty( $field ) ) {
				// unknown field
				$result = true;
			} else {
				$is_hidden = RGFormsModel::is_field_hidden( $form, $field, array() );

				if ( $is_hidden ) {
					// if conditional is enabled, but the field is hidden, ignore conditional
					$result = false;
				} else {
					$value = RGFormsModel::get_field_value( $field, array() );

					$is_match = RGFormsModel::is_value_match( $value, $feed->condition_value );

					switch ( $feed->condition_operator ) {
						case Pronamic_WP_Pay_Extensions_GravityForms_GravityForms::OPERATOR_IS:
							$result = $is_match;
							break;
						case Pronamic_WP_Pay_Extensions_GravityForms_GravityForms::OPERATOR_IS_NOT:
							$result = ! $is_match;
							break;
						default: // unknown operator
							$result = true;
							break;
					}
				}
			}
		} else {
			// condition is disabled, result is true
			$result = true;
		}

		return $result;
	}
}
