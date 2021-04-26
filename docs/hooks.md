# Hooks

- [Actions](#actions)
- [Filters](#filters)

## Actions

### `gform_ideal_fulfillment`

*The Gravity Forms PayPal Add-On executes the 'gform_paypal_fulfillment' action.*

Argument | Type | Description
-------- | ---- | -----------
`$entry` | `object` | The entry used to generate the (iDEAL) payment.
`$feed` | `object` | The feed configuration data used to generate the payment.

Source: [src/Extension.php](../src/Extension.php), [line 961](../src/Extension.php#L961-L970)

## Filters

### `pronamic_pay_gravityforms_delay_actions`

*Filters the delay actions to display on the payment feed settings page and to process.*

Argument | Type | Description
-------- | ---- | -----------
`$actions` | `array` | {<br><br>    Delay action.<br><br>    @var null\|\GFAddon $addon                       Optional reference to a Gravity Forms add-on object.<br>    @var bool          $active                      Boolean flag to indicate the delay action can be enabled (add-on active).<br>    @var string        $meta_key                    Post meta key used to store meta value if the delay action is enabled.<br>    @var bool          $delayed_payment_integration Boolean flag to indicate the delay action is defined by a delayed payment integration.<br>    @var string        $label                       The label to show on the payment feed settings page.<br>    @var callable      $delay_callback              Callback function which can be used to remove actions/filters to delay actions.<br>    @var callable      $process_callback            Callback function to process the delay action.<br><br>}

Source: [src/Extension.php](../src/Extension.php), [line 1377](../src/Extension.php#L1377-L1399)


