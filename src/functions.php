<?php

function get_pronamic_gf_pay_feeds_by_form_id( $form_id, $single = false ) {
	global $wpdb;

	$pay_gf = array();

	$db_query = $wpdb->prepare( "
		SELECT
			ID
		FROM
			$wpdb->posts
				LEFT JOIN
			$wpdb->postmeta
					ON (
				ID = post_ID
					AND
				meta_key = '_pronamic_pay_gf_form_id'
			)
		WHERE
			post_status = 'publish'
				AND
			post_type = 'pronamic_pay_gf'
				AND
			meta_value = %s
		;
	", $form_id );

	$post_ids = $wpdb->get_col( $db_query ); // WPCS: unprepared SQL OK

	if ( ! empty( $post_ids ) ) {
		foreach ( $post_ids as $post_id ) {
			$pay_gf[] = new Pronamic_WP_Pay_Extensions_GravityForms_PayFeed( $post_id );

			if ( $single ) {
				return $pay_gf[0];
			}
		}
	}

	if ( $single ) {
		return null;
	}

	return $pay_gf;
}

function get_pronamic_gf_pay_feed_by_form_id( $form_id ) {
	global $wpdb;

	return get_pronamic_gf_pay_feeds_by_form_id( $form_id, true );
}

function get_pronamic_gf_pay_conditioned_feed_by_form_id( $form_id ) {
	$feeds = get_pronamic_gf_pay_feeds_by_form_id( $form_id );

	if ( ! empty( $feeds ) ) {
		$form = RGFormsModel::get_form_meta( $form_id );

		foreach ( $feeds as $feed ) {
			if ( WP_ADMIN || ! filter_has_var( INPUT_POST, 'is_submit_'.$form_id ) ) {
				$gateway = Pronamic_WP_Pay_Plugin::get_gateway( $feed->config_id );

				if ( $gateway && ! is_null( $gateway->get_issuers() ) ) {
					return $feed;
				}
			}

			if ( Pronamic_WP_Pay_Extensions_GravityForms_Util::is_condition_true( $form, $feed ) ) {
				return $feed;
			}
		}
	}

	return null;
}

function get_pronamic_gf_pay_feed_by_entry_id( $entry_id ) {
	$feed_id = gform_get_meta( $entry_id, 'ideal_feed_id' );

	if ( ! empty( $feed_id ) ) {
		return new Pronamic_WP_Pay_Extensions_GravityForms_PayFeed( $feed_id );
	}

	return null;
}

function get_pronamic_pay_gf_form_title( $form_id ) {
	$title = null;

	global $pronamic_pay_gf_form_titles;

	if ( ! isset( $pronamic_pay_gf_form_titles ) ) {
		global $wpdb;

		$form_table_name = RGFormsModel::get_form_table_name();

		$query = "SELECT id, title FROM $form_table_name WHERE is_active;";

		$pronamic_pay_gf_form_titles = $wpdb->get_results( $query, OBJECT_K ); // WPCS: unprepared SQL OK
	}

	if ( isset( $pronamic_pay_gf_form_titles[ $form_id ] ) ) {
		$title = $pronamic_pay_gf_form_titles[ $form_id ]->title;
	}

	return $title;
}
