# WordPress Pay Extension: Gravity Forms

**Gravity Forms driver for the WordPress payment processing library.**

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
