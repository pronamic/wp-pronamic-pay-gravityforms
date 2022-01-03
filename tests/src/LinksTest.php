<?php
/**
 * Links test.
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2022 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\GravityForms
 */

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

use PHPUnit_Framework_TestCase;

/**
 * Title: WordPress pay extension Gravity Forms links test
 * Description:
 * Copyright: 2005-2022 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.0
 * @since   1.0.0
 */
class LinksTest extends PHPUnit_Framework_TestCase {
	/**
	 * Test.
	 *
	 * @dataProvider matrix_provider
	 *
	 * @param string $value    Value.
	 * @param string $expected Expected value.
	 */
	public function test( $value, $expected ) {
		$this->assertEquals( $expected, $value );
	}

	/**
	 * Test provider.
	 *
	 * @return array
	 */
	public function matrix_provider() {
		return array(
			array( 'open', Links::OPEN ),
			array( 'cancel', Links::CANCEL ),
			array( 'error', Links::ERROR ),
			array( 'success', Links::SUCCESS ),
			array( 'expired', Links::EXPIRED ),
		);
	}
}
