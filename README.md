# WordPress Pay Extension: Gravity Forms

**Gravity Forms driver for the WordPress payment processing library.**

## WordPress Actions

### `gform_ideal_fulfillment`

#### Description

This hook runs when a transaction is completed successfully for the Pronamic iDEAL plugin and can be used to fire actions dependent on a successful transaction.

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

*	https://gist.github.com/remcotolsma/d6257e299ab24908d9b9f14537b52a85

## Links

*	[Gravity Forms](http://www.gravityforms.com/)
*	[GitHub Gravity Forms](https://github.com/wp-premium/gravityforms)
