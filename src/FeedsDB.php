<?php
/**
 * Feeds database
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

use WP_Query;

/**
 * Title: WordPress pay extension Gravity Forms admin
 * Description:
 * Copyright: 2005-2024 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.3.1
 * @since   1.0.0
 */
class FeedsDB {
	/**
	 * Feeds.
	 *
	 * @var array<int, PayFeed>
	 */
	private static $feeds = [];

	/**
	 * Get feed.
	 *
	 * @param int $post_id Post ID.
	 * @return PayFeed
	 */
	public static function get_feed( $post_id ) {
		if ( ! array_key_exists( $post_id, self::$feeds ) ) {
			self::$feeds[ $post_id ] = new PayFeed( $post_id );
		}

		return self::$feeds[ $post_id ];
	}

	/**
	 * Delete feed.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function delete_feed( $post_id ) {
		\wp_delete_post( $post_id );

		if ( \array_key_exists( $post_id, self::$feeds ) ) {
			unset( self::$feeds[ $post_id ] );
		}
	}

	/**
	 * Get feeds by form ID.
	 *
	 * In earlier version of this library this was the function `get_pronamic_gf_pay_feeds_by_form_id`.
	 *
	 * @param string $form_id Gravity Forms form ID.
	 * @param array  $meta    Meta query array.
	 * @return PayFeed[]
	 */
	public static function get_feeds_by_form_id( $form_id, $meta = [] ) {
		$meta_query = [
			[
				'key'   => '_pronamic_pay_gf_form_id',
				'value' => $form_id,
			],
		];

		$meta_query = array_merge( $meta_query, $meta );

		$query = new WP_Query(
			[
				'fields'         => 'ids',
				'post_type'      => 'pronamic_pay_gf',
				'posts_per_page' => 50,
				'meta_query'     => $meta_query,
			]
		);

		$feeds = [];

		foreach ( $query->posts as $post_id ) {
			$feeds[] = self::get_feed( $post_id );
		}

		return $feeds;
	}

	/**
	 * Get conditioned feed by form ID.
	 *
	 * In earlier version of this library this was the function `get_pronamic_gf_pay_conditioned_feed_by_form_id`.
	 *
	 * @param int $form_id Gravity Forms form ID.
	 * @return array
	 */
	public static function get_active_feeds_by_form_id( $form_id ) {
		$meta = [
			[
				'relation' => 'OR',
				[
					'key'   => '_pronamic_pay_gf_feed_active',
					'value' => 1,
				],
				[
					'key'     => '_pronamic_pay_gf_feed_active',
					'compare' => 'NOT EXISTS',
				],
			],
		];

		$feeds = self::get_feeds_by_form_id( $form_id, $meta );

		return $feeds;
	}

	/**
	 * Get feed by entry ID.
	 *
	 * In earlier version of this library this was the function `get_pronamic_gf_pay_feed_by_entry_id`.
	 *
	 * @param string $entry_id Gravity Forms entry ID.
	 * @return null|PayFeed
	 */
	public static function get_feed_by_entry_id( $entry_id ) {
		$feed_id = gform_get_meta( $entry_id, 'ideal_feed_id' );

		if ( ! empty( $feed_id ) ) {
			return self::get_feed( $feed_id );
		}

		return null;
	}
}
