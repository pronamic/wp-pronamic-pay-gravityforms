<?php
/**
 * Payment methods field
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2019 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

use GF_Field_Select;
use GFForms;
use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Plugin;

/**
 * Title: WordPress pay extension Gravity Forms payment methods
 * Description:
 * Copyright: 2005-2019 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.1.10
 * @since   1.4.7
 *
 * @property int        $pronamicPayConfigId Added by admin.js.
 * @property string     $inputType           https://github.com/wp-premium/gravityforms/blob/2.3.2/includes/fields/class-gf-field.php#L769-L777
 * @property array|null $inputs              https://github.com/wp-premium/gravityforms/blob/2.3.2/includes/fields/class-gf-field.php#L416-L423
 * @property int        $formId              https://github.com/wp-premium/gravityforms/blob/2.3.2/includes/fields/class-gf-field.php#L1044
 * @property bool       $enableChoiceValue   https://github.com/wp-premium/gravityforms/search?q=enableChoiceValue
 * @property array      $choices             https://github.com/wp-premium/gravityforms/search?q=%22%24this-%3Echoices%22
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
	 * @param array $properties Field properties.
	 */
	public function __construct( $properties = array() ) {
		parent::__construct( $properties );

		/*
		 * The `inputType` of the payment methods field was in the past set to `checkbox`
		 * this results in a `GF_Field_Checkbox` field with additional inputs, but we what
		 * need is a payment methods field without additional inputs.
		 *
		 * @link https://github.com/wp-premium/gravityforms/blob/1.9.19/includes/fields/class-gf-fields.php#L60-L86
		 */
		$this->inputs = null;

		// Actions.
		if ( ! has_action( 'gform_editor_js_set_default_values', array( $this, 'editor_js_set_default_values' ) ) ) {
			add_action( 'gform_editor_js_set_default_values', array( $this, 'editor_js_set_default_values' ) );
		}

		// Admin.
		if ( is_admin() ) {
			$this->inputType = 'checkbox';

			if ( empty( $this->formId ) && 'gf_edit_forms' === filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING ) ) {
				$this->formId = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_STRING );
			}
		}

		// Choices.
		$this->enableChoiceValue = true;

		if ( isset( $this->formId ) ) {
			$this->set_choices( $this->formId );
		}

		// Set default display mode.
		if ( ! isset( $this->pronamicPayDisplayMode ) ) {
			$this->pronamicPayDisplayMode = 'select';
		}

		// Add display mode CSS classes.
		if ( false === strpos( $this->cssClass, 'pronamic_pay_display_icons' ) && 'icons' === substr( $this->pronamicPayDisplayMode, 0, 5 ) ) {
			$this->cssClass .= ' pronamic_pay_display_icons';
		}

		if ( false === strpos( $this->cssClass, 'gf_list_2col' ) && in_array( $this->pronamicPayDisplayMode, array( 'icons-64', 'icons-125' ), true ) ) {
			$this->cssClass .= ' gf_list_2col';
		}
	}

	/**
	 * Get form editor field settings for this field.
	 *
	 * @link https://github.com/wp-premium/gravityforms/blob/2.0.3/includes/fields/class-gf-field-select.php#L16-L35
	 * @link https://github.com/wp-premium/gravityforms/blob/2.0.3/includes/fields/class-gf-field.php#L144-L151
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
			'placeholder_setting',
			'description_setting',
			'css_class_setting',
			'rules_setting',
			'pronamic_pay_config_field_setting',
			'pronamic_pay_display_field_setting',
		);
	}

	/**
	 * Get the gateways for this field.
	 *
	 * @return array
	 */
	private function get_gateways() {
		$gateways = array();

		$feeds = FeedsDB::get_feeds_by_form_id( $this->formId );

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

		// Choices.
		$choices = array();

		// Gravity Forms.
		if ( is_array( $this->choices ) ) {
			foreach ( $this->choices as $choice ) {
				$value = $choice['value'];

				$choice['builtin'] = isset( $payment_methods[ $value ] );

				$choices[ $value ] = $choice;
			}
		}

		// Built-in.
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

		// Admin.
		// Page `form_settings` can be suffixed with subview, see https://github.com/wp-premium/gravityforms/blob/master/gravityforms.php#L2505-L2507.
		$page = GFForms::get_page();

		if ( ! $page || ( 'form_editor' !== $page && false === strpos( $page, 'form_settings' ) ) ) {
			$choices = array_filter( $choices, array( $this, 'filter_choice_is_selected' ) );
			$choices = array_map( array( $this, 'unselect_choice' ), $choices );
		}

		// Set choices.
		$this->choices = array_values( $choices );
	}

	/**
	 * Filter Gravity Forms selected choice.
	 *
	 * @param array $choice Choice.
	 *
	 * @return boolean true if 'isSelected' is set and true, false otherwise.
	 */
	public function filter_choice_is_selected( $choice ) {
		return is_array( $choice ) && isset( $choice['isSelected'] ) && $choice['isSelected'];
	}

	/**
	 * Unselect the specified choice.
	 *
	 * @param array $choice Choice.
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
	 * @link https://github.com/wp-premium/gravityforms/blob/2.0.3/includes/fields/class-gf-field-select.php#L41-L60
	 * @link https://github.com/wp-premium/gravityforms/blob/2.0.3/includes/fields/class-gf-field.php#L182-L193
	 *
	 * @param array  $form  Form.
	 * @param string $value Field value.
	 * @param array  $entry Entry.
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

		$field_css_id = sprintf( '#field_%1$s_%2$s', $this->formId, $this->id );

		if ( ! is_admin() && 'icons' === substr( $this->pronamicPayDisplayMode, 0, 5 ) ) {
			ob_start();

			?>

			<div class="ginput_container ginput_container_radio gfield_trigger_change">
				<ul class="gfield_radio input_<?php echo esc_attr( $this->formId ); ?>_<?php echo esc_attr( $this->id ); ?>">
					<?php

					// Icon filename replacements.
					$replacements = array(
						'_' => '-',
						' ' => '-',
					);

					// Icon file and size.
					switch ( $this->pronamicPayDisplayMode ) {
						case 'icons-24':
							$dimensions = array( 24, 24 );

							break;
						case 'icons-64':
							$dimensions = array( 64, 64 );

							break;
						case 'icons-125':
						default:
							$dimensions = array( 125, 60 );
					}

					// Loop payment methods.
					foreach ( $this->choices as $choice ) {
						// Icon file name.
						$payment_method = strtr( strtolower( $choice['value'] ), $replacements );

						if ( false !== stripos( $payment_method, 'test' ) || false !== stripos( $payment_method, 'simulation' ) ) {
							$payment_method = 'test';
						}

						$icon_path = sprintf(
							'%s/icon-%s.png',
							$payment_method,
							implode( 'x', $dimensions )
						);

						// Radio input.
						$label_content = sprintf( '<span>%s</span>', esc_html( $choice['text'] ) );

						if ( file_exists( plugin_dir_path( Plugin::$file ) . 'images/' . $icon_path ) ) {
							$icon_url = plugins_url( 'images/' . $icon_path, Plugin::$file );

							$label_content = sprintf(
								'<img src="%2$s" alt="%1$s" srcset="%3$s 2x, %4$s 3x, %5$s 4x" /><span>%1$s</span>',
								esc_html( $choice['text'] ),
								esc_url( $icon_url ),
								esc_url( str_replace( '.png', '@2x.png', $icon_url ) ),
								esc_url( str_replace( '.png', '@3x.png', $icon_url ) ),
								esc_url( str_replace( '.png', '@4x.png', $icon_url ) )
							);
						}

						printf(
							'<li class="gchoice_%1$s_%2$s_%3$s"><input type="radio" id="choice_%1$s_%2$s_%3$s" name="input_%2$s" value="%3$s" /> <label for="choice_%1$s_%2$s_%3$s">%4$s</label></li>',
							esc_attr( $this->formId ),
							esc_attr( $this->id ),
							esc_attr( $choice['value'] ),
							wp_kses_post( $label_content )
						);
					}

					?>
				</ul>
			</div>

			<style>
				.gform_wrapper <?php echo esc_html( $field_css_id ); ?> .gfield_radio li {
					display: inline-block;
					width: 50%;

					margin: 0;

					vertical-align: baseline;
				}

				.gform_wrapper <?php echo esc_html( $field_css_id ); ?> .gfield_radio li img {
					display: block;

					width: <?php echo esc_html( $dimensions[0] ); ?>px;
					height: <?php echo esc_html( $dimensions[1] ); ?>px;
				}

				.gform_wrapper <?php echo esc_html( $field_css_id ); ?> .gfield_radio li label {
					display: inline-block;
					max-width: 100%;
					width: 100%;

					margin: 0;
				}

				.gform_wrapper <?php echo esc_html( $field_css_id ); ?> .gfield_radio li input[type="radio"] {
					display: none;
				}

				<?php

				switch ( $this->pronamicPayDisplayMode ) {
					case 'icons-24':
						?>
				.gform_wrapper <?php echo esc_html( $field_css_id ); ?> .gfield_radio li label {
					margin: 0;
				}

				.gform_wrapper <?php echo esc_html( $field_css_id ); ?> .gfield_radio li label {
					width: auto;
				}

				.gform_wrapper <?php echo esc_html( $field_css_id ); ?> .gfield_radio li label span {
					display: inline-block;
				}

				.gform_wrapper <?php echo esc_html( $field_css_id ); ?> .gfield_radio li img {
					display: inline;
					vertical-align: middle;

					margin-right: 10px;
				}

				.gform_wrapper <?php echo esc_html( $field_css_id ); ?> .gfield_radio li input[type="radio"] {
					display: inline-block;

					margin-top: 0;
				}

						<?php

						break;
					case 'icons-64':
						?>
				.gform_wrapper <?php echo esc_html( $field_css_id ); ?> .gfield_radio {
					width: <?php echo esc_html( ( 64 * 2 ) + 126 ); ?>px;
				}

				.gform_wrapper <?php echo esc_html( $field_css_id ); ?> .gfield_radio li {
					text-align: center;
				}

				.gform_wrapper <?php echo esc_html( $field_css_id ); ?> .gfield_radio li label {
					margin: 0;

					padding-bottom: 10px;

					color: #bbb;
					font-weight: normal;

					border-radius: 4px;
				}

				.gform_wrapper <?php echo esc_html( $field_css_id ); ?> .gfield_radio li:hover label {
					background: #efefef;
				}

				.gform_wrapper <?php echo esc_html( $field_css_id ); ?> .gfield_radio li img {
					margin: 10px auto;
				}

				.gform_wrapper <?php echo esc_html( $field_css_id ); ?> .gfield_radio li span {
					display: table-cell;
					width: 127px;
					height: 105px;

					padding: 2px;

					text-align: center;
					vertical-align: bottom;

					white-space: normal;
				}

				.gform_wrapper <?php echo esc_html( $field_css_id ); ?> .gfield_radio li img + span {
					display: block;
					height: auto;
				}

				.gform_wrapper <?php echo esc_html( $field_css_id ); ?> .gfield_radio li input[type="radio"]:checked ~ label {
					font-weight: bold;
					color: #555;

					background: #efefef;
				}

						<?php

						break;
					case 'icons-125':
						?>

				.gform_wrapper <?php echo esc_html( $field_css_id ); ?> .gfield_radio {
					width: <?php echo esc_html( ( 125 * 2 ) + 25 ); ?>px;

					font-size: 16px;
				}

				.gform_wrapper <?php echo esc_html( $field_css_id ); ?> .gfield_radio li {
					float: left;
					width: auto;

					margin: 0 4px 4px 0;
				}

				.gform_wrapper <?php echo esc_html( $field_css_id ); ?> .gfield_radio li label {
					display: block;

					height: 68px;

					padding: 3px;

					border: 1px solid #bbb;
					border-radius: 4px;

					background: #fff;
				}

				.gform_wrapper <?php echo esc_html( $field_css_id ); ?> .gfield_radio li:hover label {
					background: #efefef;
				}

				.gform_wrapper <?php echo esc_html( $field_css_id ); ?> .gfield_radio li input[type="radio"]:checked ~ label {
					padding: 2px;

					border-width: 2px;
					border-color: #555;

					background: #efefef;
				}

				.gform_wrapper <?php echo esc_html( $field_css_id ); ?> .gfield_radio li label span {
					display: table-cell;
					width: 125px;
					height: 60px;

					text-align: center;
					vertical-align: middle;

					white-space: normal;
				}

				.gform_wrapper <?php echo esc_html( $field_css_id ); ?> .gfield_radio li label img + span {
					display: none;
				}

						<?php
				}
				?>

			</style>

			<?php

			$input = ob_get_clean();
		}

		if ( is_admin() ) {
			$feeds = FeedsDB::get_feeds_by_form_id( $form['id'] );

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
	 * @link https://github.com/wp-premium/gravityforms/blob/1.9.19/includes/fields/class-gf-field.php#L106-L113
	 * @link https://github.com/wp-premium/gravityforms/blob/1.9.19/includes/fields/class-gf-field-select.php#L12-L14
	 *
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'Payment Method', 'pronamic_ideal' );
	}

	/**
	 * Get form editor button.
	 *
	 * @link https://github.com/wp-premium/gravityforms/blob/1.9.19/includes/fields/class-gf-field.php#L115-L129
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
	 * @link https://github.com/wp-premium/gravityforms/blob/1.9.19/form_detail.php#L2353-L2368
	 * @link https://github.com/wp-premium/gravityforms/blob/1.9.19/includes/fields/class-gf-field.php#L617-L652
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
	 * @link https://github.com/wp-premium/gravityforms/blob/2.0.3/js.php#L587-L599
	 * @link https://github.com/wp-premium/gravityforms/blob/2.0.3/js/forms.js#L38-L43
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
