<?php
/**
 * Payment data
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2022 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

use GFCommon;
use Pronamic\WordPress\DateTime\DateTime;
use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Core\Util as Core_Util;
use Pronamic\WordPress\Pay\CreditCard;
use RGFormsModel;

/**
 * Title: WordPress pay extension Gravity Forms payment data
 * Description:
 * Copyright: 2005-2022 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.1.10
 * @since   1.0.1
 */
class PaymentData {
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
	 * Get description.
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
	 * Get order ID.
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
	 * Get frequency.
	 *
	 * @return int|null
	 */
	public function get_subscription_frequency() {
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
				$frequency = \intval( $this->feed->subscription_number_periods );

				break;
		}

		return empty( $frequency ) ? null : $frequency;
	}

	/**
	 * Get subscription interval.
	 *
	 * @return object
	 */
	public function get_subscription_interval() {
		$interval = (object) array(
			'unit'  => 'D',
			'value' => null,
		);

		switch ( $this->feed->subscription_interval_type ) {
			case GravityForms::SUBSCRIPTION_INTERVAL_FIELD:
				$field = RGFormsModel::get_field( $this->form, $this->feed->subscription_interval_field );

				if ( ! RGFormsModel::is_field_hidden( $this->form, $field, array(), $this->lead ) ) {
					if ( isset( $this->lead[ $this->feed->subscription_interval_field ] ) ) {
						$value = $this->lead[ $this->feed->subscription_interval_field ];

						// Interval value.
						$interval->value = \intval( $value );

						// Interval unit.
						$unit = Core_Util::string_to_interval_period( $value );

						if ( null !== $unit ) {
							$interval->unit = $unit;
						}
					}
				}

				return $interval;
			case GravityForms::SUBSCRIPTION_INTERVAL_FIXED:
				$interval->value = \intval( $this->feed->subscription_interval );
				$interval->unit  = $this->feed->subscription_interval_period;

				return $interval;
			default:
				return $interval;
		}
	}
}
