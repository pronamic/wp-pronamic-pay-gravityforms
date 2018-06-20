<?php

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

use GFCommon;
use Pronamic\WordPress\Money\Money;
use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\CreditCard;
use Pronamic\WordPress\Pay\Payments\Item;
use Pronamic\WordPress\Pay\Payments\Items;
use Pronamic\WordPress\Pay\Payments\PaymentData as Pay_PaymentData;
use Pronamic\WordPress\Pay\Subscriptions\Subscription;
use RGFormsModel;

/**
 * Title: WordPress pay extension Gravity Forms payment data
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.1
 * @since   1.0.1
 */
class PaymentData extends Pay_PaymentData {
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
	 * @var PayFeed
	 */
	private $feed;

	/**
	 * Constructs and initialize an Gravity Forms iDEAL data proxy
	 *
	 * @param array   $form
	 * @param array   $lead
	 * @param PayFeed $feed
	 */
	public function __construct( $form, $lead, $feed ) {
		parent::__construct();

		$this->form = $form;
		$this->lead = $lead;
		$this->feed = $feed;
	}

	/**
	 * Get the field value of the specified field
	 *
	 * @param string $field_name
	 *
	 * @return null|string
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

	/**
	 * Get source indicator
	 *
	 * @see Pronamic_Pay_PaymentDataInterface::get_source()
	 *
	 * @return string
	 */
	public function get_source() {
		return 'gravityformsideal';
	}

	/**
	 * Get source ID
	 *
	 * @see \Pronamic\WordPress\Pay\Payments\AbstractPaymentData::get_source_id()
	 *
	 * @return string
	 */
	public function get_source_id() {
		return $this->lead['id'];
	}

	/**
	 * Get description
	 *
	 * @see \Pronamic\WordPress\Pay\Payments\AbstractPaymentData::get_description()
	 *
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
	 * @see \Pronamic\WordPress\Pay\Payments\AbstractPaymentData::get_order_id()
	 *
	 * @return string
	 */
	public function get_order_id() {
		$prefix = $this->feed->entry_id_prefix;

		// @see http://www.gravityhelp.com/documentation/page/Entry_Object#Standard
		$order_id = $prefix . $this->lead['id'];

		// If prefix is a merge tag, only use prefix as order ID.
		if ( '{' === substr( $prefix, 0, 1 ) && '}' === substr( $prefix, -1 ) ) {
			$order_id = GFCommon::replace_variables( $prefix, $this->form, $this->lead );
		}

		return $order_id;
	}

	/**
	 * Get items
	 *
	 * @see \Pronamic\WordPress\Pay\Payments\AbstractPaymentData::get_items()
	 *
	 * @return Items
	 */
	public function get_items() {
		$items = new Items();

		$number = 0;

		// Products
		$products = GFCommon::get_product_fields( $this->form, $this->lead );

		foreach ( $products['products'] as $product ) {
			$description = $product['name'];
			$price       = GFCommon::to_number( $product['price'] );
			$quantity    = $product['quantity'];

			$item = new Item();
			$item->set_number( $number ++ );
			$item->set_description( $description );
			$item->set_price( $price );
			$item->set_quantity( $quantity );

			$items->addItem( $item );

			if ( isset( $product['options'] ) && is_array( $product['options'] ) ) {
				foreach ( $product['options'] as $option ) {
					$description = $option['option_label'];
					$price       = GFCommon::to_number( $option['price'] );

					$item = new Item();
					$item->set_number( $number ++ );
					$item->set_description( $description );
					$item->set_price( $price );
					$item->set_quantity( $quantity ); // Product quantity

					$items->addItem( $item );
				}
			}
		}

		// Shipping
		if ( isset( $products['shipping'] ) ) {
			$shipping = $products['shipping'];

			if ( isset( $shipping['price'] ) && ! empty( $shipping['price'] ) ) {
				$description = $shipping['name'];
				$price       = GFCommon::to_number( $shipping['price'] );
				$quantity    = 1;

				$item = new Item();
				$item->set_number( $number ++ );
				$item->set_description( $description );
				$item->set_price( $price );
				$item->set_quantity( $quantity );

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

				$price    = GFCommon::to_number( $value );
				$quantity = 1;

				$item = new Item();
				$item->set_number( $i );
				$item->set_description( $description );
				$item->set_price( $price );
				$item->set_quantity( $quantity );

				$items->addItem( $item );
			}
		}

		return $items;
	}

	/**
	 * Get currency alphabetic code
	 *
	 * @see \Pronamic\WordPress\Pay\Payments\AbstractPaymentData::get_currency_alphabetic_code()
	 *
	 * @return string
	 */
	public function get_currency_alphabetic_code() {
		return GFCommon::get_currency();
	}

	public function get_email() {
		return $this->get_field_value( 'email' );
	}

	public function get_first_name() {
		return $this->get_field_value( 'first_name' );
	}

