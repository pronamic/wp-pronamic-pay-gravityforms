<?php
use Pronamic\WordPress\Pay\Core\Statuses;

/**
 * Title: WordPress pay extension Gravity Forms links
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.6.7
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_GravityForms_Links {
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
	 * @param Statuses $payment_status
	 *
	 * @return string
	 * @since 1.4.4
	 */
	public static function transform_status( $payment_status ) {
		switch ( $payment_status ) {
			case Statuses::CANCELLED:
				return Pronamic_WP_Pay_Extensions_GravityForms_Links::CANCEL;

			case Statuses::EXPIRED:
				return Pronamic_WP_Pay_Extensions_GravityForms_Links::EXPIRED;

			case Statuses::FAILURE:
				return Pronamic_WP_Pay_Extensions_GravityForms_Links::ERROR;

			case Statuses::SUCCESS:
				return Pronamic_WP_Pay_Extensions_GravityForms_Links::SUCCESS;

			case Statuses::OPEN:
			default:
				return Pronamic_WP_Pay_Extensions_GravityForms_Links::OPEN;
		}
	}
}
