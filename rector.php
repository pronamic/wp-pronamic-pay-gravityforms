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
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;

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

			/*
			 * Each first-class callable is a new `Closure` object, so it can't be
			 * used to find a registered callback with `has_filter()` or
			 * `remove_filter()`.
			 */
			ArrayToFirstClassCallableRector::class  => [
				__DIR__ . '/src/IssuersField.php',
				__DIR__ . '/src/PaymentMethodsField.php',
				__DIR__ . '/src/Processor.php',
			],
		]
	)
	->withTypeCoverageLevel( 0 );
