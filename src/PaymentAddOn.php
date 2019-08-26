<?php
/**
 * Payment add-on
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2019 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

use GFAddOnFeedsTable;
use GFPaymentAddOn;
use WP_Query;

/**
 * Title: WordPress pay extension Gravity Forms payment add-on
 * Description:
 * Copyright: 2005-2019 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.1.2
 * @since   1.1.0
 */
class PaymentAddOn extends GFPaymentAddOn {
	/**
	 * Slug.
	 *
	 * @var string
	 */
	const SLUG = 'pronamic_pay';

	/**
	 * Capabilities.
	 *
	 * @link https://github.com/wp-premium/gravityformspaypal/blob/2.3.1/class-gf-paypal.php#L21-L22
	 *
	 * @var array
	 */
	protected $_capabilities = array(
		'gravityforms_pronamic_pay',
		'gravityforms_pronamic_pay_uninstall',
	);

	/**
	 * Capabilities settings page.
	 *
	 * @link https://github.com/wp-premium/gravityformspaypal/blob/2.3.1/class-gf-paypal.php#L24-L27
	 *
	 * @var string
	 */
	protected $_capabilities_settings_page = 'gravityforms_pronamic_pay';

	/**
	 * Capabilities form settings.
	 *
	 * @var string
	 */
	protected $_capabilities_form_settings = 'gravityforms_pronamic_pay';

	/**
	 * Capabilities uninstall.
	 *
	 * @var string
	 */
	protected $_capabilities_uninstall = 'gravityforms_pronamic_pay_uninstall';

	/**
	 * Construct and initialize an Gravity Forms payment add-on
	 *
	 * @link https://github.com/wp-premium/gravityforms/blob/1.9.10.15/includes/addon/class-gf-payment-addon.php
	 *
	 * @since 1.3.0
	 */
	public function __construct() {
		parent::__construct();

		/*
		 * Slug.
		 *
		 * @var string URL-friendly identifier used for form settings, add-on settings, text domain localization...
		 * @link https://github.com/wp-premium/gravityforms/blob/1.9.10.15/includes/addon/class-gf-addon.php#L24-L27
		 */
		$this->_slug = self::SLUG;

		/*
		 * Title.
		 *
		 * @var string Title of the plugin to be used on the settings page, form settings and plugins page. Example: 'Gravity Forms MailChimp Add-On'
		 * @link https://github.com/wp-premium/gravityforms/blob/1.9.10.15/includes/addon/class-gf-addon.php#L40-L43
		 */
		$this->_title = __( 'Pronamic Pay Add-On', 'pronamic_ideal' );

		/*
		 * Short title.
		 *
		 * @var string Short version of the plugin title to be used on menus and other places where a less verbose string is useful. Example: 'MailChimp'
		 * @link https://github.com/wp-premium/gravityforms/blob/1.9.10.15/includes/addon/class-gf-addon.php#L44-L47
		 */
		$this->_short_title = __( 'Pay', 'pronamic_ideal' );

		/*
		 * Actions.
		 */
		add_action( 'admin_init', array( $this, 'pronamic_maybe_save_feed' ), 20 );

		/*
		 * Filters.
		 */
		add_filter( 'gform_admin_pre_render', array( $this, 'admin_pre_render' ), 10, 1 );
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

		$post_id = wp_insert_post(
			array(
				'ID'             => $post_id,
				'post_type'      => 'pronamic_pay_gf',
				'post_title'     => $post_title,
				'post_status'    => 'publish',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			)
		);

		$url = add_query_arg(
			array(
				'page'    => 'gf_edit_forms',
				'view'    => 'settings',
				'subview' => 'pronamic_pay',
				'id'      => $form_id,
				'fid'     => $post_id,
				'message' => $post_id ? '1' : '0',
			),
			'admin.php'
		);

		wp_safe_redirect( $url );

		exit;
	}

