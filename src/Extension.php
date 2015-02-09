<?php

/**
 * Title: WordPress pay extension Gravity Forms extension
 * Description:
 * Copyright: Copyright (c) 2005 - 2015
 * Company: Pronamic
 * @author Remco Tolsma
 * @version 1.0.0
 */
class Pronamic_WP_Pay_Extensions_GravityForms_Extension {
	/**
	 * Slug
	 *
	 * @var string
	 */
	const SLUG = 'gravityformsideal';

	/**
	 * Gravity Forms minimum required version
	 *
	 * @var string
	 */
	const GRAVITY_FORMS_MINIMUM_VERSION = '1.0';

	//////////////////////////////////////////////////

	/**
	 * Bootstrap
	 */
	public static function bootstrap() {
		$extension = new Pronamic_WP_Pay_Extensions_GravityForms_Extension();
	}

	//////////////////////////////////////////////////

	/**
	 * Constructs and initialize an Gravity Forms extension object
	 */
	public function __construct() {
		// Post types
		$this->payment_form_post_type = new Pronamic_WP_Pay_Extensions_GravityForms_PaymentFormPostType();

		// Actions
		// Initialize hook, Gravity Forms uses the default priority (10)
		add_action( 'init', array( $this, 'init' ), 20 );
	}

	//////////////////////////////////////////////////

	/**
	 * Initialize
	 */
	public function init() {
		if ( $this->is_gravityforms_supported() ) {
			// Add-on
			if ( class_exists( 'GFPaymentAddOn' ) ) {
				$this->addon = new Pronamic_WP_Pay_Extensions_GravityForms_PaymentAddOn();
			}

			// Admin
			if ( is_admin() ) {
				Pronamic_WP_Pay_Extensions_GravityForms_Admin::bootstrap();
			} else {
				add_action( 'gform_pre_submission', array( $this, 'pre_submission' ) );
			}

			add_action( 'pronamic_payment_status_update_' . self::SLUG, array( $this, 'update_status' ), 10, 2 );
			add_filter( 'pronamic_payment_source_text_' . self::SLUG,   array( $this, 'source_text' ), 10, 2 );

			add_filter( 'gform_replace_merge_tags', array( $this, 'replace_merge_tags' ), 10, 7 );

			// iDEAL fields
			Pronamic_WP_Pay_Extensions_GravityForms_Fields::bootstrap();
		}
	}

	//////////////////////////////////////////////////

	/**
	 * Pre submssion
	 *
	 * @param array $form
	 */
	public function pre_submission( $form ) {
		$processor = new Pronamic_WP_Pay_Extensions_GravityForms_Processor( $form );

		$processor->pre_submission( $form );
	}

	//////////////////////////////////////////////////

	/**
	 * Source column
	 */
	public function source_text( $text, Pronamic_Pay_Payment $payment ) {
		$text  = '';

		$text .= __( 'Gravity Forms', 'pronamic_ideal' ) . '<br />';

		$text .= sprintf(
			'<a href="%s">%s</a>',
			add_query_arg( array( 'pronamic_gf_lid' => $payment->get_source_id() ), admin_url( 'admin.php' ) ),
			sprintf( __( 'Entry #%s', 'pronamic_ideal' ), $payment->get_source_id() )
		);

		return $text;
	}

	//////////////////////////////////////////////////

	/**
	 * Maybe update user role of the specified lead and feed
	 *
	 * @param array $lead
	 * @param Feed $feed
	 */
	private function maybe_update_user_role( $lead, $feed ) {
		$user = false;

		// Gravity Forms User Registration Add-On
		if ( class_exists( 'GFUserData' ) ) {
			$user = GFUserData::get_user_by_entry_id( $lead['id'] );
		}

		if ( false == $user ) {
			$created_by = $lead[ Pronamic_WP_Pay_Extensions_GravityForms_LeadProperties::CREATED_BY ];

			$user = new WP_User( $created_by );
		}

		if ( $user && ! empty( $feed->user_role_field_id ) && isset( $lead[ $feed->user_role_field_id ] ) ) {
			$value = $lead[ $feed->user_role_field_id ];
			$value = GFCommon::get_selection_value( $value );

			$user->set_role( $value );
		}
	}

	//////////////////////////////////////////////////

