<?php
/**
 * Processor
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2019 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

use GFAddOn;
use GFCommon;
use Pronamic\WordPress\Money\Currency;
use Pronamic\WordPress\Money\Money;
use Pronamic\WordPress\Money\TaxedMoney;
use Pronamic\WordPress\Pay\Address;
use Pronamic\WordPress\Pay\ContactName;
use Pronamic\WordPress\Pay\Customer;
use Pronamic\WordPress\Pay\Plugin;
use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Payments\PaymentLines;
use Pronamic\WordPress\Pay\Payments\PaymentLineType;
use WP_Error;

/**
 * Title: WordPress pay extension Gravity Forms processor
 * Description:
 * Copyright: 2005-2019 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.1.4
 * @since   1.0.0
 */
class Processor {
	/**
	 * The Pronamic Pay Gravity Forms extension
	 *
	 * @var Extension
	 */
	private $extension;

	/**
	 * The Gravity Forms form
	 *
	 * @var array
	 */
	private $form;

	/**
	 * The Gravity Forms form ID
	 *
	 * @var string
	 */
	private $form_id;

	/**
	 * Process flag
	 *
	 * @var boolean
	 */
	private $process;

	/**
	 * Payment feed
	 *
	 * @var PayFeed
	 */
	private $feed;

	/**
	 * Gateway
	 *
	 * @var Payment
	 */
	private $gateway;

	/**
	 * Payment
	 *
	 * @var Payment
	 */
	private $payment;

	/**
	 * Error
	 *
	 * @var WP_Error
	 */
	private $error;

	/**
	 * Is entry created?
	 *
	 * @var boolean
	 */
	private $entry_created;

	/**
	 * Constructs and initalize an Gravity Forms payment form processor
	 *
	 * @param array     $form      Gravity Forms form.
	 * @param Extension $extension Extension.
	 */
	public function __construct( array $form, Extension $extension ) {
		$this->extension = $extension;
		$this->form      = $form;
		$this->form_id   = isset( $form['id'] ) ? $form['id'] : null;

		// Get payment feed by form ID.
		$this->feed = FeedsDB::get_conditioned_feed_by_form_id( $this->form_id );

		if ( null !== $this->feed ) {
			$this->process = true;

			$this->add_hooks();
		}
	}

	/**
	 * Add hooks.
	 */
	private function add_hooks() {
		/*
		 * Pre submission.
		 */
		add_action( 'gform_pre_submission_' . $this->form_id, array( $this, 'pre_submission' ), 10, 1 );

		/*
		 * Handle submission.
		 */

		// Lead.
		add_action( 'gform_entry_post_save', array( $this, 'entry_post_save' ), 10, 2 );

		// Delay (@see GFFormDisplay::handle_submission > GFCommon::send_form_submission_notifications).
		add_filter( 'gform_disable_admin_notification_' . $this->form_id, array( $this, 'maybe_delay_admin_notification' ), 10, 3 );
		add_filter( 'gform_disable_user_notification_' . $this->form_id, array( $this, 'maybe_delay_user_notification' ), 10, 3 );
		add_filter( 'gform_disable_post_creation_' . $this->form_id, array( $this, 'maybe_delay_post_creation' ), 10, 3 );
		add_filter( 'gform_disable_notification_' . $this->form_id, array( $this, 'maybe_delay_notification' ), 10, 4 );

		// Confirmation (@see GFFormDisplay::handle_confirmation).
		// @link http://www.gravityhelp.com/documentation/page/Gform_confirmation.
		add_filter( 'gform_confirmation_' . $this->form_id, array( $this, 'confirmation' ), 10, 4 );

		/*
		 * After submission.
		 */
		add_action( 'gform_after_submission_' . $this->form_id, array( $this, 'after_submission' ), 10, 2 );

		add_filter( 'gform_is_delayed_pre_process_feed_' . $this->form_id, array( $this, 'maybe_delay_feed' ), 10, 4 );
		add_filter( 'gravityflow_is_delayed_pre_process_workflow', array( $this, 'maybe_delay_workflow' ), 10, 3 );
	}

	/**
	 * Check if we are processing the passed in form.
	 *
	 * @param array $form Gravity Forms form.
	 *
	 * @return bool True if the passed in form is processed, false otherwise.
	 */
	public function is_processing( $form ) {
		$is_form = false;

		if ( isset( $form['id'] ) ) {
			$is_form = ( absint( $this->form_id ) === absint( $form['id'] ) );
		}

		$is_processing = $this->process && $is_form;

		return $is_processing;
	}

