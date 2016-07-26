<?php

/**
 * Title: WordPress pay extension Gravity Forms payment methods
 * Description:
 * Copyright: Copyright (c) 2005 - 2016
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.4.7
 * @since 1.4.7
 */
class Pronamic_WP_Pay_Extensions_GravityForms_PaymentMethodsField extends GF_Field_Select {
	/**
	 * Type
	 *
	 * @var string
	 */
	const TYPE = 'pronamic_pay_payment_method_selector';

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = 'pronamic_pay_payment_method_selector';

	/**
	 * Constructs and initializes payment methods field.
	 *
	 * @param $properties
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
		if ( ! has_action( 'gform_editor_js_set_default_values', array( __CLASS__, 'editor_js_set_default_values' ) ) ) {
			add_action( 'gform_editor_js_set_default_values', array( __CLASS__, 'editor_js_set_default_values' ) );
		}

		// Admin
		if ( is_admin() ) {
			$this->inputType = 'checkbox';
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
			'choices_setting',
			'description_setting',
			'css_class_setting',
			'pronamic_pay_config_field_setting',
		);
	}

	/**
	 * Merge payment method choices from the payment gateway, leave `isSelected` in tact so users can enable/disable payment methods manual.
	 *
	 * @param int $form_id
	 */
	private function set_choices( $form_id ) {
		$payment_methods = array();

		// Feeds
		$feeds = get_pronamic_gf_pay_feeds_by_form_id( $form_id );

		$feed = reset( $feeds );

		if ( null !== $feed ) {
			$gateway = Pronamic_WP_Pay_Plugin::get_gateway( $feed->config_id );

			if ( $gateway ) {
				$field = $gateway->get_payment_method_field();

				$error = $gateway->get_error();

				// @todo What todo if error?
				if ( $field && ! is_wp_error( $error ) ) {
					foreach ( $field['choices'] as $group ) {
						foreach ( $group['options'] as $value => $label ) {
							$payment_methods[ $value ] = $label;
						}
					}
				}
			}
		}

		// Current
		foreach ( $this->choices as &$choice ) {
			$value = $choice['value'];

			if ( isset( $payment_methods[ $value ] ) ) {
				$choice['builtin'] = true;

				unset( $payment_methods[ $value ] );
			}
		}

		// Built-in
		foreach ( $payment_methods as $value => $label ) {
			$this->choices[] = array(
				'value'      => $value,
				'text'       => $label,
				'isSelected' => false,
				'builtin'    => true,
			);
		}
	}

	/**
	 * Get the field input.
	 *
	 * @see https://github.com/wp-premium/gravityforms/blob/2.0.3/includes/fields/class-gf-field-select.php#L41-L60
	 * @see https://github.com/wp-premium/gravityforms/blob/2.0.3/includes/fields/class-gf-field.php#L182-L193
	 * @param array $form
	 * @param string $value
	 * @param array $entry
	 * @return string
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {
		$input = parent::get_field_input( $form, $value, $entry );

		$feeds = get_pronamic_gf_pay_feeds_by_form_id( $form_id );

		if ( empty( $feeds ) ) {
			$link = sprintf(
				"<a class='ideal-edit-link' href='%s' target='_blank'>%s</a>",
				$new_feed_url,
				__( 'Create pay feed', 'pronamic_ideal' )
			);

			$input = $link . $input;
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
		return esc_attr__( 'Payment Method', 'pronamic_ideal' );
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
			'text'  => __( 'Payment Method', 'pronamic_ideal' ),
		);
	}

	/**
	 * Add button.
	 *
	 * @see https://github.com/wp-premium/gravityforms/blob/1.9.19/form_detail.php#L2353-L2368
	 * @see https://github.com/wp-premium/gravityforms/blob/1.9.19/includes/fields/class-gf-field.php#L617-L652
	 * @return array
	 */
	public function add_button( $field_groups ) {
		// We have to make sure the custom pay field group is added, otherwise the button won't be added.
		$field_groups = Pronamic_WP_Pay_Extensions_GravityForms_Fields::add_pay_field_group( $field_groups );

		return parent::add_button( $field_groups );
	}

	/**
	 * Editor JavaScript default field values.
	 *
	 * @see https://github.com/wp-premium/gravityforms/blob/2.0.3/js.php#L587-L599
	 */
	static function editor_js_set_default_values() {
		$label = __( 'Choose a payment method', 'pronamic_ideal' );

		?>
		case '<?php echo esc_js( self::TYPE ); ?>' :
			if ( ! field.label ) {
				field.label = '<?php echo esc_js( $label ); ?>';
			}

			break;
		<?php
	}
}
