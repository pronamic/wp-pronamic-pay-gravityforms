<?php
/**
 * Gravity Forms Dependency
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

use Pronamic\WordPress\Pay\Dependencies\Dependency;

/**
 * Gravity Forms Dependency
 *
 * @author  Reüel van der Steege
 * @version 2.2.0
 * @since   2.2.0
 */
class GravityFormsDependency extends Dependency {
	/**
	 * Is met.
	 *
	 * @link https://github.com/WebSharks/s2Member/blob/130816/s2member/s2member.php#L69
	 * @return bool True if dependency is met, false otherwise.
	 */
	public function is_met() {
		if ( ! \class_exists( '\GFCommon' ) ) {
			return false;
		}

		return \version_compare(
			\GFCommon::$version,
			'1.7.0',
			'>='
		);
	}
}
