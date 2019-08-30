<?php
/**
 * Payment data
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2019 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

use GFCommon;
use Pronamic\WordPress\DateTime\DateTime;
use Pronamic\WordPress\Money\Money;
use Pronamic\WordPress\Money\TaxedMoney;
use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Core\Util as Core_Util;
use Pronamic\WordPress\Pay\CreditCard;
use Pronamic\WordPress\Pay\Payments\Item;
use Pronamic\WordPress\Pay\Payments\Items;
use Pronamic\WordPress\Pay\Payments\PaymentData as Pay_PaymentData;
use Pronamic\WordPress\Pay\Subscriptions\Subscription;
use RGFormsModel;

/**
 * Title: WordPress pay extension Gravity Forms payment data
 * Description:
 * Copyright: 2005-2019 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.1.10
 * @since   1.0.1
 */
class PaymentData extends Pay_PaymentData {
	/**
	 * Gravity Forms form object
	 *
	 * @link http://www.gravityhelp.com/documentation/page/Form_Object
	 * @var array
	 */
	private $form;

	/**
	 * Gravity Forms entry object
	 *
	 * @link http://www.gravityhelp.com/documentation/page/Entry_Object
	 * @var array
	 */
	private $lead;

	/**
	 * Payment feed object
	 *
	 * @var PayFeed
	 */
	private $feed;

	/**
	 * Constructs and initialize an Gravity Forms iDEAL data proxy
	 *
	 * @param array   $form Gravity Forms form.
	 * @param array   $lead Gravity Forms lead/entry.
	 * @param PayFeed $feed Pay feed.
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
	 * @param string $field_name Field name.
	 *
	 * @return null|string
	 */
	public function get_field_value( $field_name ) {
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
		$order_id = $this->feed->order_id;

		if ( ! empty( $this->feed->entry_id_prefix ) ) {
			$order_id = $this->feed->entry_id_prefix . $order_id;
		}

		if ( ! GFCommon::has_merge_tag( $order_id ) ) {
			$order_id .= '{entry_id}';
		}

		$order_id = GFCommon::replace_variables( $order_id, $this->form, $this->lead );

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

		// Products.
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

			$items->add_item( $item );

			if ( isset( $product['options'] ) && is_array( $product['options'] ) ) {
				foreach ( $product['options'] as $option ) {
					$description = $option['option_label'];
					$price       = GFCommon::to_number( $option['price'] );

					$item = new Item();
					$item->set_number( $number ++ );
					$item->set_description( $description );
					$item->set_price( $price );
					$item->set_quantity( $quantity ); // Product quantity.

					$items->add_item( $item );
				}
			}
		}

		// Shipping.
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

