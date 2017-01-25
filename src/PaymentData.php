<?php

/**
 * Title: WordPress pay extension Gravity Forms payment data
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.6.0
 * @since 1.0.1
 */
class Pronamic_WP_Pay_Extensions_GravityForms_PaymentData extends Pronamic_WP_Pay_PaymentData {
	/**
	 * Gravity Forms form object
	 *
	 * @see http://www.gravityhelp.com/documentation/page/Form_Object
	 * @var array
	 */
	private $form;

	/**
	 * Gravity Forms entry object
	 *
	 * @see http://www.gravityhelp.com/documentation/page/Entry_Object
	 * @var array
	 */
	private $lead;

	/**
	 * Pronamic iDEAL feed object
	 *
	 * @var Pronamic_WP_Pay_Extensions_GravityForms_PayFeed
	 */
	private $feed;

	//////////////////////////////////////////////////

	/**
	 * Constructs and initialize an Gravity Forms iDEAL data proxy
	 *
	 * @param array $form
	 * @param array $lead
	 * @param Pronamic_WP_Pay_Extensions_GravityForms_PayFeed $feed
	 */
	public function __construct( $form, $lead, $feed ) {
		parent::__construct();

		$this->form = $form;
		$this->lead = $lead;
		$this->feed = $feed;
	}

	//////////////////////////////////////////////////

	/**
	 * Get the field value of the specified field
	 *
	 * @param string $field_name
	 * @return Ambigous <NULL, multitype:>
	 */
	private function get_field_value( $field_name ) {
		if ( ! isset( $this->feed->fields[ $field_name ] ) ) {
			return null;
		}

		$field_id = $this->feed->fields[ $field_name ];

		if ( ! isset( $this->lead[ $field_id ] ) ) {
			return null;
		}

		return $this->lead[ $field_id ];
	}

	//////////////////////////////////////////////////

	/**
	 * Get source indicator
	 *
	 * @see Pronamic_Pay_PaymentDataInterface::get_source()
	 * @return string
	 */
	public function get_source() {
		return 'gravityformsideal';
	}

	/**
	 * Get source ID
	 *
	 * @see Pronamic_Pay_AbstractPaymentData::get_source_id()
	 */
	public function get_source_id() {
		return $this->lead['id'];
	}

	//////////////////////////////////////////////////

	/**
	 * Get description
	 *
	 * @see Pronamic_Pay_PaymentDataInterface::get_description()
	 * @return string
	 */
	public function get_description() {
		$description = $this->feed->transaction_description;

		if ( empty( $description ) ) {
			$description = '{entry_id}';
		}

		$description = GFCommon::replace_variables( $description, $this->form, $this->lead );

		return $description;
	}

	/**
	 * Get order ID
	 *
	 * @see Pronamic_Pay_PaymentDataInterface::get_order_id()
	 * @return string
	 */
	public function get_order_id() {
		// @see http://www.gravityhelp.com/documentation/page/Entry_Object#Standard
		$order_id = $this->feed->entry_id_prefix . $this->lead['id'];

		return $order_id;
	}

	/**
	 * Get items
	 *
	 * @see Pronamic_Pay_PaymentDataInterface::get_items()
	 * @return Pronamic_IDeal_Items
	 */
	public function get_items() {
		$items = new Pronamic_IDeal_Items();

		$number = 0;

		// Products
		$products = GFCommon::get_product_fields( $this->form, $this->lead );

		foreach ( $products['products'] as $product ) {
			$description = $product['name'];
			$price = GFCommon::to_number( $product['price'] );
			$quantity = $product['quantity'];

			$item = new Pronamic_IDeal_Item();
			$item->setNumber( $number++ );
			$item->setDescription( $description );
			$item->setPrice( $price );
			$item->setQuantity( $quantity );

			$items->addItem( $item );

			if ( isset( $product['options'] ) && is_array( $product['options'] ) ) {
				foreach ( $product['options'] as $option ) {
					$description = $option['option_label'];
					$price = GFCommon::to_number( $option['price'] );

					$item = new Pronamic_IDeal_Item();
					$item->setNumber( $number++ );
					$item->setDescription( $description );
					$item->setPrice( $price );
					$item->setQuantity( $quantity ); // Product quantity

					$items->addItem( $item );
				}
			}
		}

		// Shipping
		if ( isset( $products['shipping'] ) ) {
			$shipping = $products['shipping'];

			if ( isset( $shipping['price'] ) && ! empty( $shipping['price'] ) ) {
				$description = $shipping['name'];
				$price = GFCommon::to_number( $shipping['price'] );
				$quantity = 1;

				$item = new Pronamic_IDeal_Item();
				$item->setNumber( $number++ );
				$item->setDescription( $description );
				$item->setPrice( $price );
				$item->setQuantity( $quantity );

				$items->addItem( $item );
			}
		}

		// Donations
		$donation_fields = GFCommon::get_fields_by_type( $this->form, array( 'donation' ) );

		foreach ( $donation_fields as $i => $field ) {
			$value = RGFormsModel::get_lead_field_value( $this->lead, $field );

			if ( ! empty( $value ) ) {
				$description = '';
				if ( isset( $field['adminLabel'] ) && ! empty( $field['adminLabel'] ) ) {
					$description = $field['adminLabel'];
				} elseif ( isset( $field['label'] ) ) {
					$description = $field['label'];
				}

				$separator_position = strpos( $value, '|' );
				if ( false !== $separator_position ) {
					$label = substr( $value, 0, $separator_position );
					$value = substr( $value, $separator_position + 1 );

					$description .= ' - ' . $label;
				}

				$price = GFCommon::to_number( $value );
				$quantity = 1;

				$item = new Pronamic_IDeal_Item();
				$item->setNumber( $i );
				$item->setDescription( $description );
				$item->setQuantity( $quantity );
				$item->setPrice( $price );

				$items->addItem( $item );
			}
		}

		return $items;
	}

