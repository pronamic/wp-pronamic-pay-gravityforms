<?php

/**
 * Title: WordPress pay extension Gravity Forms links test
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.0.0
 */
class Pronamic_WP_Pay_Extensions_GravityForms_LinksTest extends PHPUnit_Framework_TestCase {
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
			array( 'open', Pronamic_WP_Pay_Extensions_GravityForms_Links::OPEN ),
			array( 'cancel', Pronamic_WP_Pay_Extensions_GravityForms_Links::CANCEL ),
			array( 'error', Pronamic_WP_Pay_Extensions_GravityForms_Links::ERROR ),
			array( 'success', Pronamic_WP_Pay_Extensions_GravityForms_Links::SUCCESS ),
			array( 'expired', Pronamic_WP_Pay_Extensions_GravityForms_Links::EXPIRED ),
		);
	}
}
