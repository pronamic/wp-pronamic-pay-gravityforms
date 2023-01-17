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
		elements.feed = $element.find( '#gf_ideal_feed' );
		elements.gravityForm = $element.find( '#gf_ideal_gravity_form' );
		elements.configId = $element.find( '#gf_ideal_config_id' );
		elements.delayNotifications = $element.find( '#gf_ideal_delay_notifications' );
		elements.fieldSelectFields = $element.find( 'select.field-select' );
		elements.subscriptionAmountType = $element.find( 'input[name="_pronamic_pay_gf_subscription_amount_type"]' );
		elements.subscriptionAmountField = $element.find( '#pronamic_pay_gf_subscription_amount_field' );
		elements.subscriptionIntervalType = $element.find( 'input[name="_pronamic_pay_gf_subscription_interval_type"]' );
		elements.subscriptionInterval = $element.find( '#pronamic_pay_gf_subscription_interval' );
		elements.subscriptionIntervalPeriod = $element.find( '#pronamic_pay_gf_subscription_interval_period' );
		elements.subscriptionIntervalDateType = $element.find( 'input[name="_pronamic_pay_gf_subscription_interval_date_type"]' );
		elements.subscriptionIntervalDate = $element.find( '#pronamic_pay_gf_subscription_interval_date' );
		elements.subscriptionIntervalDateDay = $element.find( '#pronamic_pay_gf_subscription_interval_date_day' );
		elements.subscriptionIntervalDateMonth = $element.find( '#pronamic_pay_gf_subscription_interval_date_month' );
		elements.subscriptionIntervalField = $element.find( '#pronamic_pay_gf_subscription_interval_field' );
		elements.subscriptionFrequencyType = $element.find( 'input[name="_pronamic_pay_gf_subscription_frequency_type"]' );
		elements.subscriptionNumberPeriods = $element.find( '#pronamic_pay_gf_subscription_number_periods' );
		elements.subscriptionFrequencyField = $element.find( '#pronamic_pay_gf_subscription_frequency_field' );
		elements.subscriptionTrialEnabled = $element.find( '#pronamic_pay_gf_subscription_trial_enabled' );

		// Data
		var feed = JSON.parse( elements.feed.val() );
		var gravityForm = JSON.parse( elements.gravityForm.val() );

		/**
		 * Get fields by types
		 * 
		 * @param types
		 * @return Array
		 */
		this.getFieldsByType = function( types ) {
			var fields = [];

			if ( gravityForm ) {				
				for ( var i = 0; i < gravityForm.fields.length; i++ ) {
					if ( $.inArray( gravityForm.fields[ i ].type, types ) >= 0 ) {
						fields.push(gravityForm.fields[ i ]);
					}
				}
			}

			return fields;
		};
		
		this.getInputs = function() {
			var inputs = [];
			
			if ( gravityForm ) {
				$.each( gravityForm.fields, function( key, field ) {
					if ( field.inputs ) {
						$.each( field.inputs, function( key, input ) {
							inputs.push( input );
						} );
					} else if ( ! field.displayOnly ) {
						inputs.push ( field );
					}
				} );
			}
			
			return inputs;
		};
		
		/**
		 * Update config
		 */
		this.updateConfigFields = function() {
			var method = elements.configId.find( 'option:selected' ).attr( 'data-ideal-method' );

			$element.find( '.extra-settings' ).hide();
			$element.find( '.method-' + method ).show();
		};
		
		this.updateNotifications = function() {			
			elements.delayNotifications.empty();

			if ( gravityForm ) {
				$.each( gravityForm.notifications, function( key, notification ) {
					if ( 'form_submission' !== notification.event ) {
						return;
					}

					var item = $( '<li>' ).appendTo( elements.delayNotifications );
					
					var fieldId = 'pronamic-pay-gf-notification-' + notification.id;

					$( '<input type="checkbox" name="_pronamic_pay_gf_delay_notification_ids[]">' )
						.attr( 'id', fieldId )
						.val( notification.id )
						.prop( 'checked', $.inArray( notification.id, feed.delayNotificationIds ) >= 0 )
						.appendTo( item );
					
					item.append( ' ' );
					
					$( '<label>' )
						.attr( 'for', fieldId )
						.text( notification.name )
						.appendTo( item );
				} );
			}
		};
		
		/**
		 * Update subscription fields
		 */
		this.updateSubscriptionFields = function() {
			if ( gravityForm ) {
				elements.subscriptionAmountField.empty();
				elements.subscriptionIntervalField.empty();
				elements.subscriptionFrequencyField.empty();

				var products = [];

				if ( gravityForm ) {
					$.each( gravityForm.fields, function( key, field ) {
						if ( 'product' === field.type ) {
							products.push( field );
						}
					} );
				}

				// Recurring amount field
				$element = $( elements.subscriptionAmountField );

				$( '<option>' ).appendTo( $element );

				$.each( products, function( key, product ) {
					var label = product.adminLabel ? product.adminLabel : product.label;

					$( '<option>' )
						.attr( 'value', product.id )
						.text( label )
						/* jshint eqeqeq: false */
						.prop( 'selected', feed.subscriptionAmountField == product.id )
						/* jshint eqeqeq: true */
						.appendTo( $element );
				} );

				elements.subscriptionAmountType.on( 'change', function() {
					var amountType = elements.subscriptionAmountType.filter( ':checked' ).val();

					$( element ).find( '.pronamic-pay-gf-subscription-amount-settings' ).hide();

					if ( '' === amountType ) {
                        elements.subscriptionAmountType.parents( 'tr' ).siblings().hide();
					} else {
                        elements.subscriptionAmountType.parents('tr').siblings().show();

						// Set background color of visible even rows
						var rows = elements.subscriptionAmountType.parents( 'table' ).find( 'tr' );

						rows.removeClass( 'even' );
						rows.filter( ':visible:even' ).addClass( 'even' );
                    }

                    var amountSettings = $( element ).find( '.pronamic-pay-gf-subscription-amount-settings.amount-' + amountType );

                    if ( amountSettings.length > 0 ) {
                        amountSettings.show();
                    }
				} );

				elements.subscriptionAmountType.trigger( 'change' );

				// Interval
				$element = $( elements.subscriptionIntervalField );

				$( '<option>' ).appendTo( $element );

				$.each( obj.getInputs(), function( key, input ) {
					var label = input.adminLabel ? input.adminLabel : input.label;

					$( '<option>' )
						.attr( 'value', input.id )
						.text( label )
						/* jshint eqeqeq: false */
						.prop( 'selected', feed.subscriptionIntervalField == input.id )
						/* jshint eqeqeq: true */
						.appendTo( $element );
				} );

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

				elements.subscriptionIntervalDateType.on( 'change', function() {
					var intervalDateType = elements.subscriptionIntervalDateType.filter( ':checked' ).val();

					$( element ).find( '.pronamic-pay-gf-subscription-interval-date-settings' ).hide();

					var intervalDateSettings = $( element ).find( '.pronamic-pay-gf-subscription-interval-date-settings.interval-date-' + intervalDateType );

					if ( intervalDateSettings.length > 0 ) {
						intervalDateSettings.show();
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

				$( element ).find( 'select.pronamic-pay-gf-subscription-interval-date-sync-settings' ).on( 'change', function() {
					elements.subscriptionIntervalDateType.filter( '[value="sync"]' ).prop( 'checked', true );

					elements.subscriptionIntervalDateType.trigger( 'change' );
				} );

				elements.subscriptionIntervalType.trigger( 'change' );
				elements.subscriptionIntervalPeriod.trigger( 'change' );

				// Frequency
				$element = $( elements.subscriptionFrequencyField );

				$( '<option>' ).appendTo( $element );

				$.each( obj.getInputs(), function( key, product ) {
					var label = product.adminLabel ? product.adminLabel : product.label;

					$( '<option>' )
						.attr( 'value', product.id )
						.text( label )
						/* jshint eqeqeq: false */
						.prop( 'selected', feed.subscriptionFrequencyField == product.id )
						/* jshint eqeqeq: true */
						.appendTo( $element );
				} );

				elements.subscriptionFrequencyType.on( 'change', function() {
					var frequencyType = elements.subscriptionFrequencyType.filter( ':checked' ).val();

					$( element ).find( '.pronamic-pay-gf-subscription-frequency-settings' ).hide();

					var frequencySettings = $( element ).find( '.pronamic-pay-gf-subscription-frequency-settings.frequency-' + frequencyType );

					if ( frequencySettings.length > 0 ) {
						frequencySettings.show();
					}
				} );

				elements.subscriptionFrequencyType.trigger( 'change' );

				/**
				 * Trial period.
				 */

				// Trial enabled.
				elements.subscriptionTrialEnabled.on( 'change', function() {
					var enabled = elements.subscriptionTrialEnabled.filter( ':checked' ).length > 0;

					var trialSettings = $( element ).find( '.pronamic-pay-gf-subscription-trial-settings' );

					if ( enabled ) {
						trialSettings.show();

						return;
					}

					trialSettings.hide();
				} );

				elements.subscriptionTrialEnabled.trigger( 'change' );
			}
		};

		/**
		 * Update select fields
		 */
		this.updateSelectFields = function() {
			if ( gravityForm ) {
				elements.fieldSelectFields.empty();

				elements.fieldSelectFields.each( function( i, element ) {
					$element = $( element );

					var name = $element.data( 'gateway-field-name' );

					// Auto detect option.
					var auto_option_label = $element.data( 'auto-option-label' );

					if ( '' !== auto_option_label ) {
						$( '<option>' )
							.attr( 'value', 'auto' )
							.text( auto_option_label )
							/* jshint eqeqeq: false */
							.prop( 'selected', feed.fields[name] == 'auto' )
							/* jshint eqeqeq: true */
							.appendTo( $element );
					}

					$( '<option>' ).appendTo( $element );

					$.each( obj.getInputs(), function( key, input ) {
						var label = input.adminLabel ? input.adminLabel : input.label;

						$( '<option>' )
							.attr( 'value', input.id )
							.text( label )
							/* jshint eqeqeq: false */
							.prop( 'selected', feed.fields[ name ] == input.id )
							/* jshint eqeqeq: true */
							.appendTo( $element );
					} );
				} );
			}
		};

		/**
		 * Update fields
		 */
		this.updateFields = function() {
			obj.updateConfigFields();
			obj.updateSubscriptionFields();
			obj.updateSelectFields();
			obj.updateNotifications();
		};

		// Function calls
		obj.updateFields();

		elements.configId.change( obj.updateConfigFields );
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
