<?php

/**
 * Title: WordPress pay extension Gravity Forms links
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.4.4
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
	 * @param Pronamic_WP_Pay_Statuses $payment_status
	 *
	 * @return string
	 * @since 1.4.4
	 */
	public static function transform_status( $payment_status ) {
		switch ( $payment_status ) {
			case Pronamic_WP_Pay_Statuses::CANCELLED :
				return Pronamic_WP_Pay_Extensions_GravityForms_Links::CANCEL;

				break;
			case Pronamic_WP_Pay_Statuses::EXPIRED :
				return Pronamic_WP_Pay_Extensions_GravityForms_Links::EXPIRED;

				break;
			case Pronamic_WP_Pay_Statuses::FAILURE :
				return Pronamic_WP_Pay_Extensions_GravityForms_Links::ERROR;

				break;
			case Pronamic_WP_Pay_Statuses::SUCCESS :
				return Pronamic_WP_Pay_Extensions_GravityForms_Links::SUCCESS;

				break;
			case Pronamic_WP_Pay_Statuses::OPEN :
			default :
				return Pronamic_WP_Pay_Extensions_GravityForms_Links::OPEN;

				break;
		}
	}
}