	//////////////////////////////////////////////////
	// Currency
	//////////////////////////////////////////////////

	/**
	 * Get currency alphabetic code
	 *
	 * @see Pronamic_Pay_PaymentDataInterface::get_currency_alphabetic_code()
	 * @return string
	 */
	public function get_currency_alphabetic_code() {
		return GFCommon::get_currency();
	}

	//////////////////////////////////////////////////
	// Customer
	//////////////////////////////////////////////////

	public function get_email() {
		return $this->get_field_value( 'email' );
	}

	public function get_customer_name() {
		return $this->get_field_value( 'first_name' ) . ' ' . $this->get_field_value( 'last_name' );
	}

	public function get_address() {
		return $this->get_field_value( 'address1' ) . ' ' . $this->get_field_value( 'address2' );
	}

	public function get_city() {
		return $this->get_field_value( 'city' );
	}

	public function get_zip() {
		return $this->get_field_value( 'zip' );
	}

	public function get_country() {
		return $this->get_field_value( 'country' );
	}

	public function get_telephone_number() {
		return $this->get_field_value( 'telephone_number' );
	}

	//////////////////////////////////////////////////
	// URL's
	//////////////////////////////////////////////////

	public function get_normal_return_url() {
		$url = $this->feed->get_url( Pronamic_WP_Pay_Extensions_GravityForms_Links::OPEN );

		if ( empty( $url ) ) {
			$url = parent::get_normal_return_url();
		}

		return $url;
	}

	public function get_cancel_url() {
		$url = $this->feed->get_url( Pronamic_WP_Pay_Extensions_GravityForms_Links::CANCEL );

		if ( empty( $url ) ) {
			$url = parent::get_cancel_url();
		}

		return $url;
	}

	public function get_success_url() {
		$url = $this->feed->get_url( Pronamic_WP_Pay_Extensions_GravityForms_Links::SUCCESS );

		if ( empty( $url ) ) {
			$url = parent::get_success_url();
		}

		return $url;
	}

	public function get_error_url() {
		$url = $this->feed->get_url( Pronamic_WP_Pay_Extensions_GravityForms_Links::ERROR );

		if ( empty( $url ) ) {
			$url = parent::get_error_url();
		}

		return $url;
	}

	//////////////////////////////////////////////////
	// Payment method
	//////////////////////////////////////////////////

	public function get_payment_method() {
		$fields = GFCommon::get_fields_by_type( $this->form, array( Pronamic_WP_Pay_Extensions_GravityForms_PaymentMethodsField::TYPE ) );

		foreach ( $fields as $field ) {
			if ( ! RGFormsModel::is_field_hidden( $this->form, $field, array() ) ) {
				$method = RGFormsModel::get_field_value( $field );

				if ( ! $this->get_subscription() && Pronamic_WP_Pay_PaymentMethods::DIRECT_DEBIT_IDEAL === $method ) {
					// DIRECT_DEBIT_IDEAL can only be used for subscription payments.

					$method = Pronamic_WP_Pay_PaymentMethods::IDEAL;
				}

				return $method;
			}
		}
	}

	//////////////////////////////////////////////////
	// Issuer
	//////////////////////////////////////////////////

	public function get_issuer_id() {
		$fields = GFCommon::get_fields_by_type( $this->form, array( Pronamic_WP_Pay_Extensions_GravityForms_IssuersField::TYPE ) );

		foreach ( $fields as $field ) {
			if ( ! RGFormsModel::is_field_hidden( $this->form, $field, array() ) ) {
				return RGFormsModel::get_field_value( $field );
			}
		}
	}

	//////////////////////////////////////////////////
	// Creditcard
	//////////////////////////////////////////////////

