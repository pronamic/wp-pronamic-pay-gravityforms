<?php
/**
 * Extension
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

use GF_Field;
use GFAddOn;
use GFAPI;
use GFCommon;
use GFFormDisplay;
use GFForms;
use GFUserData;
use Pronamic\WordPress\Money\Money;
use Pronamic\WordPress\Pay\AbstractPluginIntegration;
use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Payments\PaymentStatus;
use Pronamic\WordPress\Pay\Core\Util as Core_Util;
use Pronamic\WordPress\Pay\Customer;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Subscriptions\SubscriptionStatus;
use Pronamic\WordPress\Pay\Subscriptions\Subscription;
use RGFormsModel;
use WP_User;

/**
 * Title: WordPress pay extension Gravity Forms extension
 * Description:
 * Copyright: 2005-2024 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.6.1
 * @since   1.0.0
 */
class Extension extends AbstractPluginIntegration {
	/**
	 * Slug
	 *
	 * @var string
	 */
	const SLUG = 'gravityformsideal';

	/**
	 * Payment add-on.
	 *
	 * @var PaymentAddOn
	 */
	public $addon;

	/**
	 * Construct Gravity Forms plugin integration.
	 */
	public function __construct() {
		parent::__construct(
			[
				'name'    => __( 'Gravity Forms', 'pronamic_ideal' ),
				'version' => '2.3.0',
			]
		);

		// Dependencies.
		$dependencies = $this->get_dependencies();

		$dependencies->add( new GravityFormsDependency() );
	}

	/**
	 * Setup plugin integration.
	 *
	 * @return void
	 */
	public function setup() {
		add_filter( 'pronamic_payment_source_text_' . self::SLUG, [ $this, 'source_text' ], 10, 2 );
		add_filter( 'pronamic_payment_source_description_' . self::SLUG, [ $this, 'source_description' ], 10, 2 );
		add_filter( 'pronamic_subscription_source_text_' . self::SLUG, [ $this, 'subscription_source_text' ], 10, 2 );
		add_filter( 'pronamic_subscription_source_description_' . self::SLUG, [ $this, 'subscription_source_description' ], 10, 2 );

		// Check if dependencies are met and integration is active.
		if ( ! $this->is_active() ) {
			return;
		}

		// Dutch translations.
		\Pronamic\WordPress\GravityFormsNL\Plugin::instance();

		// Post types.
		new PaymentFormPostType();

		// Actions
		// Initialize hook, Gravity Forms uses the default priority (10).
		add_action( 'init', [ $this, 'init' ], 20 );

		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );

		// Add-on
		// The `class_exists` call is required to prevent strange errors on some hosting environments.
		if ( Core_Util::class_method_exists( 'GFForms', 'include_payment_addon_framework' ) ) {
			GFForms::include_payment_addon_framework();

			if ( class_exists( 'GFPaymentAddOn' ) ) {
				$this->addon = new PaymentAddOn();
			}
		}

