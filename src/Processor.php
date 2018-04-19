<?php

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

use GFAddOn;
use GFCommon;
use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Plugin;
use WP_Error;

/**
 * Title: WordPress pay extension Gravity Forms processor
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 1.6.7
 * @since   1.0.0
 */
class Processor {
	/**
	 * The Pronamic iDEAL Gravity Forms extension
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
	 * @param array     $form
	 * @param Extension $extension
	 */
	public function __construct( array $form, Extension $extension ) {
		$this->extension = $extension;
		$this->form      = $form;
		$this->form_id   = isset( $form['id'] ) ? $form['id'] : null;

		// Get payment feed by form ID
		$this->feed = get_pronamic_gf_pay_conditioned_feed_by_form_id( $this->form_id );

		if ( null !== $this->feed ) {
			$this->process = true;

			$this->add_hooks();
		}
	}

	/**
	 * Add hooks
	 */
	private function add_hooks() {
		/*
		 * Pre submission
		 */
		add_action( 'gform_pre_submission_' . $this->form_id, array( $this, 'pre_submission' ), 10, 1 );

		/*
		 * Handle submission
		 */
		// Lead
		add_action( 'gform_entry_post_save', array( $this, 'entry_post_save' ), 10, 2 );

		// Delay (@see GFFormDisplay::handle_submission > GFCommon::send_form_submission_notifications)
		add_filter( 'gform_disable_admin_notification_' . $this->form_id, array( $this, 'maybe_delay_admin_notification' ), 10, 3 );
		add_filter( 'gform_disable_user_notification_' . $this->form_id, array( $this, 'maybe_delay_user_notification' ), 10, 3 );
		add_filter( 'gform_disable_post_creation_' . $this->form_id, array( $this, 'maybe_delay_post_creation' ), 10, 3 );
		add_filter( 'gform_disable_notification_' . $this->form_id, array( $this, 'maybe_delay_notification' ), 10, 4 );

		// Confirmation (@see GFFormDisplay::handle_confirmation)
		// @see http://www.gravityhelp.com/documentation/page/Gform_confirmation
		add_filter( 'gform_confirmation_' . $this->form_id, array( $this, 'confirmation' ), 10, 4 );

		/*
		 * After submission
		 */
		add_action( 'gform_after_submission_' . $this->form_id, array( $this, 'after_submission' ), 10, 2 );

		add_filter( 'gform_is_delayed_pre_process_feed', array( $this, 'maybe_delay_user_registration' ), 10, 4 );
	}

