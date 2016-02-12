<?php

/**
 * Title: WordPress pay extension Gravity Forms fields
 * Description:
 * Copyright: Copyright (c) 2005 - 2016
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.4.2
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_GravityForms_Fields {
	/**
	 * Bootstrap
	 */
	public static function bootstrap() {
		add_filter( 'gform_enable_credit_card_field', '__return_true' );

		add_filter( 'gform_add_field_buttons', array( __CLASS__, 'add_field_buttons' ) );
		add_filter( 'gform_field_input',       array( __CLASS__, 'acquirer_field_input' ), 10, 5 );
		add_filter( 'gform_field_input',       array( __CLASS__, 'payment_method_field_input' ), 10, 5 );
		add_filter( 'gform_field_type_title',  array( __CLASS__, 'field_type_title' ) );
		add_filter( 'gform_admin_pre_render',  array( __CLASS__, 'admin_payment_method_options' ) );

		add_action( 'gform_editor_js_set_default_values', array( __CLASS__, 'editor_js_default_field_labels' ) );
	}

	/**
	 * Acquirrer field input
	 *
	 * @param string $field_content
	 * @param string $field
	 * @param string $value
	 * @param string $lead_id
	 * @param string $form_id
	 */
	public static function acquirer_field_input( $field_content, $field, $value, $lead_id, $form_id ) {
		$type = RGFormsModel::get_input_type( $field );

		if ( Pronamic_WP_Pay_Extensions_GravityForms_IssuerDropDown::TYPE === $type ) {
			$id            = $field['id'];
			$field_id      = IS_ADMIN || 0 === $form_id ? "input_$id" : 'input_' . $form_id . "_$id";

			$class_suffix  = 'entry' === RG_CURRENT_VIEW ? '_admin' : '';
			$size          = rgar( $field, 'size' );

			$class         = $size . $class_suffix;
			$css_class     = trim( esc_attr( $class ) . ' gfield_ideal_acquirer_select' );

			$tab_index     = GFCommon::get_tabindex();

			$disabled_text = ( IS_ADMIN && 'entry' !== RG_CURRENT_VIEW ) ? "disabled='disabled'" : '';

			$html = '';

			$feed = get_pronamic_gf_pay_conditioned_feed_by_form_id( $form_id, true );

			/**
			 * Developing warning:
			 * Don't use single quotes in the HTML you output, it is buggy in combination with SACK
			 */
			if ( IS_ADMIN ) {
				if ( null === $feed ) {
					$new_feed_url = add_query_arg( 'post_type', 'pronamic_pay_gf', admin_url( 'post-new.php' ) );

					$html .= sprintf(
						"<a class='ideal-edit-link' href='%s' target='_blank'>%s</a>",
						$new_feed_url,
						__( 'Create iDEAL feed', 'pronamic_ideal' )
					);

					$feeds = get_pronamic_gf_pay_feeds_by_form_id( $form_id );

					if ( count( $feeds ) > 0 ) {
						$html .= sprintf(
							"<p class='pronamic-pay-error'><strong>%s</strong><br><em>%s</em></p>",
							__( 'This field is not supported by your payment gateway.', 'pronamic-ideal' ),
							sprintf(
								__( 'Please remove it from this form or %sadd a supported payment gateway%s.', 'pronamic-ideal' ),
								sprintf( '<a href="%s" target="_blank">', esc_attr( $new_feed_url ) ),
								'</a>'
							)
						);
					}
				} else {
					$html .= sprintf(
						"<a class='ideal-edit-link' href='%s' target='_blank'>%s</a>",
						get_edit_post_link( $feed->id ),
						__( 'Edit iDEAL feed', 'pronamic_ideal' )
					);
				}
			}

			$html_input = '';
			$html_error = '';

			if ( null !== $feed ) {
				$gateway = Pronamic_WP_Pay_Plugin::get_gateway( $feed->config_id );

				if ( $gateway ) {
					// Always use iDEAL payment method for issuer field
					$payment_method = $gateway->get_payment_method();

					$gateway->set_payment_method( Pronamic_WP_Pay_PaymentMethods::IDEAL );

					$issuer_field = $gateway->get_issuer_field();

					$error = $gateway->get_error();

					if ( is_wp_error( $error ) ) {
						$html_error .= Pronamic_WP_Pay_Plugin::get_default_error_message();
						$html_error .= '<br /><em>' . $error->get_error_message() . '</em>';
					} elseif ( $issuer_field ) {
						$choices = $issuer_field['choices'];

						$options = Pronamic_WP_HTML_Helper::select_options_grouped( $choices, $value );
						// Double quotes are not working, se we replace them with an single quote
						$options = str_replace( '"', '\'', $options );

						$html_input  = '';
						$html_input .= sprintf( "<select name='input_%d' id='%s' class='%s' %s %s>", $id, $field_id, $css_class, $tab_index, $disabled_text );
						$html_input .= sprintf( '%s', $options );
						$html_input .= sprintf( '</select>' );
					}

					// Reset payment method to original value
					$gateway->set_payment_method( $payment_method );
				}
			}

			if ( $html_error ) {
				$html .= sprintf( "<div class='gfield_description validation_message'>" );
				$html .= sprintf( '%s', $html_error );
				$html .= sprintf( '</div>' );
			} else {
				$html .= sprintf( "<div class='ginput_container ginput_ideal'>" );
				$html .= sprintf( '%s', $html_input );
				$html .= sprintf( '</div>' );
			}

			$field_content = $html;
		}

		return $field_content;
	}

	/**
	 * Payment method field input
	 *
	 * @param string $field_content
	 * @param string $field
	 * @param string $value
	 * @param string $lead_id
	 * @param string $form_id
	 */
	public static function payment_method_field_input( $field_content, $field, $value, $lead_id, $form_id ) {
		if ( Pronamic_WP_Pay_Extensions_GravityForms_PaymentMethodSelector::TYPE === $field->type ) {
			$id            = $field['id'];
			$field_id      = IS_ADMIN || 0 === $form_id ? "input_$id" : 'input_' . $form_id . "_$id";

			$class_suffix  = ( RG_CURRENT_VIEW === 'entry' ) ? '_admin' : '';
			$size          = rgar( $field, 'size' );

			$class         = $size . $class_suffix;
			$css_class     = trim( esc_attr( $class ) . ' gfield_pronamic_pay_payment_method_select' );

			$tab_index     = GFCommon::get_tabindex();

			$disabled_text = ( IS_ADMIN && 'entry' !== RG_CURRENT_VIEW ) ? "disabled='disabled'" : '';

			$html = '';

			/**
			 * Developing warning:
			 * Don't use single quotes in the HTML you output, it is buggy in combination with SACK
			 */
			if ( IS_ADMIN ) {
				$feed = get_pronamic_gf_pay_conditioned_feed_by_form_id( $form_id, true );

				if ( null === $feed ) {
					$html .= sprintf(
						"<a class='ideal-edit-link' href='%s' target='_blank'>%s</a>",
						add_query_arg( 'post_type', 'pronamic_pay_gf', admin_url( 'post-new.php' ) ),
						__( 'Create iDEAL feed', 'pronamic_ideal' )
					);
				} else {
					$html .= sprintf(
						"<a class='ideal-edit-link' href='%s' target='_blank'>%s</a>",
						get_edit_post_link( $feed->id ),
						__( 'Edit iDEAL feed', 'pronamic_ideal' )
					);
				}
			}

			if ( ( IS_ADMIN && empty( $field->choices ) ) || ! is_array( $field->choices ) ) {
				$options = self::get_payment_method_options( $form_id );
			} else {
				$options = array();

				foreach ( $field->choices as $choice ) {
					if ( $choice['isSelected'] ) {
						$options[ $choice['value'] ] = $choice['text'];
					}
				}
			}

			if ( is_wp_error( $options ) ) {
				$html .= sprintf( "<div class='gfield_description validation_message'>" );
				$html .= Pronamic_WP_Pay_Plugin::get_default_error_message();
				$html .= '<br /><em>' . $error->get_error_message() . '</em>';
				$html .= sprintf( '</div>' );
			} else {
				$options = Pronamic_WP_HTML_Helper::select_options_grouped( array( array( 'options' => $options ) ) );
				// Double quotes are not working, se we replace them with an single quote
				$options = str_replace( '"', '\'', $options );

				$onchange = IS_ADMIN ? null : $field->get_conditional_logic_event( 'change' );

				$html .= sprintf( "<div class='ginput_container ginput_ideal'>" );
				$html .= sprintf( "<select name='input_%d' id='%s' class='%s' %s %s %s>", $id, $field_id, $css_class, $tab_index, $disabled_text, $onchange );
				$html .= sprintf( '%s', $options );
				$html .= sprintf( '</select>' );
				$html .= sprintf( '</div>' );
			}

			$field_content = $html;
		}

		return $field_content;
	}

	public static function get_payment_method_options( $form_id ) {
		$feed    = get_pronamic_gf_pay_conditioned_feed_by_form_id( $form_id );
		$options = array();

		if ( null !== $feed ) {
			$gateway = Pronamic_WP_Pay_Plugin::get_gateway( $feed->config_id );

			if ( $gateway ) {
				$payment_method_field = $gateway->get_payment_method_field();

				$error = $gateway->get_error();

				if ( is_wp_error( $error ) ) {
					$options = $error;
				} elseif ( $payment_method_field ) {
					$options = $payment_method_field['choices'][0]['options'];
				}
			}
		}

		return $options;
	}

	/**
	 * Add choices to payment method selector fields
	 *
	 * @param  array $form
	 * @return array $form
	 */
	public static function admin_payment_method_options( $form ) {
		foreach ( $form['fields'] as $i => $field ) {
			if ( Pronamic_WP_Pay_Extensions_GravityForms_PaymentMethodSelector::TYPE === $field->type && empty( $field->choices ) ) {
				$options = self::get_payment_method_options( $form['id'] );

				if ( is_wp_error( $options ) ) {
					$options = array();
				}

				$field->inputType = 'checkbox';
				$field->enableChoiceValue = true;
				$field->choices = array();

				foreach ( $options as $payment_method => $name ) {
					$field->choices[] = array(
						'text'                  => $name,
						'value'                 => $payment_method,
						'isSelected'            => true,
						'price'                 => '',
						'pronamic_supported_pm' => strval( $payment_method ),
					);
				}
			}
		}

		return $form;
	}

	/**
	 * Add field buttons
	 *
	 * @param array $groups
	 */
	public static function add_field_buttons( $groups ) {
		// Fields
		$fields = array(
			array(
				'class'     => 'button',
				'value'     => __( 'Issuer', 'pronamic_ideal' ),
				'data-type' => Pronamic_WP_Pay_Extensions_GravityForms_IssuerDropDown::TYPE,
			),
			array(
				'class'     => 'button',
				'value'     => __( 'Payment Method', 'pronamic_ideal' ),
				'data-type' => Pronamic_WP_Pay_Extensions_GravityForms_PaymentMethodSelector::TYPE,
			),
		);

		// Backwards compatibility version 1.9
		// @see https://github.com/gravityforms/gravityforms/blob/1.9/js/form_editor.js#L24-L26
		if ( Pronamic_WP_Pay_Extensions_GravityForms_GravityForms::version_compare( '1.9', '<' ) ) {
			foreach ( $fields as &$field ) {
				$field['onclick'] = sprintf( "StartAddField('%s');", $field['data-type'] );
			}
		}

		// Group
		$group = array(
			'name'   => 'pronamic_pay_fields',
			'label'  => __( 'Payment Fields', 'pronamic_ideal' ),
			'fields' => $fields,
		);

		$groups[] = $group;

		return $groups;
	}

	/**
	 * Field type title
	 *
	 * @param string $type
	 */
	public static function field_type_title( $type ) {
		switch ( $type ) {
			case Pronamic_WP_Pay_Extensions_GravityForms_IssuerDropDown::TYPE:
				return __( 'Issuer Drop Down', 'pronamic_ideal' );
				break;

			case Pronamic_WP_Pay_Extensions_GravityForms_PaymentMethodSelector::TYPE:
				return __( 'Payment Method Selector', 'pronamic_ideal' );
				break;
		}

		return $type;
	}

	/**
	 * Default field labels
	 */
	public static function editor_js_default_field_labels() {
		$labels = array(
			Pronamic_WP_Pay_Extensions_GravityForms_IssuerDropDown::TYPE        => __( 'Choose a bank for iDEAL payment', 'pronamic_ideal' ),
			Pronamic_WP_Pay_Extensions_GravityForms_PaymentMethodSelector::TYPE => __( 'Choose a payment method', 'pronamic_ideal' ),
		);

		foreach ( $labels as $type => $label ) {
			?>
			case '<?php echo esc_js( $type ); ?>':
				field.label = "<?php echo esc_js( $label ); ?>";
				break;
			<?php
		}
	}
}
