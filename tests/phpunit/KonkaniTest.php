<?php

use MediaWiki\MediaWikiServices;

/**
 * @coversNothing
 */
class KonkaniTest extends MediaWikiIntegrationTestCase {

	public function testKonkani() {
		// Test that the Konkani localisation, which we handle with a weird alias (T347625), works correctly.

		$lang = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'gom' );
		$tsTime = new MWTimestamp( '20121006173100' );
		$currentTime = new MWTimestamp( '20121006173200' );
		$this->assertEquals(
			'1 मिन्टां आदीं',
			$lang->getHumanTimestamp( $tsTime, $currentTime ),
			'1 minute ago'
		);
	}

}
