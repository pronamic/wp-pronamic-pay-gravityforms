<?php
/**
 * Links
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

use Pronamic\WordPress\Pay\Payments\PaymentStatus;

/**
 * Title: WordPress pay extension Gravity Forms links
 * Description:
 * Copyright: 2005-2024 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.1.12
 * @since   1.0.0
 */
class Links {
	/**
	 * Indicator for the open status link
	 *
	 * @var string
	 */
	const OPEN = 'open';

	/**
	 * Indicator for the cancel status link
	 *
	 * @var string
	 */
	const CANCEL = 'cancel';

	/**
	 * Indicator for the error status link
	 *
	 * @var string
	 */
	const ERROR = 'error';

	/**
	 * Indicator for the success status link
	 *
	 * @var string
	 */
	const SUCCESS = 'success';

	/**
	 * Indicator for the expired status link
	 *
	 * @var string
	 */
	const EXPIRED = 'expired';

	/**
	 * Link for payment status.
	 *
	 * @since 1.4.4
	 *
	 * @param string $payment_status Payment status.
	 * @return string
	 */
	public static function transform_status( $payment_status ) {
		switch ( $payment_status ) {
			case PaymentStatus::CANCELLED:
				return self::CANCEL;
			case PaymentStatus::EXPIRED:
				return self::EXPIRED;
			case PaymentStatus::FAILURE:
				return self::ERROR;
			case PaymentStatus::SUCCESS:
				return self::SUCCESS;
			case PaymentStatus::OPEN:
			default:
				return self::OPEN;
		}
	}
}
