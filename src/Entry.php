<?php

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

/**
 * Title: WordPress pay extension Gravity Forms entry
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 1.2.3
 * @since   1.0.0
 */
class Entry {
	/**
	 * Check if the specified entry payment is approved
	 *
	 * @param array $entry
	 *
	 * @return boolean true if payment is approved, false otherwise
	 */
	public static function is_payment_approved( array $entry ) {
		$approved = false;

		if ( isset( $entry[ LeadProperties::PAYMENT_STATUS ] ) ) {
			$payment_status = $entry[ LeadProperties::PAYMENT_STATUS ];

			$approved = in_array( $payment_status, array(
				// @since 1.0.0
				PaymentStatuses::APPROVED,
				// @since 1.2.3
				PaymentStatuses::PAID,
			), true );
		}

		return $approved;
	}
}