	/**
	 * Update lead status of the specified payment
	 *
	 * @param string $payment
	 */
	public function update_status( Pronamic_Pay_Payment $payment, $can_redirect = false ) {
		$lead_id = $payment->get_source_id();

		$lead = RGFormsModel::get_lead( $lead_id );

		if ( $lead ) {
			$form_id = $lead['form_id'];

			$form = RGFormsModel::get_form( $form_id );
			$feed = get_pronamic_gf_pay_feed_by_form_id( $form_id );

			$data = new Pronamic_WP_Pay_Extensions_GravityForms_PaymentData( $form, $lead, $feed );

			if ( $feed ) {
				$url = null;

				switch ( $payment->status ) {
					case Pronamic_WP_Pay_Statuses::CANCELLED :
						$lead[ Pronamic_WP_Pay_Extensions_GravityForms_LeadProperties::PAYMENT_STATUS ] = Pronamic_WP_Pay_Extensions_GravityForms_PaymentStatuses::CANCELLED;

						$url = $data->get_cancel_url();

						break;
					case Pronamic_WP_Pay_Statuses::EXPIRED :
						$lead[ Pronamic_WP_Pay_Extensions_GravityForms_LeadProperties::PAYMENT_STATUS ] = Pronamic_WP_Pay_Extensions_GravityForms_PaymentStatuses::EXPIRED;

						$url = $feed->get_url( Pronamic_WP_Pay_Extensions_GravityForms_Links::EXPIRED );

						break;
					case Pronamic_WP_Pay_Statuses::FAILURE :
						$lead[ Pronamic_WP_Pay_Extensions_GravityForms_LeadProperties::PAYMENT_STATUS ] = Pronamic_WP_Pay_Extensions_GravityForms_PaymentStatuses::FAILED;

						$url = $data->get_error_url();

						break;
					case Pronamic_WP_Pay_Statuses::SUCCESS :
						if ( ! Pronamic_WP_Pay_Extensions_GravityForms_Entry::is_payment_approved( $lead ) ) {
							// Only fullfill order if the payment isn't approved aloready
							$lead[ Pronamic_WP_Pay_Extensions_GravityForms_LeadProperties::PAYMENT_STATUS ] = Pronamic_WP_Pay_Extensions_GravityForms_PaymentStatuses::APPROVED;

							// @see https://github.com/gravityforms/gravityformspaypal/blob/2.3.1/class-gf-paypal.php#L1741-L1742
							if ( $this->addon ) {
								$action = array(
									'id'             => $payment->get_transaction_id(),
									'type'           => 'complete_payment',
									'transaction_id' => $payment->get_transaction_id(),
									'amount'         => $payment->get_amount(),
									'entry_id'       => $lead['id'],
								);

								$this->addon->complete_payment( $lead, $action );
							}

							$this->fulfill_order( $lead );
						}

						$url = $data->get_success_url();

						break;
					case Pronamic_WP_Pay_Statuses::OPEN :
					default :
						$url = $data->get_normal_return_url();

						break;
				}

				Pronamic_WP_Pay_Extensions_GravityForms_GravityForms::update_entry( $lead );

				if ( $url && $can_redirect ) {
					wp_redirect( $url, 303 );

					exit;
				}
			}
		}
	}