	/**
	 * Pre submission.
	 *
	 * @param array $form Gravity Forms form.
	 */
	public function pre_submission( $form ) {
		if ( ! $this->is_processing( $form ) ) {
			return;
		}

		/*
		 * Delay actions.
		 *
		 * The Add-Ons mainly use the 'gform_after_submission' to export entries, to delay this we have to remove these
		 * actions before this filter executes.
		 *
		 * @link https://github.com/wp-premium/gravityforms/blob/1.8.16/form_display.php#L101-L103.
		 * @link https://github.com/wp-premium/gravityforms/blob/1.8.16/form_display.php#L111-L113.
		 */

		foreach ( $this->feed->delay_actions as $slug => $data ) {
			$delayed_payment_integration = ( isset( $data['delayed_payment_integration'] ) && true === $data['delayed_payment_integration'] );

			if ( isset( $data['addon'] ) && ! $delayed_payment_integration ) {
				remove_filter( 'gform_entry_post_save', array( $data['addon'], 'maybe_process_feed' ), 10 );
			}

			if ( isset( $data['delay_callback'] ) ) {
				call_user_func( $data['delay_callback'] );
			}
		}
	}

	/**
	 * Entry post save.
	 *
	 * @param array $lead Gravity Forms lead/entry.
	 * @param array $form Gravity Forms form.
	 *
	 * @return array
	 */
	public function entry_post_save( $lead, $form ) {
		if ( ! $this->is_processing( $form ) ) {
			return $lead;
		}

		// Check for payment ID.
		$payment_id = gform_get_meta( $lead['id'], 'pronamic_payment_id' );

		if ( ! empty( $payment_id ) ) {
			return $lead;
		}

		// Gateway.
		$this->gateway = Plugin::get_gateway( $this->feed->config_id );

		if ( ! $this->gateway ) {
			return $lead;
		}

		// Payment data.
		$data = new PaymentData( $form, $lead, $this->feed );

		// Does payment data contain any items?
		$items = $data->get_items();

		if ( 0 === iterator_count( $items ) ) {
			return $lead;
		}

		// Update entry meta.
		gform_update_meta( $lead['id'], 'ideal_feed_id', $this->feed->id );
		gform_update_meta( $lead['id'], 'payment_gateway', 'pronamic_pay' );

		$lead[ LeadProperties::PAYMENT_STATUS ]   = PaymentStatuses::PROCESSING;
		$lead[ LeadProperties::PAYMENT_DATE ]     = gmdate( 'y-m-d H:i:s' );
		$lead[ LeadProperties::TRANSACTION_TYPE ] = GravityForms::TRANSACTION_TYPE_PAYMENT;

		GravityForms::update_entry( $lead );

		// Set payment method to iDEAL if issuer ID is set.
		$payment_method = $data->get_payment_method();

		if ( null === $data->get_payment_method() && null !== $data->get_issuer_id() ) {
			$payment_method = PaymentMethods::IDEAL;
		}

		// Don't delay feed actions for free payments.
		$amount = $data->get_amount()->get_value();

		if ( empty( $amount ) ) {
			$this->feed->delay_actions = array();
		}

		// Payment.
		$payment = new Payment();

		$payment->title = sprintf(
			/* translators: %s: title */
			__( 'Payment for %s', 'pronamic_ideal' ),
			$data->get_title()
		);

		$payment->user_id                = $data->get_user_id();
		$payment->config_id              = $this->feed->config_id;
		$payment->order_id               = $data->get_order_id();
		$payment->description            = $data->get_description();
		$payment->source                 = $data->get_source();
		$payment->source_id              = $data->get_source_id();
		$payment->email                  = $data->get_email();
		$payment->method                 = $payment_method;
		$payment->issuer                 = $data->get_issuer( $payment_method );
		$payment->analytics_client_id    = $data->get_analytics_client_id();
		$payment->recurring              = $data->get_recurring();
		$payment->subscription           = $data->get_subscription();
		$payment->subscription_id        = $data->get_subscription_id();
		$payment->subscription_source_id = $data->get_subscription_source_id();
		$payment->set_total_amount( $data->get_amount() );
		$payment->set_credit_card( $data->get_credit_card() );

		// Name.
		$name = new ContactName();

		$name->set_prefix( $data->get_field_value( 'prefix_name' ) );
		$name->set_first_name( $data->get_field_value( 'first_name' ) );
		$name->set_middle_name( $data->get_field_value( 'middle_name' ) );
		$name->set_last_name( $data->get_field_value( 'last_name' ) );
		$name->set_suffix( $data->get_field_value( 'suffix_name' ) );

		// Customer.
		$customer = new Customer();

		$customer->set_name( $name );
		$customer->set_phone( $data->get_field_value( 'telephone_number' ) );
		$customer->set_email( $data->get_field_value( 'email' ) );

		$payment->set_customer( $customer );

		// Address.
		$address = new Address();

		$country_name = $data->get_field_value( 'country' );
		$country_code = GFCommon::get_country_code( $country_name );

		if ( empty( $country_code ) ) {
			$country_code = null;
		}

		$address->set_name( $name );
		$address->set_line_1( $data->get_field_value( 'address1' ) );
		$address->set_line_2( $data->get_field_value( 'address2' ) );
		$address->set_postal_code( $data->get_field_value( 'zip' ) );
		$address->set_city( $data->get_field_value( 'city' ) );
		$address->set_region( $data->get_field_value( 'state' ) );
		$address->set_country_code( $country_code );
		$address->set_country_name( $country_name );
		$address->set_email( $data->get_field_value( 'email' ) );
		$address->set_phone( $data->get_field_value( 'telephone_number' ) );

		$payment->set_billing_address( $address );
		$payment->set_shipping_address( $address );

		// Lines.
		$payment->lines = new PaymentLines();

		$product_fields = GFCommon::get_product_fields( $form, $lead );

		if ( is_array( $product_fields ) ) {
			// Products.
			if ( array_key_exists( 'products', $product_fields ) && is_array( $product_fields['products'] ) ) {
				$products = $product_fields['products'];

				foreach ( $products as $key => $product ) {
					$line = $payment->lines->new_line();

					$line->set_id( $key );

					if ( array_key_exists( 'name', $product ) ) {
						$line->set_name( $product['name'] );
					}

					if ( array_key_exists( 'price', $product ) ) {
						$value = GFCommon::to_number( $product['price'] );

						$line->set_unit_price( new TaxedMoney( $value, $payment->get_total_amount()->get_currency() ) );

						if ( array_key_exists( 'quantity', $product ) ) {
							$value = ( $value * intval( $product['quantity'] ) );
						}

						$line->set_total_amount( new TaxedMoney( $value, $payment->get_total_amount()->get_currency() ) );
					}

					if ( array_key_exists( 'quantity', $product ) ) {
						$line->set_quantity( intval( $product['quantity'] ) );
					}

					if ( array_key_exists( 'options', $product ) && is_array( $product['options'] ) ) {
						$options = $product['options'];

						foreach ( $options as $option ) {
							$line = $payment->lines->new_line();

							$line->set_quantity( 1 );

							if ( array_key_exists( 'option_label', $option ) ) {
								$line->set_name( $option['option_label'] );
							}

							if ( array_key_exists( 'price', $option ) ) {
								$value = GFCommon::to_number( $option['price'] );

								$line->set_unit_price( new TaxedMoney( $value, $payment->get_total_amount()->get_currency() ) );

								$line->set_total_amount( new TaxedMoney( $value, $payment->get_total_amount()->get_currency() ) );
							}
						}
					}
				}
			}

			// Shipping.
			if ( array_key_exists( 'shipping', $product_fields ) && is_array( $product_fields['shipping'] ) ) {
				$shipping = $product_fields['shipping'];

				$line = $payment->lines->new_line();

				$line->set_type( PaymentLineType::SHIPPING );
				$line->set_quantity( 1 );

				if ( array_key_exists( 'id', $shipping ) ) {
					$line->set_id( $shipping['id'] );
				}

				if ( array_key_exists( 'name', $shipping ) ) {
					$line->set_name( $shipping['name'] );
				}

				if ( array_key_exists( 'price', $shipping ) ) {
					$value = $shipping['price'];

					$line->set_unit_price( new TaxedMoney( $value, $payment->get_total_amount()->get_currency() ) );

					$line->set_total_amount( new TaxedMoney( $value, $payment->get_total_amount()->get_currency() ) );
				}
			}
		}

		// Start.
		$this->payment = Plugin::start_payment( $payment );

		$this->error = $this->gateway->get_error();

		// Update entry meta.
		gform_update_meta( $lead['id'], 'pronamic_payment_id', $this->payment->get_id() );
		gform_update_meta( $lead['id'], 'pronamic_subscription_id', $this->payment->get_subscription_id() );

		$lead[ LeadProperties::PAYMENT_STATUS ] = PaymentStatuses::transform( $this->payment->get_status() );
		$lead[ LeadProperties::PAYMENT_AMOUNT ] = $this->payment->get_total_amount()->get_value();
		$lead[ LeadProperties::TRANSACTION_ID ] = $this->payment->get_transaction_id();

		GravityForms::update_entry( $lead );

		// Error handling.
		if ( $this->gateway->has_error() ) {
			return $lead;
		}

		// Pending payment.
		if ( PaymentStatuses::PROCESSING === $lead[ LeadProperties::PAYMENT_STATUS ] ) {
			// Add pending payment.
			$action = array(
				'id'             => $this->payment->get_id(),
				'transaction_id' => $this->payment->get_transaction_id(),
				'amount'         => $this->payment->get_total_amount()->get_value(),
				'entry_id'       => $lead['id'],
			);

			$this->extension->payment_action( 'add_pending_payment', $lead, $action );
		}

		// Return lead.
		return $lead;
	}

