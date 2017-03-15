<?php

/**
 * Title: WordPress pay extension Gravity Forms fields
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.6.3
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_GravityForms_Fields {
	/**
	 * Construct and intialize custom Gravity Forms fields.
	 */
	public function __construct() {
		// Enable credit card field
		add_filter( 'gform_enable_credit_card_field', '__return_true' );

		// Register custom fields
		if ( Pronamic_WP_Pay_Class::method_exists( 'GF_Fields', 'register' ) ) {
			GF_Fields::register( new Pronamic_WP_Pay_Extensions_GravityForms_IssuersField() );

			// We do some voodoo in the payment methods field class which requires the `gform_gf_field_create` filter added in Gravity Forms 1.9.19.
			if ( Pronamic_WP_Pay_Extensions_GravityForms_GravityForms::version_compare( '1.9.19', '>=' ) ) {
				GF_Fields::register( new Pronamic_WP_Pay_Extensions_GravityForms_PaymentMethodsField() );
			}
		}

		// Add extra fields settings
		add_action( 'gform_field_standard_settings', array( $this, 'field_standard_settings' ), 10, 2 );
	}

	/**
	 * Add pay field group to the Gravity Forms field groups.
	 *
	 * @see https://github.com/wp-premium/gravityforms/blob/1.9.19/form_detail.php#L2353-L2368
	 * @since 1.4.7
	 * @param array $field_groups
	 * @return array
	 */
	public static function add_pay_field_group( $field_groups ) {
		if ( ! isset( $field_groups['pronamic_pay_fields'] ) ) {
			$field_groups['pronamic_pay_fields'] = array(
				'name'   => 'pronamic_pay_fields',
				'label'  => __( 'Payment Fields', 'pronamic_ideal' ),
				'fields' => array(),
			);
		}

		return $field_groups;
	}

	/**
	 * Field standard settings.
	 *
	 * @see https://github.com/wp-premium/gravityforms/blob/1.9.19/form_detail.php#L525
	 * @param int $position position of the field settings
	 * @param int $form_id current form ID
	 */
	public function field_standard_settings( $position, $form_id ) {
		if ( 10 !== $position ) {
			return;
		}

		$feeds = get_pronamic_gf_pay_feeds_by_form_id( $form_id );

		if ( empty( $feeds ) ) {
			return;
		}

		?>
		<li class="pronamic_pay_config_field_setting field_setting">
			<label for="pronamic_pay_config_field">
				<?php esc_html_e( 'Payment Gateway Configuration', 'pronamic_ideal' ); ?>

				<?php gform_tooltip( 'form_field_pronamic_pay_config' ) ?>
			</label>

			<select id="pronamic_pay_config_field" onchange="SetFieldProperty( 'pronamicPayConfigId', jQuery( this ).val() );" class="fieldwidth-3">
				<option value=""><?php esc_html_e( '— Use Payment Feed Setting —', 'pronamic_ideal' ); ?></option>
				<?php

				foreach ( $feeds as $feed ) {
					printf(
						'<option value="%s">%s</option>',
						esc_attr( $feed->config_id ),
						get_the_title( $feed->config_id )
					);
				}

				?>
			</select>
		</li>
		<?php
	}
}
