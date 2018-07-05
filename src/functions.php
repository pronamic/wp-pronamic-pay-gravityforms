<?php

use Pronamic\WordPress\Pay\Extensions\GravityForms\PayFeed;
use Pronamic\WordPress\Pay\Extensions\GravityForms\Util;

function get_pronamic_gf_pay_feeds_by_form_id( $form_id, $meta = array() ) {
	$feeds = array();

	$meta_query = array(
		array(
			'key'   => '_pronamic_pay_gf_form_id',
			'value' => $form_id,
		),
	);

	$meta_query = array_merge( $meta_query, $meta );

	$query = new WP_Query( array(
		'fields'         => 'ids',
		'post_type'      => 'pronamic_pay_gf',
		'posts_per_page' => 50,
		'meta_query'     => $meta_query,
	) );

	foreach ( $query->posts as $post_id ) {
		$feeds[] = new PayFeed( $post_id );
	}

	return $feeds;
}

function get_pronamic_gf_pay_conditioned_feed_by_form_id( $form_id ) {
	$meta = array(
		array(
			'relation' => 'OR',
			array(
				'key'   => '_pronamic_pay_gf_feed_active',
				'value' => 1,
			),
			array(
				'key'     => '_pronamic_pay_gf_feed_active',
				'compare' => 'NOT EXISTS',
			),
		),
	);

	$feeds = get_pronamic_gf_pay_feeds_by_form_id( $form_id, $meta );

	if ( ! empty( $feeds ) ) {
		$form = RGFormsModel::get_form_meta( $form_id );

		foreach ( $feeds as $feed ) {
			if ( Util::is_condition_true( $form, $feed ) ) {
				return $feed;
			}
		}
	}

	return null;
}

function get_pronamic_gf_pay_feed_by_entry_id( $entry_id ) {
	$feed_id = gform_get_meta( $entry_id, 'ideal_feed_id' );

	if ( ! empty( $feed_id ) ) {
		return new PayFeed( $feed_id );
	}

	return null;
}
