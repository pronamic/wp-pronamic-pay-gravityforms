# Change Log

All notable changes to this project will be documented in this file.

This projects adheres to [Semantic Versioning](http://semver.org/) and [Keep a CHANGELOG](http://keepachangelog.com/).

## [Unreleased][unreleased]
- Only process payments if amount is higher than zero.

## [1.2.3] - 2015-04-02
*	Entry with payment status 'Paid' are now also seen as 'approved'.
*	Use entry ID as default transaction description.
*	WordPress Coding Standards optimizations.

## [1.2.2] - 2015-03-03
*	Changed WordPress pay core library requirment from ~1.0.0 to >=1.1.0.
*	Use the new Pronamic_WP_Pay_Class::method_exists() function in the WordPress pay core library.
*	Added Pronamic_WP_Pay_Extensions_GravityForms_GravityForms::version_compare() function.
*	Fixed issue with adding issuer dropdown field in Gravity Forms version 1.9 or higher.

## [1.2.1] - 2015-02-13
*	Fix - PHP crashed on opline 3 of method_exists() errors on some hosting environments.

## [1.2.0] - 2015-02-12
*	This library now uses the [GFPaymentAddOn class](https://github.com/gravityforms/gravityforms/blob/1.8/includes/addon/class-gf-payment-addon.php) wich was introduced in Gravity Forms version 1.8.
*	Changed payment gateway slug from 'ideal' to 'pronamic_pay'.

## [1.1.0] - 2015-02-06
*	Added some more classes from the Pronamic iDEAL plugin to this library.
*	Added support for the Gravity Forms Zapier Add-On.

## [1.0.2] - 2015-01-20
*	Require WordPress pay core library version 1.0.0.

## [1.0.1] - 2015-01-02
*	Fixed fatal error class not found.

## [1.0.0] - 2015-01-01
*	First release.