	public function get_last_name() {
		return $this->get_field_value( 'last_name' );
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

	public function get_normal_return_url() {
		$url = $this->feed->get_url( Links::OPEN );

		if ( empty( $url ) ) {
			$url = parent::get_normal_return_url();
		}

		return $url;
	}

	public function get_cancel_url() {
		$url = $this->feed->get_url( Links::CANCEL );

		if ( empty( $url ) ) {
			$url = parent::get_cancel_url();
		}

		return $url;
	}

	public function get_success_url() {
		$url = $this->feed->get_url( Links::SUCCESS );

		if ( empty( $url ) ) {
			$url = parent::get_success_url();
		}

		return $url;
	}

	public function get_error_url() {
		$url = $this->feed->get_url( Links::ERROR );

		if ( empty( $url ) ) {
			$url = parent::get_error_url();
		}

		return $url;
	}

	public function get_payment_method() {
		$fields = GFCommon::get_fields_by_type( $this->form, array( Fields::PAYMENT_METHODS_FIELD_TYPE ) );

		foreach ( $fields as $field ) {
			if ( ! RGFormsModel::is_field_hidden( $this->form, $field, array() ) ) {
				$method = RGFormsModel::get_field_value( $field );

				if ( ! $this->get_subscription() && PaymentMethods::DIRECT_DEBIT_IDEAL === $method ) {
					// DIRECT_DEBIT_IDEAL can only be used for subscription payments.

					$method = PaymentMethods::IDEAL;
				}

				return $method;
			}
		}
	}

	public function get_issuer_id() {
		$fields = GFCommon::get_fields_by_type( $this->form, array( IssuersField::TYPE ) );

		foreach ( $fields as $field ) {
			if ( RGFormsModel::is_field_hidden( $this->form, $field, array() ) ) {
				continue;
			}

			return RGFormsModel::get_field_value( $field );
		}
	}

	public function get_credit_card() {
		$credit_card = null;

		$credit_card_fields = GFCommon::get_fields_by_type( $this->form, array( 'creditcard' ) );

		$credit_card_field = array_shift( $credit_card_fields );

		if ( $credit_card_field ) {
			$credit_card = new CreditCard();

			// Number
			$variable_name = sprintf( 'input_%s_1', $credit_card_field['id'] );
			$number        = filter_input( INPUT_POST, $variable_name, FILTER_SANITIZE_STRING );

			$credit_card->set_number( $number );

			// Expiration date
			$variable_name   = sprintf( 'input_%s_2', $credit_card_field['id'] );
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
			$name          = filter_input( INPUT_POST, $variable_name, FILTER_SANITIZE_STRING );

			$credit_card->set_name( $name );
		}

		return $credit_card;
	}

	public function get_subscription() {
		// Amount
		$amount = 0;

		switch ( $this->feed->subscription_amount_type ) {
			case GravityForms::SUBSCRIPTION_AMOUNT_TOTAL:
				$items = $this->get_items();

				$amount = $items->get_amount()->get_amount();

				break;
			case GravityForms::SUBSCRIPTION_AMOUNT_FIELD:
				$field_id = $this->feed->subscription_amount_field;

				$product_fields = GFCommon::get_product_fields( $this->form, $this->lead );

				if ( isset( $product_fields['products'][ $field_id ] ) ) {
					$amount  = GFCommon::to_number( $product_fields['products'][ $field_id ]['price'] );
					$amount *= $product_fields['products'][ $field_id ]['quantity'];
				}

				break;
		}

		if ( 0 === $amount ) {
			return;
		}

		// Interval
		$interval            = '';
		$interval_period     = 'D';
		$interval_date       = '';
		$interval_date_day   = '';
		$interval_date_month = '';

		switch ( $this->feed->subscription_interval_type ) {
			case GravityForms::SUBSCRIPTION_INTERVAL_FIELD:
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
			case GravityForms::SUBSCRIPTION_INTERVAL_FIXED:
				$interval            = $this->feed->subscription_interval;
				$interval_period     = $this->feed->subscription_interval_period;
				$interval_date       = $this->feed->subscription_interval_date;
				$interval_date_day   = $this->feed->subscription_interval_date_day;
				$interval_date_month = $this->feed->subscription_interval_date_month;

				break;
		}

		// Frequency
		$frequency = '';

		switch ( $this->feed->subscription_frequency_type ) {
			case GravityForms::SUBSCRIPTION_FREQUENCY_FIELD:
				$field = RGFormsModel::get_field( $this->form, $this->feed->subscription_frequency_field );

				if ( ! RGFormsModel::is_field_hidden( $this->form, $field, array(), $this->lead ) ) {
					if ( isset( $this->lead[ $this->feed->subscription_frequency_field ] ) ) {
						$frequency = intval( $this->lead[ $this->feed->subscription_frequency_field ] );
					}
				}

				break;
			case GravityForms::SUBSCRIPTION_FREQUENCY_FIXED:
				$frequency = $this->feed->subscription_frequency;

				break;
		}

		$subscription                      = new Subscription();
		$subscription->frequency           = $frequency;
		$subscription->interval            = $interval;
		$subscription->interval_period     = $interval_period;
		$subscription->interval_date       = $interval_date;
		$subscription->interval_date_day   = $interval_date_day;
		$subscription->interval_date_month = $interval_date_month;
		$subscription->description         = $this->get_description();

		$subscription->set_amount( new Money(
			$amount,
			$this->get_currency_alphabetic_code()
		) );

		return $subscription;
	}
}
