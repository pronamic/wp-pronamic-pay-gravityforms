<?php
/**
 * Gravity Forms
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

use GFAPI;
use GFCommon;
use GFFormsModel;
use Pronamic\WordPress\Pay\Core\Util as Core_Util;

/**
 * Title: WordPress pay extension Gravity Forms
 * Description:
 * Copyright: 2005-2024 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.0
 * @since   1.0.0
 */
class GravityForms {
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

	/**
	 * Check if Gravity Forms is active (Automattic/developer style).
	 *
	 * @link https://bitbucket.org/Pronamic/gravityforms/src/42773f75ad7ad9ac9c31ce149510ff825e4aa01f/gravityforms.php?at=1.7.8#cl-95
	 * @link https://github.com/Automattic/developer/blob/1.1.2/developer.php#L73
	 *
	 * @return bool
	 */
	public static function is_active() {
		return class_exists( 'GFForms' );
	}

	/**
	 * Update entry property.
	 *
	 * @param int    $entry_id Entry ID.
	 * @param string $property Name of the property to update.
	 * @param string $value    Value for the property.
	 */
	public static function update_entry_property( $entry_id, $property, $value ) {
		if ( Core_Util::class_method_exists( 'GFAPI', 'update_entry_property' ) ) {
			GFAPI::update_entry_property( $entry_id, $property, $value );
		} elseif ( Core_Util::class_method_exists( 'GFFormsModel', 'update_lead_property' ) ) {
			GFFormsModel::update_lead_property( $entry_id, $property, $value );
		}
	}

	/**
	 * Get entry property.
	 *
	 * @param int    $entry_id Entry ID.
	 * @param string $property Name of the property to fetch.
	 *
	 * @return mixed|null
	 */
	public static function get_entry_property( $entry_id, $property ) {
		$entry = GFFormsModel::get_lead( $entry_id );

		if ( isset( $entry[ $property ] ) ) {
			return $entry[ $property ];
		}

		return null;
	}

	/**
	 * Compare the current Gravity Forms version
	 *
	 * @param string $version  Version.
	 * @param string $operator Compare operator.
	 *
	 * @return bool
	 */
	public static function version_compare( $version, $operator ) {
		if ( class_exists( 'GFCommon' ) ) {
			return version_compare( GFCommon::$version, $version, $operator );
		}

		return false;
	}
}
