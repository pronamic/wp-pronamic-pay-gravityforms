<?php
/**
 * Lead properties
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

/**
 * Title: WordPress pay extension Gravity Forms lead properties
 * Description:
 * Copyright: 2005-2024 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.0
 * @since   1.0.0
 */
class LeadProperties {
	/**
	 * Lead property payment status
	 *
	 * @var string
	 */
	const PAYMENT_STATUS = 'payment_status';

	/**
	 * Lead property payment amount
	 *
	 * @var string
	 */
	const PAYMENT_AMOUNT = 'payment_amount';

	/**
	 * Lead property payment date
	 *
	 * @var string
	 */
	const PAYMENT_DATE = 'payment_date';

	/**
	 * Lead property transaction ID
	 *
	 * @var string
	 */
	const TRANSACTION_ID = 'transaction_id';

	/**
	 * Lead property transaction type
	 *
	 * @var string
	 */
	const TRANSACTION_TYPE = 'transaction_type';

	/**
	 * Lead property created by
	 *
	 * @var string
	 */
	const CREATED_BY = 'created_by';
}
