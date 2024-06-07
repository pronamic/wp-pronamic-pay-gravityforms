# Change Log

All notable changes to this project will be documented in this file.

This projects adheres to [Semantic Versioning](http://semver.org/) and [Keep a CHANGELOG](http://keepachangelog.com/).

## [Unreleased][unreleased]
-

## [4.8.0] - 2024-06-07

### Added

- Added consumer bank account name and IBAN merge tags.

### Changed

- Updated payment date alignment setting name and description (https://github.com/pronamic/wp-pay-core/issues/182). ([df49d2d](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/df49d2d9a370ab0515f309914dbc1c506dfaa7c1))
- Updated to PHP 8.1. ([5af380d](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/5af380db735827727b1f0cb19881ced44c2c8d06))
- The iDEAL issuers field now uses the SVG images from https://github.com/pronamic/wp-pay-logos.

### Composer

- Removed `wp-pay-gateways/mollie` `^4.10`.
- Added `pronamic/ideal-issuers` `^1.1`.
- Added `pronamic/wp-pay-logos` `^2.2`.
- Changed `php` from `>=8.0` to `>=8.1`.
- Changed `wp-pay/core` from `^4.17` to `v4.19.0`.
	Release notes: https://github.com/pronamic/wp-pay-core/releases/tag/v4.19.0

Full set of changes: [`4.7.0...4.8.0`][4.8.0]

[4.8.0]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/v4.7.0...v4.8.0

## [4.7.0] - 2024-05-15

### Commits

- Improve redirect to entry in case the entry has been deleted. ([cddab3b](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/cddab3b4ba8f284c47a0a6ac58aeb4ce027d2b74))
- Manual revert fe6438a40da3784b5b38fb662eb305977c5c93c1, was causing performance issues. ([4c7ecb3](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/4c7ecb34c61607c204f394230d90372dd4f6ef2b))

### Composer

- Added `automattic/jetpack-autoloader` `^3.0`.
- Added `composer/installers` `^2.2`.
- Added `woocommerce/action-scheduler` `^3.7`.
- Added `wp-pay-gateways/mollie` `^4.10`.
- Changed `wp-pay/core` from `^4.16` to `v4.17.0`.
	Release notes: https://github.com/pronamic/wp-pay-core/releases/tag/v4.17.0

Full set of changes: [`4.6.1...4.7.0`][4.7.0]

[4.7.0]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/v4.6.1...v4.7.0

## [4.6.1] - 2024-03-26

### Commits

- Fixed "All output should be run through an escaping function". ([bb98644](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/bb986440ecb2fd48b1f14bad289010a1dfc87425))
- Fixed "The method parameter $x is never used" warnings. ([b22a1d1](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/b22a1d187a991b8f1cb3721e75bc19315e928ce0))
- Allow dynamic properties in payment feed (resolves “Deprecated: Creation of dynamic property Pronamic\WordPress\Pay\Extensions\GravityForms\PayFeed::$[…] is deprecated”). ([e444081](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/e4440810d6544fe9f07c984711e648f712f18dff))

### Composer

- Changed `php` from `>=7.4` to `>=8.0`.
- Changed `pronamic/wp-money` from `^2.2` to `v2.4.3`.
	Release notes: https://github.com/pronamic/wp-money/releases/tag/v2.4.3
- Changed `pronamic/wp-number` from `^1.2` to `v1.3.0`.
	Release notes: https://github.com/pronamic/wp-number/releases/tag/v1.3.0
- Changed `wp-pay/core` from `^4.7` to `v4.16.0`.
	Release notes: https://github.com/pronamic/wp-pay-core/releases/tag/v4.16.0

Full set of changes: [`4.6.0...4.6.1`][4.6.1]

[4.6.1]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/v4.6.0...v4.6.1

## [4.6.0] - 2024-02-07

### Changed

- Optimize performance by reusing instances of `PayFeed` from memory. ([fa89eab](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/fa89eaba746000d5c432b480f1b4f0b4b8e07994))

### Fixed

- Fixed deleting feeds through `PaymentAddOn::delete_feeds()`. ([89f88b7](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/89f88b7ea1b27af52418bf34a04b5c31690f5ff3))

Full set of changes: [`4.5.8...4.6.0`][4.6.0]

[4.6.0]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/v4.5.8...v4.6.0

## [4.5.8] - 2023-11-06

### Commits

- Reduce `get_pronamic_payment()` calls. ([49ced02](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/49ced0204791fbcf05425eafa00b6cea6090c0ae))

Full set of changes: [`4.5.7...4.5.8`][4.5.8]

[4.5.8]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/v4.5.7...v4.5.8

## [4.5.7] - 2023-10-30

### Commits

- Set default label for delayed actions. ([7dc44ae](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/7dc44ae93d891b15aca46b2dafc7646c29b5a37e))
- Check if subscription is available, fixes #35. ([9682673](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/9682673ff1126e0c6a085fe2973c2658172c7b69))

Full set of changes: [`4.5.6...4.5.7`][4.5.7]

[4.5.7]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/v4.5.6...v4.5.7

## [4.5.6] - 2023-07-12

### Commits

- Added label for subscription ID merge tag. ([22466f0](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/22466f07a111097b45d10933e2cbf38132dc1b34))
- Added merge tag `{pronamic_subscription_id}`. ([9d25939](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/9d25939eb1e31d9aa75eaaa4a99a6ecd16a842be))

Full set of changes: [`4.5.5...4.5.6`][4.5.6]

[4.5.6]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/v4.5.5...v4.5.6

## [4.5.5] - 2023-06-01

### Commits

- Switch from `pronamic/wp-deployer` to `pronamic/pronamic-cli`. ([a2363f9](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/a2363f9905b39c539c0be7466caebe8a1faf5514))
- Added support for `gform_confirmation_anchor`. ([ae07289](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/ae07289b7be7bd791c67f75d08b9d68a9f6973ec))
- Updated .gitattributes ([89b6396](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/89b6396e6e6401c06fa3f9eda661a88d6542c20e))

Full set of changes: [`4.5.4...4.5.5`][4.5.5]

[4.5.5]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/v4.5.4...v4.5.5

## [4.5.4] - 2023-03-30

### Commits

- Fixed refunded amount check. ([ea2f0e9](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/ea2f0e9463052d4d5ce349783a0569eb75505644))
- Updated field icons. ([4bf8242](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/4bf8242652cb73b2dfdefc46c5141e0ad479ebfa))

Full set of changes: [`4.5.3...4.5.4`][4.5.4]

[4.5.4]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/v4.5.3...v4.5.4

## [4.5.3] - 2023-03-10

### Commits

- Set Composer type to `wordpress-plugin`. ([9e5255a](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/9e5255a40908235d28ecd8cbcdcbc8dc5fe7d1fa))
- Updated .gitattributes ([fca89e2](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/fca89e24ea2420a931cc19333d1b140ef55c0fec))
- Set default `inputs` parameter to `true` in dropdown input. ([69a3f52](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/69a3f525a76fe2a5c8bf1bd11a2173a5ebd51aad))

Full set of changes: [`4.5.2...4.5.3`][4.5.3]

[4.5.3]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/v4.5.2...v4.5.3

## [4.5.2] - 2023-02-16

### Fixed

- Fixed field inputs options in recurring amount settings field. ([#30](https://github.com/pronamic/wp-pronamic-pay-gravityforms/issues/30))

### Composer

- Changed `wp-pay/core` from `^4.6` to `v4.7.2`.
	Release notes: https://github.com/pronamic/wp-pay-core/releases/tag/v4.7.2
Full set of changes: [`4.5.1...4.5.2`][4.5.2]

[4.5.2]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/v4.5.1...v4.5.2

## [4.5.1] - 2023-01-31
### Commits

- Fixed all choices being removed from payment method field when using the Gravity Forms Partial Entries Add-On (fixes #27). ([73cb2e8](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/73cb2e8bd9b87825de95456a6dbb971a4bd86a6c))

### Composer

- Changed `php` from `>=8.0` to `>=7.4`.
Full set of changes: [`4.5.0...4.5.1`][4.5.1]

[4.5.1]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/v4.5.0...v4.5.1

## [4.5.0] - 2023-01-18
### Added

- Added support for trial period subscription.

### Changed

- Editing a Gravity Forms payment feed uses less JavaScript. 

### Commits

- Only show delay notifications settings if there is one ore more. ([b85708e](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/b85708e1b3d75a7e0e85926aeb475e626b84d33e))

Full set of changes: [`4.4.2...4.5.0`][4.5.0]

[4.5.0]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/v4.4.2...v4.5.0

## [4.4.2] - 2023-01-04
### Fixed

- Fixed problem with saving status page settings in payment feed. ([#14](https://github.com/pronamic/wp-pronamic-pay-gravityforms/issues/14))

### Commits

- Use REST API to sanitize complex input types. ([4c0ffa3](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/4c0ffa32a3e824231f908358bec666c25eccbbcb))
- Use file hashes for script and style version. ([d6d75c4](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/d6d75c460b6797b9525e34e7c8b0c80ae05f282a))
- Fixed PHPStan:  Callback expects 1 parameter, $accepted_args is set to 3. ([5b2dfc6](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/5b2dfc610233bc78468abdf8c6bd992338907721))
- Removed `GravityForms::update_entry( $entry )` function, no longer support Gravity Forms versions before 1.8.8. ([f945126](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/f945126f8e66c082189939c6bb9404901905ad1d))
- The `gform_entry_post_save` hook is a filter, not an action. ([eec0d35](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/eec0d3598457af5f34bbc0571f2467efbf19661c))
- Happy 2023. ([8572f8e](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/8572f8e14b5ec827ff8f900d50ba3063958febc3))

Full set of changes: [`4.4.1...4.4.2`][4.4.2]

[4.4.2]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/v4.4.1...v4.4.2

## [4.4.1] - 2022-12-23

### Commits

- Fixed incorrect update message after updating payment feed. ([39f5e1e](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/39f5e1e6e64a58881c67c177f5872806a29749c8))

Full set of changes: [`4.4.0...4.4.1`][4.4.1]

[4.4.1]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/v4.4.0...v4.4.1

## [4.4.0] - 2022-12-23

### Commits

- Added support for https://github.com/WordPress/wp-plugin-dependencies. ([5d7688c](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/5d7688cde8bac5597f81a8c6b137f4006db46aa2))
- Fixed "Deprecated: dirname(): Passing null to parameter #1 ($path) of type string is deprecated in /wp-content/plugins/gravityforms/includes/addon/class-gf-addon.php on line 6168". ([91e5328](https://github.com/pronamic/wp-pronamic-pay-gravityforms/commit/91e5328becdde0eb48a369bc46a204288a12cf53))

### Composer

- Changed `php` from `>=5.6.20` to `>=8.0`.
- Changed `pronamic/wp-datetime` from `^2.0` to `v2.1.0`.
	Release notes: https://github.com/pronamic/wp-datetime/releases/tag/v4.3.0
- Changed `pronamic/wp-money` from `^2.0` to `v2.2.0`.
	Release notes: https://github.com/pronamic/wp-money/releases/tag/v4.3.0
- Changed `pronamic/wp-number` from `^1.1` to `v1.2.0`.
	Release notes: https://github.com/pronamic/wp-number/releases/tag/v4.3.0
- Changed `wp-pay/core` from `^4.5` to `v4.6.0`.
	Release notes: https://github.com/pronamic/wp-pay-core/releases/tag/v4.3.0
Full set of changes: [`4.3.0...4.4.0`][4.4.0]

[4.4.0]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/v4.3.0...v4.4.0

## [4.3.0] - 2022-11-07
- No support for manual renewals with Gravity Forms.

## [4.2.2] - 2022-10-11
- Fixed catching exceptions in issuer field (pronamic/wp-pronamic-pay-gravityforms#10).

## [4.2.1] - 2022-09-27
- Update to `wp-pay/core` version `^4.4`.

## [4.2.0] - 2022-09-26
- Fixed conditional logic object without any logic.
- Updated for new payment methods and fields registration.

## [4.1.1] - 2022-08-15
- Fixed compatibility issue with plugins producing output in `gform_admin_pre_render` filter.
- Fixed duplicate configurations in payment gateway configuration field setting ([pronamic/wp-pronamic-pay-gravityforms#8](https://github.com/pronamic/wp-pronamic-pay-gravityforms/issues/8)).

## [4.1.0] - 2022-04-11
- Improve payment and subscription source text when Gravity Forms plugin is not active.
- Fix possible invalid empty conditional logic object.
- Add support for `gf_list_*` CSS classes in payment methods field (resolves pronamic/wp-pronamic-pay#312).
- Update IssuersField.php
- Remove error usage.
- Remove gateway error usage, exception should be handled downstream.

## [4.0.1] - 2022-02-16
- Fixed processing delayed feeds during fulfilment of free payments (e.g. user registration for entry with discount; [pronamic/wp-pronamic-pay#279](https://github.com/pronamic/wp-pronamic-pay/issues/279)).

## [4.0.0] - 2022-01-10
### Changed
- Updated to https://github.com/pronamic/wp-pay-core/releases/tag/4.0.0.
- Use Number object for money and calculations.

### Fixed
- Also set alignment phase amount to payment lines amount when not prorating.
- Fixed setting quantity for product options (props @R3dRidl3).

## [3.0.2] - 2021-09-16
- Updated issuers field to only use active payment feeds.
- Fixed duplicate `pronamic_payment_id` entry meta ([pronamic/wp-pronamic-pay#208](https://github.com/pronamic/wp-pronamic-pay/issues/208)).
- Fixed empty merge tags in 'Form is submitted' notification event.

## [3.0.1] - 2021-09-03
- Updated processing of free payments (allows credit card authorizations for subscriptions).

## [3.0.0] - 2021-08-05
- Updated to `pronamic/wp-pay-core`  version `3.0.0`.
- Updated to `pronamic/wp-money`  version `2.0.0`.
- Changed `TaxedMoney` to `Money`, no tax info.
- Switched to `pronamic/wp-coding-standards`.
- Process `0` value for frequency as infinite periods definition for subscription phase.

## [2.7.0] - 2021-06-18
- Added initial support for refunds [#119](https://github.com/pronamic/wp-pronamic-pay/issues/119).

## [2.6.1] - 2021-05-28
- Improved Gravity Forms 2.5.3 compatibility.
- Fixed payment feed conditional logic setting.
- Fixed loading admin script in form editor.

## [2.6.0] - 2021-04-26
- Improved compatibility with Gravity Forms 2.5.
- Improved displaying payment methods icons.
- Removed support for Gravity Forms version < 1.7.

## [2.5.2] - 2021-01-14
- Removed old @see.
- Update readme.

## [2.5.1] - 2020-11-19
- Updated getting subscription from payment period.

## [2.5.0] - 2020-11-09
- Changed 'Frequency' to 'Number of Periods' in payment feed subscription settings.
- Changed 'Synchronized payment date' to 'Fixed Subscription Period' in payment feed subscription settings.
- Places Euro symbol left of amount in Gravity Forms currency when using Dutch language.
- Added Dutch address notation for Gravity Forms.
- Added support for new subscription phases and periods.
- Fixed unselected options in payment method selector after processing conditional logic.

## [2.4.1] - 2020-07-08
- Added support for company name and VAT number.
- Improved Gravity Forms 2.5 beta compatibility.

## [2.4.0] - 2020-06-02
- Add filter `pronamic_pay_gravityforms_delay_actions` for delayed actions.
- Fix empty formatted amount in entry notes if value is `0`.

## [2.3.1] - 2020-04-20
- Fixed PHP notices and warnings.
- Use integration version number for scripts and styles.

## [2.3.0] - 2020-04-03
- Added payment feed fields settings to auto detect first visible field of type in entry.
- Added `pronamic_pay_again_url` merge tag, which can be used to pre-populate form after failed payment.
- Fixed "Warning: Invalid argument supplied for foreach()" in calculation variables select.
- Improved payment feed conditions with support for all fields and multiple rules.
- Improved forms list performance.

## [2.2.0] - 2020-03-19
- Added consumer bank details name and IBAN field settings.
- Fixed adding payment line for shipping costs only when shipping field is being used.
- Fixed dynamically setting selected payment method.
- Fixed feed activation toggle.
- Improved field visibility check with entry.
- Improved payment methods field choices in field input (fixes compatibility with `Gravity Forms Entries in Excel` plugin).
- Extension extends abstract plugin integration with dependency.

## [2.1.15] - 2020-02-03
- Only prorate subscription amount when form field has been set for recurring amount.
- Fixed incorrect currency with multi-currency add-on.
- Fixed starting subscriptions with zero interval days.

## [2.1.14] - 2019-12-22
- Added merge tags for bank transfer recipient details.
- Added notice about subscription frequency being in addition to the first payment.
- Fixed synchronized payment date monthday and month settings.
- Improved error handling with exceptions.
- Improved payment method field creation.
- Updated subscription source details.

## [2.1.13] - 2019-10-07
- Fixed subscription interval date settings field.

## [2.1.12] - 2019-10-04
- Improved RTL support in 'Synchronized payment date' settings fields.
- Fixed loading extension in multisite when plugin is network activated and Gravity Forms is activated per site.

## [2.1.11] - 2019-09-02
- Fix entry payment fulfillment.

## [2.1.10] - 2019-08-30
- Fix possible error with subscriptions "Uncaught Exception: DatePeriod::__construct(): This constructor accepts either...".
- Improve GF Nested Forms compatibility.

## [2.1.9] - 2019-08-26
- Updated packages.
- Removed non-existing Gravity Forms payment status `Reversed`.
- Support `Refunded` status.
- Added merge tag for Pronamic subscription payment ID.
- Run `maybe_process_feed()` if add-on supports delayed payment integration (e.g. Gravity Forms User Registration).
- Update post author and customer user ID for users registered through Gravity Forms User Registration add-on.
- Renamed 'next payment' to 'next payment date'.
- Fulfill orders only once.

## [2.1.8] - 2019-05-15
- Fix payment method field options deselected when saving from form settings subviews.
- Update entry payment status when subscription is manually activated.
- Disable asynchronous feed processing for delayed actions.

## [2.1.7] - 2019-02-04
- Prevent empty country code for unknown countries.

## [2.1.6] - 2019-01-21
- Fixed fatal error in Gravity Forms recurring payments if plugin is not activated.
- Fixed issue with prorating amount when synchronized payment dates are not enabled.
- Enabled placeholder setting for issuers and payment methods field.
- Added extra field map options.
- Added support for payment lines.

## [2.1.5] - 2018-12-13
- Fixed unintended use of synchronized payment date setting for fixed intervals.

## [2.1.4] - 2018-12-12
- Fix delayed feed action for Gravity Forms Zapier add-on.
- Fix fatal error when sending renewal notices.
- Fix delayed feed actions for free payments.
- Update item methods in payment data.

## [2.1.3] - 2018-09-28
- Trigger events for field on change.

## [2.1.2] - 2018-09-12
- Improved support for addons with delayed payment integration support.
- Improved support for delayed Gravity Flow workflows.

## [2.1.1] - 2018-08-28
- The `add_pending_payment` action is no longer triggered for entries without pending payments.

## [2.1.0] - 2018-08-16
- Added support for synchronized subscription payment dates.
- Changed Entry ID prefix field to a Order ID field.
- Set conditional logic dependency for fields used in payment feed conditions.
- Added Pronamic subscription amount merge tag {pronamic_subscription_amount}.
- Added support for duplicating payment feeds.
- Added custom display mode field setting.
- Improved handling delay actions support.
- Removed support for "Gravity Forms User Registration Add-On" version < 3.0.

## [2.0.1] - 2018-06-01
- Fixed using merge tag as order ID.

## [2.0.0] - 2018-05-14
- Switched to PHP namespaces.

## [1.6.7] - 2017-12-12
- Added support for delaying Sliced Invoices feed processing.
- Filter payment method choices if not in form editor.
- Added support for delaying Moneybird feed processing.
- Simplified merge tag replacement.

## [1.6.6] - 2017-09-13
- Implemented `get_first_name()` and `get_last_name()`.
- Fix possible PHP notices for undefined index `id`.

## [1.6.5] - 2017-04-07
- Fulfill order with payment status 'Paid'.
- Prevent sending delayed notification twice.

## [1.6.4] - 2017-03-15
- Use version compare helper to prevent fatal errors.

## [1.6.3] - 2017-03-15
- Updated new feed URL link in payment fields.
- Only load the payment methods field if Gravity Forms version is > 1.9.19.
- Simplified loading and setting up the Gravity Forms extension with a early return.
- Fixed 'Warning: Missing argument 3 for gf_apply_filters()'.
- Added support for delaying ActiveCampaign subscriptions.

## [1.6.2] - 2017-02-10
- No longer check on the payment feed post ID, a empty payment feed post ID is allowed when creating new payment feeds.
- Auto enable new payment feeds.
- Make `is_active` backwards compatible when getting feeds.
- Added support for No Conflict Mode.

## [1.6.1] - 2017-02-09
- Only check admin referer for payment feeds and not when saving/testing configurations.

## [1.6.0] - 2017-01-25
- Added support for subscriptions.
- Added temporary pay feeds moved notice.
- Added filter function for the payment source description.
- Added filter for source URL.

## [1.5.2] - 2016-11-16
- Simplified CSS for WordPress admin elements.
- Enabled choice values for payment methods field.

## [1.5.1] - 2016-10-28
- Changed Gravity Forms admin menu item text 'iDEAL' to 'Payment Feeds'.
- Changed text 'Payment Form(s)' to 'Payment Feed(s)'.

## [1.5.0] - 2016-10-27
- Return gateway if issuers are found, not only when issuers is not null.
- Fixed adding built-in payment method twice.
- Fixed incorrect 'field is not supported' notice by setting form ID when adding field.

## [1.4.9] - 2016-10-20
- Implemented the new `pronamic_payment_redirect_url` filter and added some early returns.
- Fixed deprecated usage of GFUserData.
- Refactored custom payment fields.

## [1.4.8] - 2016-07-06
- Added support for filtering payment data with `gform_post_save`.

## [1.4.7] - 2016-06-08
- Set link type to confirmation if set and no URL or page have been set.
- Cleaned up feed config (tabs, descriptions, tooltips, update confirmations if form changes).
- Added icon and 'Add new' link to payment addon settings page.
- Added Merge Tag button to transaction description field (without AJAX form change support).
- Switched to use of `GF_Field` class.
- Fixed text domain, `pronamic-ideal` is `pronamic_ideal`.

## [1.4.6] - 2016-04-13
- Set global WordPress gateway config as default config in gateways.
- Fixed 'Notice: Undefined index: type'.
- Use 'pay feed' instead of 'iDEAL feed' in payment method selector field.

## [1.4.5] - 2016-03-23
- Fixed - Parse error: syntax error, unexpected T_PAAMAYIM_NEKUDOTAYIM in Extension.php on line 274.

## [1.4.4] - 2016-03-23
- Added support for merge tag 'pronamic_payment_id'.
- Added ability to use Gravity Forms confirmations (with merge tag support) as payment status page.

## [1.4.3] - 2016-03-02
- Return value of get_payment_method() must be string, not array.
- Set link type if none selected.
- WordPress Coding Standards optimizations.
- Added support for country field.
- Added support for telephone number field.
- Moved scripts from the Pronamic iDEAL plugin to this repository.
- Moved styles from the Pronamic iDEAL plugin to this Gravity Forms library.
- Removed use of images.
- Renamed 'iDEAL feed' to 'pay feed' since we support much more payment methods then iDEAL.

## [1.4.2] - 2016-02-12
- Renamed 'iDEAL Fields' to 'Payment Fields' since it's more then iDEAL.
- Fixed typo `sprint` to `sprintf`.

## [1.4.1] - 2016-02-05
- Fixed 'Warning: Invalid argument supplied for foreach() on line 200'.

## [1.4.0] - 2016-02-01
- Updated payment status property instead of whole entry.
- Added Payment Method Selector field and support for payment methods.
- Added default field labels for issuer drop down and payment method selector.
- Added payment method choices from gateway to payment method selector field.
- Fix PHP error if no choices are selected in payment method selector.

## [1.3.2] - 2015-10-21
- Fix missing issuer dropdown in form editor and front end for feeds with condition enabled.
- No longer use an custom query to get the pay Gravity Forms posts.
- Added an extra parameter to retrieve payments feed with an gateway with iDEAL issuers.
- No longer redirect with 303 status code.

## [1.3.1] - 2015-10-14
- Add support for multiple payment feeds with conditions per form.
- Only use visible issuer dropdowns (allows conditional logic on issuer dropdown field.

## [1.3.0] - 2015-06-16
### Added
- Added support for Gravity Forms AWeber Add-On version 2.2.1.
- Added support for Gravity Forms Campaign Monitor Add-On version 3.3.2.
- Added support for Gravity Forms MailChimp Add-On version 3.6.3.

## [1.2.4] - 2015-05-26
### Changed
- Only process payments if amount is higher than zero.

## [1.2.3] - 2015-04-02
- Entry with payment status 'Paid' are now also seen as 'approved'.
- Use entry ID as default transaction description.
- WordPress Coding Standards optimizations.

## [1.2.2] - 2015-03-03
- Changed WordPress pay core library requirement from ~1.0.0 to >=1.1.0.
- Use the new Pronamic_WP_Pay_Class::method_exists() function in the WordPress pay core library.
- Added Pronamic_WP_Pay_Extensions_GravityForms_GravityForms::version_compare() function.
- Fixed issue with adding issuer dropdown field in Gravity Forms version 1.9 or higher.

## [1.2.1] - 2015-02-13
- Fix - PHP crashed on line 3 of method_exists() errors on some hosting environments.

## [1.2.0] - 2015-02-12
- This library now uses the [GFPaymentAddOn class](https://github.com/wp-premium/gravityforms/blob/1.8/includes/addon/class-gf-payment-addon.php) which was introduced in Gravity Forms version 1.8.
- Changed payment gateway slug from 'ideal' to 'pronamic_pay'.

## [1.1.0] - 2015-02-06
- Added some more classes from the Pronamic iDEAL plugin to this library.
- Added support for the Gravity Forms Zapier Add-On.

## [1.0.2] - 2015-01-20
- Require WordPress pay core library version 1.0.0.

## 1.0.1 - 2015-01-02
- Fixed fatal error class not found.

## 1.0.0 - 2015-01-01
- First release.

[unreleased]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/4.3.0...HEAD
[4.3.0]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/4.2.2...4.3.0
[4.2.2]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/4.2.1...4.2.2
[4.2.1]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/4.2.0...4.2.1
[4.2.0]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/4.1.1...4.2.0
[4.1.1]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/4.1.0...4.1.1
[4.1.0]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/4.0.1...4.1.0
[4.0.1]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/4.0.0...4.0.1
[4.0.0]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/3.0.2...4.0.0
[3.0.2]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/3.0.1...3.0.2
[3.0.1]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/3.0.0...3.0.1
[3.0.0]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.7.0...3.0.0
[2.7.0]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.6.1...2.7.0
[2.6.1]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.6.0...2.6.1
[2.6.0]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.5.2...2.6.0
[2.5.2]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.5.1...2.5.2
[2.5.1]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.5.0...2.5.1
[2.5.0]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.4.1...2.5.0
[2.4.1]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.4.0...2.4.1
[2.4.0]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.3.1...2.4.0
[2.3.1]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.3.0...2.3.1
[2.3.0]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.2.0...2.3.0
[2.2.0]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.1.15...2.2.0
[2.1.15]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.1.14...2.1.15
[2.1.14]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.1.13...2.1.14
[2.1.13]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.1.12...2.1.13
[2.1.12]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.1.11...2.1.12
[2.1.11]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.1.10...2.1.11
[2.1.10]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.1.9...2.1.10
[2.1.9]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.1.8...2.1.9
[2.1.8]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.1.7...2.1.8
[2.1.7]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.1.6...2.1.7
[2.1.6]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.1.5...2.1.6
[2.1.5]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.1.4...2.1.5
[2.1.4]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.1.3...2.1.4
[2.1.3]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.1.2...2.1.3
[2.1.2]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.1.1...2.1.2
[2.1.1]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.1.0...2.1.1
[2.1.0]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.0.1...2.1.0
[2.0.1]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.6.7...2.0.0
[1.6.7]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.6.6...1.6.7
[1.6.6]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.6.5...1.6.6
[1.6.5]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.6.4...1.6.5
[1.6.4]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.6.3...1.6.4
[1.6.3]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.6.2...1.6.3
[1.6.2]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.6.1...1.6.2
[1.6.1]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.6.0...1.6.1
[1.6.0]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.5.2...1.6.0
[1.5.2]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.5.1...1.5.2
[1.5.1]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.5.0...1.5.1
[1.5.0]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.4.9...1.5.0
[1.4.9]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.4.8...1.4.9
[1.4.8]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.4.7...1.4.8
[1.4.7]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.4.6...1.4.7
[1.4.6]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.4.5...1.4.6
[1.4.5]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.4.4...1.4.5
[1.4.4]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.4.3...1.4.4
[1.4.3]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.4.2...1.4.3
[1.4.2]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.4.1...1.4.2
[1.4.1]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.4.0...1.4.1
[1.4.0]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.3.2...1.4.0
[1.3.2]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.3.1...1.3.2
[1.3.1]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.3.0...1.3.1
[1.3.0]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.2.4...1.3.0
[1.2.4]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.2.3...1.2.4
[1.2.3]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.2.2...1.2.3
[1.2.2]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.2.1...1.2.2
[1.2.1]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.0.2...1.1.0
[1.0.2]: https://github.com/pronamic/wp-pronamic-pay-gravityforms/compare/1.0.1...1.0.2
