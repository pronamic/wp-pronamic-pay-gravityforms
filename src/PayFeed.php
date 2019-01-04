<?php
/**
 * Pay feed
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2019 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

use WP_Post;

/**
 * Title: WordPress pay extension Gravity Forms pay feed
 * Description:
 * Copyright: 2005-2019 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.1.2
 * @since   1.4.4
 */
class PayFeed {
	/**
	 * Indicator for an link to an WordPress page
	 *
	 * @var string
	 */
	const LINK_TYPE_PAGE = 'page';

	/**
	 * Indicator for an link to an URL
	 *
	 * @var string
	 */
	const LINK_TYPE_URL = 'url';

	/**
	 * Indicator for an link to the Gravity Forms confirmation.
	 *
	 * @var string
	 * @since 1.4.4
	 */
	const LINK_TYPE_CONFIRMATION = 'confirmation';

	/**
	 * The payment (post) ID.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * The payment post object.
	 *
	 * @var WP_Post
	 */
	public $post;

	/**
	 * Condition enabled flag.
	 *
	 * @var bool
	 */
	public $condition_enabled;

	/**
	 * Delay notification ID's contains an array of notification ID's wich
	 * should be delayed till the payment is succesfull.
	 *
	 * @since Gravity Forms 1.7
	 * @var array
	 */
	public $delay_notification_ids;

	/**
	 * Flag to delay the creation of an post till the the payment is succesfull.
	 *
	 * @var boolean
	 */
	public $delay_post_creation;

	/**
	 * Flag to delay the creation of an post till the the payment is succesfull.
	 *
	 * @deprecated Gravity Forms 1.7
	 * @var boolean
	 */
	public $delay_admin_notification;

	/**
	 * Flag to delay the creation of an post till the the payment is succesfull.
	 *
	 * @deprecated Gravity Forms 1.7
	 * @var boolean
	 */
	public $delay_user_notification;

	/**
	 * Construct and initialize payment object.
	 *
	 * @param int $post_id Post ID.
	 */
	public function __construct( $post_id ) {
		$this->id   = $post_id;
		$this->post = get_post( $post_id );

		// Load.
		$this->form_id                 = get_post_meta( $post_id, '_pronamic_pay_gf_form_id', true );
		$this->config_id               = get_post_meta( $post_id, '_pronamic_pay_gf_config_id', true );
		$this->entry_id_prefix         = get_post_meta( $post_id, '_pronamic_pay_gf_entry_id_prefix', true );
		$this->order_id                = get_post_meta( $post_id, '_pronamic_pay_gf_order_id', true );
		$this->transaction_description = get_post_meta( $post_id, '_pronamic_pay_gf_transaction_description', true );
		$this->condition_enabled       = get_post_meta( $post_id, '_pronamic_pay_gf_condition_enabled', true );
		$this->condition_field_id      = get_post_meta( $post_id, '_pronamic_pay_gf_condition_field_id', true );
		$this->condition_operator      = get_post_meta( $post_id, '_pronamic_pay_gf_condition_operator', true );
		$this->condition_value         = get_post_meta( $post_id, '_pronamic_pay_gf_condition_value', true );

		// Delay actions.
		$this->delay_admin_notification = get_post_meta( $post_id, '_pronamic_pay_gf_delay_admin_notification', true );
		$this->delay_user_notification  = get_post_meta( $post_id, '_pronamic_pay_gf_delay_user_notification', true );
		$this->delay_post_creation      = get_post_meta( $post_id, '_pronamic_pay_gf_delay_post_creation', true );

		$this->delay_actions = array();

		$delay_actions = Extension::get_delay_actions();

		$delay_actions = array_filter(
			$delay_actions,
			function( $action ) {
				return $action['active'];
			}
		);

		foreach ( $delay_actions as $slug => $data ) {
			if ( '1' === get_post_meta( $post_id, $data['meta_key'], true ) ) {
				$this->delay_actions[ $slug ] = $data;
			}
		}

		// Other.
		$this->user_role_field_id = get_post_meta( $post_id, '_pronamic_pay_gf_user_role_field_id', true );

		// Subscription.
		$this->subscription_amount_type           = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_amount_type', true );
		$this->subscription_amount_field          = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_amount_field', true );
		$this->subscription_interval_type         = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_interval_type', true );
		$this->subscription_interval              = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_interval', true );
		$this->subscription_interval_period       = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_interval_period', true );
		$this->subscription_interval_date_type    = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_interval_date_type', true );
		$this->subscription_interval_date         = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_interval_date', true );
		$this->subscription_interval_date_day     = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_interval_date_day', true );
		$this->subscription_interval_date_month   = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_interval_date_month', true );
		$this->subscription_interval_date_prorate = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_interval_date_prorate', true );
		$this->subscription_interval_field        = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_interval_field', true );
		$this->subscription_frequency_type        = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_frequency_type', true );
		$this->subscription_frequency             = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_frequency', true );
		$this->subscription_frequency_field       = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_frequency_field', true );

		// Delay notification IDs.
		$ids                          = get_post_meta( $post_id, '_pronamic_pay_gf_delay_notification_ids', true );
		$this->delay_notification_ids = is_array( $ids ) ? $ids : array();

		// Fields.
		$fields       = get_post_meta( $post_id, '_pronamic_pay_gf_fields', true );
		$this->fields = is_array( $fields ) ? $fields : array();

		// Links.
		$links       = get_post_meta( $post_id, '_pronamic_pay_gf_links', true );
		$this->links = is_array( $links ) ? $links : array();
	}

	/**
	 * Get the URL of the specified name
	 *
	 * @param string $name Name.
	 *
	 * @return false|null|string
	 */
	public function get_url( $name ) {
		$url = null;

		if ( isset( $this->links[ $name ] ) ) {
			$link = $this->links[ $name ];

			// link is a standard class object, the type variable could not be defined.
			if ( isset( $link['type'] ) ) {
				switch ( $link['type'] ) {
					case self::LINK_TYPE_PAGE:
						$url = get_permalink( $link['page_id'] );

						break;
					case self::LINK_TYPE_URL:
						$url = $link['url'];

						break;
				}
			}
		}

		return $url;
	}

	/**
	 * Returns a boolean if this feed has some delayed notifications
	 *
	 * @return boolean
	 */
	public function has_delayed_notifications() {
		return ( ! empty( $this->delay_notification_ids ) );
	}
}
