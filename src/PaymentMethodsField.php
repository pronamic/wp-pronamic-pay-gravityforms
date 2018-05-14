<?php

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

use GF_Field_Select;
use GFForms;
use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Plugin;

/**
 * Title: WordPress pay extension Gravity Forms payment methods
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.0
 * @since   1.4.7
 */
class PaymentMethodsField extends GF_Field_Select {
	/**
	 * Type
	 *
	 * @var string
	 */
	const TYPE = Fields::PAYMENT_METHODS_FIELD_TYPE;

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = Fields::PAYMENT_METHODS_FIELD_TYPE;

	/**
	 * Constructs and initializes payment methods field.
	 *
	 * @param array $properties
	 */
	public function __construct( $properties = array() ) {
		parent::__construct( $properties );

		/*
		 * The `inputType` of the payment methods field was in the past set to `checkbox`
		 * this results in a `GF_Field_Checkbox` field with additional inputs, but we what
		 * need is a payment methods field without additional inputs.
		 *
		 * @see https://github.com/wp-premium/gravityforms/blob/1.9.19/includes/fields/class-gf-fields.php#L60-L86
		 */
		$this->inputs = null;

		// Actions
		if ( ! has_action( 'gform_editor_js_set_default_values', array( $this, 'editor_js_set_default_values' ) ) ) {
			add_action( 'gform_editor_js_set_default_values', array( $this, 'editor_js_set_default_values' ) );
		}

		// Admin
		if ( is_admin() ) {
			$this->inputType = 'checkbox';

			if ( empty( $this->formId ) && 'gf_edit_forms' === filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING ) ) {
				$this->formId = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_STRING );
			}
		}

		// Choices
		$this->enableChoiceValue = true;

		if ( isset( $this->formId ) ) {
			$this->set_choices( $this->formId );
		}
	}

	/**
	 * Get form editor field settings for this field.
	 *
	 * @see https://github.com/wp-premium/gravityforms/blob/2.0.3/includes/fields/class-gf-field-select.php#L16-L35
	 * @see https://github.com/wp-premium/gravityforms/blob/2.0.3/includes/fields/class-gf-field.php#L144-L151
	 *
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
			'choices_setting',
			'description_setting',
			'css_class_setting',
			'rules_setting',
			'pronamic_pay_config_field_setting',
		);
	}

	/**
	 * Get the gateways for this field.
	 *
	 * @return array
	 */
	private function get_gateways() {
		$gateways = array();

		$feeds = get_pronamic_gf_pay_feeds_by_form_id( $this->formId );

		// Get all config IDs.
		$config_ids = wp_list_pluck( $feeds, 'config_id' );

		// Remove duplicates.
		$config_ids = array_unique( $config_ids );

		// Check if field config ID setting is set as config of a payment feed.
		if ( isset( $this->pronamicPayConfigId ) && in_array( $this->pronamicPayConfigId, $config_ids, true ) ) {
			$gateway = Plugin::get_gateway( $this->pronamicPayConfigId );

			if ( $gateway ) {
				$gateways[] = $gateway;
			}
		}

		// Get all gateways if config ID setting is unused.
		if ( empty( $gateways ) ) {
			$gateways = array_map(
				array( 'Pronamic\WordPress\Pay\Plugin', 'get_gateway' ),
				$config_ids
			);

			// Remove non-existing gateways.
			$gateways = array_filter( $gateways );
		}

		return $gateways;
	}

	/**
	 * Merge payment method choices from the payment gateway, leave `isSelected` in tact so users can enable/disable payment methods manual.
	 *
	 * @param int $form_id Form ID.
	 */
	private function set_choices( $form_id ) {
		// Gateway available payment methods.
		$payment_methods = $this->get_gateway_payment_methods();

		// Choices
		$choices = array();

		// Gravity Forms
		if ( is_array( $this->choices ) ) {
			foreach ( $this->choices as $choice ) {
				$value = $choice['value'];

				$choice['builtin'] = isset( $payment_methods[ $value ] );

				$choices[ $value ] = $choice;
			}
		}

		// Built-in
		foreach ( $payment_methods as $value => $label ) {
			// Only add built-in payment if it's not already set.
			if ( ! isset( $choices[ $value ] ) ) {
				$choices[ $value ] = array(
					'value'      => $value,
					'text'       => $label,
					'isSelected' => false,
					'builtin'    => true,
				);
			}
		}

		// Admin
		if ( ! in_array( GFForms::get_page(), array( 'form_editor', 'form_settings' ) ) ) {
			$choices = array_filter( $choices, array( $this, 'filter_choice_is_selected' ) );
			$choices = array_map( array( $this, 'unselect_choice' ), $choices );
		}

		// Set choices
		$this->choices = array_values( $choices );
	}

	/**
	 * Filter Gravity Forms selected choice.
	 *
	 * @param array $choice
	 *
	 * @return boolean true if 'isSelected' is set and true, false otherwise.
	 */
	public function filter_choice_is_selected( $choice ) {
		return is_array( $choice ) && isset( $choice['isSelected'] ) && $choice['isSelected'];
	}

	/**
	 * Unselect the specified choice.
	 *
	 * @param array $choice
	 *
	 * @return array choice
	 */
	public function unselect_choice( $choice ) {
		$choice['isSelected'] = false;

		return $choice;
	}

	/**
	 * Get the field input.
	 *
	 * @see https://github.com/wp-premium/gravityforms/blob/2.0.3/includes/fields/class-gf-field-select.php#L41-L60
	 * @see https://github.com/wp-premium/gravityforms/blob/2.0.3/includes/fields/class-gf-field.php#L182-L193
	 *
	 * @param array  $form
	 * @param string $value
	 * @param array  $entry
	 *
	 * @return string
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {
		// Error handling.
		if ( is_wp_error( $this->error ) ) {
			return $this->error->get_error_message();
		}

		// Input.
		$input = parent::get_field_input( $form, $value, $entry );

		if ( is_admin() ) {
			$feeds = get_pronamic_gf_pay_feeds_by_form_id( $form['id'] );

			if ( empty( $feeds ) ) {
				$link = sprintf(
					"<a class='ideal-edit-link' href='%s' target='_blank'>%s</a>",
					esc_url( Admin::get_new_feed_url( $form['id'] ) ),
					esc_html__( 'New Payment Feed', 'pronamic_ideal' )
				);

				$input = $link . $input;
			}
		}

		return $input;
	}

	/**
	 * Get form editor field title.
	 *
	 * @see https://github.com/wp-premium/gravityforms/blob/1.9.19/includes/fields/class-gf-field.php#L106-L113
	 * @see https://github.com/wp-premium/gravityforms/blob/1.9.19/includes/fields/class-gf-field-select.php#L12-L14
	 *
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'Payment Method', 'pronamic_ideal' );
	}

	/**
	 * Get form editor button.
	 *
	 * @see https://github.com/wp-premium/gravityforms/blob/1.9.19/includes/fields/class-gf-field.php#L115-L129
	 *
	 * @return array
	 */
	public function get_form_editor_button() {
		return array(
			'group' => 'pronamic_pay_fields',
			'text'  => __( 'Payment Method', 'pronamic_ideal' ),
		);
	}

	/**
	 * Add button.
	 *
	 * @see https://github.com/wp-premium/gravityforms/blob/1.9.19/form_detail.php#L2353-L2368
	 * @see https://github.com/wp-premium/gravityforms/blob/1.9.19/includes/fields/class-gf-field.php#L617-L652
	 *
	 * @param array $field_groups Field groups.
	 *
	 * @return array
	 */
	public function add_button( $field_groups ) {
		// We have to make sure the custom pay field group is added, otherwise the button won't be added.
		$field_groups = Fields::add_pay_field_group( $field_groups );

		return parent::add_button( $field_groups );
	}

	/**
	 * Get gateway available payment methods.
	 *
	 * @return array
	 */
	private function get_gateway_payment_methods() {
		$gateways = $this->get_gateways();

		$payment_methods = array();

		foreach ( $gateways as $gateway ) {
			$options = $gateway->get_payment_method_field_options( false );

			$payment_methods = array_merge( $payment_methods, $options );

			$this->error = $gateway->get_error();
		}

		if ( empty( $payment_methods ) ) {
			$active_methods = PaymentMethods::get_active_payment_methods();

			foreach ( $active_methods as $payment_method ) {
				$payment_methods[ $payment_method ] = PaymentMethods::get_name( $payment_method );
			}
		}

		return $payment_methods;
	}

	/**
	 * Editor JavaScript default field values.
	 *
	 * @see https://github.com/wp-premium/gravityforms/blob/2.0.3/js.php#L587-L599
	 * @see https://github.com/wp-premium/gravityforms/blob/2.0.3/js/forms.js#L38-L43
	 */
	public function editor_js_set_default_values() {
		?>
		case '<?php echo esc_js( self::TYPE ); ?>' :
		if ( ! field.label ) {
		field.label = '<?php echo esc_js( __( 'Choose a payment method', 'pronamic_ideal' ) ); ?>';
		}

		field.enableChoiceValue = true;

		if ( ! field.choices ) {
		field.choices = new Array();

		<?php foreach ( $this->get_gateway_payment_methods() as $payment_method => $name ) : ?>

			var choice = new Choice( <?php echo wp_json_encode( $name ); ?>, <?php echo wp_json_encode( strval( $payment_method ) ); ?> );

			choice.isSelected = true;
			choice.builtin    = true;

			field.choices.push( choice );

		<?php endforeach; ?>

		}

		break;
		<?php
	}
}
