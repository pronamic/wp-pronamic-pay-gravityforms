<?php
/**
 * Payment methods field
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

use GF_Field;
use GF_Field_Select;
use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Plugin;

/**
 * Title: WordPress pay extension Gravity Forms payment methods
 * Description:
 * Copyright: 2005-2024 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.6.1
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
	public function __construct( $properties = [] ) {
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
		if ( ! has_action( 'gform_editor_js_set_default_values', [ $this, 'editor_js_set_default_values' ] ) ) {
			add_action( 'gform_editor_js_set_default_values', [ $this, 'editor_js_set_default_values' ] );
		}

		// Filters.
		if ( ! has_filter( 'gform_gf_field_create', [ $this, 'field_create' ] ) ) {
			add_filter( 'gform_gf_field_create', [ $this, 'field_create' ], 10, 2 );
		}

		if ( ! has_filter( 'gform_get_field_value', [ $this, 'get_field_value' ] ) ) {
			add_filter( 'gform_get_field_value', [ $this, 'get_field_value' ], 10, 3 );
		}

		if ( ! has_filter( 'gform_form_update_meta', [ __CLASS__, 'form_update_meta' ] ) ) {
			add_filter( 'gform_form_update_meta', [ __CLASS__, 'form_update_meta' ], 10, 3 );
		}

		if ( ! has_filter( 'gform_pre_render', [ __CLASS__, 'form_pre_render' ] ) ) {
			add_filter( 'gform_pre_render', [ __CLASS__, 'form_pre_render' ], 10, 3 );
		}

		// Admin.
		if ( is_admin() ) {
			$this->inputType = 'checkbox';

			/*
			 * Inputs property must be iterable.
			 *
			 * @link https://github.com/wp-premium/gravityforms/blob/2.4.17/common.php#L804-L805
			 */
			$this->inputs = [];

			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$page = \array_key_exists( 'page', $_GET ) ? \sanitize_text_field( \wp_unslash( $_GET['page'] ) ) : null;

			if ( empty( $this->formId ) && 'gf_edit_forms' === $page ) {
				$this->formId = \array_key_exists( 'id', $_GET ) ? \sanitize_text_field( \wp_unslash( $_GET['id'] ) ) : null;
			}

			// phpcs:enable WordPress.Security.NonceVerification.Recommended
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

		if ( false === strpos( $this->cssClass, 'gf_list_' ) && in_array( $this->pronamicPayDisplayMode, [ 'icons-64', 'icons-125' ], true ) ) {
			$this->cssClass .= ' gf_list_2col';
		}
	}

	/**
	 * Filter the GF_Field object after it is created.
	 *
	 * @param \GF_Field $field      A field object.
	 * @param array     $properties An array of field properties used to generate the field object.
	 *
	 * @return \GF_Field
	 *
	 * @link    https://docs.gravityforms.com/gform_gf_field_create/
	 */
	public function field_create( $field, $properties ) {
		// Check field type.
		if ( $this->type !== $field->type ) {
			return $field;
		}

		// Check field object class.
		if ( ! ( $field instanceof \Pronamic\WordPress\Pay\Extensions\GravityForms\PaymentMethodsField ) ) {
			$field = new self( $properties );
		}

		return $field;
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
		return [
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
		return 'gform-icon--credit-card';
	}

	/**
	 * Get the gateways for this field.
	 *
	 * @return array
	 */
	private function get_gateways() {
		$gateways = [];

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
				[ 'Pronamic\WordPress\Pay\Plugin', 'get_gateway' ],
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
		// Prevent HTTP requests in forms list.
		if ( \doing_filter( 'gform_form_actions' ) ) {
			$this->choices = [];

			return;
		}

		// Gateway available payment methods.
		$payment_methods = $this->get_gateway_payment_methods();

		// Choices.
		$choices = [];

		// Gravity Forms.
		if ( is_array( $this->choices ) ) {
			foreach ( $this->choices as $choice ) {
				$value = $choice['value'];

				$choice['builtin'] = isset( $payment_methods[ $value ] );

				$choice['enabled'] = isset( $choice['isSelected'] ) && $choice['isSelected'];

				$choices[ $value ] = $choice;
			}
		}

		// Built-in.
		foreach ( $payment_methods as $value => $label ) {
			// Only add built-in payment if it's not already set.
			if ( ! isset( $choices[ $value ] ) ) {
				$choices[ $value ] = [
					'value'      => $value,
					'text'       => $label,
					'isSelected' => false,
					'builtin'    => true,
					'enabled'    => false,
				];
			}
		}

		// Set choices.
		$this->choices = array_values( $choices );
	}

	/**
	 * Filter Gravity Forms enabled choice.
	 *
	 * @param array $choice Choice.
	 *
	 * @return boolean true if `enabled` is set and true, false otherwise.
	 */
	public static function filter_choice_is_enabled( $choice ) {
		return is_array( $choice ) && isset( $choice['enabled'] ) && $choice['enabled'];
	}

	/**
	 * Unselect the specified choice.
	 *
	 * @param array $choice Choice.
	 */
	public static function unselect_choice( &$choice ) {
		$choice['isSelected'] = false;
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
	public function get_field_input( $form, $value = null, $entry = null ) {
		// Filter choices for display.
		$choices = $this->choices;

		$display_choices = array_filter( $choices, [ __CLASS__, 'filter_choice_is_enabled' ] );

		// Select first item.
		\array_walk( $display_choices, [ __CLASS__, 'unselect_choice' ] );

		$index = \array_key_first( $display_choices );

		if ( null !== $index ) {
			$display_choices[ $index ]['isSelected'] = true;
		}

		$this->choices = \array_values( $display_choices );

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
						'_' => '-',
						' ' => '-',
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

					// Loop payment methods.
					foreach ( $this->choices as $choice ) {
						// Icon file name.
						$payment_method = $choice['value'];

						// Radio input.
						$label_content = \sprintf( '<span>%s</span>', esc_html( $choice['text'] ) );

						if ( \array_key_exists( $payment_method, PaymentMethods::get_payment_methods() ) ) {
							$icon_url = PaymentMethods::get_icon_url( $payment_method );

							if ( null !== $icon_url ) {
								$label_content = \sprintf(
									'<img src="%2$s" alt="%1$s" /><span>%1$s</span>',
									\esc_html( $choice['text'] ),
									\esc_url( $icon_url )
								);
							}
						}

						\printf(
							'<li class="gchoice_%1$s_%2$s_%3$s"><input type="radio" id="choice_%1$s_%2$s_%3$s" name="input_%2$s" value="%3$s" %5$s/> <label for="choice_%1$s_%2$s_%3$s">%4$s</label></li>',
							\esc_attr( $this->formId ),
							\esc_attr( $this->id ),
							\esc_attr( $choice['value'] ),
							\wp_kses_post( $label_content ),
							\checked( $choice['value'], $value, false )
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
				}

				.gform_wrapper <?php echo esc_html( $field_css_id ); ?> .gfield_radio li img {
					display: block;

					width: <?php echo esc_html( 'icons-125' === $this->pronamicPayDisplayMode ? $dimensions[0] . 'px' : 'auto' ); ?>;
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
					width: <?php echo esc_html( 128 * 2 ); ?>px;
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

		// Reset choices.
		$this->choices = $choices;

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
	 * Get value entry detail.
	 *
	 * @param array|string $value    Value.
	 * @param string       $currency Currency.
	 * @param bool         $use_text Use text from choices.
	 * @param string       $format   Format.
	 * @param string       $media    Media.
	 *
	 * @return string
	 */
	public function get_value_entry_detail( $value, $currency = '', $use_text = false, $format = 'html', $media = 'screen' ) {
		$use_text = true;

		return parent::get_value_entry_detail( $value, $currency, $use_text, $format, $media );
	}

	/**
	 * Get field value.
	 *
	 * @param string|array  $value Field value.
	 * @param array         $entry Entry.
	 * @param GF_Field|null $field Field.
	 *
	 * @return string|array
	 */
	public function get_field_value( $value, $entry, $field ) {
		if ( ! \is_object( $field ) ) {
			return $value;
		}

		if ( self::TYPE !== $field->type ) {
			return $value;
		}

		// Check if already a string value.
		if ( ! \is_array( $value ) ) {
			return $value;
		}

		$value = \array_shift( $value );

		return $value;
	}

	/**
	 * Filter form update meta.
	 *
	 * @param array<mixed> $form_meta Form meta.
	 * @param int          $form_id   Form id.
	 * @param string       $meta_name Meta name.
	 *
	 * @return array<mixed>
	 */
	public static function form_update_meta( $form_meta, $form_id, $meta_name ) {
		// Check meta name.
		if ( 'display_meta' !== $meta_name ) {
			return $form_meta;
		}

		// Set input type.
		foreach ( $form_meta['fields'] as &$field ) {
			if ( self::TYPE !== $field['type'] ) {
				continue;
			}

			$field->inputType = 'select';
		}

		return $form_meta;
	}

	/**
	 * Form pre render.
	 *
	 * @param array<string, mixed> $form         Form.
	 * @param bool                 $ajax         Whether or not to use AJAX.
	 * @param array                $field_values Field values.
	 * @return array<string, mixed>
	 */
	public static function form_pre_render( $form, $ajax, $field_values ) {
		foreach ( $form['fields'] as $key => &$field ) {
			// Check field type.
			if ( self::TYPE !== $field->type ) {
				continue;
			}

			// Remove unselected choices.
			$display_choices = array_filter( $field['choices'], [ __CLASS__, 'filter_choice_is_enabled' ] );

			// Select first item.
			\array_walk( $display_choices, [ __CLASS__, 'unselect_choice' ] );

			$index = \array_key_first( $display_choices );

			if ( null !== $index ) {
				$display_choices[ $index ]['isSelected'] = true;
			}


			// Set field choices.
			$field['choices'] = $display_choices;
		}

		return $form;
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
		return [
			'group' => 'pronamic_pay_fields',
			'text'  => __( 'Payment Method', 'pronamic_ideal' ),
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
	 * Get gateway available payment methods.
	 *
	 * @return array
	 */
	private function get_gateway_payment_methods() {
		$gateways = $this->get_gateways();

		$payment_methods = [];

		foreach ( $gateways as $gateway ) {
			$methods = $gateway->get_payment_methods(
				[
					'status' => [ '', 'active' ],
				]
			);

			foreach ( $methods as $method ) {
				$payment_methods[ $method->get_id() ] = $method->get_name();
			}
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
