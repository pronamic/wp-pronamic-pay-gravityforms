<?php
/**
 * Bootstrap tests
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2026 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

putenv( 'WP_PHPUNIT__TESTS_CONFIG=tests/wp-config.php' );

require_once __DIR__ . '/../vendor/autoload.php';

require_once getenv( 'WP_PHPUNIT__DIR' ) . '/includes/functions.php';

/**
 * Manually load plugin.
 */
function _manually_load_plugin() {
	require __DIR__ . '/../pronamic-pay-gravity-forms.php';

	global $pronamic_ideal;

	$pronamic_ideal = pronamic_pay_plugin();
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

/**
 * SQLite database drop-in.
 *
 * Copies the `db.php` drop-in from the SQLite Database Integration plugin into
 * the WordPress content directory, so the tests run without a MySQL server.
 *
 * @link https://github.com/WordPress/sqlite-database-integration
 */
$sqlite_plugin_dir = dirname( __DIR__ ) . '/vendor/wp-plugin/sqlite-database-integration';

$sqlite_dropin = file_get_contents( $sqlite_plugin_dir . '/db.copy' );

if ( false === $sqlite_dropin ) {
	echo 'SQLite Database Integration plugin not found, run `composer install`.' . PHP_EOL;

	exit( 1 );
}

$sqlite_dropin = str_replace(
	[
		'{SQLITE_IMPLEMENTATION_FOLDER_PATH}',
		'{SQLITE_PLUGIN}',
	],
	[
		$sqlite_plugin_dir,
		'sqlite-database-integration/load.php',
	],
	$sqlite_dropin
);

$wp_content_dir = dirname( __DIR__ ) . '/wordpress/wp-content';

if ( ! is_dir( $wp_content_dir ) ) {
	mkdir( $wp_content_dir );
}

file_put_contents( $wp_content_dir . '/db.php', $sqlite_dropin );

// Bootstrap.
require getenv( 'WP_PHPUNIT__DIR' ) . '/includes/bootstrap.php';
