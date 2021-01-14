# WordPress Pay Extension: Gravity Forms

**Gravity Forms driver for the WordPress payment processing library.**

## WordPress Actions

### `gform_ideal_fulfillment`

#### Description

This hook runs when a transaction is completed successfully for the Pronamic Pay plugin and can be used to fire actions dependent on a successful transaction.

#### Usage

```php
add_action( 'gform_ideal_fulfillment', 'your_function_name', 10, 2 );
```

#### Parameters

**`$entry`** | [Entry Object](https://www.gravityhelp.com/documentation/article/entry-object/)

The entry used to generate the transaction.

**`$feed`** | [Feed Object](https://github.com/wp-pay-extensions/gravityforms/blob/master/src/PayFeed.php)

The Feed configuration data used to generate the order.

#### Examples

```php
/**
 * Gravity Forms iDEAL fulfillment hook.
 *
 * @see https://github.com/wp-pay-extensions/gravityforms/blob/1.6.4/src/Extension.php#L750-L751
 * @param array $entry
 * @param Pronamic_WP_Pay_Extensions_GravityForms_PayFeed $feed
 */
function custom_gform_ideal_fulfillment( $entry, $feed ) {
    $payment_id = gform_get_meta( $entry['id'], 'pronamic_payment_id' );

    $payment = get_pronamic_payment( $payment_id );

    error_log( print_r( $payment, true ) );
}

add_action( 'gform_ideal_fulfillment', 'custom_gform_ideal_fulfillment', 10, 2 );
```

```php
/**
 * Gravity Forms iDEAL fulfillment hook.
 *
 * @see https://github.com/wp-pay-extensions/gravityforms/blob/1.6.4/src/Extension.php#L750-L751
 * @param array $entry
 * @param Pronamic_WP_Pay_Extensions_GravityForms_PayFeed $feed
 */
function gform_ideal_fulfillment_update_entry( $entry, $feed ) {
     $field_id = '';

     $entry[ $field_id ] = 'New value';

     GFAPI::update_entry( $entry );
}

add_action( 'gform_ideal_fulfillment', 'gform_ideal_fulfillment_update_entry', 10, 2 );
```

## WordPress Filters

### `pronamic_pay_gravityforms_delay_actions`

#### Description

Filters the delay actions to display on the payment feed settings page and to process.

#### Usage

```php
add_filter( 'pronamic_pay_gravityforms_delay_actions', 'your_function_name' );
```

#### Examples

```php
<?php

/**
 * Filter Pronamic Pay delay actions for Gravity Forms.
 *
 * @link https://gist.github.com/rvdsteege/6b0afe10f81b1bc99d335ff484206fa9
 */
\add_filter( 'pronamic_pay_gravityforms_delay_actions', function( $delay_actions ) {
	$delay_actions['gp_unique_id'] = array(
		'active'                      => true,
		'meta_key'                    => '_pronamic_pay_gf_delay_gp_unique_id',
		'delayed_payment_integration' => false,
		'label'                       => \__( 'Wait for payment to create a Gravity Perks Unique ID.', 'text-domain' ),
		'delay_callback'              => function() {
			\add_filter( 'gpui_wait_for_payment', function( $enabled ) {
				$enabled = true;

				return $enabled;
			} );

			\add_filter( 'gpui_wait_for_payment_feed', function( $feed, $form, $entry ) {
				if ( class_exists( '\Pronamic\WordPress\Pay\Extensions\GravityForms\FeedsDB' ) ) {
					$feed = \Pronamic\WordPress\Pay\Extensions\GravityForms\FeedsDB::get_feed_by_entry_id( $entry['id'] );

					if ( null === $feed ) {
						$feeds = \Pronamic\WordPress\Pay\Extensions\GravityForms\FeedsDB::get_active_feeds_by_form_id( $entry['form_id'] );

						$feed = array_shift( $feeds );
					}
				}

				return $feed;
			} );
		},
		'process_callback'            => function( $entry, $form ) {
			\gp_unique_id_field()->populate_field_value( $entry, $form, true );
		}
	);

	return $delay_actions;
} );
```

*	https://gist.github.com/remcotolsma/d6257e299ab24908d9b9f14537b52a85

## Links

*	[Gravity Forms](http://www.gravityforms.com/)
*	[GitHub Gravity Forms](https://github.com/wp-premium/gravityforms)
