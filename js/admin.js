/* global ajaxurl */
/* global gform */
/* global form */
( function( $ ) {
	/**
	 * Gravity Forms pay feed editor
	 */
	var gravityFormsPayFeedEditor = function( element ) {
		var obj = this;
		var $element = $( element );

		// Elements
		var elements = {};
		elements.feed = $element.find( '#gf_ideal_feed' );
		elements.gravityForm = $element.find( '#gf_ideal_gravity_form' );
		elements.formId = $element.find( '#_pronamic_pay_gf_form_id' );
		elements.configId = $element.find( '#gf_ideal_config_id' );
		elements.delayPostCreationItem = $element.find( '#gf_ideal_delay_post_creation_item' );
		elements.conditionEnabled = $element.find( '#gf_ideal_condition_enabled' );
		elements.conditionConfig = $element.find( '#gf_ideal_condition_config' );
		elements.conditionFieldId = $element.find( '#gf_ideal_condition_field_id' );
		elements.conditionOperator = $element.find( '#gf_ideal_condition_operator' );
		elements.conditionValue = $element.find( '#gf_ideal_condition_value' );
		elements.conditionMessage = $element.find( '#gf_ideal_condition_message' );
		elements.confirmationSelectFields = $element.find( '.gf_ideal_confirmation_select' );
		elements.userRoleFieldId = $element.find( '#gf_ideal_user_role_field_id' );
		elements.delayNotifications = $element.find( '#gf_ideal_delay_notifications' );
		elements.fieldSelectFields = $element.find( 'select.field-select' );
		elements.subscriptionAmountType = $element.find( 'input[name="_pronamic_pay_gf_subscription_amount_type"]' );
		elements.subscriptionAmountField = $element.find( '#pronamic_pay_gf_subscription_amount_field' );
		elements.subscriptionIntervalType = $element.find( 'input[name="_pronamic_pay_gf_subscription_interval_type"]' );
		elements.subscriptionInterval = $element.find( '#pronamic_pay_gf_subscription_interval' );
		elements.subscriptionIntervalPeriod = $element.find( '#pronamic_pay_gf_subscription_interval_period' );
		elements.subscriptionIntervalField = $element.find( '#pronamic_pay_gf_subscription_interval_field' );
		elements.subscriptionFrequencyType = $element.find( 'input[name="_pronamic_pay_gf_subscription_frequency_type"]' );
		elements.subscriptionFrequency = $element.find( '#pronamic_pay_gf_subscription_frequency' );
		elements.subscriptionFrequencyField = $element.find( '#pronamic_pay_gf_subscription_frequency_field' );

		// Data
		var feed = $.parseJSON( elements.feed.val() );
		var gravityForm = $.parseJSON( elements.gravityForm.val() );

		/**
		 * Update delay post creation item
		 */
		this.updateDelayPostCreationItem = function() {
			var display = false;

			if ( gravityForm ) {
				// Displaying delayed post creation setting if current form has a post field
				var postFields = obj.getFieldsByType( [ 'post_title', 'post_content', 'post_excerpt', 'post_category', 'post_custom_field', 'post_image', 'post_tag' ] );

				if ( postFields.length > 0 ) {
					display = true;
				}
			}
			
			elements.delayPostCreationItem.toggle( display );
		};

		/**
		 * Update confirmations
		 */
		this.updateConfirmationFields = function() {
			elements.confirmationSelectFields.empty();
			$( '<option>' ).appendTo( elements.confirmationSelectFields );

			if ( gravityForm ) {
				$.each( elements.confirmationSelectFields, function( index, field ) {
					var linkName = $( field ).attr( 'data-pronamic-link-name' ),
						isSelected = false;

					$.each( gravityForm.confirmations, function( confirmationId, confirmation ) {
						isSelected = false;

						if ( 'object' === typeof feed.links ) {
							isSelected = ( feed.links[ linkName ].confirmation_id === confirmation.id );
						}

						$( '<option>' )
							.attr( 'value', confirmation.id )
							.text( confirmation.name )
							/* jshint eqeqeq: false */
							.prop( 'selected', isSelected )
							/* jshint eqeqeq: true */
							.appendTo( field );
					});
				} );
			}
		};

		/**
		 * Toggle condition config
		 */
		this.toggleConditionConfig = function() {
			var options = elements.conditionFieldId.find( 'option' );

			if ( 1 >= options.length ) {
				elements.conditionConfig.fadeOut( 'fast' );
				elements.conditionEnabled.before( elements.conditionConfig );
				elements.conditionMessage.show();
				elements.conditionEnabled.val( 0 );
			} else {
				elements.conditionConfig.fadeIn( 'fast' );
				elements.conditionEnabled.after( elements.conditionConfig );
				elements.conditionMessage.hide();
				elements.conditionEnabled.val( 1 );
			}
		};

		/**
		 * Update condition fields
		 */
		this.updateConditionFields = function() {
			elements.conditionFieldId.empty();
			$( '<option>' ).appendTo( elements.conditionFieldId );

			if ( gravityForm ) {
				$.each( gravityForm.fields, function( key, field ) {
					var type = field.inputType ? field.inputType : field.type;
	
					var index = $.inArray( type, [ 'checkbox', 'radio', 'select' ] );
					if ( index >= 0 ) {
						var label = field.adminLabel ? field.adminLabel : field.label;

						$( '<option>' )
							.attr( 'value', field.id )
							.text (label )
							/* jshint eqeqeq: false */
							.prop( 'selected', ( 1 == feed.conditionEnabled && feed.conditionFieldId == field.id ) )
							/* jshint eqeqeq: true */
							.appendTo( elements.conditionFieldId );
					}
				});
				
				elements.conditionOperator.val( feed.conditionOperator );
			}
		};

		/**
		 * Update condition values
		 */
		this.updateConditionValues = function() {
			var id	= elements.conditionFieldId.val();
			var field = obj.getFieldById( id );
			
			elements.conditionValue.empty();
			$( '<option>' ).appendTo( elements.conditionValue );

			if ( ! field ) {
				elements.conditionOperator.prop( 'disabled', true );
				elements.conditionValue.prop( 'disabled', true );
			} else {
				elements.conditionOperator.removeProp( 'disabled' );
				elements.conditionValue.removeProp( 'disabled' );
			}

			if ( field && field.choices ) {
				$.each( field.choices, function( key, choice ) {
					var value = choice.value ? choice.value : choice.text;

					$( '<option>' )
						.attr( 'value', value )
						.text( choice.text )
						.appendTo( elements.conditionValue );
				} );
				
				elements.conditionValue.val( feed.conditionValue );
			}
		};

		/**
		 * Get field by the specified id
		 */
		this.getFieldById = function( id ) {
			if ( gravityForm ) {
				for ( var i = 0; i < gravityForm.fields.length; i++ ) {
					/* jshint eqeqeq: false */
					if ( gravityForm.fields[ i ].id == id ) {
						return gravityForm.fields[ i ];
					}
					/* jshint eqeqeq: true */
				}
			}
			
			return null;
		};

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
		 * Change form
		 */
		this.changeForm = function() {
			jQuery.get(
				ajaxurl, {
					action: 'gf_get_form_data', 
					formId: elements.formId.val()
				},
				function( response ) {
					if ( response.success ) {
						gravityForm = response.data;

						obj.updateFields();
					}
				}
			);
		};
		
		/**
		 * Update user role 
		 */
		this.updateUserRoleFields = function() {
			elements.userRoleFieldId.empty();
			$( '<option>' ).appendTo( elements.userRoleFieldId );

			if ( gravityForm ) {
				$.each( gravityForm.fields, function( key, field ) {
					var label = field.adminLabel ? field.adminLabel : field.label;
	
					$( '<option>' )
						.attr( 'value', field.id )
						.text( label )
						/* jshint eqeqeq: false */
						.prop( 'selected', feed.userRoleFieldId == field.id )
						/* jshint eqeqeq: true */
						.appendTo( elements.userRoleFieldId );
				} );
			}
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

					if ( intervalSettings.length > 0 ) {
						intervalSettings.show();
					}
				} );

				elements.subscriptionIntervalType.trigger( 'change' );

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
			obj.updateDelayPostCreationItem();
			obj.updateConfirmationFields();
			obj.updateConditionFields();
			obj.toggleConditionConfig();
			obj.updateConditionValues();
			obj.updateUserRoleFields();
			obj.updateSubscriptionFields();
			obj.updateSelectFields();
			obj.updateNotifications();
		};

		// Function calls
		obj.updateFields();

		elements.formId.change( obj.changeForm );
		elements.configId.change( obj.updateConfigFields );
		elements.conditionFieldId.change( obj.updateConditionValues );
	};

	//////////////////////////////////////////////////

	/**
	 * jQuery plugin - Gravity Forms pay feed editor
	 */
	$.fn.gravityFormsPayFeedEditor = function() {
		return this.each( function() {
			var $this = $( this );

			if ( $this.data( 'gf-pay-feed-editor' ) ) {
				return;
			}

			var editor = new gravityFormsPayFeedEditor( this );

			$this.data( 'gf-pay-feed-editor', editor );
		} );
	};

	//////////////////////////////////////////////////

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
		}

		// Action on load field settings
		$( document ).on( 'gform_load_field_settings', function( e, field ) {
			$( '#pronamic_pay_config_field' ).val( field.pronamicPayConfigId );
		} );
	} );
} )( jQuery );
