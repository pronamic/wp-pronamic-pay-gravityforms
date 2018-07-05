<?php

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

use GF_Field_Select;
use Pronamic\WordPress\Pay\Core\Gateway;
use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Plugin;

/**
 * Title: WordPress pay extension Gravity Forms issuers field
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.0
 * @since   1.4.7
 */
class IssuersField extends GF_Field_Select {
	/**
	 * Type
	 *
	 * @var string
	 */
	const TYPE = Fields::ISSUERS_FIELD_TYPE;

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = Fields::ISSUERS_FIELD_TYPE;

	/**
	 * Constructs and initializes issuers field.
	 *
	 * @param $properties
	 */
	public function __construct( $properties = array() ) {
		parent::__construct( $properties );

		// Actions
		if ( ! has_action( 'gform_editor_js_set_default_values', array( __CLASS__, 'editor_js_set_default_values' ) ) ) {
			add_action( 'gform_editor_js_set_default_values', array( __CLASS__, 'editor_js_set_default_values' ) );
		}

		if ( ! isset( $this->formId ) && defined( 'DOING_AJAX' ) && DOING_AJAX && filter_has_var( INPUT_POST, 'form_id' ) && 'rg_add_field' === filter_input( INPUT_POST, 'action' ) && false !== strpos( filter_input( INPUT_POST, 'field' ), self::TYPE ) ) {
			$this->formId = filter_input( INPUT_POST, 'form_id', FILTER_SANITIZE_NUMBER_INT );
		}

		// Choices
		if ( isset( $this->formId ) ) {
			$this->set_choices( $this->formId );
		}
	}

	/**
	 * Get form editor field settings for this field.
	 *
	 * @see https://github.com/wp-premium/gravityforms/blob/2.0.3/includes/fields/class-gf-field-select.php#L16-L35
	 * @see https://github.com/wp-premium/gravityforms/blob/2.0.3/includes/fields/class-gf-field.php#L144-L151
	 * @return array
	 */
	public function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'error_message_setting',
			'enable_enhanced_ui_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'size_setting',
			'description_setting',
			'css_class_setting',
			'rules_setting',
			'pronamic_pay_config_field_setting',
		);
	}

	/**
	 * Get the iDEAL gateway for this field.
	 *
	 * @return null|Gateway
	 */
	private function get_gateway() {
		$gateway = null;

		if ( isset( $this->pronamicPayConfigId ) && ! empty( $this->pronamicPayConfigId ) ) {
			$gateway = Plugin::get_gateway( $this->pronamicPayConfigId );
		}

		if ( ! $gateway ) {
			$feeds = FeedsDB::get_feeds_by_form_id( $this->formId );

			foreach ( $feeds as $feed ) {
				$gateway = Plugin::get_gateway( $feed->config_id );

				if ( $gateway ) {
					$issuers = $gateway->get_transient_issuers();

					if ( empty( $issuers ) ) {
						continue;
					}

					return $gateway;
				}
			}
		}

		return $gateway;
	}

	/**
	 * Set the issuer choices for this issuers field.
	 *
	 * @param int $form_id
	 */
	private function set_choices( $form_id ) {
		$this->choices = array();

		$gateway = $this->get_gateway();

		if ( ! $gateway ) {
			return;
		}

		// Always use iDEAL payment method for issuer field
		$gateway->set_payment_method( PaymentMethods::IDEAL );

		$field = $gateway->get_issuer_field();

		$this->error = $gateway->get_error();

		// @todo What todo if error?
		if ( ! $field || is_wp_error( $this->error ) ) {
			return;
		}

		foreach ( $field['choices'] as $group ) {
			foreach ( $group['options'] as $value => $label ) {
				$this->choices[] = array(
					'value' => $value,
					'text'  => $label,
				);
			}
		}
	}

	/**
	 * Get the field input.
	 *
	 * @link https://github.com/wp-premium/gravityforms/blob/2.0.3/includes/fields/class-gf-field-select.php#L41-L60
	 *
	 * @link https://github.com/wp-premium/gravityforms/blob/2.0.3/includes/fields/class-gf-field.php#L182-L193
	 * @link https://github.com/wp-premium/gravityforms/blob/2.3.2/includes/fields/class-gf-field.php#L228-L239
	 *
	 * @param array        $form  The Form Object currently being processed.
	 * @param string|array $value The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param null|array   $entry Null or the Entry Object currently being edited.
	 *
	 * @return string
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {
		// Error handling
		if ( is_wp_error( $this->error ) ) {
			return $this->error->get_error_message();
		}

		// Input
		$input = parent::get_field_input( $form, $value, $entry );

		if ( is_admin() ) {
			$feeds = get_pronamic_gf_pay_feeds_by_form_id( $form['id'] );

			$new_feed_url = Admin::get_new_feed_url( $form['id'] );

			if ( empty( $feeds ) ) {
				$link = sprintf(
					'<a class="ideal-edit-link" href="%s" target="_blank">%s</a>',
					esc_url( $new_feed_url ),
					__( 'New Payment Feed', 'pronamic_ideal' )
				);

				$input = $link . $input;
			}

			if ( ! empty( $feeds ) && empty( $this->choices ) ) {
				// If there are feeds and no choices it's very likely this field is no supported by the gateway.
				$error = sprintf(
					'<p class="pronamic-pay-error"><strong>%s</strong><br><em>%s</em></p>',
					__( 'This field is not supported by your payment gateway.', 'pronamic_ideal' ),
					sprintf(
						/* translators: %s: new feed URL */
						__( 'Please remove it from this form or <a href="%s" target="_blank">add a supported payment gateway</a>.', 'pronamic_ideal' ),
						esc_url( $new_feed_url )
					)
				);

				$input = $error . $input;
			}
		}

		return $input;
	}

	/**
	 * Get form editor field title.
	 *
	 * @see https://github.com/wp-premium/gravityforms/blob/1.9.19/includes/fields/class-gf-field.php#L106-L113
	 * @see https://github.com/wp-premium/gravityforms/blob/1.9.19/includes/fields/class-gf-field-select.php#L12-L14
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'Issuer', 'pronamic_ideal' );
	}

	/**
	 * Get form editor button.
	 *
	 * @see https://github.com/wp-premium/gravityforms/blob/1.9.19/includes/fields/class-gf-field.php#L115-L129
	 * @return array
	 */
	public function get_form_editor_button() {
		return array(
			'group' => 'pronamic_pay_fields',
			'text'  => __( 'Issuer', 'pronamic_ideal' ),
		);
	}

	/**
	 * Add button.
	 *
	 * @see https://github.com/wp-premium/gravityforms/blob/1.9.19/form_detail.php#L2353-L2368
	 * @see https://github.com/wp-premium/gravityforms/blob/1.9.19/includes/fields/class-gf-field.php#L617-L652
	 *
	 * @param $field_groups
	 *
	 * @return array
	 */
	public function add_button( $field_groups ) {
		// We have to make sure the custom pay field group is added, otherwise the button won't be added.
		$field_groups = Fields::add_pay_field_group( $field_groups );

		return parent::add_button( $field_groups );
	}

	/**
	 * Editor JavaScript default field values.
	 *
	 * @see https://github.com/wp-premium/gravityforms/blob/2.0.3/js.php#L587-L599
	 */
	public static function editor_js_set_default_values() {
		$label = __( 'Choose a bank for iDEAL payment', 'pronamic_ideal' );

		?>
		case '<?php echo esc_js( self::TYPE ); ?>' :
		if ( ! field.label ) {
		field.label = '<?php echo esc_js( $label ); ?>';
		}

		break;
		<?php
	}
}
