<?php
/**
 * Util
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2019 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

use RGFormsModel;

/**
 * Title: WordPress pay extension Gravity Forms extension
 * Description:
 * Copyright: 2005-2019 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.0
 * @since   1.0.0
 */
class Util {
	/**
	 * Check if the iDEAL condition is true
	 *
	 * @param mixed $form Gravity Form form.
	 * @param mixed $feed Pay feed.
	 *
	 * @return bool
	 */
	public static function is_condition_true( $form, $feed ) {
		if ( is_array( $form ) ) {
			$form = RGFormsModel::get_form_meta( $form['id'] );
		}

		if ( is_array( $feed ) ) {
			$feed = new PayFeed( $feed['ID'] );
		}

		if ( ! $feed->condition_enabled ) {
			return true;
		}

		$field = RGFormsModel::get_field( $form, $feed->condition_field_id );

		// Unknown field.
		if ( empty( $field ) ) {
			return true;
		}

		$is_hidden = RGFormsModel::is_field_hidden( $form, $field, array() );

		// Ignore condition if the field is hidden.
		if ( $is_hidden ) {
			return false;
		}

		$value = RGFormsModel::get_field_value( $field, array() );

		$is_match = RGFormsModel::is_value_match( $value, $feed->condition_value );

		switch ( $feed->condition_operator ) {
			case GravityForms::OPERATOR_IS:
				return $is_match;

			case GravityForms::OPERATOR_IS_NOT:
				return ! $is_match;

			default:
				return true;
		}
	}
}
