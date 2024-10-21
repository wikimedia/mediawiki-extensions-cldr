<?php

namespace MediaWiki\Extension\CLDR;

use MediaWiki\MediaWikiServices;

/**
 * Series of main alphabet symbols for a language, parsed from CLDR data.
 * https://www.unicode.org/cldr/charts/45/by_type/core_data.alphabetic_information.html
 *
 * @license GPL-2.0-or-later
 */
class Alphabets {

	/** @var array<string,string[]|null> */
	private static array $cache = [];

	/**
	 * Get alphabet for a locale or its fallbacks
	 *
	 * The index or main alphabet, found in CLDR main `characters.exemplarCharacters`,
	 * and reduced to a simple form for creating a sequence.
	 *
	 * @param string $code Locale to query. If no entry exists, the fallback
	 * locales are iterated.
	 * @return string[] a sequence of symbols
	 */
	public static function getIndexCharacters( string $code ): array {
		$fallbacks = [
			$code,
			...MediaWikiServices::getInstance()->getLanguageFallback()->getAll( $code )
		];
		foreach ( $fallbacks as $languageCode ) {
			$indexCharacters = self::loadLanguage( $languageCode );
			if ( $indexCharacters ) {
				return $indexCharacters;
			}
		}
		return [];
	}

	/**
	 * Load alphabets for a particular language.
	 *
	 * @param string $code The language to return the list in
	 * @return string[]|null a list of characters in the language's index alphabet.
	 */
	private static function loadLanguage( string $code ): ?array {
		if ( !array_key_exists( $code, self::$cache ) ) {
			$langNameUtils = MediaWikiServices::getInstance()->getLanguageNameUtils();

			if ( !$langNameUtils->isValidBuiltInCode( $code ) ) {
				return null;
			}

			$filename = __DIR__ . '/../CldrMain/' .
				$langNameUtils->getFileName( 'CldrMain', $code );
			if ( file_exists( $filename ) ) {
				$indexCharacters = null;
				require $filename;
				self::$cache[$code] = $indexCharacters;
			} else {
				wfDebug( __METHOD__ . ": Unable to load alphabet for $filename\n" );
			}
		}

		return self::$cache[$code] ?? null;
	}
}
