<?php
/**
 * Util
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2020 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

use RGFormsModel;

/**
 * Title: WordPress pay extension Gravity Forms extension
 * Description:
 * Copyright: 2005-2020 Pronamic
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

	/**
	 * Get detected field ID.
	 *
	 * @param string $field_name Field name.
	 * @param array  $form       Form.
	 * @param array  $entry      Entry.
	 *
	 * @return string|null
	 */
	public static function get_detected_field_id( $field_name, $form, $entry ) {
		// Field types with optional input ID suffix as key.
		$types = array(
			'name'    => array(
				2 => 'prefix_name',
				3 => 'first_name',
				4 => 'middle_name',
				6 => 'last_name',
				8 => 'suffix_name',
			),
			'address' => array(
				1 => 'address1',
				2 => 'address2',
				3 => 'city',
				4 => 'state',
				5 => 'zip',
				6 => 'country',
			),
			'phone'   => array( 'telephone_number' ),
			'email'   => array( 'email' ),
		);

		// Determine type and input ID suffix (if applicable).
		$input_type   = null;
		$input_suffix = null;

		foreach ( $types as $type => $fields ) {
			$search = \array_search( $field_name, $fields, true );

			if ( false === $search ) {
				continue;
			}

			$input_type = $type;

			// Input ID suffix.
			if ( \in_array( $type, array( 'name', 'address' ), true ) ) {
				$input_suffix = $search;
			}

			break;
		}

		// Find first visible field of type.
		foreach ( $form['fields'] as $field ) {
			// Check field type.
			if ( $field->type !== $input_type ) {
				continue;
			}

			// Check field visibility.
			if ( RGFormsModel::is_field_hidden( $form, $field, array(), $entry ) ) {
				continue;
			}

			// Input ID needs suffix?
			if ( null !== $input_suffix ) {
				return $field->id . '.' . $input_suffix;
			}

			return $field->id;
		}

		return null;
	}
}
