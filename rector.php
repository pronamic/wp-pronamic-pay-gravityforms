<?php
/**
 * Rector
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2026 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
	->withPaths(
		[
			__DIR__ . '/src',
			__DIR__ . '/tests',
			__DIR__ . '/views',
		]
	)
	// uncomment to reach your current PHP version
	// ->withPhpSets()
	->withTypeCoverageLevel( 0 );
