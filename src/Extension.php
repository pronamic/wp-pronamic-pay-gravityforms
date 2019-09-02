<?php
/**
 * Extension
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2019 Pronamic
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
use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Core\Recurring;
use Pronamic\WordPress\Pay\Core\Statuses;
use Pronamic\WordPress\Pay\Core\Util as Core_Util;
use Pronamic\WordPress\Pay\Customer;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Subscriptions\Subscription;
use RGFormsModel;
use WP_User;

/**
 * Title: WordPress pay extension Gravity Forms extension
 * Description:
 * Copyright: 2005-2019 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.1.11
 * @since   1.0.0
 */
class Extension {
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
	private $addon;

	/**
	 * Bootstrap
	 */
	public static function bootstrap() {
		if ( ! GravityForms::is_active() ) {
			return;
		}

		$extension = new Extension();
		$extension->setup();
	}

	/**
	 * Setup.
	 */
	public function setup() {
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	}

	/**
	 * Plugins loaded
	 */
	public function plugins_loaded() {
		// Gravity Forms version 1.0 is required.
		if ( GravityForms::version_compare( '1.0', '<' ) ) {
			return;
		}

		// Post types.
		$this->payment_form_post_type = new PaymentFormPostType();

		// Actions
		// Initialize hook, Gravity Forms uses the default priority (10).
		add_action( 'init', array( $this, 'init' ), 20 );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		// Add-on
		// The `class_exists` call is required to prevent strange errors on some hosting environments.
		if ( Core_Util::class_method_exists( 'GFForms', 'include_payment_addon_framework' ) ) {
			GFForms::include_payment_addon_framework();

			if ( class_exists( 'GFPaymentAddOn' ) ) {
				$this->addon = new PaymentAddOn();
			}
		}

		// Fields.
		$this->fields = new Fields();
	}

