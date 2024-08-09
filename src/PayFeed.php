<?php
/**
 * Pay feed
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

use WP_Post;

/**
 * Title: WordPress pay extension Gravity Forms pay feed
 * Description:
 * Copyright: 2005-2024 Pronamic
 * Company: Pronamic
 *
 * @author   Remco Tolsma
 * @version  2.6.1
 * @since    1.4.4
 *
 * @todo     This class has too many settings properties, should we move this to a settings/options array?
 * @property mixed $form_id
 * @property mixed $config_id
 * @property mixed $entry_id_prefix
 * @property mixed $order_id
 * @property mixed $transaction_description
 * @property mixed $delay_actions
 * @property mixed $user_role_field_id
 * @property mixed $subscription_amount_type
 * @property mixed $subscription_amount_field
 * @property mixed $subscription_interval_type
 * @property mixed $subscription_interval
 * @property mixed $subscription_interval_period
 * @property mixed $subscription_interval_date_type
 * @property mixed $subscription_interval_date
 * @property mixed $subscription_interval_date_day
 * @property mixed $subscription_interval_date_month
 * @property mixed $subscription_interval_date_prorate
 * @property mixed $subscription_interval_field
 * @property mixed $subscription_frequency_type
 * @property mixed $subscription_number_periods
 * @property mixed $subscription_frequency_field
 * @property mixed $fields
 * @property mixed $links
 */
