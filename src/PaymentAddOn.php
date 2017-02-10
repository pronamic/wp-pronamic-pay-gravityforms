<?php

/**
 * Title: WordPress pay extension Gravity Forms payment add-on
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.6.2
 * @since 1.1.0
 */
class Pronamic_WP_Pay_Extensions_GravityForms_PaymentAddOn extends GFPaymentAddOn {
	const SLUG = 'pronamic_pay';

	/**
	 * Construct and initialize an Gravity Forms payment add-on
	 *
	 * @see https://github.com/wp-premium/gravityforms/blob/1.9.10.15/includes/addon/class-gf-payment-addon.php
	 *
	 * @since 1.3.0
	 */
	public function __construct() {
		parent::__construct();

		/*
		 * Slug
		 *
		 * @var string URL-friendly identifier used for form settings, add-on settings, text domain localization...
		 * @see https://github.com/wp-premium/gravityforms/blob/1.9.10.15/includes/addon/class-gf-addon.php#L24-L27
		 */
		$this->_slug = self::SLUG;

		/*
	 	 * Title
	 	 *
	 	 * @var string Title of the plugin to be used on the settings page, form settings and plugins page. Example: 'Gravity Forms MailChimp Add-On'
		 * @see https://github.com/wp-premium/gravityforms/blob/1.9.10.15/includes/addon/class-gf-addon.php#L40-L43
		 */
		$this->_title = __( 'WordPress Pay Add-On', 'pronamic_ideal' );

		/*
		 * Short title
		 *
		 * @var string Short version of the plugin title to be used on menus and other places where a less verbose string is useful. Example: 'MailChimp'
		 * @see https://github.com/wp-premium/gravityforms/blob/1.9.10.15/includes/addon/class-gf-addon.php#L44-L47
		 */
		$this->_short_title = __( 'Pay', 'pronamic_ideal' );

		/*
		 * Actions
		 */
		add_action( 'admin_init', array( $this, 'pronamic_maybe_save_feed' ), 20 );
	}

	/**
	 * Maybe save feed.
	 */
	public function pronamic_maybe_save_feed() {
		if ( ! filter_has_var( INPUT_POST, 'pronamic_pay_nonce' ) ) {
			return;
		}

		if ( ! filter_has_var( INPUT_GET, 'id' ) ) {
			return;
		}

		if ( ! filter_has_var( INPUT_GET, 'fid' ) ) {
			return;
		}

		$form_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_STRING );
		$post_id = filter_input( INPUT_GET, 'fid', FILTER_SANITIZE_STRING );

		if ( empty( $form_id ) ) {
			return;
		}

		check_admin_referer( 'pronamic_pay_save_pay_gf', 'pronamic_pay_nonce' );

		$post_title = filter_input( INPUT_POST, '_pronamic_pay_gf_post_title', FILTER_SANITIZE_STRING );

		if ( '' === trim( $post_title ) ) {
			$feeds = $this->get_feeds( $form_id );

			$post_title = sprintf(
				'%s #%s',
				__( 'Payment feed', 'pronamic_ideal' ),
				count( $feeds ) + 1
			);
		}

		$post_id = wp_insert_post( array(
			'ID'             => $post_id,
			'post_type'      => 'pronamic_pay_gf',
			'post_title'     => $post_title,
			'post_status'    => 'publish',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		) );

		$url = add_query_arg( array(
			'page'    => 'gf_edit_forms',
			'view'    => 'settings',
			'subview' => 'pronamic_pay',
			'id'      => $form_id,
			'fid'     => $post_id,
			'message' => $post_id ? '1' : '0',
		), 'admin.php' );

		wp_redirect( $url );

