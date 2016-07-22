<?php

/**
 * Title: WordPress pay extension Gravity Forms issuers field
 * Description:
 * Copyright: Copyright (c) 2005 - 2016
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.4.7
 * @since 1.4.7
 */
class Pronamic_WP_Pay_Extensions_GravityForms_IssuersField extends GF_Field_Select {
	/**
	 * Type
	 *
	 * @var string
	 */
	const TYPE = 'ideal_issuer_drop_down';

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = 'ideal_issuer_drop_down';

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

		if ( ! has_action( 'gform_field_standard_settings', array( __CLASS__, 'field_settings_config' ) ) ) {
			add_action( 'gform_field_standard_settings', array( __CLASS__, 'field_settings_config' ), 10, 2 );
		}

		// Admin
		if ( is_admin() ) {
			$this->inputType = 'dropdown';
		}

		// Choices
		if ( isset( $this->formId ) ) {
			$this->set_choices( $this->formId );
		}
	}

	public function get_form_editor_field_settings() {
		$settings = parent::get_form_editor_field_settings();

		$settings[] = 'pronamic_pay_config_field_setting';

		return $settings;
	}

	private function set_choices( $form_id ) {
		$feeds = get_pronamic_gf_pay_feeds_by_form_id( $form_id );

		$feed = reset( $feeds );

		if ( null !== $feed ) {
			$gateway = Pronamic_WP_Pay_Plugin::get_gateway( $feed->config_id );

			if ( $gateway ) {
				// Always use iDEAL payment method for issuer field
				$payment_method = $gateway->get_payment_method();

				$gateway->set_payment_method( Pronamic_WP_Pay_PaymentMethods::IDEAL );

				$field = $gateway->get_issuer_field();

				$error = $gateway->get_error();

				if ( is_wp_error( $error ) ) {
					// @todo
				} elseif ( $field ) {
					$this->choices = array();

					foreach ( $field['choices'] as $group ) {
						foreach ( $group['options'] as $value => $label ) {
							$this->choices[] = array(
								'value'      => $value,
								'text'       => $label,
								//'isSelected' => false,
							);
						}
					}
				}

				// Reset payment method to original value
				$gateway->set_payment_method( $payment_method );
			}
		}
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
	 * @see https://github.com/wp-premium/gravityforms/blob/1.9.19/js.php#L834-L836
	 */
	static function editor_js_set_default_values() {
		$label = __( 'Choose a bank for iDEAL payment', 'pronamic_ideal' );

		?>
		case '<?php echo esc_js( self::TYPE ); ?>':
			field.label = '<?php echo esc_js( $label ); ?>';

			break;
		<?php
	}

	/**
	 * Editor configuration field
	 *
	 * @see https://github.com/wp-premium/gravityforms/blob/1.9.19/form_detail.php#L525
	 */
	static function field_settings_config( $position, $form_id ) {
		if ( 10 !== $position ) {
			return;
		}

		?>
		<li class="pronamic_pay_config_field_setting field_setting">
			<label for="pronamic_pay_config_field">
				<?php esc_html_e( 'iDEAL configuration', 'pronamic_ideal' ); ?>
				<?php gform_tooltip( 'form_field_pronamic_pay_config' ) ?>
			</label>
			<select id="pronamic_pay_config_field" onchange="SetFieldProperty('pronamicPayConfig', jQuery(this).val());">
				<?php

				$feeds = get_pronamic_gf_pay_feeds_by_form_id( $form_id );

				if ( count( $feeds ) > 1 ) :

					foreach ( $feeds as $feed ) :

						printf(
							'<option value="%s">%s</option>',
							esc_attr( $feed->config_id ),
							get_the_title( $feed->config_id )
						);

					endforeach;

				endif;

				?>
			</select>
		</li>
		<?php
	}
}
