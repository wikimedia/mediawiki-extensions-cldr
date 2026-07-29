<?php

use MediaWiki\Extension\CLDR\CLDRParser;

/**
 * @license GPL-2.0-or-later
 *
 * @covers MediaWiki\Extension\CLDR\CLDRParser
 */
class CLDRParserTest extends MediaWikiIntegrationTestCase {

	public function testParseMain() {
		$expectedResult = [
			'languageNames' => [
				'aa' => 'Afar',
				'ab' => 'Abkasies',
				'ace' => 'Atsjenees',
			],
			'currencyNames' => [
				'AED' => 'Verenigde Arabiese Emirate-dirham',
				'AFN' => 'Afgaanse afgani',
				'ALL' => 'Albanese lek',
				'RUB' => 'Russiese roebel',
			],
			'currencySymbols' => [
				'RUB' => '₽'
			],
			'countryNames' => [
				'AC' => 'Ascensioneiland',
				'AD' => 'Andorra',
				'AE' => 'Verenigde Arabiese Emirate',
			],
			'timeUnits' => [
				'century-one' => '{0} eeu',
				'century-other' => '{0} eeue',
				'decade-one' => '{0} dekade',
				'decade-other' => '{0} dekades',
			],
			'indexCharacters' => [ 'A', 'B', 'C', 'D', 'E', 'F', 'GH' ]
		];
		$p = new CLDRParser();
		$this->assertEquals(
			$expectedResult,
			$p->parseMain( __DIR__ . '/../data/main.xml' )
		);
	}

	public function testParseSupplemental() {
		$expectedResult = [
			'currencyFractions' => [
				'!DEFAULT' => [
					'digits' => '2',
					'rounding' => '0'
				],
				'DKK' => [
					'digits' => '2',
					'rounding' => '0',
					'cashRounding' => '50',
				],
				'GYD' => [
					'digits' => '2',
					'rounding' => '0',
					'cashDigits' => '0',
					'cashRounding' => '0',
				],
			],
			'localeCurrencies' => [
				'AC' => [ 'SHP' ],
				'AD' => [ 'EUR' ],
			],
		];
		$p = new CLDRParser();
		$this->assertEquals(
			$expectedResult,
			$p->parseSupplemental( __DIR__ . '/../data/supplemental.xml' )
		);
	}

	public function testParseParentLocales() {
		$expectedResult = [
			// A script that is not the likely one for the language inherits from root
			'xx_Cyrl' => 'root',
			'yy_Arab' => 'root',
			'xx_GB' => 'xx_001',
			'xx_IE' => 'xx_001',
			// The component="collations" block does not apply to the data we extract, so it must
			// not override xx_Cyrl above
		];
		$p = new CLDRParser();
		$this->assertSame(
			$expectedResult,
			$p->parseParentLocales( __DIR__ . '/../data/supplemental.xml' )
		);
	}

	public function testInheritFromParents() {
		$dataDir = __DIR__ . '/../data/inheritance';
		// xx_GB reaches xx_001 through an override, and xx_001 reaches xx by truncation
		$parentLocales = [ 'xx_GB' => 'xx_001' ];
		$expectedResult = [
			'languageNames' => [
				// Inherited from xx, overridden by xx_001, overridden by xx_GB, added by xx_GB
				'aa' => 'Afar',
				'ab' => 'Abkhazian (world)',
				'ace' => 'Achinese (GB)',
				'zu' => 'Zulu (GB)',
			],
			'countryNames' => [
				'AC' => 'Ascension Island',
				'AD' => 'Andorra (world)',
			],
			'currencyNames' => [ 'AED' => 'dirham' ],
			'currencySymbols' => [ 'AED' => 'DH' ],
			'timeUnits' => [
				'century-one' => '{0} century (world)',
				'century-other' => '{0} centuries',
			],
			// A sequence, so it is inherited whole rather than merged key by key
			'indexCharacters' => [ 'C', 'A', 'B' ],
		];
		$p = new CLDRParser();
		$data = $p->parseMain( "$dataDir/xx_GB.xml" );
		$actualResult = $p->inheritFromParents( $data, $dataDir, 'xx_GB', $parentLocales );

		$this->assertEquals( $expectedResult, $actualResult );
		$this->assertSame(
			[ 'aa', 'ab', 'ace', 'zu' ],
			array_keys( $actualResult['languageNames'] ),
			'Inherited entries are sorted in among the ones the locale defines itself'
		);
		$this->assertSame(
			[ 'C', 'A', 'B' ],
			$actualResult['indexCharacters'],
			'The order of an inherited alphabet is preserved'
		);
	}

	public function testInheritFromParentsDetectsCycles() {
		$p = new CLDRParser();
		$this->expectException( RuntimeException::class );
		$p->inheritFromParents(
			$p->parseMain( __DIR__ . '/../data/main.xml' ),
			__DIR__ . '/../data/inheritance',
			'xx_GB',
			[ 'xx_GB' => 'xx_001', 'xx_001' => 'xx_GB' ]
		);
	}

	public function testParseCurrencySymbols() {
		$expectedResult = [
			'currencySymbols' => [
				'AUD' => [
					'test-aa' => '$AU',
					'test-bb' => [
						'CA' => '$ AU',
					],
				],
				'RUB' => [
					'test-aa' => '₽'
				]
			]
		];
		$p = new CLDRParser();
		$this->assertEquals(
			$expectedResult,
			$p->parseCurrencySymbols( __DIR__ . '/../data/currencyFixtures/' )
		);
	}
}
