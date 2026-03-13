<?php

declare( strict_types = 1 );

/**
 * @coversNothing
 * @license GPL-2.0-or-later
 */
class LocalNamesTest extends MediaWikiIntegrationTestCase {

	/** @dataProvider provideLanguageCodes */
	public function testLocalNamesExistInEnglish( string $languageCode ): void {
		$languageNameUtils = $this->getServiceContainer()->getLanguageNameUtils();
		$languageNames = $languageNameUtils->getLanguageNames( $languageCode, $languageNameUtils::ALL );
		$languageCodes = array_keys( $languageNames );
		$languageNamesEn = $languageNameUtils->getLanguageNames( 'en', $languageNameUtils::ALL );
		$languageCodesEn = array_keys( $languageNamesEn );

		// basically $this->assertArrayContains( $languageCodes, $languageCodesEn ),
		// but with a better failure message (no need to print the hundreds of common codes)
		$extraLanguageCodes = array_values( array_diff( $languageCodes, $languageCodesEn ) );
		if ( $extraLanguageCodes === [] ) {
			$this->addToAssertionCount( 1 );
			return;
		}
		$this->fail(
			"There are more language names for '$languageCode' than for 'en'; " .
			"most likely, the following language code(s) should be added to LocalNamesEn.php:\n" .
			var_export( $extraLanguageCodes, true )
		);
	}

	public static function provideLanguageCodes(): iterable {
		$languageCodes = [];
		foreach ( [
			[ __DIR__ . '/../../CldrMain/', 'CldrMain' ],
			[ __DIR__ . '/../../LocalNames/', 'LocalNames' ],
		] as [ $dir, $nameBase ] ) {
			foreach ( scandir( $dir ) as $entry ) {
				$path = $dir . $entry;
				if ( !is_file( $path ) ) {
					continue;
				}
				// inverse LanguageNameUtils::getFileName()
				$languageCode = str_replace(
					'_',
					'-',
					lcfirst(
						substr(
							$entry,
							strlen( $nameBase ),
							-strlen( '.php' ),
						)
					)
				);
				if ( $languageCode === 'en' ) {
					continue;
				}
				$languageCodes[] = $languageCode;
			}
		}
		$languageCodes = array_unique( $languageCodes );
		sort( $languageCodes );
		foreach ( $languageCodes as $languageCode ) {
			yield $languageCode => [ $languageCode ];
		}
	}

}
