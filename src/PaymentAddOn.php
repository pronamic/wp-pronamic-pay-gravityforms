<?php

/**
 * Title: WordPress pay extension Gravity Forms payment add-on
 * Description:
 * Copyright: Copyright (c) 2005 - 2015
 * Company: Pronamic
 * @author Remco Tolsma
 * @version 1.1.0
 */
class Pronamic_WP_Pay_Extensions_GravityForms_PaymentAddOn extends GFPaymentAddOn {
	/**
	 * Add-on slug
	 *
	 * The add-on slug is required for the Gravity Forms payment add-on class
	 * so it can verify callbacks.
	 *
	 * @see https://github.com/gravityforms/gravityforms/blob/1.9/includes/addon/class-gf-payment-addon.php#L641-L644
	 * @see https://github.com/gravityforms/gravityforms/blob/1.9/includes/addon/class-gf-payment-addon.php#L628-L634
	 */
	protected $_slug = 'pronamic_pay';
}
