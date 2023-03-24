<?php
/**
 * Issuers field
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2023 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

use GF_Field_Select;
use Pronamic\WordPress\Pay\Core\Gateway;
use Pronamic\WordPress\Pay\Fields\IDealIssuerSelectField;
use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Plugin;

/**
 * Title: WordPress pay extension Gravity Forms issuers field
 * Description:
 * Copyright: 2005-2023 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.1.10
 * @since   1.4.7
 *
 * @property int   $pronamicPayConfigId Added by admin.js.
 * @property int   $formId              https://github.com/wp-premium/gravityforms/blob/2.3.2/includes/fields/class-gf-field.php#L1044
 * @property array $choices             https://github.com/wp-premium/gravityforms/search?q=%22%24this-%3Echoices%22
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
	 * @param array $properties Properties.
	 */
	public function __construct( $properties = [] ) {
		parent::__construct( $properties );

		// Actions.
		if ( ! has_action( 'gform_editor_js_set_default_values', [ __CLASS__, 'editor_js_set_default_values' ] ) ) {
			add_action( 'gform_editor_js_set_default_values', [ __CLASS__, 'editor_js_set_default_values' ] );
		}

		if (
			! isset( $this->formId )
				&&
			wp_doing_ajax()
				&&
			filter_has_var( INPUT_POST, 'form_id' )
				&&
			'rg_add_field' === filter_input( INPUT_POST, 'action' )
				&&
			false !== strpos( filter_input( INPUT_POST, 'field' ), self::TYPE )
		) {
			$this->formId = filter_input( INPUT_POST, 'form_id', FILTER_SANITIZE_NUMBER_INT );
		}

		// Choices.
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

		if ( false === strpos( $this->cssClass, 'gf_list_2col' ) && in_array( $this->pronamicPayDisplayMode, [ 'icons-64', 'icons-125' ], true ) ) {
			$this->cssClass .= ' gf_list_2col';
		}
	}

	/**
	 * Get form editor field settings for this field.
	 *
	 * @link https://github.com/wp-premium/gravityforms/blob/2.0.3/includes/fields/class-gf-field-select.php#L16-L35
	 * @link https://github.com/wp-premium/gravityforms/blob/2.0.3/includes/fields/class-gf-field.php#L144-L151
	 * @return array
	 */
	public function get_form_editor_field_settings() {
		return [
			'conditional_logic_field_setting',
			'error_message_setting',
			'enable_enhanced_ui_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'size_setting',
			'placeholder_setting',
			'description_setting',
			'css_class_setting',
			'rules_setting',
			'pronamic_pay_config_field_setting',
			'pronamic_pay_display_field_setting',
		];
	}

	/**
	 * Returns the field's form editor icon.
	 *
	 * This could be an icon url or a gform-icon class.
	 *
	 * @link https://github.com/pronamic/gravityforms/blob/2.7.3/includes/fields/class-gf-field-address.php#L51-L62
	 * @return string
	 */
	public function get_form_editor_field_icon() {
		return 'gform-icon--quiz';
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
				// Check if feed is active.
				if ( '0' === get_post_meta( $feed->id, '_pronamic_pay_gf_feed_active', true ) ) {
					continue;
				}

				$gateway = Plugin::get_gateway( $feed->config_id );

				if ( null === $gateway ) {
					continue;
				}

				// Always use iDEAL payment method for issuer field.
				$issuer_field = $gateway->first_payment_method_field( PaymentMethods::IDEAL, IDealIssuerSelectField::class );

				if ( null === $issuer_field ) {
					continue;
				}

				/**
				 * The iDEAL issuer field options can be requested from the
				 * gateway and that can result in exceptions. In this case,
				 * that's no problem and we'll move on to the next
				 * feed/gateway.
				 *
				 * @link https://github.com/pronamic/wp-pronamic-pay-gravityforms/issues/10
				 */
				try {
					$options = $issuer_field->get_options();
				} catch ( \Exception $e ) {
					continue;
				}

				return $gateway;
			}
		}

		return $gateway;
	}

	/**
	 * Set the issuer choices for this issuers field.
	 *
	 * @param int $form_id Gravity Forms form ID.
	 */
	private function set_choices( $form_id ) {
		$this->choices = [];

		// Prevent HTTP requests in forms list.
		if ( \doing_filter( 'gform_form_actions' ) ) {
			return;
		}

		$gateway = $this->get_gateway();

		if ( ! $gateway ) {
			return;
		}

		// Always use iDEAL payment method for issuer field.
		$issuer_field = $gateway->first_payment_method_field( PaymentMethods::IDEAL, IDealIssuerSelectField::class );

		if ( null === $issuer_field ) {
			return;
		}

		/**
		 * The iDEAL issuer field options can be requested from the
		 * gateway and that can result in exceptions. In this case,
		 * that's no problem and we'll move on to the next
		 * feed/gateway.
		 *
		 * @link https://github.com/pronamic/wp-pronamic-pay-gravityforms/issues/10
		 */
		try {
			/**
			 * Gravity Forms has no support for <optgroup>  elements.
			 *
			 * @link https://github.com/pronamic/wp-pronamic-pay/issues/154#issuecomment-1183309350
			 */
			$options = $issuer_field->get_flat_options();

			foreach ( $options as $option ) {
				/**
				 * Gravity Forms automatically fills an empty value with the label.
				 * For a first empty choice option, Gravity Forms works with a
				 * `placeholder` property.
				 * 
				 * @link https://github.com/pronamic/wp-pronamic-pay-gravityforms/issues/19
				 */
				if ( '' === $option->value ) {
					$this->placeholder = $option->label;

					continue;
				}

				$this->choices[] = [
					'value' => $option->value,
					'text'  => $option->label,
				];
			}
		} catch ( \Exception $e ) {
			return;
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
					$replacements = [
						' bankiers' => '',
						' '         => '-',
					];

					// Icon file and size.
					switch ( $this->pronamicPayDisplayMode ) {
						case 'icons-24':
							$dimensions = [ 24, 24 ];

							break;
						case 'icons-64':
							$dimensions = [ 64, 64 ];

							break;
						case 'icons-125':
						default:
							$dimensions = [ 125, 60 ];
					}

					$images_path = plugin_dir_path( Plugin::$file ) . 'images/';

					// Loop issuers.
					foreach ( $this->choices as $choice ) {
						// Ignore choices without value.
						if ( \array_key_exists( 'value', $choice ) && empty( $choice['value'] ) ) {
							continue;
						}

						// Icon file name.
						$issuer = strtr( strtolower( $choice['text'] ), $replacements );

						if ( false !== stripos( $issuer, 'test' ) || false !== stripos( $issuer, 'simulation' ) ) {
							$issuer = 'test';
						}

						if ( ! is_dir( $images_path . $issuer ) && is_dir( $images_path . $issuer . '-bank' ) ) {
							$issuer .= '-bank';
						}

						$icon_path = sprintf(
							'%s/icon-%s.png',
							$issuer,
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
	 * @link https://github.com/wp-premium/gravityforms/blob/1.9.19/includes/fields/class-gf-field.php#L106-L113
	 * @link https://github.com/wp-premium/gravityforms/blob/1.9.19/includes/fields/class-gf-field-select.php#L12-L14
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'Issuer', 'pronamic_ideal' );
	}

	/**
	 * Get form editor button.
	 *
	 * @link https://github.com/wp-premium/gravityforms/blob/1.9.19/includes/fields/class-gf-field.php#L115-L129
	 * @return array
	 */
	public function get_form_editor_button() {
		return [
			'group' => 'pronamic_pay_fields',
			'text'  => __( 'Issuer', 'pronamic_ideal' ),
		];
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
	 * Editor JavaScript default field values.
	 *
	 * @link https://github.com/wp-premium/gravityforms/blob/2.0.3/js.php#L587-L599
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
