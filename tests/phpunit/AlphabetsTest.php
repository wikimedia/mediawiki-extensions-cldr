<?php

use MediaWiki\Extension\CLDR\Alphabets;

/**
 * @covers \MediaWiki\Extension\CLDR\Alphabets
 * @license GPL-2.0-or-later
 */
class AlphabetsTest extends MediaWikiIntegrationTestCase {

	/**
	 * @dataProvider providerGetIndexCharacters
	 */
	public function testGetIndexCharacters( string $languageCode, array $expected ) {
		$actual = Alphabets::getIndexCharacters( $languageCode );
		$this->assertSame( $expected, $actual );
	}

	public static function providerGetIndexCharacters() {
		return [
			'No RTL issues' => [ 'ar', [
				'ا', 'ب', 'ت', 'ث', 'ج', 'ح', 'خ', 'د', 'ذ', 'ر', 'ز', 'س', 'ش',
				'ص', 'ض', 'ط', 'ظ', 'ع', 'غ', 'ف', 'ق', 'ك', 'ل', 'م', 'ن', 'ه',
				'و', 'ي'
			] ],
			'Uses index alphabet first' => [ 'en', range( 'A', 'Z' ) ],
			'Handles multi-symbol letters' => [ 'ln', [
				'A', 'B', 'C', 'D', 'E', 'Ɛ', 'F', 'G', 'Gb', 'H', 'I', 'K',
				'L', 'M', 'Mb', 'Mp', 'N', 'Nd', 'Ng', 'Nk', 'Ns', 'Nt', 'Ny',
				'Nz', 'O', 'P', 'R', 'S', 'T', 'U', 'V', 'W', 'Y', 'Z'
			] ],
			'Decodes unicode escape into charpoint' => [ 'mr', [
				"\u{200D}", 'ॐ', 'ं', 'ः', 'अ', 'आ', 'इ', 'ई', 'उ', 'ऊ', 'ऋ',
				'ऌ', 'ए', 'ऐ', 'ऑ', 'ओ', 'औ', 'क', 'ख', 'ग', 'घ', 'ङ', 'च', 'छ',
				'ज', 'झ', 'ञ', 'ट', 'ठ', 'ड', 'ढ', 'ण', 'त', 'थ', 'द', 'ध', 'न',
				'प', 'फ', 'ब', 'भ', 'म', 'य', 'र', 'ल', 'व', 'श', 'ष', 'स', 'ह',
				'ळ', 'ऽ', 'ॅ', '्'
			] ]
		];
	}

}
