<?php

/**
 * Title: WordPress pay extension Gravity Forms payment methods
 * Description:
 * Copyright: Copyright (c) 2005 - 2016
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.4.6
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_GravityForms_PaymentMethodsField extends GF_Field_Select {
	/**
	 * Type
	 */
	public $type = 'pronamic_pay_payment_method_selector';

	/**
	 * Constructs and initializes payment methods field.
	 *
	 * @param $properties
	 */
	public function __construct( $properties ) {
		parent::__construct( $properties );

		$this->inputs = null;
	}
}