		// Fields.
		new Fields();
	}

	/**
	 * Initialize
	 */
	public function init() {
		// Admin.
		if ( is_admin() ) {
			Admin::bootstrap();
		} else {
			add_action( 'gform_pre_submission', [ $this, 'pre_submission' ] );
		}

		add_filter( 'pronamic_payment_source_url_' . self::SLUG, [ $this, 'source_url' ], 10, 2 );
		add_filter( 'pronamic_subscription_source_url_' . self::SLUG, [ $this, 'subscription_source_url' ], 10, 2 );
		add_filter( 'pronamic_payment_redirect_url_' . self::SLUG, [ $this, 'redirect_url' ], 10, 2 );
		add_action( 'pronamic_payment_status_update_' . self::SLUG, [ $this, 'update_status' ], 10, 2 );
		add_action( 'pronamic_pay_update_payment', [ $this, 'update_payment' ] );
		add_action( 'pronamic_subscription_status_update_' . self::SLUG, [ $this, 'subscription_update_status' ] );
		add_action( 'pronamic_subscription_renewal_notice_' . self::SLUG, [ $this, 'subscription_renewal_notice' ] );
		add_filter( 'pronamic_pay_subscription_amount_editable_' . self::SLUG, '__return_true' );

		add_filter( 'gform_replace_merge_tags', [ $this, 'replace_merge_tags' ], 10, 7 );

		add_filter( 'gform_gf_field_create', [ $this, 'field_create' ], 10, 2 );

		\add_filter( 'gform_form_args', [ $this, 'maybe_prepopulate_form' ], 10, 1 );
		\add_filter( 'gform_pre_render', [ $this, 'allow_field_prepopulation' ], 10, 1 );

		// Register scripts and styles if Gravity Forms No-Conflict Mode is enabled.
		add_filter( 'gform_noconflict_scripts', [ $this, 'no_conflict_scripts' ] );
		add_filter( 'gform_noconflict_styles', [ $this, 'no_conflict_styles' ] );

		\add_filter( 'gform_payment_statuses', [ $this, 'gform_payment_statuses' ] );

		$this->maybe_display_confirmation();
	}

	/**
	 * Field create.
	 *
	 * @param GF_Field $field      Field object.
	 * @param array    $properties Field properties.
	 *
	 * @return GF_Field|IssuersField|PaymentMethodsField
	 */
	public function field_create( $field, $properties ) {
		/*
		 * The `inputType` of the payment methods field was in the past set to `checkbox`
		 * this results in a `GF_Field_Checkbox` field, but we really need a payment methods
		 * field.
		 *
		 * @link https://github.com/wp-premium/gravityforms/blob/1.9.19/includes/fields/class-gf-fields.php#L60-L86
		 */
		switch ( $field->type ) {
			case Fields::ISSUERS_FIELD_TYPE:
				return new IssuersField( $properties );
			case Fields::PAYMENT_METHODS_FIELD_TYPE:
				return new PaymentMethodsField( $properties );
		}

		return $field;
	}

	/**
	 * Admin enqueue scripts.
	 */
	public function admin_enqueue_scripts() {
		$screen = get_current_screen();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$subview = \array_key_exists( 'subview', $_GET ) ? \sanitize_text_field( \wp_unslash( $_GET['subview'] ) ) : '';

		if (
			'toplevel_page_gf_edit_forms' !== $screen->id
				&&
			'pronamic_pay_gf' !== $screen->post_type
				&&
			'pronamic_pay' !== $subview
		) {
			return;
		}

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_style(
			'pronamic-pay-gravityforms',
			plugins_url( 'css/admin' . $min . '.css', __DIR__ ),
			[],
			\hash_file( 'crc32', \plugin_dir_path( __DIR__ ) . 'css/admin' . $min . '.css' )
		);

		wp_register_script(
			'pronamic-pay-gravityforms',
			plugins_url( 'js/admin' . $min . '.js', __DIR__ ),
			[ 'jquery' ],
			\hash_file( 'crc32', \plugin_dir_path( __DIR__ ) . 'js/admin' . $min . '.js' ),
			true
		);

		wp_enqueue_style( 'pronamic-pay-gravityforms' );

		wp_enqueue_script( 'pronamic-pay-gravityforms' );
	}

	/**
	 * Gravity Forms No Conflict scripts.
	 *
	 * @link https://www.gravityhelp.com/documentation/article/gform_noconflict_scripts/
	 *
	 * @param array $scripts Scripts.
	 *
	 * @return array
	 */
	public function no_conflict_scripts( $scripts ) {
		$scripts[] = 'jquery-tiptip';
		$scripts[] = 'pronamic-pay-admin';
		$scripts[] = 'pronamic-pay-gravityforms';

		return $scripts;
	}

	/**
	 * Gravity Forms No Conflict styles.
	 *
	 * @link https://www.gravityhelp.com/documentation/article/gform_noconflict_styles/
	 *
	 * @param array $styles Styles.
	 *
	 * @return array
	 */
	public function no_conflict_styles( $styles ) {
		$styles[] = 'jquery-tiptip';
		$styles[] = 'pronamic-pay-icons';
		$styles[] = 'pronamic-pay-admin';
		$styles[] = 'pronamic-pay-gravityforms';

		return $styles;
	}

	/**
	 * Pre submission
	 *
	 * @param array $form Form.
	 */
	public function pre_submission( $form ) {
		$processor = new Processor( $form, $this );

		$processor->pre_submission( $form );
	}

	/**
	 * Source column.
	 *
	 * @param string  $text    Source text.
	 * @param Payment $payment Payment.
	 *
	 * @return string
	 */
	public function source_text( $text, Payment $payment ) {
		$text = __( 'Gravity Forms', 'pronamic_ideal' ) . '<br />';

		$text .= sprintf(
			'<a href="%1$s">%2$s</a>',
			add_query_arg( [ 'pronamic_gf_lid' => $payment->get_source_id() ], admin_url( 'admin.php' ) ),
			/* translators: %s: source id  */
			sprintf( __( 'Entry #%s', 'pronamic_ideal' ), $payment->get_source_id() )
		);

		return $text;
	}

	/**
	 * Source description.
	 *
	 * @param string  $description Description.
	 * @param Payment $payment     Payment.
	 *
	 * @return string
	 */
	public function source_description( $description, Payment $payment ) {
		return __( 'Gravity Forms Entry', 'pronamic_ideal' );
	}

	/**
	 * Source URL.
	 *
	 * @param string  $url     Source URL.
	 * @param Payment $payment Payment.
	 *
	 * @return string
	 */
	public function source_url( $url, Payment $payment ) {
		return \add_query_arg( 'pronamic_gf_lid', $payment->get_source_id(), \admin_url( 'admin.php' ) );
	}

	/**
	 * Subscription source text.
	 *
	 * @param string       $text         Source text.
	 * @param Subscription $subscription Subscription.
	 *
	 * @return string
	 */
	public function subscription_source_text( $text, Subscription $subscription ) {
		$text = __( 'Gravity Forms', 'pronamic_ideal' ) . '<br />';

		$text .= sprintf(
			'<a href="%1$s">%2$s</a>',
			add_query_arg( [ 'pronamic_gf_lid' => $subscription->get_source_id() ], admin_url( 'admin.php' ) ),
			/* translators: %s: source id  */
			sprintf( __( 'Entry #%s', 'pronamic_ideal' ), $subscription->get_source_id() )
		);

		return $text;
	}

	/**
	 * Subscription source description.
	 *
	 * @param string       $description  Description.
	 * @param Subscription $subscription Subscription.
	 *
	 * @return string
	 */
	public function subscription_source_description( $description, Subscription $subscription ) {
		return __( 'Gravity Forms Entry', 'pronamic_ideal' );
	}

	/**
	 * Subscription source URL.
	 *
	 * @param string       $url          Source URL.
	 * @param Subscription $subscription Subscription.
	 *
	 * @return string
	 */
	public function subscription_source_url( $url, Subscription $subscription ) {
		return \add_query_arg( 'pronamic_gf_lid', $subscription->get_source_id(), \admin_url( 'admin.php' ) );
	}

	/**
	 * Maybe update user role of the specified lead and feed
	 *
	 * @param array   $lead Lead.
	 * @param PayFeed $feed Payment feed.
	 */
	private function maybe_update_user_role( $lead, $feed ) {
		$user = false;

		// Gravity Forms User Registration Add-on.
		if ( \function_exists( '\gf_user_registration' ) ) {
			// Version >= 3.
			$user = \gf_user_registration()->get_user_by_entry_id( $lead['id'] );
		} elseif ( class_exists( 'GFUserData' ) ) {
			$user = GFUserData::get_user_by_entry_id( $lead['id'] );
		}

		if ( false === $user ) {
			$created_by = $lead[ LeadProperties::CREATED_BY ];

			$user = new WP_User( $created_by );
		}

		if ( $user && ! empty( $feed->user_role_field_id ) && isset( $lead[ $feed->user_role_field_id ] ) ) {
			$value = $lead[ $feed->user_role_field_id ];
			$value = GFCommon::get_selection_value( $value );

			$user->set_role( $value );
		}
	}

	/**
	 * Maybe update payment user.
	 *
	 * @param array   $lead Lead.
	 * @param PayFeed $feed Payment feed.
	 *
	 * @return void
	 */
	private function maybe_update_payment_user( $lead, $feed ) {
		$user = false;

		// Gravity Forms User Registration Add-on.
		if ( \function_exists( '\gf_user_registration' ) ) {
			// Version >= 3.
			$user = \gf_user_registration()->get_user_by_entry_id( $lead['id'] );
		} elseif ( class_exists( 'GFUserData' ) ) {
			$user = GFUserData::get_user_by_entry_id( $lead['id'] );
		}

		if ( false === $user ) {
			return;
		}

		// Find payment.
		$payment_id = gform_get_meta( $lead['id'], 'pronamic_payment_id' );

		$payment = get_pronamic_payment( $payment_id );

		if ( null === $payment ) {
			return;
		}

		// Update payment customer user ID and post author.
		if ( null === $payment->get_customer() ) {
			$payment->set_customer( new Customer() );
		}

		// Set payment customer user ID.
		$payment->get_customer()->set_user_id( $user->ID );

		$payment->save();

		// Update payment post author.
		wp_update_post(
			[
				'ID'          => $payment->get_id(),
				'post_author' => $user->ID,
			]
		);

		// Update subscription customer user ID and post author.
		$subscriptions = $payment->get_subscriptions();

		foreach ( $subscriptions as $subscription ) {
			if ( null === $subscription->get_customer() ) {
				$subscription->set_customer( new Customer() );
			}

			// Set subscription customer user ID.
			$subscription->get_customer()->set_user_id( $user->ID );

			$subscription->save();

			// Update subscription post author.
			wp_update_post(
				[
					'ID'          => $subscription->get_id(),
					'post_author' => $user->ID,
				]
			);
		}
	}

	/**
	 * Payment redirect URL filter.
	 *
	 * @since unreleased
	 *
	 * @param string  $url     Redirect URL.
	 * @param Payment $payment Payment.
	 *
	 * @return string
	 */
	public function redirect_url( $url, $payment ) {
		$lead_id = $payment->get_source_id();

		$lead = RGFormsModel::get_lead( $lead_id );

		if ( ! $lead ) {
			return $url;
		}

		$feed = FeedsDB::get_feed_by_entry_id( $lead_id );

		if ( ! $feed ) {
			return $url;
		}

		$new_url = null;

		switch ( $payment->status ) {
			case PaymentStatus::CANCELLED:
				$new_url = $feed->get_url( Links::CANCEL );

				break;
			case PaymentStatus::EXPIRED:
				$new_url = $feed->get_url( Links::EXPIRED );

				break;
			case PaymentStatus::FAILURE:
				$new_url = $feed->get_url( Links::ERROR );

				break;
			case PaymentStatus::SUCCESS:
				$new_url = $feed->get_url( Links::SUCCESS );

				break;
			case PaymentStatus::OPEN:
				$new_url = $feed->get_url( Links::OPEN );

				break;
		}

		if ( null !== $new_url ) {
			$url = $new_url;
		}

		// Process Gravity Forms confirmations if link type is confirmation.
		$link = Links::transform_status( $payment->status );

		if ( isset( $feed->links[ $link ]['type'] ) && PayFeed::LINK_TYPE_CONFIRMATION === $feed->links[ $link ]['type'] ) {
			$amount = $payment->get_total_amount()->get_value();

			if ( empty( $amount ) ) {
				$confirmation = true;
			} else {
				$confirmation = $this->get_confirmation( $lead, $payment->status );
			}

			if ( ! empty( $confirmation ) ) {
				if ( is_array( $confirmation ) && isset( $confirmation['redirect'] ) ) {
					$url = $confirmation['redirect'];
				} else {
					$url = add_query_arg(
						[
							'pay_confirmation' => $payment->get_id(),
							'hash'             => \wp_hash( $payment->get_id() ),
						],
						$lead['source_url']
					);

					$anchor = GFFormDisplay::get_anchor( GFAPI::get_form( $lead['form_id'] ), false );

					$url .= $anchor['id'];
				}
			}
		}

		return $url;
	}

	/**
	 * Update lead status of the specified payment
	 *
	 * @param Payment $payment      Payment.
	 * @param bool    $can_redirect Whether or not to redirect.
	 */
	public function update_status( Payment $payment, $can_redirect = false ) {
		$lead_id = $payment->get_source_id();

		$lead = RGFormsModel::get_lead( $lead_id );

		if ( ! $lead ) {
			return;
		}

		$form_id = $lead['form_id'];

		$form = RGFormsModel::get_form_meta( $form_id );
		$feed = FeedsDB::get_feed_by_entry_id( $lead_id );

		if ( ! $feed ) {
			return;
		}

		$data = new PaymentData( $form, $lead, $feed );

		$action = [
			'id'             => $payment->get_id(),
			'transaction_id' => $payment->get_transaction_id(),
			'amount'         => $payment->get_total_amount()->get_value(),
			'entry_id'       => $lead['id'],
		];

		// Get subscription ID from payment period.
		$periods = $payment->get_periods();

		if ( null !== $periods ) {
			foreach ( $periods as $period ) {
				$action['subscription_id'] = $period->get_phase()->get_subscription()->get_id();
			}
		}

		/**
		 * For follow-yp subscription payments we execute the `'add_subscription_payment'`
		 * action instead of the regular `'complete_payment'` action. There is a follow-up
		 * payment if the Pronamic payment ID stored with the entry does not match the
		 * payment to be processed.
		 *
		 * @link https://github.com/pronamic/wp-pronamic-pay/issues/239
		 */
		$success_action = 'complete_payment';
		$fail_action    = 'fail_payment';

		$subscriptions = $payment->get_subscriptions();

		if ( \count( $subscriptions ) > 0 ) {
			$payment_id_1 = (string) \gform_get_meta( $lead_id, 'pronamic_payment_id' );
			$payment_id_2 = (string) $payment->get_id();

			if ( $payment_id_1 !== $payment_id_2 ) {
				$success_action = 'add_subscription_payment';
				$fail_action    = 'fail_subscription_payment';

				if ( PaymentStatus::OPEN === $payment->status ) {
					\gform_update_meta( $lead['id'], 'pronamic_subscription_payment_id', $payment_id_2 );
				}
			}
		}

		switch ( $payment->status ) {
			case PaymentStatus::CANCELLED:
				$this->payment_action( $fail_action, $lead, $action, PaymentStatuses::CANCELLED );

				break;
			case PaymentStatus::EXPIRED:
				$this->payment_action( $fail_action, $lead, $action, PaymentStatuses::EXPIRED );

				break;
			case PaymentStatus::FAILURE:
				$this->payment_action( $fail_action, $lead, $action, PaymentStatuses::FAILED );

				break;
			case PaymentStatus::SUCCESS:
				if ( ! Entry::is_payment_approved( $lead ) || 'add_subscription_payment' === $success_action ) {
					// @link https://github.com/wp-premium/gravityformspaypal/blob/2.3.1/class-gf-paypal.php#L1741-L1742
					$this->payment_action( $success_action, $lead, $action, PaymentStatuses::PAID );
				}

				// Create subscription.
				if ( ! Entry::is_payment_approved( $lead ) && isset( $action['subscription_id'] ) && ! empty( $action['subscription_id'] ) ) {
					$action['subscription_start_date'] = gmdate( 'Y-m-d H:i:s' );

					$this->payment_action( 'create_subscription', $lead, $action );
				}

				// Fulfill order.
				$this->fulfill_order( $lead );

				break;
			case PaymentStatus::OPEN:
			default:
				// Nothing to-do.

				break;
		}
	}

	/**
	 * Update payment.
	 *
	 * @param Payment $payment Payment.
	 */
	public function update_payment( Payment $payment ) {
		/**
		 * Check if the payment source is Gravity Forms.
		 */
		$source = $payment->get_source();

		if ( 'gravityformsideal' !== $source ) {
			return;
		}

		/**
		 * Search for the Gravity Forms entry by payment source ID.
		 *
		 * @link https://docs.gravityforms.com/api-functions/#get-entry
		 */
		$entry_id = $payment->get_source_id();

		$entry = \GFAPI::get_entry( $entry_id );

		if ( \is_wp_error( $entry ) ) {
			return;
		}

		/**
		 * Total amount.
		 */
		$total_amount = $payment->get_total_amount();

		if ( null === $total_amount ) {
			return;
		}

		/**
		 * Update refunded amount.
		 *
		 * @link https://github.com/pronamic/wp-pronamic-pay/issues/119
		 */
		$refunded_amount = $payment->get_refunded_amount();

		$refunded_amount_value = $refunded_amount->get_value();

		$entry_refunded_amount_value = (float) \gform_get_meta( $entry_id, 'pronamic_pay_refunded_amount' );

		if ( $entry_refunded_amount_value >= $refunded_amount_value ) {
			return;
		}

		$diff_amount = $refunded_amount->subtract( new Money( $entry_refunded_amount_value ) );

		$result = $this->addon->refund_payment(
			$entry,
			[
				// The Gravity Forms payment add-on callback feature uses the action ID to prevent processing an action twice.
				'id'             => '',
				'type'           => 'refund_payment',
				/**
				 * Unfortunately we don't have a specific transaction ID for this refund at this point.
				 *
				 * @link https://en.wikipedia.org/wiki/%C3%98
				 * @link https://unicode-table.com/en/2205/
				 */
				'transaction_id' => '∅',
				'entry_id'       => $entry_id,
				'amount'         => $diff_amount->get_value(),
				/**
				 * Override the default Gravity Forms payment status.
				 *
				 * @link https://github.com/wp-premium/gravityforms/blob/2.4.20/includes/addon/class-gf-payment-addon.php#L1910-L1912
				 */
				'payment_status' => $refunded_amount->get_value() < $total_amount->get_value() ? 'PartlyRefunded' : 'Refunded',
				/**
				 * Override the default Gravity Forms payment refund note.
				 *
				 * @link https://github.com/wp-premium/gravityforms/blob/2.4.20/includes/addon/class-gf-payment-addon.php#L1920-L1922
				 */
				'note'           => \sprintf(
					/* translators: %s: refunded amount */
					\__( 'Payment has been (partially) refunded. Amount: %s.', 'pronamic_ideal' ),
					$diff_amount->format_i18n()
				),
			]
		);

		if ( true === $result ) {
			\gform_update_meta( $entry_id, 'pronamic_pay_refunded_amount', $refunded_amount_value );
		}
	}

	/**
	 * Update lead status of the specified subscription
	 *
	 * @param Subscription $subscription Subscription.
	 */
	public function subscription_update_status( Subscription $subscription ) {
		$lead_id = $subscription->get_source_id();

		$lead = RGFormsModel::get_lead( $lead_id );

		if ( ! $lead ) {
			return;
		}

		$feed = FeedsDB::get_feed_by_entry_id( $lead_id );

		if ( ! $feed ) {
			return;
		}

		if ( empty( $lead['is_fulfilled'] ) ) {
			return;
		}

		// Get amount from current phase.
		$amount = null;

		$current_phase = $subscription->get_current_phase();

		if ( null !== $current_phase ) {
			$amount = $current_phase->get_amount()->get_value();
		}

		// Action.
		$action = [
			'id'              => $subscription->get_id(),
			'subscription_id' => $subscription->get_id(),
			'amount'          => $amount,
			'entry_id'        => $lead['id'],
		];

		switch ( $subscription->get_status() ) {
			case SubscriptionStatus::ACTIVE:
				if ( ! Entry::is_payment_active( $lead ) ) {
					$action['note'] = __( 'Subscription manually activated.', 'pronamic_ideal' );

					// Set amount to `0` to prevent incorrect revenue in reports.
					$action['amount'] = 0;

					$this->payment_action( 'add_subscription_payment', $lead, $action, PaymentStatuses::PAID );
				}

				break;
			case SubscriptionStatus::CANCELLED:
				$this->payment_action( 'cancel_subscription', $lead, $action, PaymentStatuses::CANCELLED );

				break;
			case SubscriptionStatus::COMPLETED:
				// @todo are we sure an 'expired subscription' is the same as the Pronamic\WordPress\Pay\Core\Statuses::COMPLETED status?
				$this->payment_action( 'expire_subscription', $lead, $action, PaymentStatuses::EXPIRED );

				break;
		}
	}

	/**
	 * Send subscription renewal notice
	 *
	 * @param Subscription $subscription Subscription.
	 */
	public function subscription_renewal_notice( Subscription $subscription ) {
		if ( ! $this->addon ) {
			return;
		}

		$lead_id = $subscription->get_source_id();

		$lead = RGFormsModel::get_lead( $lead_id );

		if ( ! $lead ) {
			return;
		}

		$feed = FeedsDB::get_feed_by_entry_id( $lead_id );

		if ( ! $feed ) {
			return;
		}

		// Get amount from current phase.
		$amount = null;

		$current_phase = $subscription->get_current_phase();

		if ( null !== $current_phase ) {
			$amount = $current_phase->get_amount()->get_value();
		}

		$action = [
			'subscription_id' => $subscription->get_id(),
			'amount'          => $amount,
			'entry_id'        => $lead['id'],
			'type'            => 'renew_subscription',
		];

		$this->addon->post_payment_action( $lead, $action );
	}

	/**
	 * Process add-on payment action.
	 *
	 * $action = array(
	 *     'type' => 'cancel_subscription',     // required
	 *     'transaction_id' => '',              // required (if payment)
	 *     'subscription_id' => '',             // required (if subscription)
	 *     'amount' => '0.00',                  // required (some exceptions)
	 *     'entry_id' => 1,                     // required (some exceptions)
	 *     'transaction_type' => '',
	 *     'payment_status' => '',
	 *     'note' => ''
	 * );
	 *
	 * @param string $type           Payment action type.
	 * @param array  $lead           Gravity Forms lead.
	 * @param array  $action         Payment action.
	 * @param string $payment_status Payment action status.
	 *
	 * @return bool
	 * @link https://github.com/wp-premium/gravityforms/blob/2.1.0.1/includes/addon/class-gf-payment-addon.php#L1133-L1172
	 */
	public function payment_action( $type, $lead, $action, $payment_status = null ) {
		if ( ! $this->addon ) {
			if ( PaymentStatuses::PAID === $payment_status ) {
				$payment_status = PaymentStatuses::APPROVED;
			}

			$lead[ LeadProperties::PAYMENT_STATUS ] = $payment_status;

			// Update payment status property of lead.
			GravityForms::update_entry_property(
				$lead['id'],
				LeadProperties::PAYMENT_STATUS,
				$lead[ LeadProperties::PAYMENT_STATUS ]
			);

			return false;
		}

		if ( ! is_array( $action ) ) {
			return false;
		}

		if ( null !== $payment_status ) {
			$action['payment_status'] = $payment_status;
		}

		/*
		 * Prevent empty formatted amount in entry notes.
		 *
		 * @link https://github.com/wp-premium/gravityforms/blob/2.4.17/includes/addon/class-gf-payment-addon.php#L3628
		 */
		if ( '0' === (string) $action['amount'] ) {
			$action['amount'] = '0.00';
		}

		$action['type'] = $type;

		$result = false;

		switch ( $action['type'] ) {
			case 'complete_payment':
				$result = $this->addon->complete_payment( $lead, $action );

				break;
			case 'refund_payment':
				$result = $this->addon->refund_payment( $lead, $action );

				break;
			case 'fail_payment':
				$result = $this->addon->fail_payment( $lead, $action );

				break;
			case 'add_pending_payment':
				$result = $this->addon->add_pending_payment( $lead, $action );

				break;
			case 'void_authorization':
				$result = $this->addon->void_authorization( $lead, $action );

				break;
			case 'create_subscription':
				$result = $this->addon->start_subscription( $lead, $action );

				break;
			case 'cancel_subscription':
				$feed = FeedsDB::get_feed_by_entry_id( $lead['id'] );

				if ( ! isset( $action['note'] ) ) {
					$action['note'] = __( 'Subscription manually canceled.', 'pronamic_ideal' );
				}

				$result = $this->addon->cancel_subscription( $lead, $feed, $action['note'] );

				break;
			case 'expire_subscription':
				$result = $this->addon->expire_subscription( $lead, $action );

				break;
			case 'add_subscription_payment':
				$result = $this->addon->add_subscription_payment( $lead, $action );

				break;
			case 'fail_subscription_payment':
				$result = $this->addon->fail_subscription_payment( $lead, $action );

				break;
		}

		return $result;
	}

	/**
	 * Fulfill order
	 *
	 * @param array $entry Gravity Forms entry.
	 */
	public function fulfill_order( $entry ) {
		// Check if already fulfilled.
		if ( Entry::is_fulfilled( $entry ) ) {
			return;
		}

		$entry_id = rgar( $entry, 'id' );

		// Get entry with current payment status.
		$entry = RGFormsModel::get_lead( $entry_id );

		$feed = FeedsDB::get_feed_by_entry_id( $entry_id );

		if ( null !== $feed ) {
			$this->maybe_update_user_role( $entry, $feed );

			$this->maybe_update_payment_user( $entry, $feed );

			$form = RGFormsModel::get_form_meta( $entry['form_id'] );

			// Delay post creation.
			// @link https://github.com/wp-premium/gravityforms/blob/1.8.20.5/forms_model.php#L2383.
			// @link https://github.com/wp-premium/gravityformspaypal/blob/1.10.3/paypal.php#L2411-L2415.
			if ( $feed->delay_post_creation ) {
				RGFormsModel::create_post( $form, $entry );
			}

			foreach ( $feed->delay_actions as $slug => $data ) {
				if ( isset( $data['addon'] ) ) {
					$addon = $data['addon'];

					if ( method_exists( $addon, 'maybe_process_feed' ) ) {
						/*
						 * Disable asynchronous feed processing for delayed actions.
						 *
						 * @link https://github.com/wp-premium/gravityforms/blob/2.4.7.3/includes/addon/class-gf-feed-addon.php#L1694
						 * @link https://github.com/wp-premium/gravityforms/blob/2.4.7.3/includes/addon/class-gf-feed-addon.php#L455-L486
						 */
						add_filter( 'gform_is_feed_asynchronous_' . $form['id'], '__return_false' );

						$addon->maybe_process_feed( $entry, $form );
					}
				}

				if ( isset( $data['process_callback'] ) ) {
					call_user_func( $data['process_callback'], $entry, $form );
				}
			}

			// Delay notifications.
			// Determine if the feed has Gravity Form 1.7 Feed IDs.
			if ( $feed->has_delayed_notifications() ) {
				$delay_notification_ids = [];

				foreach ( $feed->delay_notification_ids as $notification_id ) {
					if ( ! isset( $form['notifications'][ $notification_id ] ) ) {
						continue;
					}

					if ( isset( $form['notifications'][ $notification_id ]['event'] ) && 'form_submission' !== $form['notifications'][ $notification_id ]['event'] ) {
						continue;
					}

					$delay_notification_ids[] = $notification_id;
				}

				// @link https://bitbucket.org/Pronamic/gravityforms/src/42773f75ad7ad9ac9c31ce149510ff825e4aa01f/common.php?at=1.7.8#cl-1512
				GFCommon::send_notifications( $delay_notification_ids, $form, $entry, true, 'form_submission' );
			}

			if ( $feed->delay_admin_notification && Core_Util::class_method_exists( 'GFCommon', 'send_admin_notification' ) ) {
				// @link https://github.com/wp-premium/gravityforms/blob/1.8.9/common.php#L1265-L1270.
				GFCommon::send_admin_notification( $form, $entry );
			}

			if ( $feed->delay_user_notification && Core_Util::class_method_exists( 'GFCommon', 'send_user_notification' ) ) {
				// @link https://github.com/wp-premium/gravityforms/blob/1.8.9/common.php#L1258-L1263.
				GFCommon::send_user_notification( $form, $entry );
			}
		}

		// Store entry payment fulfillment in custom meta.
		gform_update_meta( $entry_id, 'pronamic_pay_payment_fulfilled', true );

		/**
		 * Execute payment fulfillment action (PayPal uses `gform_paypal_fulfillment`).
		 *
		 * @link https://docs.gravityforms.com/gform_paypal_fulfillment/
		 * @link https://docs.gravityforms.com/entry-object/
		 * @since 1.0.0
		 * @param object $entry The entry used to generate the (iDEAL) payment.
		 * @param object $feed  The feed configuration data used to generate the payment.
		 * @deprecated Fulfillment of payments without amount (free) will be removed in the future. Use `gform_post_payment_completed` action instead.
		 */
		\do_action( 'gform_ideal_fulfillment', $entry, $feed );
	}

	/**
	 * Maybe display the Gravity Forms confirmation.
	 *
	 * @return void
	 */
	public function maybe_display_confirmation() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! \array_key_exists( 'pay_confirmation', $_GET ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$payment_id = (int) \sanitize_text_field( \wp_unslash( $_GET['pay_confirmation'] ) );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! \array_key_exists( 'hash', $_GET ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$hash = \sanitize_text_field( \wp_unslash( $_GET['hash'] ) );

		if ( \wp_hash( $payment_id ) !== $hash ) {
			return;
		}

		$payment = get_pronamic_payment( $payment_id );

		if ( null === $payment ) {
			return;
		}

		$lead_id = $payment->get_source_id();

		$lead = RGFormsModel::get_lead( $lead_id );

		// Return if lead does not exist.
		if ( ! $lead ) {
			return;
		}

		$confirmation = $this->get_confirmation( $lead, $payment->status );

		// Display confirmation if it exists.
		if ( ! empty( $confirmation ) ) {
			if ( is_array( $confirmation ) && isset( $confirmation['redirect'] ) ) {
				wp_redirect( $confirmation['redirect'] );

				exit;
			}

			$form = GFAPI::get_form( $lead['form_id'] );

			GFFormDisplay::$submission[ $form['id'] ] = [
				'is_confirmation'      => true,
				'confirmation_message' => $confirmation,
				'form'                 => $form,
				'lead'                 => $lead,
			];
		}
	}

	/**
	 * Get confirmations for lead based on payment status.
	 *
	 * @param array  $lead           Lead.
	 * @param string $payment_status Payment status.
	 *
	 * @return mixed
	 */
	public function get_confirmation( $lead, $payment_status = PaymentStatus::OPEN ) {
		$form = GFAPI::get_form( $lead['form_id'] );

		$feed = FeedsDB::get_feed_by_entry_id( $lead['id'] );

		$link = Links::transform_status( $payment_status );

		if ( ! class_exists( 'GFFormDisplay' ) ) {
			require_once GFCommon::get_base_path() . '/form_display.php';
		}

		// Use only link confirmation if set.
		if ( isset( $feed->links[ $link ]['confirmation_id'] ) && ! empty( $feed->links[ $link ]['confirmation_id'] ) ) {
			$confirmation_id = $feed->links[ $link ]['confirmation_id'];

			if ( isset( $form['confirmations'][ $confirmation_id ] ) ) {
				$form['confirmations'] = array_intersect_key( $form['confirmations'], [ $confirmation_id => true ] );
			}
		}

		return GFFormDisplay::handle_confirmation( $form, $lead, false );
	}

	/**
	 * Replace merge tags
	 *
	 * @param string      $text       The text in which merge tags are being processed.
	 * @param array|false $form       The Form object if available or false.
	 * @param array|false $entry      The Entry object if available or false.
	 * @param boolean     $url_encode Indicates if the urlencode function should be applied.
	 * @param boolean     $esc_html   Indicates if the esc_html function should be applied.
	 * @param boolean     $nl2br      Indicates if the nl2br function should be applied.
	 * @param string      $format     The format requested for the location the merge is being used. Possible values: html, text or url.
	 *
	 * @return string
	 */
	public function replace_merge_tags( $text, $form, $entry, $url_encode, $esc_html, $nl2br, $format ) {
		$subscription_amount       = '';
		$subscription_cancel_url   = '';
		$subscription_renew_url    = '';
		$subscription_renewal_date = '';

		$subscription_id = gform_get_meta( rgar( $entry, 'id' ), 'pronamic_subscription_id' );

		$subscription = empty( $subscription_id ) ? null : \get_pronamic_subscription( $subscription_id );

		if ( null !== $subscription ) {
			$next_payment_date = $subscription->get_next_payment_date();

			if ( $next_payment_date ) {
				$subscription_renewal_date = date_i18n( get_option( 'date_format' ), $next_payment_date->getTimestamp() );
			}

			// Get amount from current phase.
			$current_phase = $subscription->get_current_phase();

			$subscription_amount = null;

			if ( null !== $current_phase ) {
				$subscription_amount = $current_phase->get_amount()->format_i18n();
			}

			$subscription_cancel_url = $subscription->get_cancel_url();
			$subscription_renew_url  = $subscription->get_renewal_url();
		}

		$payment_id              = (string) gform_get_meta( rgar( $entry, 'id' ), 'pronamic_payment_id' );
		$subscription_payment_id = (string) gform_get_meta( rgar( $entry, 'id' ), 'pronamic_subscription_payment_id' );

		/**
		 * Bank transfer recipient details.
		 *
		 * Use bank transfer details from last subscription payment if available.
		 */
		$payment = null;

		$payment = ( '' === $subscription_payment_id ) ? $payment : \get_pronamic_payment( $subscription_payment_id );

		if ( null === $payment ) {
			$payment = ( '' === $payment_id ) ? $payment : \get_pronamic_payment( $payment_id );
		}

		$bank_transfer_recipient_reference      = '';
		$bank_transfer_recipient_bank_name      = '';
		$bank_transfer_recipient_name           = '';
		$bank_transfer_recipient_iban           = '';
		$bank_transfer_recipient_bic            = '';
		$bank_transfer_recipient_city           = '';
		$bank_transfer_recipient_country        = '';
		$bank_transfer_recipient_account_number = '';

		if ( null !== $payment ) {
			$bank_transfer_recipient = $payment->get_bank_transfer_recipient_details();

			if ( null !== $bank_transfer_recipient ) {
				// Bank transfer reference.
				$bank_transfer_recipient_reference = \strval( $bank_transfer_recipient->get_reference() );

				// Bank account.
				$bank_account = $bank_transfer_recipient->get_bank_account();

				if ( null !== $bank_account ) {
					$bank_transfer_recipient_bank_name      = \strval( $bank_account->get_bank_name() );
					$bank_transfer_recipient_name           = \strval( $bank_account->get_name() );
					$bank_transfer_recipient_iban           = \strval( $bank_account->get_iban() );
					$bank_transfer_recipient_bic            = \strval( $bank_account->get_bic() );
					$bank_transfer_recipient_city           = \strval( $bank_account->get_city() );
					$bank_transfer_recipient_country        = \strval( $bank_account->get_country() );
					$bank_transfer_recipient_account_number = \strval( $bank_account->get_account_number() );
				}
			}
		}

		/**
		 * Consumer bank account details.
		 */
		$consumer_bank_account_name = '';
		$consumer_iban              = '';

		if ( null !== $payment ) {
			$consumer_bank_details = $payment->get_consumer_bank_details();

			if ( null !== $consumer_bank_details ) {
				$consumer_bank_account_name = \strval( $consumer_bank_details->get_name() );
				$consumer_iban              = \strval( $consumer_bank_details->get_iban() );
			}
		}

		// Pay again URL.
		$pay_again_url = \rgar( $entry, 'source_url' );

		if ( null !== $payment ) {
			$pay_again_url = \add_query_arg(
				[
					'pay_again' => $payment->get_id(),
					'key'       => $payment->key,
				],
				rgar( $entry, 'source_url' )
			);
		}

		// Replacements.
		$replacements = [
			'{payment_status}'                     => rgar( $entry, 'payment_status' ),
			'{payment_date}'                       => rgar( $entry, 'payment_date' ),
			'{transaction_id}'                     => rgar( $entry, 'transaction_id' ),
			'{payment_amount}'                     => GFCommon::to_money( rgar( $entry, 'payment_amount' ), rgar( $entry, 'currency' ) ),
			'{pronamic_payment_id}'                => $payment_id,
			'{pronamic_pay_again_url}'             => $pay_again_url,
			'{pronamic_payment_bank_transfer_recipient_reference}' => $bank_transfer_recipient_reference,
			'{pronamic_payment_bank_transfer_recipient_bank_name}' => $bank_transfer_recipient_bank_name,
			'{pronamic_payment_bank_transfer_recipient_name}' => $bank_transfer_recipient_name,
			'{pronamic_payment_bank_transfer_recipient_iban}' => $bank_transfer_recipient_iban,
			'{pronamic_payment_bank_transfer_recipient_bic}' => $bank_transfer_recipient_bic,
			'{pronamic_payment_bank_transfer_recipient_city}' => $bank_transfer_recipient_city,
			'{pronamic_payment_bank_transfer_recipient_country}' => $bank_transfer_recipient_country,
			'{pronamic_payment_bank_transfer_recipient_account_number}' => $bank_transfer_recipient_account_number,
			'{pronamic_payment_consumer_bank_account_name}' => $consumer_bank_account_name,
			'{pronamic_payment_consumer_iban}'     => $consumer_iban,
			'{pronamic_subscription_id}'           => $subscription_id,
			'{pronamic_subscription_payment_id}'   => $subscription_payment_id,
			'{pronamic_subscription_amount}'       => $subscription_amount,
			'{pronamic_subscription_cancel_url}'   => $subscription_cancel_url,
			'{pronamic_subscription_renew_url}'    => $subscription_renew_url,
			'{pronamic_subscription_renewal_date}' => $subscription_renewal_date,
		];

		if ( $url_encode ) {
			foreach ( $replacements as &$value ) {
				$value = rawurlencode( $value );
			}
		}

		$text = strtr( $text, $replacements );

		return $text;
	}

	/**
	 * Get delay actions based on active addons and built-in delay support.
	 *
	 * @return array
	 */
	public static function get_delay_actions() {
		$actions = [
			'gravityformsactivecampaign'   => [
				'active'                      => false,
				'meta_key_suffix'             => 'activecampaign_subscription',
				'delayed_payment_integration' => true,
				'label'                       => __( 'Subscribing the user to ActiveCampaign', 'pronamic_ideal' ),
			],
			'gravityformsaweber'           => [
				'active'                      => false,
				'meta_key_suffix'             => 'aweber_subscription',
				'delayed_payment_integration' => true,
				'label'                       => __( 'Subscribing the user to AWeber', 'pronamic_ideal' ),
				'delay_callback'              => function () {
					// @link https://github.com/wp-premium/gravityformsaweber/blob/1.4.2/aweber.php#L124-L125
					\remove_action( 'gform_post_submission', [ 'GFAWeber', 'export' ], 10 );
				},
				'process_callback'            => function ( $entry, $form ) {
					if ( Core_Util::class_method_exists( 'GFAWeber', 'export' ) ) {
						call_user_func( [ 'GFAWeber', 'export' ], $entry, $form, false );
					}
				},
			],
			'gravityformscampaignmonitor'  => [
				'active'                      => false,
				'meta_key_suffix'             => 'campaignmonitor_subscription',
				'delayed_payment_integration' => true,
				'label'                       => __( 'Subscribing the user to Campaign Monitor', 'pronamic_ideal' ),
				'delay_callback'              => function () {
					// @link https://github.com/wp-premium/gravityformscampaignmonitor/blob/2.5.1/campaignmonitor.php#L124-L125
					\remove_action( 'gform_after_submission', [ 'GFCampaignMonitor', 'export' ], 10 );
				},
				'process_callback'            => function ( $entry, $form ) {
					// @link https://github.com/wp-premium/gravityformscampaignmonitor/blob/2.5.1/campaignmonitor.php#L1184
					if ( Core_Util::class_method_exists( 'GFCampaignMonitor', 'export' ) ) {
						call_user_func( [ 'GFCampaignMonitor', 'export' ], $entry, $form, false );
					}
				},
			],
			'gravityformsmailchimp'        => [
				'active'                      => false,
				'meta_key_suffix'             => 'mailchimp_subscription',
				'delayed_payment_integration' => true,
				'label'                       => __( 'Subscribing the user to MailChimp', 'pronamic_ideal' ),
				'delay_callback'              => function () {
					// @link https://github.com/wp-premium/gravityformsmailchimp/blob/2.4.1/mailchimp.php#L120-L121
					\remove_action( 'gform_after_submission', [ 'GFMailChimp', 'export' ], 10 );
				},
				'process_callback'            => function ( $entry, $form ) {
					// @link https://github.com/wp-premium/gravityformsmailchimp/blob/2.4.5/mailchimp.php#L1512.
					if ( Core_Util::class_method_exists( 'GFMailChimp', 'export' ) ) {
						call_user_func( [ 'GFMailChimp', 'export' ], $entry, $form, false );
					}
				},
			],
			'slicedinvoices'               => [
				'active'                      => false,
				'meta_key_suffix'             => 'sliced_invoices',
				'delayed_payment_integration' => false,
				'label'                       => __( 'Creating quotes and invoices with Sliced Invoices', 'pronamic_ideal' ),
			],
			'gravityforms-moneybird'       => [
				'active'                      => false,
				'meta_key_suffix'             => 'moneybird',
				'delayed_payment_integration' => false,
				'label'                       => __( 'Sending estimates and invoices with Moneybird', 'pronamic_ideal' ),
			],
			'gravityformstwilio'           => [
				'active'                      => false,
				'meta_key_suffix'             => 'twilio',
				'delayed_payment_integration' => true,
				'label'                       => __( 'Sending data to Twilio', 'pronamic_ideal' ),
			],
			'gravityformswebhooks'         => [
				'active'                      => false,
				'meta_key_suffix'             => 'webhooks',
				'delayed_payment_integration' => false,
				'label'                       => __( 'Sending a trigger to Webhooks', 'pronamic_ideal' ),
			],
			'gravityformsdropbox'          => [
				'active'                      => false,
				'meta_key_suffix'             => 'dropbox',
				'delayed_payment_integration' => false,
				'label'                       => __( 'Uploading files to Dropbox', 'pronamic_ideal' ),
			],
			'gravityformszapier'           => [
				'active'                      => class_exists( 'GFZapier' ),
				'meta_key_suffix'             => 'zapier',
				'delayed_payment_integration' => false,
				'label'                       => __( 'Sending data to Zapier', 'pronamic_ideal' ),
				'delay_callback'              => function () {
					// @link https://github.com/wp-premium/gravityformszapier/blob/1.4.2/zapier.php#L106
					remove_action( 'gform_after_submission', [ 'GFZapier', 'send_form_data_to_zapier' ], 10 );
				},
				'process_callback'            => function ( $entry, $form ) {
					// @link https://github.com/wp-premium/gravityformszapier/blob/1.4.2/zapier.php#L469-L533.
					if ( Core_Util::class_method_exists( 'GFZapier', 'send_form_data_to_zapier' ) ) {
						call_user_func( [ 'GFZapier', 'send_form_data_to_zapier' ], $entry, $form );
					}
				},
			],
			'gravityformsuserregistration' => [
				'active'                      => false,
				'meta_key_suffix'             => 'user_registration',
				'delayed_payment_integration' => true,
				'label'                       => __( 'Registering the user', 'pronamic_ideal' ),
			],
			'gravityflow'                  => [
				'active'                      => false,
				'meta_key_suffix'             => 'gravityflow',
				'delayed_payment_integration' => true,
				'label'                       => __( 'Start the Workflow once payment has been received.', 'pronamic_ideal' ),
				'delay_callback'              => function () {
					// @link https://github.com/gravityflow/gravityflow/blob/master/class-gravity-flow.php#L4711-L4720
				},
				'process_callback'            => function ( $entry, $form ) {
					// @link https://github.com/gravityflow/gravityflow/blob/master/class-gravity-flow.php#L4730-L4746
					if ( ! \class_exists( '\Gravity_Flow' ) ) {
						return;
					}

					if ( ! \method_exists( '\Gravity_Flow', 'get_instance' ) ) {
						return;
					}

					$gravityflow = \Gravity_Flow::get_instance();

					$gravityflow->process_workflow( $form, $entry['id'] );
				},
			],
		];

		$addons = GFAddOn::get_registered_addons();

		foreach ( $addons as $class ) {
			$addon = call_user_func( [ $class, 'get_instance' ] );

			$slug = $addon->get_slug();

			if ( isset( $addon->delayed_payment_integration ) ) {
				if ( ! isset( $actions[ $slug ] ) ) {
					$actions[ $slug ] = [];
				}

				$actions[ $slug ]['meta_key_suffix']             = $slug;
				$actions[ $slug ]['delayed_payment_integration'] = true;
				$actions[ $slug ]['label']                       = \sprintf(
					/* translators: %s: plugin title */
					\__( 'Process %s feeds', 'pronamic_ideal' ),
					$addon->plugin_page_title()
				);

				if ( isset( $addon->delayed_payment_integration['option_label'] ) ) {
					$actions[ $slug ]['label'] = $addon->delayed_payment_integration['option_label'];
				}
			}

			if ( isset( $actions[ $slug ] ) ) {
				$actions[ $slug ]['addon']  = $addon;
				$actions[ $slug ]['active'] = true;
			}
		}

		foreach ( $actions as $slug => $data ) {
			$actions[ $slug ]['meta_key'] = '_pronamic_pay_gf_delay_' . $data['meta_key_suffix'];
		}

		/**
		 * Filters the delay actions to display on the payment feed settings page and to process.
		 *
		 * @since 2.4.0
		 *
		 * @link https://github.com/wp-premium/gravityforms/blob/2.4.17/print-entry.php#L148-L163
		 * @link https://github.com/phpDocumentor/phpDocumentor/issues/1712
		 *
		 * @param array $actions {
		 *
		 *     Delay action.
		 *
		 *     @var null|\GFAddon $addon                       Optional reference to a Gravity Forms add-on object.
		 *     @var bool          $active                      Boolean flag to indicate the delay action can be enabled (add-on active).
		 *     @var string        $meta_key                    Post meta key used to store meta value if the delay action is enabled.
		 *     @var bool          $delayed_payment_integration Boolean flag to indicate the delay action is defined by a delayed payment integration.
		 *     @var string        $label                       The label to show on the payment feed settings page.
		 *     @var callable      $delay_callback              Callback function which can be used to remove actions/filters to delay actions.
		 *     @var callable      $process_callback            Callback function to process the delay action.
		 *
		 * }
		 */
		$actions = \apply_filters( 'pronamic_pay_gravityforms_delay_actions', $actions );

		return $actions;
	}

	/**
	 * Maybe pre-populate form.
	 *
	 * @param array $args Form arguments.
	 * @return array
	 */
	public function maybe_prepopulate_form( $args ) {
		// Check empty field values.
		if ( isset( $args['field_values'] ) && ! empty( $args['field_values'] ) ) {
			return $args;
		}

		// Get payment retry entry.
		$entry = $this->get_payment_retry_entry();

		if ( null === $entry ) {
			return $args;
		}

		// Set field values.
		$field_values = [];

		foreach ( $entry as $key => $value ) {
			$is_numeric   = \is_numeric( $key );
			$contains_dot = ( false !== \strpos( $key, '.' ) );

			if ( ! $is_numeric && ! $contains_dot ) {
				continue;
			}

			$input_id = sprintf( 'input_%s', $key );
			$input_id = \str_replace( '.', '_', $input_id );

			$field_values[ $input_id ] = $value;
		}

		$args['field_values'] = $field_values;

		return $args;
	}

	/**
	 * Allow field pre-population.
	 *
	 * @param array $form Form.
	 * @return array
	 */
	public function allow_field_prepopulation( $form ) {
		// Get payment retry entry.
		$entry = $this->get_payment_retry_entry();

		if ( null === $entry ) {
			return $form;
		}

		// Allow field pre-population.
		foreach ( $form['fields'] as &$field ) {
			$input_name = sprintf( 'input_%s', $field->id );
			$input_name = \str_replace( '.', '_', $input_name );

			$field->allowsPrepopulate = true;
			$field->inputName         = $input_name;

			// Field inputs.
			if ( \is_array( $field['inputs'] ) ) {
				$new_inputs = $field['inputs'];

				foreach ( $new_inputs as &$input ) {
					$input_name = sprintf( 'input_%s', $input['id'] );
					$input_name = \str_replace( '.', '_', $input_name );

					$input['name'] = $input_name;
				}

				$field['inputs'] = $new_inputs;
			}
		}

		return $form;
	}

	/**
	 * Get payment retry entry.
	 *
	 * @return null|array
	 */
	public function get_payment_retry_entry() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Using payment key instead of nonce.
		if ( ! \filter_has_var( \INPUT_GET, 'pay_again' ) ) {
			return null;
		}

		if ( ! \array_key_exists( 'key', $_GET ) ) {
			return null;
		}

		// Check payment.
		$payment_id = \filter_input( \INPUT_GET, 'pay_again', \FILTER_SANITIZE_NUMBER_INT );

		$payment = \get_pronamic_payment( $payment_id );

		if ( null === $payment ) {
			return null;
		}

		// Check Gravity Forms source.
		if ( self::SLUG !== $payment->get_source() ) {
			return null;
		}

		// Check if payment key is valid.
		if ( empty( $payment->key ) ) {
			return null;
		}

		$key = \sanitize_text_field( \wp_unslash( $_GET['key'] ) );

		if ( $payment->key !== $key ) {
			return null;
		}

		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Get entry.
		$entry_id = $payment->get_source_id();

		$entry = RGFormsModel::get_lead( $entry_id );

		if ( false === $entry ) {
			return null;
		}

		return $entry;
	}

	/**
	 * Gravity Forms payment statuses.
	 * Gravity Forms does not have a partial refund status by default, we'll add it.
	 *
	 * @link https://github.com/wp-premium/gravityforms/blob/2.4.20/common.php#L5327-L5357
	 * @link https://github.com/wp-pay-extensions/easy-digital-downloads/blob/2.1.4/src/Extension.php#L486-L507
	 *
	 * @param array $payment_statuses Payment statuses.
	 * @return array<string, string>
	 */
	public function gform_payment_statuses( $payment_statuses ) {
		/**
		 * Note: The Gravity Forms payment status is limited to 15 chars (`varchar(15)`).
		 * That's why we use `PartlyRefunded` (14) instead of `PartiallyRefunded` (17).
		 *
		 * @link https://github.com/wp-premium/gravityforms/blob/2.4.20/includes/class-gf-upgrade.php#L435
		 */
		if ( \array_key_exists( 'PartlyRefunded', $payment_statuses ) ) {
			return $payment_statuses;
		}

		$payment_statuses['PartlyRefunded'] = __( 'Partially Refunded', 'pronamic_ideal' );

		return $payment_statuses;
	}
}