		exit;
	}

	/**
	 * Form settings page
	 *
	 * @since 1.3.0
	 */
	public function form_settings( $form ) {
		$form_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_STRING );
		$post_id = filter_input( INPUT_GET, 'fid', FILTER_SANITIZE_STRING );

		if ( filter_has_var( INPUT_GET, 'fid' ) ) {
			require( dirname( __FILE__ ) . '/../views/html-admin-feed-gf-box.php' );
		} else {
			$this->feed_list_page( $form );
		}
	}

	/**
	 * Feed list title.
	 *
	 * @return string
	 */
	public function feed_list_title() {
		$title = sprintf(
			'<i class="dashicons dashicons-money fa-"></i> %s',
			esc_html__( 'Pay Feeds', 'pronamic_ideal' )
		);

		if ( ! $this->can_create_feed() ) {
			return $title;
		}

		$url = add_query_arg( array( 'fid' => '0' ) );

		$title .= sprintf(
			'<a class="add-new-h2" href="%s">%s</a>',
			esc_url( $url ),
			esc_html__( 'Add New', 'pronamic_ideal' )
		);

		return $title;
	}

	public function get_feed_table( $form ) {
		$feeds                 = $this->get_feeds( rgar( $form, 'id' ) );
		$columns               = $this->feed_list_columns();
		$bulk_actions          = $this->get_bulk_actions();
		$action_links          = $this->get_action_links();
		$column_value_callback = array( $this, 'get_column_value' );
		$no_item_callback      = array( $this, 'feed_list_no_item_message' );
		$message_callback      = array( $this, 'feed_list_message' );

		$feed_table = new GFAddOnFeedsTable( $feeds, $this->_slug, $columns, $bulk_actions, $action_links, $column_value_callback, $no_item_callback, $message_callback, $this );

		$feed_table->prepare_items();

		return $feed_table;
	}

	public function get_feeds( $form_id = null ) {
		$query = new WP_Query( array(
			'post_type'      => 'pronamic_pay_gf',
			'posts_per_page' => 50,
			'meta_query'     => array(
				array(
					'key'   => '_pronamic_pay_gf_form_id',
					'value' => $form_id,
				),
			),
		) );

		$posts = array();

		foreach ( $query->posts as $post ) {
			$post = (array) $post;

			$post['id'] = $post['ID'];

			// Is activated?
			$post['is_active'] = true;

			if ( '0' === get_post_meta( $post['id'], '_pronamic_pay_gf_feed_active', true ) ) {
				$post['is_active'] = false;
			}

			$post['meta'] = array(
				'transactionType' => 'product',
			);

			$posts[] = $post;
		}

		return $posts;
	}

	public function is_feed_condition_met( $feed, $form, $entry ) {
		return Pronamic_WP_Pay_Extensions_GravityForms_Util::is_condition_true( $form, $feed );
	}

	public function feed_list_columns() {
		return array(
			'name'                    => esc_html__( 'Name', 'pronamic_ideal' ),
			'transaction_description' => esc_html__( 'Transaction Description', 'pronamic_ideal' ),
			'configuration'           => esc_html__( 'Configuration', 'pronamic_ideal' ),
		);
	}

	/**
	 * Column name value.
	 *
	 * @param  array $feed
	 *
	 * @since unreleased
	 */
	public function get_column_value_name( $feed ) {
		$title = get_the_title( $feed['id'] );

		if ( empty( $title ) ) {
			$title = __( 'Default pay feed', 'pronamic_ideal' );
		}

		$edit_url = add_query_arg( array( 'fid' => $feed['id'] ) );

		?>

		<a href="<?php echo esc_url( $edit_url ); ?>"><strong><?php echo esc_html( $title ); ?></strong></a>

		<?php
	}

	/**
	 * Column transaction description value.
	 *
	 * @param  array $feed
	 *
	 * @since unreleased
	 */
	public function get_column_value_transaction_description( $feed ) {
		$description = get_post_meta( $feed['id'], '_pronamic_pay_gf_transaction_description', true );

		echo esc_html( $description );
	}

	/**
	 * Column configuration value.
	 *
	 * @param  array $feed
	 *
	 * @since unreleased
	 */
	public function get_column_value_configuration( $feed ) {
		$config_id = get_post_meta( $feed['id'], '_pronamic_pay_gf_config_id', true );

		$title = get_the_title( $config_id );

		if ( empty( $config_id ) || empty( $title ) ) {
			$title = '-';
		}

		echo esc_html( $title );
	}

	/**
	 * Display text if no pay feeds exist yet.
	 *
	 * @since unreleased
	 */
	public function feed_list_no_item_message() {
		printf( // WPCS: XSS ok
			__( 'This form doesn\'t have any pay feeds. Let\'s go %1$screate one%2$s.', 'pronamic_ideal' ),
			'<a href="' . esc_url( add_query_arg( array( 'fid' => 0 ) ) ) . '">',
			'</a>'
		);
	}

	/**
	 * Add supported notification events.
	 *
	 * @param array $form The form currently being processed.
	 *
	 * @return array
	 */
	public function supported_notification_events( $form ) {
		$form = (array) $form;

		$query = new WP_Query( array(
			'post_type'			=> 'pronamic_pay_gf',
			'posts_per_page'	=> 50,
			'meta_query'		=> array(
				array(
					'key'   => '_pronamic_pay_gf_form_id',
					'value' => $form['id'],
				),
			),
		) );

		if ( ! $query->have_posts() ) {
			return false;
		}

		$events = array(
			'complete_payment'          => esc_html__( 'Payment Completed', 'pronamic_ideal' ),
			'fail_payment'              => esc_html__( 'Payment Failed', 'pronamic_ideal' ),
			'add_pending_payment'       => esc_html__( 'Payment Pending', 'pronamic_ideal' ),
		);

		foreach ( $query->posts as $post ) {
			// Get the config ID from the pay feed
			$config_id = get_post_meta( $post->ID, '_pronamic_pay_gf_config_id', true );

			// Get the gateway from the configuration
			$gateway = Pronamic_WP_Pay_Plugin::get_gateway( $config_id );

			if ( $gateway && $gateway->supports( 'recurring' ) ) {
				$subscription_events = array(
					'create_subscription'       => esc_html__( 'Subscription Created', 'pronamic_ideal' ),
					'cancel_subscription'       => esc_html__( 'Subscription Canceled', 'pronamic_ideal' ),
					'expire_subscription'       => esc_html__( 'Subscription Expired', 'pronamic_ideal' ),
					'renew_subscription'        => esc_html__( 'Subscription Renewal Notice', 'pronamic_ideal' ),
					'add_subscription_payment'  => esc_html__( 'Subscription Payment Added', 'pronamic_ideal' ),
					'fail_subscription_payment' => esc_html__( 'Subscription Payment Failed', 'pronamic_ideal' ),
				);

				$events = array_merge( $events, $subscription_events );

				return $events;
			}
		}

		return $events;
	}

	/**
	 * Ajax feed activation toggle
	 */
	public function ajax_toggle_is_active() {
		$feed_id   = filter_input( INPUT_POST, 'feed_id', FILTER_SANITIZE_STRING );
		$is_active = filter_input( INPUT_POST, 'is_active', FILTER_SANITIZE_NUMBER_INT );

		$this->update_feed_active( $feed_id, $is_active );

		die();
	}

	/**
	 * Activate feed
	 */
	public function update_feed_active( $feed_id, $is_active ) {
		return update_post_meta( $feed_id, '_pronamic_pay_gf_feed_active', $is_active );
	}

	/**
	 * Delete feed
	 */
	public function delete_feed( $feed_id ) {
		wp_delete_post( $feed_id );
	}
}