	public function get_credit_card() {
		$credit_card = null;

		$credit_card_fields = GFCommon::get_fields_by_type( $this->form, array( 'creditcard' ) );

		$credit_card_field = array_shift( $credit_card_fields );

		if ( $credit_card_field ) {
			$credit_card = new Pronamic_Pay_CreditCard();

			// Number
			$variable_name = sprintf( 'input_%s_1', $credit_card_field['id'] );
			$number = filter_input( INPUT_POST, $variable_name, FILTER_SANITIZE_STRING );

			$credit_card->set_number( $number );

			// Expiration date
			$variable_name = sprintf( 'input_%s_2', $credit_card_field['id'] );
			$expiration_date = filter_input( INPUT_POST, $variable_name, FILTER_VALIDATE_INT, FILTER_FORCE_ARRAY );

			$month = array_shift( $expiration_date );
			$year  = array_shift( $expiration_date );

			$credit_card->set_expiration_month( $month );
			$credit_card->set_expiration_year( $year );

			// Security code
			$variable_name = sprintf( 'input_%s_3', $credit_card_field['id'] );
			$security_code = filter_input( INPUT_POST, $variable_name, FILTER_SANITIZE_STRING );

			$credit_card->set_security_code( $security_code );

			// Name
			$variable_name = sprintf( 'input_%s_5', $credit_card_field['id'] );
			$name = filter_input( INPUT_POST, $variable_name, FILTER_SANITIZE_STRING );

			$credit_card->set_name( $name );
		}

		return $credit_card;
	}

	//////////////////////////////////////////////////
	// Subscription
	//////////////////////////////////////////////////

	public function get_subscription() {
		// Amount
		$amount = 0;

		switch ( $this->feed->subscription_amount_type ) {
			case Pronamic_WP_Pay_Extensions_GravityForms_GravityForms::SUBSCRIPTION_AMOUNT_TOTAL :
				$items = $this->get_items();

				$amount = $items->get_amount();

				break;
			case Pronamic_WP_Pay_Extensions_GravityForms_GravityForms::SUBSCRIPTION_AMOUNT_FIELD :
				$field_id = $this->feed->subscription_amount_field;

				$product_fields = GFCommon::get_product_fields( $this->form, $this->lead );

				if ( isset( $product_fields['products'][ $field_id ] ) ) {
					$amount = GFCommon::to_number( $product_fields['products'][ $field_id ]['price'] );
					$amount *= $product_fields['products'][ $field_id ]['quantity'];
				}

				break;
		}

		if ( 0 === $amount ) {
			return;
		}

		// Interval
		$interval = '';
		$interval_period = 'D';

		switch ( $this->feed->subscription_interval_type ) {
			case Pronamic_WP_Pay_Extensions_GravityForms_GravityForms::SUBSCRIPTION_INTERVAL_FIELD :
				$field = RGFormsModel::get_field( $this->form, $this->feed->subscription_interval_field );

				if ( ! RGFormsModel::is_field_hidden( $this->form, $field, array(), $this->lead ) ) {
					if ( isset( $this->lead[ $this->feed->subscription_interval_field ] ) ) {
						$interval = $this->lead[ $this->feed->subscription_interval_field ];

						if ( '0' === $interval ) {
							return;
						}
					}
				}

				break;
			case Pronamic_WP_Pay_Extensions_GravityForms_GravityForms::SUBSCRIPTION_INTERVAL_FIXED :
				$interval        = $this->feed->subscription_interval;
				$interval_period = $this->feed->subscription_interval_period;

				break;
		}

		// Frequency
		$frequency = '';

		switch ( $this->feed->subscription_frequency_type ) {
			case Pronamic_WP_Pay_Extensions_GravityForms_GravityForms::SUBSCRIPTION_FREQUENCY_FIELD :
				$field = RGFormsModel::get_field( $this->form, $this->feed->subscription_frequency_field );

				if ( ! RGFormsModel::is_field_hidden( $this->form, $field, array(), $this->lead ) ) {
					if ( isset( $this->lead[ $this->feed->subscription_frequency_field ] ) ) {
						$frequency = $this->lead[ $this->feed->subscription_frequency_field ];
					}
				}

				break;
			case Pronamic_WP_Pay_Extensions_GravityForms_GravityForms::SUBSCRIPTION_FREQUENCY_FIXED :
				$frequency = $this->feed->subscription_frequency;

				break;
		}

		$subscription                  = new Pronamic_Pay_Subscription();
		$subscription->frequency       = $frequency;
		$subscription->interval        = $interval;
		$subscription->interval_period = $interval_period;
		$subscription->amount          = $amount;
		$subscription->currency        = $this->get_currency();
		$subscription->description     = $this->get_description();

		return $subscription;
	}
}
