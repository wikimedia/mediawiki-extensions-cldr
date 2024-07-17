<?php

declare( strict_types = 1 );

/**
 * @coversNothing
 * @license GPL-2.0-or-later
 */
class LocalNamesTest extends MediaWikiIntegrationTestCase {

	/** @dataProvider provideLocalNames */
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

	public static function provideLocalNames(): iterable {
		foreach ( scandir( __DIR__ . '/../../LocalNames' ) as $entry ) {
			$path = __DIR__ . '/../../LocalNames/' . $entry;
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
						// strlen( 'LocalNames' )
						10,
						// strlen( '.php' )
						-4,
					)
				)
			);
			if ( $languageCode === 'en' ) {
				continue;
			}
			yield $languageCode => [ $languageCode ];
		}
	}

}
