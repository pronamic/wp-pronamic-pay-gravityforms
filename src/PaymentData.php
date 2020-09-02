<?php
/**
 * Payment data
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2020 Pronamic
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
use Pronamic\WordPress\Pay\Subscriptions\SubscriptionPhase;
use Pronamic\WordPress\Pay\Subscriptions\SubscriptionPhaseBuilder;
use Pronamic\WordPress\Pay\Subscriptions\ProratingRule;
use RGFormsModel;

/**
 * Title: WordPress pay extension Gravity Forms payment data
 * Description:
 * Copyright: 2005-2020 Pronamic
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

		if ( 'auto' === $field_id ) {
			$field_id = Util::get_detected_field_id( $field_name, $this->form, $this->lead );
		}

		if ( null === $field_id ) {
			return null;
		}

		if ( ! isset( $this->lead[ $field_id ] ) ) {
			return null;
		}

		return $this->lead[ $field_id ];
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
	 * Get currency alphabetic code.
	 *
	 * @see \Pronamic\WordPress\Pay\Payments\AbstractPaymentData::get_currency_alphabetic_code()
	 *
	 * @return string
	 */
	public function get_currency_alphabetic_code() {
		if ( isset( $this->lead['currency'] ) ) {
			return $this->lead['currency'];
		}

		return GFCommon::get_currency();
	}

	/**
	 * Get payment method.
	 *
	 * @return string|null
	 */
	public function get_payment_method() {
		$fields = GFCommon::get_fields_by_type( $this->form, array( Fields::PAYMENT_METHODS_FIELD_TYPE ) );

		foreach ( $fields as $field ) {
			if ( ! RGFormsModel::is_field_hidden( $this->form, $field, array(), $this->lead ) ) {
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
			return null;
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
					}
				}

				$interval = intval( $interval );

				// Do not start subscriptions for `0` interval.
				if ( 0 === $interval ) {
					return null;
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

		// Subscription.
		$subscription = new Subscription();

		$subscription->description = $this->get_description();

		// Proration phase.
		$start_date = new \DateTimeImmutable();

		$amount = $this->get_amount();

		$regular_phase = ( new SubscriptionPhaseBuilder() )
			->with_start_date( $start_date )
			->with_amount( $amount )
			->with_interval( $interval, $interval_period )
			->with_total_periods( $subscription->frequency )
			->create();

		if ( 'sync' === $this->feed->subscription_interval_date_type ) {
			$proration_rule = new ProratingRule( $interval_period );

			switch ( $interval_period ) {
				case 'D':
					break;
				case 'W':
					$proration_rule->by_numeric_day_of_the_week( \intval( $this->feed->subscription_interval_date ) );
					break;
				case 'M':
					$proration_rule->by_numeric_day_of_the_month( \intval( $this->feed->subscription_interval_date_day ) );
					break;
				case 'Y':
					$proration_rule->by_numeric_day_of_the_month( \intval( $this->feed->subscription_interval_date_day ) );
					$proration_rule->by_numeric_month( \intval( $this->feed->subscription_interval_date_month ) );
			}

			$align_date = $proration_rule->get_date( $start_date );

			$proration_phase = SubscriptionPhase::prorate( $regular_phase, $align_date, ( '1' === $this->feed->subscription_interval_date_prorate ) );

			$subscription->add_phase( $proration_phase );
		}

		$subscription->add_phase( $regular_phase );

		// Total amount.
		$subscription->set_total_amount( $amount );

		return $subscription;
	}
}
