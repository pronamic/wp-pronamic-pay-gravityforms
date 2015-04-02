# Changelog

## 1.2.3
*	Entry with payment status 'Paid' are now also seen as 'approved'.
*	WordPress Coding Standards optimizations.

## 1.2.2
*	Changed WordPress pay core library requirment from ~1.0.0 to >=1.1.0.
*	Use the new Pronamic_WP_Pay_Class::method_exists() function in the WordPress pay core library.
*	Added Pronamic_WP_Pay_Extensions_GravityForms_GravityForms::version_compare() function.
*	Fixed issue with adding issuer dropdown field in Gravity Forms version 1.9 or higher.

## 1.2.1
*	Fix - PHP crashed on opline 3 of method_exists() errors on some hosting environments.

## 1.2.0
*	This library now uses the [GFPaymentAddOn class](https://github.com/gravityforms/gravityforms/blob/1.8/includes/addon/class-gf-payment-addon.php) wich was introduced in Gravity Forms version 1.8.
*	Changed payment gateway slug from 'ideal' to 'pronamic_pay'.

## 1.1.0
*	Added some more classes from the Pronamic iDEAL plugin to this library.
*	Added support for the Gravity Forms Zapier Add-On.

## 1.0.2
*	Require WordPress pay core library version 1.0.0.

## 1.0.1
*	Fixed fatal error class not found.

## 1.0.0
*	First release.
