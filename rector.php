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

use Rector\CodingStyle\Rector\FuncCall\FunctionFirstClassCallableRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
	->withPaths(
		[
			__DIR__ . '/src',
			__DIR__ . '/tests',
			__DIR__ . '/views',
		]
	)
	->withPhpSets()
	->withSkip(
		[
			/*
			 * WordPress Coding Standards does not recognize first-class callables
			 * such as `sanitize_text_field( ... )` as sanitizing functions.
			 */
			FunctionFirstClassCallableRector::class => [
				__DIR__ . '/src/PaymentData.php',
			],
		]
	)
	->withTypeCoverageLevel( 0 );
