<?php
/**
 * Admin payment form post type
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2019 Pronamic
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
 * Copyright: 2005-2019 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.1.2
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
	 * Constructs and intialize admin payment form post type.
	 */
	public function __construct() {
		add_filter( 'manage_edit-pronamic_pay_gf_columns', array( $this, 'edit_columns' ) );

		add_action( 'manage_pronamic_pay_gf_posts_custom_column', array( $this, 'custom_columns' ), 10, 2 );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

		if ( GravityForms::version_compare( '1.7', '>=' ) ) {
			add_action( 'gform_after_delete_form', array( $this, 'delete_payment_form' ) );
		}

		add_filter( 'wp_insert_post_data', array( $this, 'insert_post_data' ), 99, 2 );

		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_post' ) );
	}

	/**
	 * Edit columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function edit_columns( $columns ) {
		$columns = array(
			'cb'                                      => '<input type="checkbox" />',
			'title'                                   => __( 'Title', 'pronamic_ideal' ),
			'pronamic_pay_gf_form'                    => __( 'Form', 'pronamic_ideal' ),
			'pronamic_pay_gf_config'                  => __( 'Configuration', 'pronamic_ideal' ),
			'pronamic_pay_gf_transaction_description' => __( 'Transaction Description', 'pronamic_ideal' ),
			'date'                                    => __( 'Date', 'pronamic_ideal' ),
		);

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
								array(
									'page' => 'gf_edit_forms',
									'id'   => $form_id,
								),
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
			array( $this, 'meta_box_config' ),
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

		include dirname( __FILE__ ) . '/../views/html-admin-feed-meta-box.php';
	}

	/**
	 * When the form is deleted from the trash, deletes our custom post.
	 *
	 * @param int $form_id The ID of the form being deleted.
	 */
	public function delete_payment_form( $form_id ) {
		$query = new WP_Query(
			array(
				'post_type'  => 'pronamic_pay_gf',
				'meta_query' => array(
					array(
						'key'   => '_pronamic_pay_gf_form_id',
						'value' => $form_id,
					),
				),
			)
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
		if ( ! filter_has_var( INPUT_POST, 'pronamic_pay_nonce' ) ) {
			return $data;
		}

		$nonce = filter_input( INPUT_POST, 'pronamic_pay_nonce', FILTER_SANITIZE_STRING );

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'pronamic_pay_save_pay_gf' ) ) {
			return $data;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $data;
		}

		// Check the user's permissions.
		if ( ! current_user_can( 'edit_post', $postarr['ID'] ) ) {
			return $data;
		}

		/* OK, its safe for us to save the data now. */
		if ( filter_has_var( INPUT_POST, '_pronamic_pay_gf_post_title' ) ) {
			$post_title = filter_input( INPUT_POST, '_pronamic_pay_gf_post_title', FILTER_SANITIZE_STRING );

			$data['post_title'] = sanitize_text_field( wp_unslash( $post_title ) );
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
		if ( ! filter_has_var( INPUT_POST, 'pronamic_pay_nonce' ) ) {
			return;
		}

		$nonce = filter_input( INPUT_POST, 'pronamic_pay_nonce', FILTER_SANITIZE_STRING );

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'pronamic_pay_save_pay_gf' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check the user's permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		/* OK, its safe for us to save the data now. */
		$definition = array(
			'_pronamic_pay_gf_form_id'                     => 'sanitize_text_field',
			'_pronamic_pay_gf_config_id'                   => 'sanitize_text_field',
			'_pronamic_pay_gf_entry_id_prefix'             => 'sanitize_text_field',
			'_pronamic_pay_gf_order_id'                    => 'sanitize_text_field',
			'_pronamic_pay_gf_transaction_description'     => 'sanitize_text_field',
			'_pronamic_pay_gf_condition_enabled'           => FILTER_VALIDATE_BOOLEAN,
			'_pronamic_pay_gf_condition_field_id'          => 'sanitize_text_field',
			'_pronamic_pay_gf_condition_operator'          => 'sanitize_text_field',
			'_pronamic_pay_gf_condition_value'             => 'sanitize_text_field',
			'_pronamic_pay_gf_delay_admin_notification'    => FILTER_VALIDATE_BOOLEAN,
			'_pronamic_pay_gf_delay_user_notification'     => FILTER_VALIDATE_BOOLEAN,
			'_pronamic_pay_gf_delay_notification_ids'      => array(
				'filter' => FILTER_SANITIZE_STRING,
				'flags'  => FILTER_REQUIRE_ARRAY,
			),
			'_pronamic_pay_gf_delay_post_creation'         => FILTER_VALIDATE_BOOLEAN,
			'_pronamic_pay_gf_fields'                      => array(
				'filter' => FILTER_SANITIZE_STRING,
				'flags'  => FILTER_REQUIRE_ARRAY,
			),
			'_pronamic_pay_gf_links'                       => array(
				'filter' => FILTER_SANITIZE_STRING,
				'flags'  => FILTER_REQUIRE_ARRAY,
			),
			'_pronamic_pay_gf_user_role_field_id'          => 'sanitize_text_field',
			'_pronamic_pay_gf_subscription_amount_type'    => 'sanitize_text_field',
			'_pronamic_pay_gf_subscription_amount_field'   => 'sanitize_text_field',
			'_pronamic_pay_gf_subscription_interval_type'  => 'sanitize_text_field',
			'_pronamic_pay_gf_subscription_interval'       => FILTER_SANITIZE_NUMBER_INT,
			'_pronamic_pay_gf_subscription_interval_period' => 'sanitize_text_field',
			'_pronamic_pay_gf_subscription_interval_date_type' => 'sanitize_text_field',
			'_pronamic_pay_gf_subscription_interval_date'  => 'sanitize_text_field',
			'_pronamic_pay_gf_subscription_interval_date_day' => 'sanitize_text_field',
			'_pronamic_pay_gf_subscription_interval_date_month' => 'sanitize_text_field',
			'_pronamic_pay_gf_subscription_interval_date_prorate' => FILTER_VALIDATE_BOOLEAN,
			'_pronamic_pay_gf_subscription_interval_field' => 'sanitize_text_field',
			'_pronamic_pay_gf_subscription_frequency_type' => 'sanitize_text_field',
			'_pronamic_pay_gf_subscription_frequency'      => FILTER_SANITIZE_NUMBER_INT,
			'_pronamic_pay_gf_subscription_frequency_field' => 'sanitize_text_field',
		);

		$delay_actions = Extension::get_delay_actions();

		$delay_actions = array_filter(
			$delay_actions,
			function( $action ) {
				return $action['active'];
			}
		);

		foreach ( $delay_actions as $action ) {
			$definition[ $action['meta_key'] ] = FILTER_VALIDATE_BOOLEAN;
		}

		foreach ( $definition as $meta_key => $function ) {
			$meta_value = null;

			if ( 'sanitize_text_field' === $function ) {
				if ( isset( $_POST[ $meta_key ] ) ) { // WPCS: input var OK.
					$meta_value = sanitize_text_field( wp_unslash( $_POST[ $meta_key ] ) ); // WPCS: input var OK.
				}
			} else {
				$filter  = $function;
				$options = null;

				if ( is_array( $function ) && isset( $function['filter'] ) ) {
					$filter  = $function['filter'];
					$options = $function;
				}

				$meta_value = filter_input( INPUT_POST, $meta_key, $filter, $options );
			}

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

			if ( isset( $meta_value ) && '' !== $meta_value ) {
				update_post_meta( $post_id, $meta_key, $meta_value );
			} else {
				delete_post_meta( $post_id, $meta_key );
			}
		}

		if ( filter_has_var( INPUT_POST, '_pronamic_pay_gf_condition_field_id' ) ) {
			if ( '' !== filter_input( INPUT_POST, '_pronamic_pay_gf_condition_field_id' ) ) {
				update_post_meta( $post_id, '_pronamic_pay_gf_condition_enabled', true );
			} else {
				delete_post_meta( $post_id, '_pronamic_pay_gf_condition_enabled' );
			}
		}

		$active = get_post_meta( $post_id, '_pronamic_pay_gf_feed_active', true );

		if ( '' === $active ) {
			update_post_meta( $post_id, '_pronamic_pay_gf_feed_active', '1' );
		}
	}
}
