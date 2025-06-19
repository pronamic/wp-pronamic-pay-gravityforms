<?php
/**
 * Plugin Name: Pronamic Pay Gravity Forms Add-On
 * Plugin URI: https://www.pronamic.eu/plugins/pronamic-pay-gravityforms/
 * Description: Extend the Pronamic Pay plugin with Gravity Forms support to receive payments through a variety of payment providers.
 *
 * Version: 4.10.1
 * Requires at least: 4.7
 * Requires PHP: 7.4
 *
 * Author: Pronamic
 * Author URI: https://www.pronamic.eu/
 *
 * Text Domain: pronamic-pay-gravity-forms
 * Domain Path: /languages/
 *
 * License: GPL-3.0-or-later
 *
 * Depends: wp-pay/core
 *
 * GitHub URI: https://github.com/pronamic/wp-pronamic-pay-gravityforms
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Autoload.
 */
require_once __DIR__ . '/vendor/autoload_packages.php';

/**
 * Bootstrap.
 */
\Pronamic\WordPress\Pay\Plugin::instance(
	[
		'file'             => __FILE__,
		'action_scheduler' => __DIR__ . '/packages/woocommerce/action-scheduler/action-scheduler.php',
	]
);

add_filter(
	'pronamic_pay_plugin_integrations',
	function ( $integrations ) {
		foreach ( $integrations as $integration ) {
			if ( $integration instanceof \Pronamic\WordPress\Pay\Extensions\GravityForms\Extension ) {
				return $integrations;
			}
		}

		$integrations[] = new \Pronamic\WordPress\Pay\Extensions\GravityForms\Extension();

		return $integrations;
	}
);

if ( class_exists( \Pronamic\WordPress\Pay\Gateways\Mollie\Integration::class ) ) {
	add_filter(
		'pronamic_pay_gateways',
		function ( $gateways ) {
			$gateways[] = new \Pronamic\WordPress\Pay\Gateways\Mollie\Integration(
				[
					'manual_url' => \__( 'https://www.pronamicpay.com/en/manuals/how-to-connect-mollie-to-wordpress-with-pronamic-pay/', 'pronamic-pay-gravity-forms' ),
				]
			);

			return $gateways;
		}
	);
}
