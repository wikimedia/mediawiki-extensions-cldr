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
