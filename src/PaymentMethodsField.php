<?php

/**
 * Title: WordPress pay extension Gravity Forms payment methods
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.6.3
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
	 * Get the gateway for this field.
	 *
	 * @return
	 */
	private function get_gateway() {
		$gateway = null;

		if ( isset( $this->pronamicPayConfigId ) && ! empty( $this->pronamicPayConfigId ) ) {
			$gateway = Pronamic_WP_Pay_Plugin::get_gateway( $this->pronamicPayConfigId );
		}

		if ( ! $gateway ) {
			$feeds = get_pronamic_gf_pay_feeds_by_form_id( $this->formId );

			foreach ( $feeds as $feed ) {
				$gateway = Pronamic_WP_Pay_Plugin::get_gateway( $feed->config_id );

				if ( $gateway && null !== $gateway->get_payment_method_field() ) {
					return $gateway;
				}
			}
		}

		return $gateway;
	}

	/**
	 * Merge payment method choices from the payment gateway, leave `isSelected` in tact so users can enable/disable payment methods manual.
	 *
	 * @param int $form_id
	 */
	private function set_choices( $form_id ) {
		$payment_methods = array();

		// Gateway
		$gateway = $this->get_gateway();

		if ( $gateway ) {
			$field = $gateway->get_payment_method_field();

			$this->error = $gateway->get_error();

			// @todo What todo if error?
			if ( $field && ! is_wp_error( $this->error ) ) {
				foreach ( $field['choices'] as $group ) {
					if ( isset( $group['options'] ) ) {
						foreach ( $group['options'] as $value => $label ) {
							$payment_methods[ $value ] = $label;
						}
					}
				}
			}
		}

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
		if ( ! is_admin() ) {
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
	 * @return boolean true if 'isSelected' is set and true, false otherwise.
	 */
	public function filter_choice_is_selected( $choice ) {
		return is_array( $choice ) && isset( $choice['isSelected'] ) && $choice['isSelected'];
	}

	/**
	 * Unselect the specified choice.
	 *
	 * @param array $choice
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
	 * @param array $form
	 * @param string $value
	 * @param array $entry
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

			if ( empty( $feeds ) ) {
				$link = sprintf(
					"<a class='ideal-edit-link' href='%s' target='_blank'>%s</a>",
					esc_url( Pronamic_WP_Pay_Extensions_GravityForms_Admin::get_new_feed_url( $form['id'] ) ),
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
	 * @see https://github.com/wp-premium/gravityforms/blob/2.0.3/js/forms.js#L38-L43
	 */
	static function editor_js_set_default_values() {
		?>
		case '<?php echo esc_js( self::TYPE ); ?>' :
			if ( ! field.label ) {
				field.label = '<?php echo esc_js( __( 'Choose a payment method', 'pronamic_ideal' ) ); ?>';
			}

			field.enableChoiceValue = true;

			if ( ! field.choices ) {
				field.choices = new Array();

				<?php foreach ( Pronamic_WP_Pay_PaymentMethods::get_payment_methods() as $value => $label ) : ?>

					var choice = new Choice( <?php echo json_encode( $label ); ?>, <?php echo json_encode( $value ); ?> );

					choice.isSelected = true;
					choice.builtin    = true;

					field.choices.push( choice );

				<?php endforeach; ?>

			}

			break;
		<?php
	}
}
