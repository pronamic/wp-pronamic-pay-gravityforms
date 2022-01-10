<?php
/**
 * Admin
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2022 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

use RGFormsModel;
use stdClass;

/**
 * Title: WordPress pay extension Gravity Forms admin
 * Description:
 * Copyright: 2005-2022 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.1.14
 * @since   1.0.0
 */
class Admin {
	/**
	 * Bootstrap.
	 */
	public static function bootstrap() {
		// Actions.
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_redirect_to_entry' ) );

		add_action( 'gform_entry_info', array( __CLASS__, 'entry_info' ), 10, 2 );

		// Filters.
		add_filter( 'gform_custom_merge_tags', array( __CLASS__, 'custom_merge_tags' ), 10 );

		// Actions - AJAX.
		add_action( 'wp_ajax_gf_get_form_data', array( __CLASS__, 'ajax_get_form_data' ) );
	}

	/**
	 * Admin initialize.
	 */
	public static function admin_init() {
		new AdminPaymentFormPostType();
	}

	/**
	 * Add menu item to form settings.
	 *
	 * @param array $menu_items Array with form settings menu items.
	 *
	 * @return array
	 */
	public static function form_settings_menu_item( $menu_items ) {
		$menu_items[] = array(
			'name'  => 'pronamic_pay',
			'label' => __( 'Pay', 'pronamic_ideal' ),
			'query' => array( 'fid' => null ),
		);

		return $menu_items;
	}

	/**
	 * Render entry info of the specified form and lead
	 *
	 * @param string $form_id Gravity Forms form ID.
	 * @param array  $lead    Gravity Forms lead/entry.
	 */
	public static function entry_info( $form_id, $lead ) {
		$payment_id = gform_get_meta( $lead['id'], 'pronamic_payment_id' );

		if ( ! $payment_id ) {
			return;
		}

		printf(
			'<a href="%s">%s</a>',
			esc_attr( get_edit_post_link( $payment_id ) ),
			esc_html( get_the_title( $payment_id ) )
		);
	}

	/**
	 * Custom merge tags.
	 *
	 * @param array $merge_tags Array with merge tags.
	 * @return array
	 */
	public static function custom_merge_tags( $merge_tags ) {
		// Payment.
		$merge_tags[] = array(
			'label' => __( 'Payment Status', 'pronamic_ideal' ),
			'tag'   => '{payment_status}',
		);

		$merge_tags[] = array(
			'label' => __( 'Payment Date', 'pronamic_ideal' ),
			'tag'   => '{payment_date}',
		);

		$merge_tags[] = array(
			'label' => __( 'Transaction Id', 'pronamic_ideal' ),
			'tag'   => '{transaction_id}',
		);

		$merge_tags[] = array(
			'label' => __( 'Payment Amount', 'pronamic_ideal' ),
			'tag'   => '{payment_amount}',
		);

		$merge_tags[] = array(
			'label' => __( 'Pronamic Payment ID', 'pronamic_ideal' ),
			'tag'   => '{pronamic_payment_id}',
		);

		$merge_tags[] = array(
			'label' => __( 'Pronamic Pay Again URL', 'pronamic_ideal' ),
			'tag'   => '{pronamic_pay_again_url}',
		);

		// Bank transfer.
		$merge_tags[] = array(
			'label' => __( 'Pronamic bank transfer recipient reference', 'pronamic_ideal' ),
			'tag'   => '{pronamic_payment_bank_transfer_recipient_reference}',
		);

		$merge_tags[] = array(
			'label' => __( 'Pronamic bank transfer recipient bank name', 'pronamic_ideal' ),
			'tag'   => '{pronamic_payment_bank_transfer_recipient_bank_name}',
		);

		$merge_tags[] = array(
			'label' => __( 'Pronamic bank transfer recipient name', 'pronamic_ideal' ),
			'tag'   => '{pronamic_payment_bank_transfer_recipient_name}',
		);

		$merge_tags[] = array(
			'label' => __( 'Pronamic bank transfer recipient IBAN', 'pronamic_ideal' ),
			'tag'   => '{pronamic_payment_bank_transfer_recipient_iban}',
		);

		$merge_tags[] = array(
			'label' => __( 'Pronamic bank transfer recipient BIC', 'pronamic_ideal' ),
			'tag'   => '{pronamic_payment_bank_transfer_recipient_bic}',
		);

		$merge_tags[] = array(
			'label' => __( 'Pronamic bank transfer recipient city', 'pronamic_ideal' ),
			'tag'   => '{pronamic_payment_bank_transfer_recipient_city}',
		);

		$merge_tags[] = array(
			'label' => __( 'Pronamic bank transfer recipient country', 'pronamic_ideal' ),
			'tag'   => '{pronamic_payment_bank_transfer_recipient_country}',
		);

		$merge_tags[] = array(
			'label' => __( 'Pronamic bank transfer recipient account number', 'pronamic_ideal' ),
			'tag'   => '{pronamic_payment_bank_transfer_recipient_account_number}',
		);

		// Subscription.
		$merge_tags[] = array(
			'label' => __( 'Pronamic Subscription Payment ID', 'pronamic_ideal' ),
			'tag'   => '{pronamic_subscription_payment_id}',
		);

		$merge_tags[] = array(
			'label' => __( 'Pronamic Subscription Amount', 'pronamic_ideal' ),
			'tag'   => '{pronamic_subscription_amount}',
		);

		$merge_tags[] = array(
			'label' => __( 'Pronamic Subscription Cancel URL', 'pronamic_ideal' ),
			'tag'   => '{pronamic_subscription_cancel_url}',
		);

		$merge_tags[] = array(
			'label' => __( 'Pronamic Subscription Renew URL', 'pronamic_ideal' ),
			'tag'   => '{pronamic_subscription_renew_url}',
		);

		$merge_tags[] = array(
			'label' => __( 'Pronamic Subscription Renewal Date', 'pronamic_ideal' ),
			'tag'   => '{pronamic_subscription_renewal_date}',
		);

		return $merge_tags;
	}

	/**
	 * Maybe redirect to Gravity Forms entry
	 */
	public static function maybe_redirect_to_entry() {
		if ( ! filter_has_var( INPUT_GET, 'pronamic_gf_lid' ) ) {
			return;
		}

		$lead_id = filter_input( INPUT_GET, 'pronamic_gf_lid', FILTER_SANITIZE_STRING );

		$lead = RGFormsModel::get_lead( $lead_id );

		if ( ! empty( $lead ) ) {
			$url = add_query_arg(
				array(
					'page' => 'gf_entries',
					'view' => 'entry',
					'id'   => $lead['form_id'],
					'lid'  => $lead_id,
				),
				admin_url( 'admin.php' )
			);

			wp_safe_redirect( $url );

			exit;
		}
	}

	/**
	 * Handle AJAX request get form data
	 */
	public static function ajax_get_form_data() {
		$form_id = filter_input( INPUT_GET, 'formId', FILTER_SANITIZE_STRING );

		$data = RGFormsModel::get_form_meta( $form_id );

		wp_send_json_success( $data );
	}

	/**
	 * Get new feed URL.
	 *
	 * @since 1.6.3
	 *
	 * @param string $form_id Gravity Forms form ID.
	 * @return string
	 */
	public static function get_new_feed_url( $form_id ) {
		return add_query_arg(
			array(
				'page'    => 'gf_edit_forms',
				'view'    => 'settings',
				'subview' => 'pronamic_pay',
				'id'      => $form_id,
				'fid'     => 0,
			),
			admin_url( 'admin.php' )
		);
	}
}