	/**
	 * Initialize
	 */
	public function init() {
		// Admin.
		if ( is_admin() ) {
			Admin::bootstrap();
		} else {
			add_action( 'gform_pre_submission', array( $this, 'pre_submission' ) );
		}

		add_filter( 'pronamic_payment_redirect_url_' . self::SLUG, array( $this, 'redirect_url' ), 10, 2 );
		add_action( 'pronamic_payment_status_update_' . self::SLUG, array( $this, 'update_status' ), 10, 2 );
		add_action( 'pronamic_subscription_status_update_' . self::SLUG, array( $this, 'subscription_update_status' ) );
		add_action( 'pronamic_subscription_renewal_notice_' . self::SLUG, array( $this, 'subscription_renewal_notice' ) );
		add_filter( 'pronamic_pay_subscription_amount_editable_' . self::SLUG, '__return_true' );
		add_filter( 'pronamic_payment_source_text_' . self::SLUG, array( $this, 'source_text' ), 10, 2 );
		add_filter( 'pronamic_payment_source_description_' . self::SLUG, array( $this, 'source_description' ), 10, 2 );
		add_filter( 'pronamic_payment_source_url_' . self::SLUG, array( $this, 'source_url' ), 10, 2 );

		add_filter( 'gform_replace_merge_tags', array( $this, 'replace_merge_tags' ), 10, 7 );

		add_filter( 'gform_gf_field_create', array( $this, 'field_create' ), 10, 2 );

		add_filter( 'gform_currencies', array( __CLASS__, 'currencies' ), 10, 1 );

		// Register scripts and styles if Gravity Forms No-Conflict Mode is enabled.
		add_filter( 'gform_noconflict_scripts', array( $this, 'no_conflict_scripts' ) );
		add_filter( 'gform_noconflict_styles', array( $this, 'no_conflict_styles' ) );

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

		if (
			'pronamic_pay_gf' === $screen->post_type
			||
			'toplevel_page_gf_edit_forms' === $screen->id
		) {
			$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_register_style(
				'pronamic-pay-gravityforms',
				plugins_url( 'css/admin' . $min . '.css', dirname( __FILE__ ) ),
				array(),
				'1.4.8'
			);

			wp_register_script(
				'pronamic-pay-gravityforms',
				plugins_url( 'js/admin' . $min . '.js', dirname( __FILE__ ) ),
				array( 'jquery' ),
				'1.4.8',
				true
			);

			wp_enqueue_style( 'pronamic-pay-gravityforms' );

			wp_enqueue_script( 'pronamic-pay-gravityforms' );
		}
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
	 * Pre submssion
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
			'<a href="%s">%s</a>',
			add_query_arg( array( 'pronamic_gf_lid' => $payment->get_source_id() ), admin_url( 'admin.php' ) ),
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
		return add_query_arg( 'pronamic_gf_lid', $payment->get_source_id(), admin_url( 'admin.php' ) );
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
		if ( class_exists( 'GF_User_Registration' ) ) {
			// Version >= 3.
			$user = gf_user_registration()->get_user_by_entry_id( $lead['id'] );
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
		if ( class_exists( 'GF_User_Registration' ) ) {
			// Version >= 3.
			$user = gf_user_registration()->get_user_by_entry_id( $lead['id'] );
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

		// Update payment post author.
		if ( null === $payment->get_customer() ) {
			$payment->set_customer( new Customer() );
		}

		$payment->get_customer()->set_user_id( $user->ID );

		$payment->save();

		wp_update_post(
			array(
				'ID'          => $payment->get_id(),
				'post_author' => $user->ID,
			)
		);

		// Update subscription post author.
		$subscription = $payment->get_subscription();

		if ( null !== $subscription ) {
			if ( null === $subscription->get_customer() ) {
				$subscription->set_customer( new Customer() );
			}

			$subscription->get_customer()->set_user_id( $user->ID );

			$subscription->save();

			wp_update_post(
				array(
					'ID'          => $subscription->get_id(),
					'post_author' => $user->ID,
				)
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

		$form_id = $lead['form_id'];

		$form = RGFormsModel::get_form_meta( $form_id );
		$feed = FeedsDB::get_feed_by_entry_id( $lead_id );

		if ( ! $feed ) {
			return $url;
		}

		$data = new PaymentData( $form, $lead, $feed );

		switch ( $payment->status ) {
			case Statuses::CANCELLED:
				$url = $data->get_cancel_url();

				break;
			case Statuses::EXPIRED:
				$url = $feed->get_url( Links::EXPIRED );

				break;
			case Statuses::FAILURE:
				$url = $data->get_error_url();

				break;
			case Statuses::SUCCESS:
				$url = $data->get_success_url();

				break;
			case Statuses::OPEN:
			default:
				$url = $data->get_normal_return_url();

				break;
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
						array(
							'pay_confirmation' => $payment->get_id(),
							'_wpnonce'         => wp_create_nonce( 'gf_confirmation_payment_' . $payment->get_id() ),
						),
						$lead['source_url']
					);
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

		$action = array(
			'id'             => $payment->get_id(),
			'transaction_id' => $payment->get_transaction_id(),
			'amount'         => $payment->get_total_amount()->get_value(),
			'entry_id'       => $lead['id'],
		);

		if ( $data->get_subscription() ) {
			$action['subscription_id'] = $payment->get_subscription_id();
		}

		$success_action = 'complete_payment';
		$fail_action    = 'fail_payment';

		if ( 'recurring' === $payment->recurring_type ) {
			$success_action = 'add_subscription_payment';
			$fail_action    = 'fail_subscription_payment';
		}

		switch ( $payment->status ) {
			case Statuses::CANCELLED:
				$this->payment_action( $fail_action, $lead, $action, PaymentStatuses::CANCELLED );

				break;
			case Statuses::EXPIRED:
				$this->payment_action( $fail_action, $lead, $action, PaymentStatuses::EXPIRED );

				break;
			case Statuses::FAILURE:
				$this->payment_action( $fail_action, $lead, $action, PaymentStatuses::FAILED );

				break;
			case Statuses::REFUNDED:
				$this->payment_action( 'refund_payment', $lead, $action, PaymentStatuses::REFUNDED );

				break;
			case Statuses::SUCCESS:
				if ( ! Entry::is_payment_approved( $lead ) || 'add_subscription_payment' === $success_action ) {
					// @link https://github.com/wp-premium/gravityformspaypal/blob/2.3.1/class-gf-paypal.php#L1741-L1742
					$this->payment_action( $success_action, $lead, $action, PaymentStatuses::PAID );
				}

				// Create subscription.
				if ( ! Entry::is_payment_approved( $lead ) && Recurring::FIRST === $payment->recurring_type && isset( $action['subscription_id'] ) && ! empty( $action['subscription_id'] ) ) {
					$action['subscription_start_date'] = gmdate( 'Y-m-d H:i:s' );

					$this->payment_action( 'create_subscription', $lead, $action );
				}

				// Fulfill order.
				$this->fulfill_order( $lead );

				break;
			case Statuses::OPEN:
			default:
				if ( 'recurring' === $payment->recurring_type ) {
					gform_update_meta( $lead['id'], 'pronamic_subscription_payment_id', $payment->get_id() );
				}
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

		$action = array(
			'id'              => $subscription->get_id(),
			'subscription_id' => $subscription->get_id(),
			'amount'          => $subscription->get_total_amount()->get_value(),
			'entry_id'        => $lead['id'],
		);

		switch ( $subscription->get_status() ) {
			case Statuses::ACTIVE:
				if ( ! Entry::is_payment_active( $lead ) ) {
					$action['note'] = __( 'Subscription manually activated.', 'pronamic_ideal' );

					// Set amount to `0` to prevent incorrect revenue in reports.
					$action['amount'] = 0;

					$this->payment_action( 'add_subscription_payment', $lead, $action, PaymentStatuses::PAID );
				}

				break;
			case Statuses::CANCELLED:
				$this->payment_action( 'cancel_subscription', $lead, $action, PaymentStatuses::CANCELLED );

				break;
			case Statuses::EXPIRED:
			case Statuses::COMPLETED:
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

		$action = array(
			'subscription_id' => $subscription->get_id(),
			'amount'          => $subscription->get_total_amount()->get_value(),
			'entry_id'        => $lead['id'],
			'type'            => 'renew_subscription',
		);

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
				$delay_notification_ids = array();

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

		// The Gravity Forms PayPal Add-On executes the 'gform_paypal_fulfillment' action.
		do_action( 'gform_ideal_fulfillment', $entry, $feed );
	}

	/**
	 * Maybe display the Gravity Forms confirmation.
	 *
	 * @return void
	 */
	public function maybe_display_confirmation() {
		if ( ! filter_has_var( INPUT_GET, 'pay_confirmation' ) || ! filter_has_var( INPUT_GET, '_wpnonce' ) ) {
			return;
		}

		$payment_id = filter_input( INPUT_GET, 'pay_confirmation', FILTER_SANITIZE_NUMBER_INT );

		$nonce = filter_input( INPUT_GET, '_wpnonce', FILTER_SANITIZE_STRING );

		// Verify nonce.
		if ( ! wp_verify_nonce( $nonce, 'gf_confirmation_payment_' . $payment_id ) ) {
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

			GFFormDisplay::$submission[ $form['id'] ] = array(
				'is_confirmation'      => true,
				'confirmation_message' => $confirmation,
				'form'                 => $form,
				'lead'                 => $lead,
			);
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
	public function get_confirmation( $lead, $payment_status = Statuses::OPEN ) {
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
				$form['confirmations'] = array_intersect_key( $form['confirmations'], array( $confirmation_id => true ) );
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

		if ( ! empty( $subscription_id ) ) {
			$subscription = get_pronamic_subscription( $subscription_id );

			$next_payment_date = $subscription->get_next_payment_date();

			if ( $next_payment_date ) {
				$subscription_renewal_date = date_i18n( get_option( 'date_format' ), $next_payment_date->getTimestamp() );
			}

			$subscription_amount     = $subscription->get_total_amount()->format_i18n();
			$subscription_cancel_url = $subscription->get_cancel_url();
			$subscription_renew_url  = $subscription->get_renewal_url();
		}

		$replacements = array(
			'{payment_status}'                     => rgar( $entry, 'payment_status' ),
			'{payment_date}'                       => rgar( $entry, 'payment_date' ),
			'{transaction_id}'                     => rgar( $entry, 'transaction_id' ),
			'{payment_amount}'                     => GFCommon::to_money( rgar( $entry, 'payment_amount' ), rgar( $entry, 'currency' ) ),
			'{pronamic_payment_id}'                => gform_get_meta( rgar( $entry, 'id' ), 'pronamic_payment_id' ),
			'{pronamic_subscription_payment_id}'   => gform_get_meta( rgar( $entry, 'id' ), 'pronamic_subscription_payment_id' ),
			'{pronamic_subscription_amount}'       => $subscription_amount,
			'{pronamic_subscription_cancel_url}'   => $subscription_cancel_url,
			'{pronamic_subscription_renew_url}'    => $subscription_renew_url,
			'{pronamic_subscription_renewal_date}' => $subscription_renewal_date,
		);

		if ( $url_encode ) {
			foreach ( $replacements as &$value ) {
				$value = rawurlencode( $value );
			}
		}

		$text = strtr( $text, $replacements );

		return $text;
	}

	/**
	 * Filter currencies.
	 *
	 * @param array $currencies Available currencies.
	 *
	 * @return mixed
	 */
	public static function currencies( $currencies ) {
		if ( PaymentMethods::is_active( PaymentMethods::GULDEN ) ) {
			$currencies['NLG'] = array(
				'name'               => PaymentMethods::get_name( PaymentMethods::GULDEN ),
				'symbol_left'        => 'G',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => '',
				'decimal_separator'  => '.',
				'decimals'           => 4,
			);
		}

		return $currencies;
	}

	/**
	 * Get delay actions based on active addons and built-in delay support.
	 *
	 * @return array
	 */
	public static function get_delay_actions() {
		$actions = array(
			'gravityformsactivecampaign'   => array(
				'active'                      => false,
				'meta_key_suffix'             => 'activecampaign_subscription',
				'delayed_payment_integration' => true,
				'label'                       => __( 'Subscribing the user to ActiveCampaign', 'pronamic_ideal' ),
			),
			'gravityformsaweber'           => array(
				'active'                      => false,
				'meta_key_suffix'             => 'aweber_subscription',
				'delayed_payment_integration' => true,
				'label'                       => __( 'Subscribing the user to AWeber', 'pronamic_ideal' ),
				'delay_callback'              => function() {
					// @link https://github.com/wp-premium/gravityformsaweber/blob/1.4.2/aweber.php#L124-L125
					remove_action( 'gform_post_submission', array( 'GFAWeber', 'export' ), 10, 2 );
				},
				'process_callback'            => function( $entry, $form ) {
					if ( Core_Util::class_method_exists( 'GFAWeber', 'export' ) ) {
						call_user_func( array( 'GFAWeber', 'export' ), $entry, $form, false );
					}
				},
			),
			'gravityformscampaignmonitor'  => array(
				'active'                      => false,
				'meta_key_suffix'             => 'campaignmonitor_subscription',
				'delayed_payment_integration' => true,
				'label'                       => __( 'Subscribing the user to Campaign Monitor', 'pronamic_ideal' ),
				'delay_callback'              => function() {
					// @link https://github.com/wp-premium/gravityformscampaignmonitor/blob/2.5.1/campaignmonitor.php#L124-L125
					remove_action( 'gform_after_submission', array( 'GFCampaignMonitor', 'export' ), 10, 2 );
				},
				'process_callback'            => function( $entry, $form ) {
					// @link https://github.com/wp-premium/gravityformscampaignmonitor/blob/2.5.1/campaignmonitor.php#L1184
					if ( Core_Util::class_method_exists( 'GFCampaignMonitor', 'export' ) ) {
						call_user_func( array( 'GFCampaignMonitor', 'export' ), $entry, $form, false );
					}
				},
			),
			'gravityformsmailchimp'        => array(
				'active'                      => false,
				'meta_key_suffix'             => 'mailchimp_subscription',
				'delayed_payment_integration' => true,
				'label'                       => __( 'Subscribing the user to MailChimp', 'pronamic_ideal' ),
				'delay_callback'              => function() {
					// @link https://github.com/wp-premium/gravityformsmailchimp/blob/2.4.1/mailchimp.php#L120-L121
					remove_action( 'gform_after_submission', array( 'GFMailChimp', 'export' ), 10, 2 );
				},
				'process_callback'            => function( $entry, $form ) {
					// @link https://github.com/wp-premium/gravityformsmailchimp/blob/2.4.5/mailchimp.php#L1512.
					if ( Core_Util::class_method_exists( 'GFMailChimp', 'export' ) ) {
						call_user_func( array( 'GFMailChimp', 'export' ), $entry, $form, false );
					}
				},
			),
			'slicedinvoices'               => array(
				'active'                      => false,
				'meta_key_suffix'             => 'sliced_invoices',
				'delayed_payment_integration' => false,
				'label'                       => __( 'Creating quotes and invoices with Sliced Invoices', 'pronamic_ideal' ),
			),
			'gravityforms-moneybird'       => array(
				'active'                      => false,
				'meta_key_suffix'             => 'moneybird',
				'delayed_payment_integration' => false,
				'label'                       => __( 'Sending estimates and invoices with Moneybird', 'pronamic_ideal' ),
			),
			'gravityformstwilio'           => array(
				'active'                      => false,
				'meta_key_suffix'             => 'twilio',
				'delayed_payment_integration' => true,
				'label'                       => __( 'Sending data to Twilio', 'pronamic_ideal' ),
			),
			'gravityformswebhooks'         => array(
				'active'                      => false,
				'meta_key_suffix'             => 'webhooks',
				'delayed_payment_integration' => false,
				'label'                       => __( 'Sending a trigger to Webhooks', 'pronamic_ideal' ),
			),
			'gravityformsdropbox'          => array(
				'active'                      => false,
				'meta_key_suffix'             => 'dropbox',
				'delayed_payment_integration' => false,
				'label'                       => __( 'Uploading files to Dropbox', 'pronamic_ideal' ),
			),
			'gravityformszapier'           => array(
				'active'                      => class_exists( 'GFZapier' ),
				'meta_key_suffix'             => 'zapier',
				'delayed_payment_integration' => false,
				'label'                       => __( 'Sending data to Zapier', 'pronamic_ideal' ),
				'delay_callback'              => function() {
					// @link https://github.com/wp-premium/gravityformszapier/blob/1.4.2/zapier.php#L106
					remove_action( 'gform_after_submission', array( 'GFZapier', 'send_form_data_to_zapier' ), 10 );
				},
				'process_callback'            => function( $entry, $form ) {
					// @link https://github.com/wp-premium/gravityformszapier/blob/1.4.2/zapier.php#L469-L533.
					if ( Core_Util::class_method_exists( 'GFZapier', 'send_form_data_to_zapier' ) ) {
						call_user_func( array( 'GFZapier', 'send_form_data_to_zapier' ), $entry, $form );
					}
				},
			),
			'gravityformsuserregistration' => array(
				'active'                      => false,
				'meta_key_suffix'             => 'user_registration',
				'delayed_payment_integration' => true,
				'label'                       => __( 'Registering the user', 'pronamic_ideal' ),
			),
			'gravityflow'                  => array(
				'active'                      => false,
				'meta_key_suffix'             => 'gravityflow',
				'delayed_payment_integration' => true,
				'label'                       => __( 'Start the Workflow once payment has been received.', 'pronamic_ideal' ),
				'delay_callback'              => function() {
					// @link https://github.com/gravityflow/gravityflow/blob/master/class-gravity-flow.php#L4711-L4720
				},
				'process_callback'            => function( $entry, $form ) {
					// @link https://github.com/gravityflow/gravityflow/blob/master/class-gravity-flow.php#L4730-L4746
					if ( Core_Util::class_method_exists( 'Gravity_Flow', 'get_instance' ) ) {
						$gravityflow = \Gravity_Flow::get_instance();

						$gravityflow->process_workflow( $form, $entry['id'] );
					}
				},
			),
		);

		$addons = GFAddOn::get_registered_addons();

		foreach ( $addons as $class ) {
			$addon = call_user_func( array( $class, 'get_instance' ) );

			$slug = $addon->get_slug();

			if ( isset( $addon->delayed_payment_integration ) ) {
				if ( ! isset( $actions[ $slug ] ) ) {
					$actions[ $slug ] = array();
				}

				$actions[ $slug ]['meta_key_suffix']             = $slug;
				$actions[ $slug ]['delayed_payment_integration'] = true;

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

		return $actions;
	}
}
