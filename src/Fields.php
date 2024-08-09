<?php
/**
 * Fields
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

use GF_Fields;
use Pronamic\WordPress\Pay\Core\Util as Core_Util;

/**
 * Title: WordPress pay extension Gravity Forms fields
 * Description:
 * Copyright: 2005-2024 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.0
 * @since   1.0.0
 */
class Fields {
	/**
	 * Issuers field type.
	 */
	const ISSUERS_FIELD_TYPE = 'ideal_issuer_drop_down';

	/**
	 * Payment methods field type.
	 */
	const PAYMENT_METHODS_FIELD_TYPE = 'pronamic_pay_payment_method_selector';

	/**
	 * Construct and initialize custom Gravity Forms fields.
	 */
	public function __construct() {
		// Enable credit card field.
		add_filter( 'gform_enable_credit_card_field', '__return_true' );

		// Register custom fields.
		if ( Core_Util::class_method_exists( 'GF_Fields', 'register' ) ) {
			GF_Fields::register( new IssuersField() );

			// We do some voodoo in the payment methods field class which requires the `gform_gf_field_create` filter added in Gravity Forms 1.9.19.
			if ( GravityForms::version_compare( '1.9.19', '>=' ) ) {
				GF_Fields::register( new PaymentMethodsField() );
			}
		}

		// Add extra fields settings.
		add_action( 'gform_field_standard_settings', [ $this, 'field_standard_settings' ], 10, 2 );
	}

	/**
	 * Add pay field group to the Gravity Forms field groups.
	 *
	 * @see   https://github.com/wp-premium/gravityforms/blob/1.9.19/form_detail.php#L2353-L2368
	 * @since 1.4.7
	 *
	 * @param array $field_groups Field groups.
	 *
	 * @return array
	 */
	public static function add_pay_field_group( $field_groups ) {
		if ( ! isset( $field_groups['pronamic_pay_fields'] ) ) {
			$field_groups['pronamic_pay_fields'] = [
				'name'   => 'pronamic_pay_fields',
				'label'  => __( 'Payment Fields', 'pronamic_ideal' ),
				'fields' => [],
			];
		}

		return $field_groups;
	}

	/**
	 * Field standard settings.
	 *
	 * @link https://github.com/wp-premium/gravityforms/blob/1.9.19/form_detail.php#L525
	 *
	 * @param int $position Position of the field settings.
	 * @param int $form_id  Form ID.
	 */
	public function field_standard_settings( $position, $form_id ) {
		if ( 10 !== $position ) {
			return;
		}

		$feeds = FeedsDB::get_feeds_by_form_id( $form_id );

		if ( empty( $feeds ) ) {
			return;
		}

		?>
		<li class="pronamic_pay_config_field_setting field_setting">
			<label for="pronamic_pay_config_field" class="section_label">
				<?php \esc_html_e( 'Payment Gateway Configuration', 'pronamic_ideal' ); ?>

				<?php \gform_tooltip( 'form_field_pronamic_pay_config' ); ?>
			</label>

			<select id="pronamic_pay_config_field"
					onchange="SetFieldProperty( 'pronamicPayConfigId', jQuery( this ).val() );" class="fieldwidth-3">
				<option value=""><?php \esc_html_e( '— Use Payment Feed Setting —', 'pronamic_ideal' ); ?></option>
				<?php

				$config_ids = \wp_list_pluck( $feeds, 'config_id' );

				$config_ids = \array_unique( $config_ids );

				foreach ( $config_ids as $config_id ) {
					\printf(
						'<option value="%s">%s</option>',
						\esc_attr( $config_id ),
						\esc_html( \get_the_title( $config_id ) )
					);
				}

				?>
			</select>
		</li>

		<li class="pronamic_pay_display_field_setting field_setting">
			<label for="pronamic_pay_display_field" class="section_label">
				<?php \esc_html_e( 'Display Mode', 'pronamic_ideal' ); ?>

				<?php \gform_tooltip( 'form_field_pronamic_pay_display' ); ?>
			</label>

			<select id="pronamic_pay_display_field" onchange="SetFieldProperty( 'pronamicPayDisplayMode', jQuery( this ).val() );" class="fieldwidth-3">
				<option value=""><?php \esc_html_e( '— Use field default —', 'pronamic_ideal' ); ?></option>
				<option value="select"><?php echo \esc_html_x( 'Select', 'Field display mode', 'pronamic_ideal' ); ?></option>
				<option value="icons-24"><?php echo \esc_html_x( 'List with icons', 'Field display mode', 'pronamic_ideal' ); ?></option>
				<option value="icons-64"><?php echo \esc_html_x( 'Small icons', 'Field display mode', 'pronamic_ideal' ); ?></option>
				<option value="icons-125"><?php echo \esc_html_x( 'Large icons', 'Field display mode', 'pronamic_ideal' ); ?></option>
			</select>
		</li>
		<?php
	}
}