	/**
	 * Fulfill order
	 *
	 * @param array $entry
	 */
	public function fulfill_order( $entry ) {
		$feed = get_pronamic_gf_pay_feed_by_form_id( $entry['form_id'] );

		if ( null !== $feed ) {
			$this->maybe_update_user_role( $entry, $feed );

			$form = RGFormsModel::get_form_meta( $entry['form_id'] );

			// Delay post creation
			// @see https://github.com/gravityforms/gravityforms/blob/1.8.20.5/forms_model.php#L2383
			// @see https://github.com/gravityforms/gravityformspaypal/blob/1.10.3/paypal.php#L2411-L2415
			if ( $feed->delay_post_creation ) {
				RGFormsModel::create_post( $form, $entry );
			}

			// Delay Aweber
			// @see https://github.com/gravityforms/gravityformsaweber/blob/1.4.2/aweber.php#L1167-L1197
			if ( $feed->delay_aweber_subscription && method_exists( 'GFAWeber', 'export' ) ) {
				call_user_func( array( 'GFAWeber', 'export' ), $entry, $form, false );
			}

			// Delay Campaign Monitor
			// @see https://github.com/gravityforms/gravityformscampaignmonitor/blob/2.5.1/campaignmonitor.php#L1184
			if ( $feed->delay_campaignmonitor_subscription && method_exists( 'GFCampaignMonitor', 'export' ) ) {
				call_user_func( array( 'GFCampaignMonitor', 'export' ), $entry, $form, false );
			}

			// Delay Mailchimp
			// @see https://github.com/gravityforms/gravityformsmailchimp/blob/2.4.5/mailchimp.php#L1512
			if ( $feed->delay_mailchimp_subscription && method_exists( 'GFMailChimp', 'export' ) ) {
				call_user_func( array( 'GFMailChimp', 'export' ), $entry, $form, false );
			}

			// Delay Zapier
			// @see https://github.com/gravityforms/gravityformszapier/blob/1.4.2/zapier.php#L469-L533
			if ( $feed->delay_zapier && method_exists( 'GFZapier', 'send_form_data_to_zapier' ) ) {
				call_user_func( array( 'GFZapier', 'send_form_data_to_zapier' ), $entry, $form );
			}

			// Delay user registration
			// @see https://github.com/gravityforms/gravityformsuserregistration/blob/2.0/userregistration.php#L2133
			if ( $feed->delay_user_registration && method_exists( 'GFUser', 'gf_create_user' ) ) {
				call_user_func( array( 'GFUser', 'gf_create_user' ), $entry, $form, false );
			}

			// Delay notifications
			// Determine if the feed has Gravity Form 1.7 Feed IDs
			if ( $feed->has_delayed_notifications() ) {
				// @see https://bitbucket.org/Pronamic/gravityforms/src/42773f75ad7ad9ac9c31ce149510ff825e4aa01f/common.php?at=1.7.8#cl-1512
				GFCommon::send_notifications( $feed->delay_notification_ids, $form, $entry, true, 'form_submission' );
			}

			if ( $feed->delay_admin_notification && method_exists( 'GFCommon', 'send_admin_notification' ) ) {
				// https://github.com/gravityforms/gravityforms/blob/1.8.9/common.php#L1265-L1270
				GFCommon::send_admin_notification( $form, $entry );
			}

			if ( $feed->delay_user_notification && method_exists( 'GFCommon', 'send_user_notification' ) ) {
				// https://github.com/gravityforms/gravityforms/blob/1.8.9/common.php#L1258-L1263
				GFCommon::send_user_notification( $form, $entry );
			}
		}

		// The Gravity Forms PayPal Add-On executes the 'gform_paypal_fulfillment' action
		do_action( 'gform_ideal_fulfillment', $entry, $feed );
	}

	//////////////////////////////////////////////////

	/**
	 * Checks if Gravity Forms is supported
	 *
	 * @return true if Gravity Forms is supported, false otherwise
	 */
	public function is_gravityforms_supported() {
		if ( class_exists( 'GFCommon' ) ) {
			return version_compare( GFCommon::$version, self::GRAVITY_FORMS_MINIMUM_VERSION, '>=' );
		} else {
			return false;
		}
	}

	//////////////////////////////////////////////////

	/**
	 * Replace merge tags
	 *
	 * @param string $text
	 * @param array $form
	 * @param array $entry
	 * @param boolean $url_encode
	 * @param boolean $esc_html
	 * @param boolean $nl2br
	 * @param string $format
	 */
	public function replace_merge_tags( $text, $form, $entry, $url_encode, $esc_html, $nl2br, $format ) {
		$search = array(
			'{payment_status}',
			'{payment_date}',
			'{transaction_id}',
			'{payment_amount}',
		);

		$replace = array(
			rgar( $entry, 'payment_status' ),
			rgar( $entry, 'payment_date' ),
			rgar( $entry, 'transaction_id' ),
			GFCommon::to_money( rgar( $entry, 'payment_amount' ) , rgar( $entry, 'currency' ) ),
		);

		if ( $url_encode ) {
			foreach ( $replace as &$value ) {
				$value = urlencode( $value );
			}
		}

		$text = str_replace( $search, $replace, $text );

		return $text;
	}
}