	/**
	 * Check if we are processing the passed in form
	 *
	 * @param array $form an Gravity Forms form array
	 *
	 * @return boolean true if the passed in form is processed, false otherwise
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
	 * Pre submission
	 */
	public function pre_submission( $form ) {
		if ( ! $this->is_processing( $form ) ) {
			return;
		}

		// Delay

		// The Add-Ons mainly use the 'gform_after_submission' to export entries, to delay this we have to remove these
		// actions before this filter executes.

		// @see https://github.com/wp-premium/gravityforms/blob/1.8.16/form_display.php#L101-L103
		// @see https://github.com/wp-premium/gravityforms/blob/1.8.16/form_display.php#L111-L113

		// Maybe delay ActiveCampaign subscription
		if ( $this->feed->delay_activecampaign_subscription ) {
			// @since unreleased
			// @see https://github.com/wp-premium/gravityformsactivecampaign/blob/1.4/activecampaign.php#L44-L46
			// @see https://github.com/wp-premium/gravityforms/blob/1.9.10.15/includes/addon/class-gf-feed-addon.php#L43
			if ( function_exists( 'gf_activecampaign' ) ) {
				$addon = gf_activecampaign();

				remove_filter( 'gform_entry_post_save', array( $addon, 'maybe_process_feed' ), 10, 2 );
			}
		}

		// Maybe delay AWeber subscription
		if ( $this->feed->delay_aweber_subscription ) {
			// @see https://github.com/wp-premium/gravityformsaweber/blob/1.4.2/aweber.php#L124-L125
			remove_action( 'gform_post_submission', array( 'GFAWeber', 'export' ), 10, 2 );

			// @since 1.3.0
			// @see https://github.com/wp-premium/gravityformsaweber/blob/2.2.1/aweber.php#L48-L50
			// @see https://github.com/wp-premium/gravityforms/blob/1.9.10.15/includes/addon/class-gf-feed-addon.php#L43
			if ( function_exists( 'gf_aweber' ) ) {
				$addon = gf_aweber();

				remove_filter( 'gform_entry_post_save', array( $addon, 'maybe_process_feed' ), 10, 2 );
			}
		}

		// Maybe delay Campaign Monitor subscription
		if ( $this->feed->delay_campaignmonitor_subscription ) {
			// @see https://github.com/wp-premium/gravityformscampaignmonitor/blob/2.5.1/campaignmonitor.php#L124-L125
			remove_action( 'gform_after_submission', array( 'GFCampaignMonitor', 'export' ), 10, 2 );

			// @since 1.3.0
			// @see https://github.com/wp-premium/gravityformscampaignmonitor/blob/3.3.2/campaignmonitor.php#L48-L50
			// @see https://github.com/wp-premium/gravityforms/blob/1.9.10.15/includes/addon/class-gf-feed-addon.php#L43
			if ( function_exists( 'gf_campaignmonitor' ) ) {
				$addon = gf_campaignmonitor();

				remove_filter( 'gform_entry_post_save', array( $addon, 'maybe_process_feed' ), 10, 2 );
			}
		}

		// Maybe delay MailChimp subscription
		if ( $this->feed->delay_mailchimp_subscription ) {
			// @see https://github.com/wp-premium/gravityformsmailchimp/blob/2.4.1/mailchimp.php#L120-L121
			remove_action( 'gform_after_submission', array( 'GFMailChimp', 'export' ), 10, 2 );

			// @since 1.3.0
			// @see https://github.com/wp-premium/gravityformsmailchimp/blob/3.6.3/mailchimp.php#L48-L50
			// @see https://github.com/wp-premium/gravityforms/blob/1.9.10.15/includes/addon/class-gf-feed-addon.php#L43
			if ( function_exists( 'gf_mailchimp' ) ) {
				$addon = gf_mailchimp();

				remove_filter( 'gform_entry_post_save', array( $addon, 'maybe_process_feed' ), 10, 2 );
			}
		}

		// Maybe delay Moneybird
		if ( $this->feed->delay_moneybird ) {
			// @since unreleased
			// @see https://github.com/wp-premium/gravityforms/blob/1.9.10.15/includes/addon/class-gf-feed-addon.php#L43
			if ( function_exists( 'gf_moneybird' ) ) {
				$addon = gf_moneybird();

				remove_filter( 'gform_entry_post_save', array( $addon, 'maybe_process_feed' ), 10, 2 );
			}
		}

		// Maybe delay Twilio
		if ( $this->feed->delay_twilio ) {
			// @since unreleased
			// @see https://github.com/wp-premium/gravityforms/blob/1.9.10.15/includes/addon/class-gf-feed-addon.php#L43
			if ( function_exists( 'gf_twilio' ) ) {
				$addon = gf_twilio();

				remove_filter( 'gform_entry_post_save', array( $addon, 'maybe_process_feed' ), 10, 2 );
			}
		}

		// Maybe delay Dropbox
		if ( $this->feed->delay_dropbox ) {
			// @since unreleased
			// @see https://github.com/wp-premium/gravityforms/blob/1.9.10.15/includes/addon/class-gf-feed-addon.php#L43
			if ( function_exists( 'gf_dropbox' ) ) {
				$addon = gf_dropbox();

				remove_filter( 'gform_entry_post_save', array( $addon, 'maybe_process_feed' ), 10, 2 );
			}
		}

		// Maybe delay Zapier
		if ( $this->feed->delay_zapier ) {
			// @see https://github.com/wp-premium/gravityformszapier/blob/1.4.2/zapier.php#L106
			remove_action( 'gform_after_submission', array( 'GFZapier', 'send_form_data_to_zapier' ), 10, 2 );
		}

		if ( $this->feed->delay_sliced_invoices ) {
			// @since unreleased
			// @see https://github.com/wp-premium/gravityforms/blob/1.9.10.15/includes/addon/class-gf-feed-addon.php#L43
			// @see https://plugins.trac.wordpress.org/browser/sliced-invoices-gravity-forms/tags/1.10.0/class-sliced-invoices-gf.php#L10

			$addons = GFAddOn::get_registered_addons();

			foreach ( $addons as $class ) {
				if ( 'Sliced_Invoices_GF' !== $class ) {
					continue;
				}

				$addon = call_user_func( array( $class, 'get_instance' ) );

				remove_filter( 'gform_entry_post_save', array( $addon, 'maybe_process_feed' ), 10, 2 );
			}
		}
	}

