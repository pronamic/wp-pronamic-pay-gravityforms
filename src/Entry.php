<?php
/**
 * Entry
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

/**
 * Title: WordPress pay extension Gravity Forms entry
 * Description:
 * Copyright: 2005-2024 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.1.11
 * @since   1.0.0
 */
class Entry {
	/**
	 * Check if the entry has been fulfilled by this payment add-on.
	 *
	 * @param array $entry Gravity Forms entry.
	 *
	 * @return boolean true if fulfilled, false otherwise
	 */
	public static function is_fulfilled( array $entry ) {
		$is_fulfilled = gform_get_meta( $entry['id'], 'pronamic_pay_payment_fulfilled' );

		if ( 1 === intval( $is_fulfilled ) ) {
			return true;
		}

		return false;
	}

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
				[
					// @since 1.0.0 - Approved
					PaymentStatuses::APPROVED,
					// @since 1.2.3 - Paid
					PaymentStatuses::PAID,
				],
				true
			);
		}

		return $approved;
	}

	/**
	 * Check if the specified entry payment status is `Active`.
	 *
	 * @param array $entry Gravity Forms entry.
	 *
	 * @return boolean true if payment is active, false otherwise
	 */
	public static function is_payment_active( array $entry ) {
		$active = false;

		if ( isset( $entry[ LeadProperties::PAYMENT_STATUS ] ) ) {
			$payment_status = $entry[ LeadProperties::PAYMENT_STATUS ];

			$active = PaymentStatuses::ACTIVE === $payment_status;
		}

		return $active;
	}
}
