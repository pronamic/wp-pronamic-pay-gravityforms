<?php

/**
 * Title: WordPress pay extension Gravity Forms fields
 * Description:
 * Copyright: Copyright (c) 2005 - 2016
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.4.6
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_GravityForms_Fields {
	/**
	 * Construct and intialize custom Gravity Forms fields.
	 */
	public function __construct() {
		add_filter( 'gform_enable_credit_card_field', '__return_true' );

		add_filter( 'gform_field_input', array( $this, 'acquirer_field_input' ), 10, 5 );
		add_filter( 'gform_field_input', array( $this, 'payment_method_field_input' ), 10, 5 );

		add_filter( 'gform_admin_pre_render',  array( $this, 'admin_payment_method_options' ) );

		if ( Pronamic_WP_Pay_Class::method_exists( 'GF_Fields', 'register' ) ) {
			GF_Fields::register( new Pronamic_WP_Pay_Extensions_GravityForms_PaymentMethodsField() );
			GF_Fields::register( new Pronamic_WP_Pay_Extensions_GravityForms_IssuersField() );
		}
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
	public function acquirer_field_input( $field_content, $field, $value, $lead_id, $form_id ) {
		$type = RGFormsModel::get_input_type( $field );

		if ( Pronamic_WP_Pay_Extensions_GravityForms_IssuersField::TYPE === $type ) {
			$id            = $field['id'];
			$field_id      = IS_ADMIN || 0 === $form_id ? "input_$id" : 'input_' . $form_id . "_$id";

			$class_suffix  = ( 'entry' === RG_CURRENT_VIEW ) ? '_admin' : '';
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
						__( 'Create pay feed', 'pronamic_ideal' )
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
						__( 'Edit pay feed', 'pronamic_ideal' )
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
	public function payment_method_field_input( $field_content, $field, $value, $lead_id, $form_id ) {
		if ( Pronamic_WP_Pay_Extensions_GravityForms_PaymentMethodsField::TYPE === $field->type ) {
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
						__( 'Create pay feed', 'pronamic_ideal' )
					);
				} else {
					$html .= sprintf(
						"<a class='ideal-edit-link' href='%s' target='_blank'>%s</a>",
						get_edit_post_link( $feed->id ),
						__( 'Edit pay feed', 'pronamic_ideal' )
					);
				}
			}

			if ( ( IS_ADMIN && empty( $field->choices ) ) || ! is_array( $field->choices ) ) {
				$options = $this->get_payment_method_options( $form_id );
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

	public function get_payment_method_options( $form_id ) {
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
	public function admin_payment_method_options( $form ) {
		foreach ( $form['fields'] as $i => $field ) {
			if ( Pronamic_WP_Pay_Extensions_GravityForms_PaymentMethodsField::TYPE === $field->type && empty( $field->choices ) ) {
				$options = $this->get_payment_method_options( $form['id'] );

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
	 * Add pay field group to the Gravity Forms field groups.
	 *
	 * @see https://github.com/wp-premium/gravityforms/blob/1.9.19/form_detail.php#L2353-L2368
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
}
