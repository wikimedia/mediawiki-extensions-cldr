<?php

use MediaWiki\Extension\CLDR\AvailableCodes;

/**
 * @covers \MediaWiki\Extension\CLDR\AvailableCodes
 * @license GPL-2.0-or-later
 */
class AvailableCodesTest extends MediaWikiIntegrationTestCase {

	public function testGetCodes() {
		$codes = AvailableCodes::getCodes();
		$this->assertIsArray( $codes );
		$this->assertContainsOnly( 'string', $codes );
		$this->assertGreaterThan( 100, count( $codes ) );
		$this->assertContains( 'uz-cyrl', $codes );
		$this->assertSameSize( array_unique( $codes ), $codes );
	}

}
