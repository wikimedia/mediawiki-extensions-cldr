<?php

namespace MediaWiki\Extension\CLDR;

use InvalidArgumentException;
use MediaWiki\Languages\LanguageNameUtils;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;

/**
 * A class for querying translated language names from CLDR data.
 *
 * @author Niklas Laxström
 * @author Ryan Kaldari
 * @copyright Copyright © 2007-2011
 * @license GPL-2.0-or-later
 */
class LanguageNames {

	/** @var array */
	private static $cache = [];

	/**
	 * Missing entries fall back to the language's name for itself
	 */
	public const FALLBACK_NATIVE = 0;
	/**
	 * Missing entries are sought in the MediaWiki fallback chain
	 */
	public const FALLBACK_NORMAL = 1;
	/**
	 * Only include languages supported by core MediaWiki localisation.
	 * Corresponds to LanguageNameUtils::SUPPORTED.
	 */
	public const LIST_MW_SUPPORTED = 0;
	/**
	 * Only include languages in LIST_MW_SUPPORTED, plus anything defined in wgExtraLanguageNames.
	 * Corresponds to LanguageNameUtils::DEFINED.
	 */
	public const LIST_MW = 1;
	/**
	 * All languages in the CLDR data, including languages unknown to MediaWiki.
	 */
	public const LIST_MW_AND_CLDR = 2;

	/**
	 * Get localized language names for a particular language, using fallback languages for missing
	 * items.
	 *
	 * @param string $code
	 * @param int $fbMethod
	 * @param int $list
	 * @return array an associative array of language codes and localized language names
	 */
	public static function getNames( $code, $fbMethod = self::FALLBACK_NATIVE,
		$list = self::LIST_MW
	) {
		$xx = self::loadLanguage( $code );

		$services = MediaWikiServices::getInstance();
		$config = $services->getMainConfig();
		if ( !$config->get( MainConfigNames::UsePigLatinVariant ) ) {
			// Suppress Pig Latin unless explicitly enabled.
			unset( $xx['en-x-piglatin'] );
		}

		$native = $services->getLanguageNameUtils()
			->getLanguageNames(
				LanguageNameUtils::AUTONYMS,
				$list === self::LIST_MW_SUPPORTED ? LanguageNameUtils::SUPPORTED : LanguageNameUtils::DEFINED
			);

		if ( $fbMethod === self::FALLBACK_NATIVE ) {
			$names = array_merge( $native, $xx );
		} elseif ( $fbMethod === self::FALLBACK_NORMAL ) {
			// Load missing language names from fallback languages
			$fb = $xx;

			$fallbacks = $services->getLanguageFallback()->getAll( $code );
			foreach ( $fallbacks as $fallback ) {
				// Overwrite the things in fallback with what we have already
				$fb = array_merge( self::loadLanguage( $fallback ), $fb );
			}

			/* Add native names for codes that are not in cldr */
			$names = array_merge( $native, $fb );

			/* As a last resort, try the native name in Names.php */
			if ( isset( $native[$code] ) ) {
				$names[$code] ??= $native[$code];
			}
		} else {
			throw new InvalidArgumentException( "Invalid value for 2:\$fallback in " . __METHOD__ );
		}

		switch ( $list ) {
			case self::LIST_MW:
				/** @noinspection PhpMissingBreakStatementInspection */
			case self::LIST_MW_SUPPORTED:
				/* Remove entries that are not in fb */
				$names = array_intersect_key( $names, $native );
				/* And fall to the return */
			case self::LIST_MW_AND_CLDR:
				return $names;
			default:
				throw new InvalidArgumentException( "Invalid value for 3:\$list in " . __METHOD__ );
		}
	}

	/**
	 * Load currency names localized for a particular language. Helper function for getNames.
	 *
	 * @param string $code The language to return the list in
	 * @return array an associative array of language codes and localized language names
	 */
	private static function loadLanguage( $code ) {
		if ( isset( self::$cache[$code] ) ) {
			return self::$cache[$code];
		}

		self::$cache[$code] = [];

		$langNameUtils = MediaWikiServices::getInstance()->getLanguageNameUtils();

		if ( !$langNameUtils->isValidBuiltInCode( $code ) ) {
			return [];
		}

		/* Load override for wrong or missing entries in cldr */
		$override = __DIR__ . '/../LocalNames/' .
			$langNameUtils->getFileName( 'LocalNames', $code );
		if ( file_exists( $override ) ) {
			$languageNames = false;
			require $override;
			// @phan-suppress-next-line PhanImpossibleCondition
			if ( is_array( $languageNames ) ) {
				self::$cache[$code] = $languageNames;
			}
		}

		$filename = __DIR__ . '/../CldrMain/' .
			$langNameUtils->getFileName( 'CldrMain', $code );
		if ( file_exists( $filename ) ) {
			$languageNames = false;
			require $filename;
			// @phan-suppress-next-line PhanImpossibleCondition
			if ( is_array( $languageNames ) ) {
				self::$cache[$code] += $languageNames;
			}
		} else {
			wfDebug( __METHOD__ . ": Unable to load language names for $filename\n" );
		}

		return self::$cache[$code];
	}
}

class_alias( LanguageNames::class, 'LanguageNames' );
