/* global gform */
/* global form */
/* global SetFieldProperty */
( function( $ ) {
	/**
	 * Gravity Forms pay feed editor
	 */
	var GravityFormsPayFeedEditor = function( element ) {
		var obj = this;
		var $element = $( element );

		// Elements
		var elements = {};
		elements.subscriptionAmountType = $element.find( 'input[name="_pronamic_pay_gf_subscription_amount_type"]' );
		elements.subscriptionIntervalType = $element.find( 'input[name="_pronamic_pay_gf_subscription_interval_type"]' );
		elements.subscriptionInterval = $element.find( '#pronamic_pay_gf_subscription_interval' );
		elements.subscriptionIntervalPeriod = $element.find( '#pronamic_pay_gf_subscription_interval_period' );
		elements.subscriptionIntervalDateType = $element.find( 'input[name="_pronamic_pay_gf_subscription_interval_date_type"]' );
		elements.subscriptionIntervalDate = $element.find( '#pronamic_pay_gf_subscription_interval_date' );
		elements.subscriptionIntervalDateDay = $element.find( '#pronamic_pay_gf_subscription_interval_date_day' );
		elements.subscriptionIntervalDateMonth = $element.find( '#pronamic_pay_gf_subscription_interval_date_month' );

		/**
		 * Update fields
		 */
		this.updateFields = function() {
			elements.subscriptionAmountType.on( 'change', function() {
				var amountType = elements.subscriptionAmountType.filter( ':checked' ).val();

				if ( '' === amountType ) {
                    elements.subscriptionAmountType.parents( 'tr' ).siblings().hide();
				} else {
                    elements.subscriptionAmountType.parents('tr').siblings().show();

					// Set background color of visible even rows
					var rows = elements.subscriptionAmountType.parents( 'table' ).find( 'tr' );

					rows.removeClass( 'even' );
					rows.filter( ':visible:even' ).addClass( 'even' );
                }
			} );

			elements.subscriptionAmountType.trigger( 'change' );

			elements.subscriptionIntervalType.on( 'change', function() {
				var intervalType = elements.subscriptionIntervalType.filter( ':checked' ).val();

				$( element ).find( '.pronamic-pay-gf-subscription-interval-settings' ).hide();

				var intervalSettings = $( element ).find( '.pronamic-pay-gf-subscription-interval-settings.interval-' + intervalType );

				if ( 'fixed' !== intervalType ) {
					elements.subscriptionIntervalPeriod.val( 'D' );

					elements.subscriptionIntervalPeriod.trigger( 'change' );
				}

				if ( intervalSettings.length > 0 ) {
					intervalSettings.show();
				}
			} );

			elements.subscriptionIntervalPeriod.on( 'change', function() {
				var intervalPeriod = elements.subscriptionIntervalPeriod.val();

				$( element ).find( '.pronamic-pay-gf-subscription-interval-date-sync-settings' ).hide();

				$( element ).find( '.pronamic-pay-gf-subscription-interval-date-sync-settings.interval-' + intervalPeriod ).show();

				switch ( intervalPeriod ) {
					case 'D' :
						elements.subscriptionIntervalDateType.filter( '[value="payment_date"]' ).prop( 'checked', true );
						elements.subscriptionIntervalDateType.attr( 'disabled', 'disabled' );
						elements.subscriptionIntervalDate.val( '' );
						elements.subscriptionIntervalDateDay.val( '' );
						elements.subscriptionIntervalDateMonth.val( '' );

						break;
					case 'W' :
						elements.subscriptionIntervalDateType.removeAttr( 'disabled' );
						elements.subscriptionIntervalDate.val( '' );
						elements.subscriptionIntervalDateMonth.val( '' );

						break;
					case 'M' :
						elements.subscriptionIntervalDateType.removeAttr( 'disabled' );
						elements.subscriptionIntervalDateDay.val( '' );
						elements.subscriptionIntervalDateMonth.val( '' );

						break;
					case 'Y' :
						elements.subscriptionIntervalDateType.removeAttr( 'disabled' );
						elements.subscriptionIntervalDateDay.val( '' );

						break;
				}

				elements.subscriptionIntervalDateType.trigger( 'change' );
			} );

			$( element ).find( '.pronamic-pay-gf-subscription-interval-date-sync-settings select' ).on( 'change', function() {
				elements.subscriptionIntervalDateType.filter( '[value="sync"]' ).prop( 'checked', true );

				elements.subscriptionIntervalDateType.trigger( 'change' );
			} );

			elements.subscriptionIntervalType.trigger( 'change' );
			elements.subscriptionIntervalPeriod.trigger( 'change' );
		};

		// Function calls
		obj.updateFields();
	};

	/**
	 * jQuery plugin - Gravity Forms pay feed editor
	 */
	$.fn.gravityFormsPayFeedEditor = function() {
		return this.each( function() {
			var $this = $( this );

			if ( $this.data( 'gf-pay-feed-editor' ) ) {
				return;
			}

			var editor = new GravityFormsPayFeedEditor( this );

			$this.data( 'gf-pay-feed-editor', editor );
		} );
	};

	/**
	 * Ready
	 */
	$( document ).ready( function() {
		$( '.gforms_edit_form .ideal-edit-link' ).click( function( event ) {
			event.stopPropagation();
		} ); 
		
		$( '#gf-pay-feed-editor' ).gravityFormsPayFeedEditor();

		if ( 'undefined' !== typeof gform && 'undefined' !== typeof form ) {
			// Action on load field choices
			// @see https://github.com/wp-premium/gravityforms/blob/2.0.3/js/form_editor.js#L2428-L2442
			gform.addAction( 'gform_load_field_choices', function( args ) {
				var field = args.shift();

				if ( ! field ) {
					return;
				}

				var isPaymentMethodField = 'pronamic_pay_payment_method_selector' === field.type;

				// Toggle "Show values" checkbox
				$( 'label[for="field_choice_values_enabled"]' ).parent( 'div' ).toggle( ! isPaymentMethodField );

				if ( isPaymentMethodField ) {
					// Special treatment for supported payment methods choices
					$.each( field.choices, function( i, choice ) {
						if ( choice.builtin ) {
							var choiceValueInput = $( '.field-choice-input.field-choice-value' ).eq( i );

							// Values for payment methods provided by the gateway should not be edited
							choiceValueInput.attr( 'disabled', 'disabled' );

							// Payment methods provided by the gateway should not be removed
							choiceValueInput.parent( 'li' ).find( '.gf_delete_field_choice' ).remove();
						}
					} );
				}
			} );

			// Filter conditional logic dependencies.
			gform.addFilter( 'gform_has_conditional_logic_dependency', function( result, field_id ) {
				if ( typeof form === 'undefined' ) {
					return result;
				}

				// Check if field is used in a conditions of the payment feed(s).
				if ( -1 !== $.inArray( field_id.toString(), form.pronamic_pay_condition_field_ids ) ) {
					return true;
				}

				return result;
			} );
		}

		// Action on load field settings
		$( document ).on( 'gform_load_field_settings', function( e, field ) {
			var pronamicPayFieldSettings = {
				'pronamic_pay_config_field':  'pronamicPayConfigId',
				'pronamic_pay_display_field': 'pronamicPayDisplayMode'
			};

			$.each( pronamicPayFieldSettings, function( id, property ) {
				var $field = $( '#' + id );

				if ( $field.find( 'option[value="' + field[ property ] + '"]' ).length > 0 ) {
					$field.val( field[ property ] );
				} else {
					SetFieldProperty( property, '' );
				}
			} );
		} );
	} );
} )( jQuery );