	/**
	 * Maybe delay notifications.
	 *
	 * @param bool  $is_disabled  Is disabled flag.
	 * @param array $notification Gravity Forms notification.
	 * @param array $form         Gravity Forms form.
	 * @param array $lead         Gravity Forms lead/entry.
	 *
	 * @return bool
	 */
	public function maybe_delay_notification( $is_disabled, $notification, $form, $lead ) {
		if ( ! $is_disabled && $this->is_processing( $form ) ) {
			$is_disabled = in_array( $notification['id'], $this->feed->delay_notification_ids, true );
		}

		return $is_disabled;
	}

	/**
	 * Maybe delay admin notification
	 *
	 * @param bool  $is_disabled Is disabled flag.
	 * @param array $form        Gravity Forms form.
	 * @param array $lead        Gravity Forms lead/entry.
	 *
	 * @return boolean true if admin notification is disabled / delayed, false otherwise
	 */
	public function maybe_delay_admin_notification( $is_disabled, $form, $lead ) {
		if ( ! $is_disabled && $this->is_processing( $form ) ) {
			$is_disabled = $this->feed->delay_admin_notification;
		}

		return $is_disabled;
	}

	/**
	 * Maybe delay user notification
	 *
	 * @param bool  $is_disabled Is disabled flag.
	 * @param array $form        Gravity Forms form.
	 * @param array $lead        Gravity Forms lead/entry.
	 *
	 * @return boolean true if user notification is disabled / delayed, false otherwise
	 */
	public function maybe_delay_user_notification( $is_disabled, $form, $lead ) {
		if ( ! $is_disabled && $this->is_processing( $form ) ) {
			$is_disabled = $this->feed->delay_user_notification;
		}

		return $is_disabled;
	}

