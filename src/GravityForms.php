<?php

/**
 * Title: WordPress pay extension Gravity Forms
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.6.0
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_GravityForms_GravityForms {
	/**
	 * Indicator for an payment transaction type
	 *
	 * @var int
	 */
	const TRANSACTION_TYPE_PAYMENT = 1;

	/**
	 * Indicator for an subscription transaction type
	 *
	 * @var int
	 */
	const TRANSACTION_TYPE_SUBSCRIPTION = 2;

	//////////////////////////////////////////////////

	/**
	 * Operator is
	 *
	 * @var string
	 */
	const OPERATOR_IS = '=';

	/**
	 * Operator is not
	 *
	 * @var string
	 */
	const OPERATOR_IS_NOT = '!=';

	//////////////////////////////////////////////////

	/**
	 * Indicator for form total subscription amount type.
	 *
	 * @var string
	 */
	const SUBSCRIPTION_AMOUNT_TOTAL = 'total';

	/**
	 * Indicator for field subscription amount type.
	 *
	 * @var string
	 */
	const SUBSCRIPTION_AMOUNT_FIELD = 'field';

	/**
	 * Indicator for field subscription interval type.
	 *
	 * @var string
	 */
	const SUBSCRIPTION_INTERVAL_FIELD = 'field';

	/**
	 * Indicator for fixed subscription interval type.
	 *
	 * @var string
	 */
	const SUBSCRIPTION_INTERVAL_FIXED = 'fixed';

	/**
	 * Indicator for field subscription frequency type.
	 *
	 * @var string
	 */
	const SUBSCRIPTION_FREQUENCY_FIELD = 'field';

	/**
	 * Indicator for fixed subscription frequency type.
	 *
	 * @var string
	 */
	const SUBSCRIPTION_FREQUENCY_FIXED = 'fixed';

	/**
	 * Indicator for unlimited subscription frequency type.
	 *
	 * @var string
	 */
	const SUBSCRIPTION_FREQUENCY_UNLIMITED = 'unlimited';

	//////////////////////////////////////////////////

	/**
	 * Check if Gravity Forms is active (Automattic/developer style)
	 *
	 * @see https://bitbucket.org/Pronamic/gravityforms/src/42773f75ad7ad9ac9c31ce149510ff825e4aa01f/gravityforms.php?at=1.7.8#cl-95
	 * @see https://github.com/Automattic/developer/blob/1.1.2/developer.php#L73
	 *
	 * @return boolean
	 */
	public static function is_active() {
		return class_exists( 'GFForms' );
	}

	//////////////////////////////////////////////////

	/**
	 * Update entry
	 *
	 * @param array $entry
	 */
	public static function update_entry( $entry ) {
		/*
		 * GFFormsModel::update_lead() is no longer in use since version 1.8.8! Instead use GFAPI::update_entry().
		 *
		 * @see https://github.com/wp-premium/gravityforms/blob/1.8.13/forms_model.php#L587-L624
		 * @see https://github.com/wp-premium/gravityforms/blob/1.8.13/includes/api.php#L495-L654
		 * @see https://github.com/wp-premium/gravityforms/blob/1.8.7.11/forms_model.php#L587-L621
		 */
		if ( Pronamic_WP_Pay_Class::method_exists( 'GFAPI', 'update_entry' ) ) {
			GFAPI::update_entry( $entry );
		} elseif ( Pronamic_WP_Pay_Class::method_exists( 'GFFormsModel', 'update_lead' ) ) {
			GFFormsModel::update_lead( $entry );
		}
	}

	//////////////////////////////////////////////////

	/**
	 * Update entry property
	 *
	 * @param int    $entry_id Entry ID
	 * @param string $property Name of the property to update
	 * @param string $value    Value for the property
	 */
	public static function update_entry_property( $entry_id, $property, $value ) {
		if ( Pronamic_WP_Pay_Class::method_exists( 'GFAPI', 'update_entry_property' ) ) {
			GFAPI::update_entry_property( $entry_id, $property, $value );
		} elseif ( Pronamic_WP_Pay_Class::method_exists( 'GFFormsModel', 'update_lead_property' ) ) {
			GFFormsModel::update_lead_property( $entry_id, $property, $value );
		}
	}

	//////////////////////////////////////////////////

	/**
	 * Compare the current Gravity Forms version
	 *
	 * @param string $version
	 * @param string $operator
	 */
	public static function version_compare( $version, $operator ) {
		if ( class_exists( 'GFCommon' ) ) {
			return version_compare( GFCommon::$version, $version, $operator );
		} else {
			return false;
		}
	}
}
