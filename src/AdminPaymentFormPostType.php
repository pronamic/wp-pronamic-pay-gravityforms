<?php
/**
 * Admin payment form post type
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

use GFAPI;
use WP_Post;
use WP_Query;

/**
 * Title: WordPress admin payment form post type
 * Description:
 * Copyright: 2005-2024 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.6.1
 * @since   1.0.0
 */
class AdminPaymentFormPostType {
	/**
	 * Post type.
	 *
	 * @var string
	 */
	const POST_TYPE = 'pronamic_pay_gf';

	/**
	 * Construct and initialize admin payment form post type.
	 */
	public function __construct() {
		add_filter( 'manage_edit-pronamic_pay_gf_columns', [ $this, 'edit_columns' ] );

		add_action( 'manage_pronamic_pay_gf_posts_custom_column', [ $this, 'custom_columns' ], 10, 2 );

		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );

		add_action( 'gform_after_delete_form', [ $this, 'delete_payment_form' ] );

		add_filter( 'wp_insert_post_data', [ $this, 'insert_post_data' ], 99, 2 );

		add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save_post' ] );
	}

	/**
	 * Edit columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function edit_columns( $columns ) {
		$columns = [
			'cb'                                      => '<input type="checkbox" />',
			'title'                                   => __( 'Title', 'pronamic_ideal' ),
			'pronamic_pay_gf_form'                    => __( 'Form', 'pronamic_ideal' ),
			'pronamic_pay_gf_config'                  => __( 'Configuration', 'pronamic_ideal' ),
			'pronamic_pay_gf_transaction_description' => __( 'Transaction Description', 'pronamic_ideal' ),
			'date'                                    => __( 'Date', 'pronamic_ideal' ),
		];

		return $columns;
	}

	/**
	 * Custom columns.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function custom_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'pronamic_pay_gf_form':
				$form_id = get_post_meta( $post_id, '_pronamic_pay_gf_form_id', true );

				if ( ! empty( $form_id ) ) {
					printf(
						'<a href="%s">%s</a>',
						esc_attr(
							add_query_arg(
								[
									'page' => 'gf_edit_forms',
									'id'   => $form_id,
								],
								admin_url( 'admin.php' )
							)
						),
						esc_html( $form_id )
					);
				} else {
					echo '—';
				}

				break;
			case 'pronamic_pay_gf_config':
				$config_id = get_post_meta( $post_id, '_pronamic_pay_gf_config_id', true );

				if ( ! empty( $config_id ) ) {
					echo esc_html( get_the_title( $config_id ) );
				} else {
					echo '—';
				}

				break;
			case 'pronamic_pay_gf_transaction_description':
				echo esc_html( get_post_meta( $post_id, '_pronamic_pay_gf_transaction_description', true ) );

				break;
		}
	}

	/**
	 * Add meta boxes
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'pronamic_pay_gf',
			__( 'Configuration', 'pronamic_ideal' ),
			[ $this, 'meta_box_config' ],
			'pronamic_pay_gf',
			'normal',
			'high'
		);
	}

	/**
	 * Pronamic Pay gateway config meta box
	 *
	 * @param WP_Post $post The object for the current post/page.
	 */
	public function meta_box_config( $post ) {
		$form_id = get_post_meta( $post->ID, '_pronamic_pay_gf_form_id', true );
		$post_id = $post->ID;

		include __DIR__ . '/../views/html-admin-feed-meta-box.php';
	}

	/**
	 * When the form is deleted from the trash, deletes our custom post.
	 *
	 * @param int $form_id The ID of the form being deleted.
	 */
	public function delete_payment_form( $form_id ) {
		$query = new WP_Query(
			[
				'post_type'  => 'pronamic_pay_gf',
				'meta_query' => [
					[
						'key'   => '_pronamic_pay_gf_form_id',
						'value' => $form_id,
					],
				],
			]
		);

		foreach ( $query->posts as $post ) {
			wp_delete_post( $post->ID, true );
		}
	}

	/**
	 * When a new payment feed is created, filter the post data.
	 *
	 * @param array $data    Post data.
	 * @param array $postarr Post array.
	 *
	 * @return array
	 */
	public function insert_post_data( $data, $postarr ) {
		// Check if pay feed post type.
		if ( 'pronamic_pay_gf' !== $postarr['post_type'] ) {
			return $data;
		}

		// Check if our nonce is set.
		$nonce = \array_key_exists( 'pronamic_pay_nonce', $_POST ) ? \sanitize_text_field( \wp_unslash( $_POST['pronamic_pay_nonce'] ) ) : null;

		if ( null === $nonce ) {
			return $data;
		}

		// Verify that the nonce is valid.
		if ( ! \wp_verify_nonce( $nonce, 'pronamic_pay_save_pay_gf' ) ) {
			return $data;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( \defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $data;
		}

		// Check the user's permissions.
		if ( ! \current_user_can( 'edit_post', $postarr['ID'] ) ) {
			return $data;
		}

		/* OK, its safe for us to save the data now. */
		if ( \array_key_exists( '_pronamic_pay_gf_post_title', $_POST ) ) {
			$data['post_title'] = \sanitize_text_field( \wp_unslash( $_POST['_pronamic_pay_gf_post_title'] ) );
		}

		return $data;
	}

	/**
	 * When the post is saved, saves our custom data.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function save_post( $post_id ) {
		// Check if our nonce is set.
		$nonce = \array_key_exists( 'pronamic_pay_nonce', $_POST ) ? \sanitize_text_field( \wp_unslash( $_POST['pronamic_pay_nonce'] ) ) : null;

		if ( null === $nonce ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! \wp_verify_nonce( $nonce, 'pronamic_pay_save_pay_gf' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( \defined( '\DOING_AUTOSAVE' ) && \DOING_AUTOSAVE ) {
			return;
		}

		// Check the user's permissions.
		if ( ! \current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		/* OK, its safe for us to save the data now. */
		$definition = [
			'_pronamic_pay_gf_form_id'                     => [
				'type' => 'string',
			],
			'_pronamic_pay_gf_config_id'                   => [
				'type' => 'string',
			],
			'_pronamic_pay_gf_entry_id_prefix'             => [
				'type' => 'string',
			],
			'_pronamic_pay_gf_order_id'                    => [
				'type' => 'string',
			],
			'_pronamic_pay_gf_transaction_description'     => [
				'type' => 'string',
			],
			'_pronamic_pay_gf_delay_admin_notification'    => [
				'type' => 'boolean',
			],
			'_pronamic_pay_gf_delay_user_notification'     => [
				'type' => 'boolean',
			],
			'_pronamic_pay_gf_delay_notification_ids'      => [
				'type'        => 'array',
				'uniqueItems' => true,
				'items'       => [
					'type' => 'string',
				],
			],
			'_pronamic_pay_gf_delay_post_creation'         => [
				'type' => 'boolean',
			],
			'_pronamic_pay_gf_fields'                      => [
				'type'  => 'object',
				'items' => [
					'type' => 'string',
				],
			],
			'_pronamic_pay_gf_links'                       => [
				'type'  => 'object',
				'items' => [
					'type'       => 'object',
					'properties' => [
						'type'            => [
							'type' => 'string',
							'enum' => [
								'confirmation',
								'page',
								'url',
							],
						],
						'confirmation_id' => [
							'type' => 'string',
						],
						'page_id'         => [
							'type' => 'string',
						],
						'url'             => [
							'type'   => 'string',
							'format' => 'uri',
						],
					],
				],
			],
			'_pronamic_pay_gf_user_role_field_id'          => [
				'type' => 'string',
			],

			// Subscriptions.
			'_pronamic_pay_gf_subscription_amount_type'    => [
				'type' => 'string',
			],
			'_pronamic_pay_gf_subscription_amount_field'   => [
				'type' => 'string',
			],
			'_pronamic_pay_gf_subscription_interval_type'  => [
				'type' => 'string',
			],
			'_pronamic_pay_gf_subscription_interval'       => [
				'type' => 'integer',
			],
			'_pronamic_pay_gf_subscription_interval_period' => [
				'type' => 'string',
			],
			'_pronamic_pay_gf_subscription_interval_date_type' => [
				'type' => 'string',
			],
			'_pronamic_pay_gf_subscription_interval_date'  => [
				'type' => 'string',
			],
			'_pronamic_pay_gf_subscription_interval_date_day' => [
				'type' => 'string',
			],
			'_pronamic_pay_gf_subscription_interval_date_month' => [
				'type' => 'string',
			],
			'_pronamic_pay_gf_subscription_interval_date_prorate' => [
				'type' => 'boolean',
			],
			'_pronamic_pay_gf_subscription_interval_field' => [
				'type' => 'string',
			],
			'_pronamic_pay_gf_subscription_frequency_type' => [
				'type' => 'string',
			],
			'_pronamic_pay_gf_subscription_number_periods' => [
				'type' => 'integer',
			],
			'_pronamic_pay_gf_subscription_frequency_field' => [
				'type' => 'string',
			],
			'_pronamic_pay_gf_subscription_trial_enabled'  => [
				'type' => 'boolean',
			],
			'_pronamic_pay_gf_subscription_trial_length'   => [
				'type'    => 'integer',
				'minimum' => 1,
			],
			'_pronamic_pay_gf_subscription_trial_length_unit' => [
				'type' => 'string',
				'enum' => [
					'D',
					'W',
					'M',
					'Y',
				],
			],

			// Feed conditions.
			'_gaddon_setting_feed_condition_conditional_logic_object' => [
				'type' => 'string',
			],
			'_gform_setting_feed_condition_conditional_logic_object' => [
				'type' => 'string',
			],
		];

		$delay_actions = Extension::get_delay_actions();

		$delay_actions = array_filter(
			$delay_actions,
			function ( $action ) {
				return $action['active'];
			}
		);

		foreach ( $delay_actions as $action ) {
			$definition[ $action['meta_key'] ] = [
				'type' => 'boolean',
			];
		}

		foreach ( $definition as $meta_key => $schema ) {
			$meta_value = null;

			$meta_value = \rest_sanitize_value_from_schema(
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Input is sanitized through REST schema.
				\array_key_exists( $meta_key, $_POST ) ? $_POST[ $meta_key ] : null,
				$schema,
				$meta_key
			);

			// Set link type if none selected, use URL if both are set.
			if ( '_pronamic_pay_gf_links' === $meta_key ) {
				foreach ( $meta_value as $status => $link ) {
					if ( isset( $link['type'] ) && PayFeed::LINK_TYPE_CONFIRMATION === $link['type'] ) {
						$form_id = get_post_meta( $post_id, '_pronamic_pay_gf_form_id', true );

						if ( '' !== $form_id ) {
							$form = GFAPI::get_form( $form_id );

							if ( ! isset( $form['confirmations'][ $link['confirmation_id'] ] ) ) {
								$link['type']            = null;
								$link['confirmation_id'] = null;
							}
						}
					}

					if ( ! isset( $link['type'] ) ) {
						if ( ! empty( $link['url'] ) ) {
							$link['type'] = PayFeed::LINK_TYPE_URL;
						} elseif ( ! empty( $link['page_id'] ) ) {
							$link['type'] = PayFeed::LINK_TYPE_PAGE;
						} elseif ( ! empty( $link['confirmation_id'] ) ) {
							$link['type'] = PayFeed::LINK_TYPE_CONFIRMATION;
						}

						$meta_value[ $status ] = $link;
					}
				}
			}

			if ( '_pronamic_pay_gf_subscription_interval_date' === $meta_key ) {
				$period = array_key_exists( '_pronamic_pay_gf_subscription_interval_period', $_POST ) ? \sanitize_text_field( \wp_unslash( $_POST['_pronamic_pay_gf_subscription_interval_period'] ) ) : null;

				switch ( $period ) {
					case 'M':
						$meta_value = array_key_exists( '_pronamic_pay_gf_subscription_interval_m_date', $_POST ) ? \sanitize_text_field( \wp_unslash( $_POST['_pronamic_pay_gf_subscription_interval_m_date'] ) ) : '';
						break;
					case 'Y':
						$meta_value = array_key_exists( '_pronamic_pay_gf_subscription_interval_y_date', $_POST ) ? \sanitize_text_field( \wp_unslash( $_POST['_pronamic_pay_gf_subscription_interval_y_date'] ) ) : '';
						break;
				}
			}

			if ( isset( $meta_value ) && '' !== $meta_value ) {
				update_post_meta( $post_id, $meta_key, $meta_value );
			} else {
				delete_post_meta( $post_id, $meta_key );
			}

			// Remove deprecated subscription frequency in favor of `subscription_number_periods`.
			if ( '_pronamic_pay_gf_subscription_number_periods' === $meta_key ) {
				\delete_post_meta( $post_id, '_pronamic_pay_gf_subscription_frequency' );
			}

			if ( \in_array( $meta_key, [ '_gform_setting_feed_condition_conditional_logic_object', '_gaddon_setting_feed_condition_conditional_logic_object' ], true ) ) {
				\delete_post_meta( $post_id, '_pronamic_pay_gf_condition_field_id' );
				\delete_post_meta( $post_id, '_pronamic_pay_gf_condition_operator' );
				\delete_post_meta( $post_id, '_pronamic_pay_gf_condition_value' );
			}
		}

		// Enable conditional logic.
		if ( \filter_has_var( \INPUT_POST, '_gform_setting_feed_condition_conditional_logic' ) ) {
			if ( false !== \filter_input( \INPUT_POST, '_gform_setting_feed_condition_conditional_logic', \FILTER_VALIDATE_BOOLEAN ) ) {
				\update_post_meta( $post_id, '_pronamic_pay_gf_condition_enabled', true );
			} else {
				\delete_post_meta( $post_id, '_pronamic_pay_gf_condition_enabled' );
			}
		}

		if ( \filter_has_var( \INPUT_POST, '_gaddon_setting_feed_condition_conditional_logic' ) ) {
			if ( false !== \filter_input( \INPUT_POST, '_gaddon_setting_feed_condition_conditional_logic', \FILTER_VALIDATE_BOOLEAN ) ) {
				\update_post_meta( $post_id, '_pronamic_pay_gf_condition_enabled', true );
			} else {
				\delete_post_meta( $post_id, '_pronamic_pay_gf_condition_enabled' );
			}
		}

		$active = get_post_meta( $post_id, '_pronamic_pay_gf_feed_active', true );

		if ( '' === $active ) {
			update_post_meta( $post_id, '_pronamic_pay_gf_feed_active', '1' );
		}
	}
}
