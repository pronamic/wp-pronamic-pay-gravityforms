# Change Log

All notable changes to this project will be documented in this file.

This projects adheres to [Semantic Versioning](http://semver.org/) and [Keep a CHANGELOG](http://keepachangelog.com/).

## [Unreleased][unreleased]
-

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
- Changed WordPress pay core library requirment from ~1.0.0 to >=1.1.0.
- Use the new Pronamic_WP_Pay_Class::method_exists() function in the WordPress pay core library.
- Added Pronamic_WP_Pay_Extensions_GravityForms_GravityForms::version_compare() function.
- Fixed issue with adding issuer dropdown field in Gravity Forms version 1.9 or higher.

## [1.2.1] - 2015-02-13
- Fix - PHP crashed on opline 3 of method_exists() errors on some hosting environments.

## [1.2.0] - 2015-02-12
- This library now uses the [GFPaymentAddOn class](https://github.com/wp-premium/gravityforms/blob/1.8/includes/addon/class-gf-payment-addon.php) wich was introduced in Gravity Forms version 1.8.
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

[unreleased]: https://github.com/wp-pay-extensions/gravityforms/compare/1.6.5...HEAD
[1.6.5]: https://github.com/wp-pay-extensions/gravityforms/compare/1.6.4...1.6.5
[1.6.4]: https://github.com/wp-pay-extensions/gravityforms/compare/1.6.3...1.6.4
[1.6.3]: https://github.com/wp-pay-extensions/gravityforms/compare/1.6.2...1.6.3
[1.6.2]: https://github.com/wp-pay-extensions/gravityforms/compare/1.6.1...1.6.2
[1.6.1]: https://github.com/wp-pay-extensions/gravityforms/compare/1.6.0...1.6.1
[1.6.0]: https://github.com/wp-pay-extensions/gravityforms/compare/1.5.2...1.6.0
[1.5.2]: https://github.com/wp-pay-extensions/gravityforms/compare/1.5.1...1.5.2
[1.5.1]: https://github.com/wp-pay-extensions/gravityforms/compare/1.5.0...1.5.1
[1.5.0]: https://github.com/wp-pay-extensions/gravityforms/compare/1.4.9...1.5.0
[1.4.9]: https://github.com/wp-pay-extensions/gravityforms/compare/1.4.8...1.4.9
[1.4.8]: https://github.com/wp-pay-extensions/gravityforms/compare/1.4.7...1.4.8
[1.4.7]: https://github.com/wp-pay-extensions/gravityforms/compare/1.4.6...1.4.7
[1.4.6]: https://github.com/wp-pay-extensions/gravityforms/compare/1.4.5...1.4.6
[1.4.5]: https://github.com/wp-pay-extensions/gravityforms/compare/1.4.4...1.4.5
[1.4.4]: https://github.com/wp-pay-extensions/gravityforms/compare/1.4.3...1.4.4
[1.4.3]: https://github.com/wp-pay-extensions/gravityforms/compare/1.4.2...1.4.3
[1.4.2]: https://github.com/wp-pay-extensions/gravityforms/compare/1.4.1...1.4.2
[1.4.1]: https://github.com/wp-pay-extensions/gravityforms/compare/1.4.0...1.4.1
[1.4.0]: https://github.com/wp-pay-extensions/gravityforms/compare/1.3.2...1.4.0
[1.3.2]: https://github.com/wp-pay-extensions/gravityforms/compare/1.3.1...1.3.2
[1.3.1]: https://github.com/wp-pay-extensions/gravityforms/compare/1.3.0...1.3.1
[1.3.0]: https://github.com/wp-pay-extensions/gravityforms/compare/1.2.4...1.3.0
[1.2.4]: https://github.com/wp-pay-extensions/gravityforms/compare/1.2.3...1.2.4
[1.2.3]: https://github.com/wp-pay-extensions/gravityforms/compare/1.2.2...1.2.3
[1.2.2]: https://github.com/wp-pay-extensions/gravityforms/compare/1.2.1...1.2.2
[1.2.1]: https://github.com/wp-pay-extensions/gravityforms/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/wp-pay-extensions/gravityforms/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/wp-pay-extensions/gravityforms/compare/1.0.2...1.1.0
[1.0.2]: https://github.com/wp-pay-extensions/gravityforms/compare/1.0.1...1.0.2