				$items->add_item( $item );
			}
		}

		// Donations.
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

				$items->add_item( $item );
			}
		}

		return $items;
	}

	/**
	 * Get (prorated) amount.
	 *
	 * @return TaxedMoney
	 */
	public function get_amount() {
		$amount = parent::get_amount();

		$subscription = $this->get_subscription();

		if ( ! $subscription ) {
			return $amount;
		}

		if ( 'sync' !== $this->feed->subscription_interval_date_type ) {
			return $amount;
		}

		if ( '1' !== $this->feed->subscription_interval_date_prorate ) {
			return $amount;
		}

		// Prorate.
		$interval = $subscription->get_date_interval();

		$now = new DateTime();

		$next_date = clone $now;
		$next_date->add( $interval );

		$days_diff = $now->diff( $next_date )->days;

		$interval_date       = $subscription->get_interval_date();
		$interval_date_day   = $subscription->get_interval_date_day();
		$interval_date_month = $subscription->get_interval_date_month();

		if ( 'W' === $subscription->interval_period && is_numeric( $interval_date_day ) ) {
			$days_delta = $interval_date_day - $next_date->format( 'w' );

			$next_date->modify( sprintf( '+%s days', $days_delta ) );
		}

		if ( 'M' === $subscription->interval_period && is_numeric( $interval_date ) ) {
			$next_date->setDate( $next_date->format( 'Y' ), $next_date->format( 'm' ), $interval_date );
		}

		if ( 'M' === $subscription->interval_period && 'last' === $interval_date ) {
			$next_date->modify( 'last day of ' . $next_date->format( 'F Y' ) );
		}

		if ( 'Y' === $subscription->interval_period && is_numeric( $interval_date_month ) ) {
			$next_date->setDate( $next_date->format( 'Y' ), $interval_date_month, $next_date->format( 'd' ) );

			if ( 'last' === $interval_date ) {
				$next_date->modify( 'last day of ' . $next_date->format( 'F Y' ) );
			}
		}

		$prorated_days_diff = $now->diff( $next_date )->days;

		$amount_per_day = ( $amount->get_value() / $days_diff );

		$prorated_amount = ( $amount_per_day * $prorated_days_diff );

		$amount->set_value( $prorated_amount );

		return $amount;
	}

	/**
	 * Get currency alphabetic code.
	 *
	 * @see \Pronamic\WordPress\Pay\Payments\AbstractPaymentData::get_currency_alphabetic_code()
	 *
	 * @return string
	 */
	public function get_currency_alphabetic_code() {
		return GFCommon::get_currency();
	}

	/**
	 * Get email.
	 *
	 * @return string
	 */
	public function get_email() {
		return $this->get_field_value( 'email' );
	}

	/**
	 * Get first name.
	 *
	 * @return string
	 */
	public function get_first_name() {
		return $this->get_field_value( 'first_name' );
	}

	/**
	 * Get last name.
	 *
	 * @return string
	 */
	public function get_last_name() {
		return $this->get_field_value( 'last_name' );
	}

	/**
	 * Get customer name.
	 *
	 * @return string
	 */
	public function get_customer_name() {
		$parts = array(
			$this->get_field_value( 'first_name' ),
			$this->get_field_value( 'last_name' ),
		);

		$name = array_filter( $parts );

		return implode( ' ', $name );
	}

	/**
	 * Get address.
	 *
	 * @return string
	 */
	public function get_address() {
		$parts = array(
			$this->get_field_value( 'address1' ),
			$this->get_field_value( 'address2' ),
		);

		$address = array_filter( $parts );

		return implode( ' ', $address );
	}

	/**
	 * Get city.
	 *
	 * @return string
	 */
	public function get_city() {
		return $this->get_field_value( 'city' );
	}

	/**
	 * Get ZIP.
	 *
	 * @return string
	 */
	public function get_zip() {
		return $this->get_field_value( 'zip' );
	}

	/**
	 * Get country.
	 *
	 * @return string
	 */
	public function get_country() {
		return $this->get_field_value( 'country' );
	}

	/**
	 * Get telephone number.
	 *
	 * @return string
	 */
	public function get_telephone_number() {
		return $this->get_field_value( 'telephone_number' );
	}

	/**
	 * Get normal return URL.
	 *
	 * @return false|null|string
	 */
	public function get_normal_return_url() {
		$url = $this->feed->get_url( Links::OPEN );

		if ( empty( $url ) ) {
			$url = parent::get_normal_return_url();
		}

		return $url;
	}

	/**
	 * Get cancel URL.
	 *
	 * @return false|null|string
	 */
	public function get_cancel_url() {
		$url = $this->feed->get_url( Links::CANCEL );

		if ( empty( $url ) ) {
			$url = parent::get_cancel_url();
		}

		return $url;
	}

	/**
	 * Get success URL.
	 *
	 * @return false|null|string
	 */
	public function get_success_url() {
		$url = $this->feed->get_url( Links::SUCCESS );

		if ( empty( $url ) ) {
			$url = parent::get_success_url();
		}

		return $url;
	}

	/**
	 * Get error URL.
	 *
	 * @return false|null|string
	 */
	public function get_error_url() {
		$url = $this->feed->get_url( Links::ERROR );

		if ( empty( $url ) ) {
			$url = parent::get_error_url();
		}

		return $url;
	}

	/**
	 * Get payment method.
	 *
	 * @return string|null
	 */
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

	/**
	 * Get issuer ID.
	 *
	 * @return string|null
	 */
	public function get_issuer_id() {
		$fields = GFCommon::get_fields_by_type( $this->form, array( IssuersField::TYPE ) );

		foreach ( $fields as $field ) {
			if ( RGFormsModel::is_field_hidden( $this->form, $field, array() ) ) {
				continue;
			}

			return RGFormsModel::get_field_value( $field );
		}
	}

	/**
	 * Get credit card.
	 *
	 * @return CreditCard|null
	 */
	public function get_credit_card() {
		$credit_card = null;

		$credit_card_fields = GFCommon::get_fields_by_type( $this->form, array( 'creditcard' ) );

		$credit_card_field = array_shift( $credit_card_fields );

		if ( $credit_card_field ) {
			$credit_card = new CreditCard();

			// Number.
			$variable_name = sprintf( 'input_%s_1', $credit_card_field['id'] );
			$number        = filter_input( INPUT_POST, $variable_name, FILTER_SANITIZE_STRING );

			$credit_card->set_number( $number );

			// Expiration date.
			$variable_name   = sprintf( 'input_%s_2', $credit_card_field['id'] );
			$expiration_date = filter_input( INPUT_POST, $variable_name, FILTER_VALIDATE_INT, FILTER_FORCE_ARRAY );

			$month = array_shift( $expiration_date );
			$year  = array_shift( $expiration_date );

			$credit_card->set_expiration_month( $month );
			$credit_card->set_expiration_year( $year );

			// Security code.
			$variable_name = sprintf( 'input_%s_3', $credit_card_field['id'] );
			$security_code = filter_input( INPUT_POST, $variable_name, FILTER_SANITIZE_STRING );

			$credit_card->set_security_code( $security_code );

			// Name.
			$variable_name = sprintf( 'input_%s_5', $credit_card_field['id'] );
			$name          = filter_input( INPUT_POST, $variable_name, FILTER_SANITIZE_STRING );

			$credit_card->set_name( $name );
		}

		return $credit_card;
	}

	/**
	 * Get subscription.
	 *
	 * @return Subscription|null
	 */
	public function get_subscription() {
		// Amount.
		$amount = 0;

		switch ( $this->feed->subscription_amount_type ) {
			case GravityForms::SUBSCRIPTION_AMOUNT_TOTAL:
				$items = $this->get_items();

				$amount = $items->get_amount()->get_value();

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

		// Interval.
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

						$interval_period = Core_Util::string_to_interval_period( $interval );

						// Default to interval period in days.
						if ( null === $interval_period ) {
							$interval_period = 'D';
						}

						$interval = intval( $interval );

						// Do not start subscriptions for `0` interval.
						if ( 0 === $interval ) {
							return;
						}
					}
				}

				break;
			case GravityForms::SUBSCRIPTION_INTERVAL_FIXED:
				$interval        = $this->feed->subscription_interval;
				$interval_period = $this->feed->subscription_interval_period;

				if ( 'sync' === $this->feed->subscription_interval_date_type ) {
					$interval_date       = $this->feed->subscription_interval_date;
					$interval_date_day   = $this->feed->subscription_interval_date_day;
					$interval_date_month = $this->feed->subscription_interval_date_month;
				}

				break;
		}

		// Frequency.
		$frequency = null;

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

		$subscription->set_total_amount(
			new TaxedMoney(
				$amount,
				$this->get_currency_alphabetic_code()
			)
		);

		return $subscription;
	}
}
