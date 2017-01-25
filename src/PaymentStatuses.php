<?php

/**
 * Title: WordPress pay extension Gravity Forms payment statuses
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.0.0
 */
class Pronamic_WP_Pay_Extensions_GravityForms_PaymentStatuses {
	/**
	 * Payment status processing
	 *
	 * @var string
	 */
	const PROCESSING = 'Processing';

	/**
	 * Payment status active
	 *
	 * @var string
	 */
	const ACTIVE = 'Active';

	/**
	 * Payment status cancelled
	 *
	 * @var string
	 */
	const CANCELLED = 'Cancelled';

	/**
	 * Payment status expired
	 *
	 * @var string
	 */
	const EXPIRED = 'Expired';

	/**
	 * Payment status failed
	 *
	 * @var string
	 */
	const FAILED = 'Failed';

	/**
	 * Payment status reversed
	 *
	 * @var string
	 */
	const REVERSED = 'Reversed';

	/**
	 * Payment status approved
	 *
	 * @var string
	 */
	const APPROVED = 'Approved';

	/**
	 * Payment status paid
	 *
	 * @var string
	 */
	const PAID = 'Paid';

	/**
	 * Payment status denied
	 *
	 * @var string
	 */
	const DENIED = 'Denied';

	/**
	 * Payment status pending
	 *
	 * @var string
	 */
	const PENDING = 'Pending';

	/**
	 * Payment status refunded
	 *
	 * @var string
	 */
	const REFUNDED = 'Refunded';

	/**
	 * Payment status voided
	 *
	 * @var string
	 */
	const VOIDED = 'Voided';
}