	/**
	 * Entry post save
	 *
	 * @param array $lead
	 * @param array $form
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

		// Start payment.
		$this->payment = Plugin::start( $this->feed->config_id, $this->gateway, $data, $payment_method );

		$this->error = $this->gateway->get_error();

		// Update entry meta.
		gform_update_meta( $lead['id'], 'pronamic_payment_id', $this->payment->get_id() );
		gform_update_meta( $lead['id'], 'pronamic_subscription_id', $this->payment->get_subscription_id() );

		$lead[ LeadProperties::PAYMENT_STATUS ] = GravityForms::get_entry_property( $lead['id'], LeadProperties::PAYMENT_STATUS );
		$lead[ LeadProperties::PAYMENT_AMOUNT ] = $this->payment->get_amount()->get_amount();
		$lead[ LeadProperties::TRANSACTION_ID ] = $this->payment->get_transaction_id();

		GravityForms::update_entry( $lead );

		// Add pending payment if no gateway errors occurred.
		if ( ! $this->gateway->has_error() ) {
			// Add pending payment.
			$action = array(
				'id'             => $this->payment->get_id(),
				'transaction_id' => $this->payment->get_transaction_id(),
				'amount'         => $this->payment->get_amount()->get_amount(),
				'entry_id'       => $lead['id'],
			);

			$this->extension->payment_action( 'add_pending_payment', $lead, $action );
		}

		return $lead;
	}

	/**
	 * Maybe delay notifications.
	 *
	 * @param bool  $is_disabled
	 * @param array $notification
	 * @param array $form
	 * @param array $lead
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
	 * @param bool  $is_disabled
	 * @param array $form
	 * @param array $lead
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
	 * @param bool  $is_disabled
	 * @param array $form
	 * @param array $lead
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
	 * Maybe delay post creation
	 *
	 * @param boolean $is_disabled
	 * @param array   $form
	 * @param array   $lead
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
	 * Maybe delay user registration
	 *
	 * @param $is_delayed
	 * @param $form
	 * @param $entry
	 * @param $slug
	 *
	 * @return bool
	 */
	public function maybe_delay_user_registration( $is_delayed, $form, $entry, $slug ) {
		if ( 'gravityformsuserregistration' !== $slug ) {
			return $is_delayed;
		}

		if ( $is_delayed || ! $this->is_processing( $form ) ) {
			return $is_delayed;
		}

		$order_total = GFCommon::get_order_total( $form, $entry );

		// delay the registration IF:
		// - the delay registration option is checked
		// - the order total does NOT equal zero (no delay since there will never be a payment)
		$is_delayed = $this->feed->delay_user_registration && ( 0 !== $order_total );

		return $is_delayed;
	}

	/**
	 * Confirmation
	 *
	 * @see http://www.gravityhelp.com/documentation/page/Gform_confirmation
	 *
	 * @param $confirmation
	 * @param $form
	 * @param $lead
	 * @param $ajax
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
	 * After submission
	 *
	 * @param $lead
	 * @param $form
	 */
	public function after_submission( $lead, $form ) {

	}
}
