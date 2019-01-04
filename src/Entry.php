<?php
/**
 * Entry
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2019 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

/**
 * Title: WordPress pay extension Gravity Forms entry
 * Description:
 * Copyright: 2005-2019 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.1.2
 * @since   1.0.0
 */
class Entry {
	/**
	 * Check if the specified entry payment is approved
	 *
	 * @param array $entry Gravity Forms entry.
	 *
	 * @return boolean true if payment is approved, false otherwise
	 */
	public static function is_payment_approved( array $entry ) {
		$approved = false;

		if ( isset( $entry[ LeadProperties::PAYMENT_STATUS ] ) ) {
			$payment_status = $entry[ LeadProperties::PAYMENT_STATUS ];

			$approved = in_array(
				$payment_status,
				array(
					// @since 1.0.0 - Approved
					PaymentStatuses::APPROVED,
					// @since 1.2.3 - Paid
					PaymentStatuses::PAID,
				),
				true
			);
		}

		return $approved;
	}
}