#[\AllowDynamicProperties]
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
	 * Conditional logic object.
	 *
	 * @var null|array
	 */
	public $conditional_logic_object;

	/**
	 * Delay notification ID's contains an array of notification ID's which
	 * should be delayed till the payment is successful.
	 *
	 * @since Gravity Forms 1.7
	 * @var array
	 */
	public $delay_notification_ids;

	/**
	 * Flag to delay the creation of an post till the the payment is successful.
	 *
	 * @var boolean
	 */
	public $delay_post_creation;

	/**
	 * Flag to delay the creation of an post till the the payment is successful.
	 *
	 * @deprecated Gravity Forms 1.7
	 * @var boolean
	 */
	public $delay_admin_notification;

	/**
	 * Flag to delay the creation of an post till the the payment is successful.
	 *
	 * @deprecated Gravity Forms 1.7
	 * @var boolean
	 */
	public $delay_user_notification;

	/**
	 * Subscription frequency.
	 *
	 * @deprecated 2.5.0
	 * @var string|false
	 */
	public $subscription_frequency;

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

		// Conditional logic.
		$conditional_logic_object = get_post_meta( $post_id, '_gform_setting_feed_condition_conditional_logic_object', true );

		// Check legacy meta key `_gaddon_setting_feed_condition_conditional_logic_object`.
		if ( empty( $conditional_logic_object ) ) {
			$conditional_logic_object = get_post_meta( $post_id, '_gaddon_setting_feed_condition_conditional_logic_object', true );
		}

		// JSON decode conditional logic object.
		if ( ! empty( $conditional_logic_object ) ) {
			$conditional_logic_object = \html_entity_decode( $conditional_logic_object, \ENT_COMPAT );

			$conditional_logic_object = \json_decode( $conditional_logic_object, true );

			if ( ! empty( $conditional_logic_object ) ) {
				$this->conditional_logic_object = $conditional_logic_object;
			}

			// Unset conditional logic object without any logic.
			if ( \is_array( $this->conditional_logic_object ) && \array_key_exists( 'conditionalLogic', $this->conditional_logic_object ) && empty( $this->conditional_logic_object['conditionalLogic'] ) ) {
				$this->conditional_logic_object = null;
			}

			// The `_gform_setting_...` does not include the `conditionalLogic` key, as was the case previously with the `_gaddon_setting`.
			if ( GravityForms::version_compare( '2.5', '>=' ) && \is_array( $this->conditional_logic_object ) && ! \array_key_exists( 'conditionalLogic', $this->conditional_logic_object ) ) {
				$this->conditional_logic_object = [
					'conditionalLogic' => $this->conditional_logic_object,
				];
			}
		}

		/*
		 * Legacy condition for backwards compatibility.
		 *
		 * @since 2.3.0
		 */
		if ( null === $this->conditional_logic_object ) {
			$condition_field_id = get_post_meta( $post_id, '_pronamic_pay_gf_condition_field_id', true );
			$condition_operator = get_post_meta( $post_id, '_pronamic_pay_gf_condition_operator', true );
			$condition_value    = get_post_meta( $post_id, '_pronamic_pay_gf_condition_value', true );

			$rule = [
				'fieldId'  => 0,
				'operator' => 'is',
				'value'    => '',
			];

			if ( ! empty( $condition_field_id ) && ! empty( $condition_operator ) && ! empty( $condition_value ) ) {
				$rule = [
					'fieldId'  => $condition_field_id,
					'operator' => ( GravityForms::OPERATOR_IS === $condition_operator ? 'is' : 'isnot' ),
					'value'    => $condition_value,
				];
			}

			$this->conditional_logic_object = [
				'conditionalLogic' => [
					'actionType' => 'show',
					'logicType'  => 'all',
					'rules'      => [ $rule ],
				],
			];
		}

		// Delay actions.
		$this->delay_admin_notification = get_post_meta( $post_id, '_pronamic_pay_gf_delay_admin_notification', true );
		$this->delay_user_notification  = get_post_meta( $post_id, '_pronamic_pay_gf_delay_user_notification', true );
		$this->delay_post_creation      = get_post_meta( $post_id, '_pronamic_pay_gf_delay_post_creation', true );

		$this->delay_actions = [];

		$delay_actions = Extension::get_delay_actions();

		$delay_actions = array_filter(
			$delay_actions,
			function ( $action ) {
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
		$this->subscription_number_periods        = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_number_periods', true );
		$this->subscription_frequency_field       = get_post_meta( $post_id, '_pronamic_pay_gf_subscription_frequency_field', true );

		/*
		 * Set subscription number periods from deprecated frequency setting
		 * for backwards compatibility. Add one, because the frequency excluded
		 * the first payment and total number of periods includes the first payment.
		 */
		if ( empty( $this->subscription_number_periods ) && ! empty( $this->subscription_frequency ) ) {
			$this->subscription_number_periods = intval( $this->subscription_frequency ) + 1;
		}

		if ( empty( $this->subscription_interval_date_type ) ) {
			$this->subscription_interval_date_type = 'payment_date';
		}

		/**
		 * In version 2.5 the 'last' monthday option was removed.
		 *
		 * @link https://github.com/wp-pay-extensions/gravityforms/blob/2.4.0/views/html-admin-feed-settings.php#L753
		 * @link https://github.com/wp-pay/core/issues/17
		 */
		if ( 'last' === $this->subscription_interval_date ) {
			$this->subscription_interval_date = 28;
		}

		// Delay notification IDs.
		$ids                          = get_post_meta( $post_id, '_pronamic_pay_gf_delay_notification_ids', true );
		$this->delay_notification_ids = is_array( $ids ) ? $ids : [];

		// Fields.
		$fields       = get_post_meta( $post_id, '_pronamic_pay_gf_fields', true );
		$this->fields = is_array( $fields ) ? $fields : [];

		// Links.
		$links       = get_post_meta( $post_id, '_pronamic_pay_gf_links', true );
		$this->links = is_array( $links ) ? $links : [];
	}

	/**
	 * Get subscription trial.
	 *
	 * @return object
	 */
	public function get_subscription_trial(): object {
		$meta_key_prefix = '_pronamic_pay_gf_subscription_trial_';

		return (object) [
			'enabled'     => '1' === \get_post_meta( $this->id, $meta_key_prefix . 'enabled', true ),
			'length'      => \max( 1, (int) \get_post_meta( $this->id, $meta_key_prefix . 'length', true ) ),
			'length_unit' => \get_post_meta( $this->id, $meta_key_prefix . 'length_unit', true ),
		];
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