	/**
	 * Maybe delay post creation.
	 *
	 * @param boole $is_disabled Is disabled flag.
	 * @param array $form        Gravity Forms form.
	 * @param array $lead        Gravity Forms lead/entry.
	 *
	 * @return boolean true if post creation is disabled / delayed, false otherwise
	 */
	public function maybe_delay_post_creation( $is_disabled, $form, $lead ) {
		if ( ! $is_disabled && $this->is_processing( $form ) ) {
			$is_disabled = $this->feed->delay_post_creation;
		}

		return $is_disabled;
	}

	/**
	 * Maybe delay feed.
	 *
	 * @param bool   $is_delayed Is delayed flag.
	 * @param array  $form       Gravity Forms form.
	 * @param array  $entry      Gravity Forms entry.
	 * @param string $slug       Delay action slug.
	 *
	 * @return bool
	 */
	public function maybe_delay_feed( $is_delayed, $form, $entry, $slug ) {
		if ( $this->is_processing( $form ) && isset( $this->feed->delay_actions[ $slug ] ) ) {
			$is_delayed = true;
		}

		return $is_delayed;
	}

	/**
	 * Maybe delay workflow.
	 *
	 * @link https://github.com/gravityflow/gravityflow/blob/master/class-gravity-flow.php#L4711-L4720
	 *
	 * @param bool  $is_delayed Indicates if processing of the workflow should be delayed.
	 * @param array $entry      Gravity Forms entry.
	 * @param array $form       Gravity Forms form.
	 *
	 * @return bool
	 */
	public function maybe_delay_workflow( $is_delayed, $entry, $form ) {
		return $this->maybe_delay_feed( $is_delayed, $form, $entry, 'gravityflow' );
	}

	/**
	 * Confirmation.
	 *
	 * @link http://www.gravityhelp.com/documentation/page/Gform_confirmation
	 *
	 * @param array $confirmation Gravity Forms confirmation.
	 * @param array $form         Gravity Forms form.
	 * @param array $lead         Gravity Forms lead/entry.
	 * @param bool  $ajax         AJAX request flag.
	 *
	 * @return array|string
	 */
	public function confirmation( $confirmation, $form, $lead, $ajax ) {
		if ( ! $this->is_processing( $form ) ) {
			return $confirmation;
		}

		if ( ! $this->gateway || ! $this->payment ) {
			return $confirmation;
		}

		$confirmation = array( 'redirect' => $this->payment->get_pay_redirect_url() );

		if ( is_wp_error( $this->error ) ) {
			$html  = '<ul>';
			$html .= '<li>' . Plugin::get_default_error_message() . '</li>';

			foreach ( $this->error->get_error_messages() as $message ) {
				$html .= '<li>' . $message . '</li>';
			}

			$html .= '</ul>';

			$confirmation = $html;
		}

		return $confirmation;
	}

	/**
	 * After submission.
	 *
	 * @param array $lead Gravity Forms lead/entry.
	 * @param array $form Gravity Forms form.
	 */
	public function after_submission( $lead, $form ) {

	}
}
