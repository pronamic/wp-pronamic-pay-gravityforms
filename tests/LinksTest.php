<?php

namespace Pronamic\WordPress\Pay\Extensions\GravityForms;

use PHPUnit_Framework_TestCase;

/**
 * Title: WordPress pay extension Gravity Forms links test
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
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
	 */
	public function test( $value, $expected ) {
		$this->assertEquals( $expected, $value );
	}

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