	/**
	 * Filter the form in admin.
	 *
	 * @param array $form Gravity Forms form.
	 *
	 * @return array
	 */
	public function admin_pre_render( $form ) {
		$feeds = FeedsDB::get_feeds_by_form_id( $form['id'] );

		$condition_field_ids = array();

		foreach ( $feeds as $feed ) {
			if ( empty( $feed->condition_field_id ) ) {
				continue;
			}

			$condition_field_ids[] = $feed->condition_field_id;
		}

		$form['pronamic_pay_condition_field_ids'] = $condition_field_ids;

		return $form;
	}

	/**
	 * Form settings page.
	 *
	 * @since 1.3.0
	 *
	 * @param array $form Gravity Forms form.
	 */
	public function form_settings( $form ) {
		$form_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_STRING );
		$post_id = filter_input( INPUT_GET, 'fid', FILTER_SANITIZE_STRING );

		if ( $this->is_detail_page() ) {
			require dirname( __FILE__ ) . '/../views/html-admin-feed-gf-box.php';
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

	/**
	 * Get feed table.
	 *
	 * @param array $form Gravity Forms form.
	 *
	 * @return GFAddOnFeedsTable
	 */
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

	/**
	 * Get feeds.
	 *
	 * @param int|null $form_id Form ID.
	 *
	 * @return array Feeds.
	 */
	public function get_feeds( $form_id = null ) {
		$query = new WP_Query(
			array(
				'post_type'      => 'pronamic_pay_gf',
				'posts_per_page' => 50,
				'meta_query'     => array(
					array(
						'key'   => '_pronamic_pay_gf_form_id',
						'value' => $form_id,
					),
				),
			)
		);

		$posts = array();

		foreach ( $query->posts as $post ) {
			$post = (array) $post;

			$post = array_merge(
				$post,
				array(
					'id'        => $post['ID'],
					'form_id'   => get_post_meta( $post['ID'], '_pronamic_pay_gf_form_id', true ),
					'is_active' => true,
					'meta'      => array(
						'post'            => $post,
						'feed_name'       => $post['post_title'],
						'transactionType' => 'product',
					),
				)
			);

			// Is activated?
			if ( '0' === get_post_meta( $post['id'], '_pronamic_pay_gf_feed_active', true ) ) {
				$post['is_active'] = false;
			}

			$posts[] = $post;
		}

		return $posts;
	}

	/**
	 * Get feed.
	 *
	 * @param int|string $id Feed ID.
	 *
	 * @return false|array Feed or false if feed doesn't exist.
	 */
	public function get_feed( $id ) {
		$post = get_post( $id, ARRAY_A );

		if ( null === $post ) {
			return false;
		}

		$post = array_merge(
			$post,
			array(
				'id'        => $post['ID'],
				'form_id'   => get_post_meta( $id, '_pronamic_pay_gf_form_id', true ),
				'is_active' => true,
				'meta'      => array(
					'post'            => $post,
					'feed_name'       => $post['post_title'],
					'transactionType' => 'product',
				),
			)
		);

		// Is activated?
		if ( '0' === get_post_meta( $post['id'], '_pronamic_pay_gf_feed_active', true ) ) {
			$post['is_active'] = false;
		}

		return $post;
	}

	/**
	 * Is feed condition met?
	 *
	 * @param array $feed  Feed.
	 * @param array $form  Gravity Forms form.
	 * @param array $entry Gravity Forms entry.
	 *
	 * @return bool
	 */
	public function is_feed_condition_met( $feed, $form, $entry ) {
		return Util::is_condition_true( $form, $feed );
	}

	/**
	 * Feed list columns.
	 *
	 * @return array
	 */
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
	 * @param array $feed Feed.
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
	 * @param array $feed Feed.
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
	 * @param array $feed Feed.
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
		/* translators: 1: <a href="new feed URL">, 2: </a> */
		$label = __( 'This form doesn\'t have any pay feeds. Let\'s go %1$screate one%2$s.', 'pronamic_ideal' );

		printf(
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$label,
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

		$query = new WP_Query(
			array(
				'post_type'      => 'pronamic_pay_gf',
				'posts_per_page' => 50,
				'meta_query'     => array(
					array(
						'key'   => '_pronamic_pay_gf_form_id',
						'value' => $form['id'],
					),
				),
			)
		);

		if ( ! $query->have_posts() ) {
			return false;
		}

		$events = array(
			'complete_payment'          => esc_html__( 'Payment Completed', 'pronamic_ideal' ),
			'fail_payment'              => esc_html__( 'Payment Failed', 'pronamic_ideal' ),
			'add_pending_payment'       => esc_html__( 'Payment Pending', 'pronamic_ideal' ),

			// Subscription events.
			'create_subscription'       => esc_html__( 'Subscription Created', 'pronamic_ideal' ),
			'cancel_subscription'       => esc_html__( 'Subscription Canceled', 'pronamic_ideal' ),
			'expire_subscription'       => esc_html__( 'Subscription Expired', 'pronamic_ideal' ),
			'renew_subscription'        => esc_html__( 'Subscription Renewal Notice', 'pronamic_ideal' ),
			'add_subscription_payment'  => esc_html__( 'Subscription Payment Added', 'pronamic_ideal' ),
			'fail_subscription_payment' => esc_html__( 'Subscription Payment Failed', 'pronamic_ideal' ),
		);

		return $events;
	}

	/**
	 * Ajax feed activation toggle.
	 */
	public function ajax_toggle_is_active() {
		$feed_id   = filter_input( INPUT_POST, 'feed_id', FILTER_SANITIZE_STRING );
		$is_active = filter_input( INPUT_POST, 'is_active', FILTER_SANITIZE_NUMBER_INT );

		$this->update_feed_active( $feed_id, $is_active );

		die();
	}

	/**
	 * Activate feed.
	 *
	 * @param string $feed_id   Feed ID.
	 * @param bool   $is_active Is active flag.
	 *
	 * @return bool|int
	 */
	public function update_feed_active( $feed_id, $is_active ) {
		return update_post_meta( $feed_id, '_pronamic_pay_gf_feed_active', $is_active );
	}

	/**
	 * Allow payment feeds to be duplicated.
	 *
	 * @param int|array $id The ID of the feed to be duplicated or the feed object when duplicating a form.
	 *
	 * @return boolean
	 */
	public function can_duplicate_feed( $id ) {
		return true;
	}

	/**
	 * Insert feed.
	 *
	 * @param string|int $form_id   Form ID.
	 * @param bool       $is_active Whether or not the feed is activated.
	 * @param array      $meta      Feed meta.
	 *
	 * @return int
	 */
	public function insert_feed( $form_id, $is_active, $meta ) {
		// Original feed post is passed in meta through `get_feed()` method.
		$original_feed = $meta['post'];

		// Insert post.
		$post_id = wp_insert_post(
			array(
				'post_type'      => 'pronamic_pay_gf',
				'post_title'     => $meta['feed_name'],
				'post_status'    => 'publish',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			)
		);

		$original_meta = get_post_meta( $original_feed['ID'] );

		foreach ( $original_meta as $meta_key => $meta_value ) {
			$meta_value = array_shift( $meta_value );

			if ( is_serialized( $meta_value ) ) {
				$meta_value = unserialize( $meta_value );
			}

			switch ( $meta_key ) {
				case '_pronamic_pay_gf_form_id':
					$meta_value = $form_id;

					break;
				case '_pronamic_pay_gf_feed_active':
					$meta_value = $is_active;

					break;
			}

			update_post_meta( $post_id, $meta_key, $meta_value );
		}

		return $post_id;
	}

	/**
	 * Delete feed.
	 *
	 * @param int $feed_id Feed ID.
	 */
	public function delete_feed( $feed_id ) {
		wp_delete_post( $feed_id );
	}

	/**
	 * Delete feeds.
	 *
	 * @param int $form_id Form ID.
	 */
	public function delete_feeds( $form_id = null ) {
		if ( null === $form_id ) {
			return;
		}

		$feeds = $this->get_feeds( $form_id );

		foreach ( $feeds as $feed ) {
			$this->get_feed( $feed['ID'] );
		}
	}
}
