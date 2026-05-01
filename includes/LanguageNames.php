<?php

namespace MediaWiki\Extension\CLDR;

use InvalidArgumentException;
use MediaWiki\Json\FormatJson;
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
	private static $cacheWithoutFallbacks = [];

	private static array $cacheWithFallbacks = [];

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
		$services = MediaWikiServices::getInstance();
		$native = $services->getLanguageNameUtils()
			->getLanguageNames(
				LanguageNameUtils::AUTONYMS,
				$list === self::LIST_MW_SUPPORTED ? LanguageNameUtils::SUPPORTED : LanguageNameUtils::DEFINED
			);

		if ( $fbMethod === self::FALLBACK_NATIVE ) {
			$names = array_merge(
				$native,
				self::loadLanguageWithoutFallbacks( $code )
			);
		} elseif ( $fbMethod === self::FALLBACK_NORMAL ) {
			$names = array_merge(
				$native,
				self::loadLanguageWithFallbacks( $code )
			);

			/* As a last resort, try the native name in Names.php */
			if ( isset( $native[$code] ) ) {
				$names[$code] ??= $native[$code];
			}
		} else {
			throw new InvalidArgumentException( "Invalid value for 2:\$fallback in " . __METHOD__ );
		}

		$config = $services->getMainConfig();
		if ( !$config->get( MainConfigNames::UsePigLatinVariant ) ) {
			// Suppress Pig Latin unless explicitly enabled.
			unset( $names['en-x-piglatin'] );
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
	 * Load language names localized for a particular language, without applying fallback languages.
	 * Helper function for getNames.
	 *
	 * @param string $code The language to return the list in
	 * @return array an associative array of language codes and localized language names
	 */
	private static function loadLanguageWithoutFallbacks( $code ) {
		if ( isset( self::$cacheWithoutFallbacks[$code] ) ) {
			return self::$cacheWithoutFallbacks[$code];
		}

		self::$cacheWithoutFallbacks[$code] = [];

		$langNameUtils = MediaWikiServices::getInstance()->getLanguageNameUtils();

		if ( !$langNameUtils->isValidBuiltInCode( $code ) ) {
			return [];
		}

		/*
		 * Load messages from i18n files (they are messages for translation purposes,
		 * allowing translators to correct missing entries in cldr or add extra ones,
		 * but we don't load them via the message infrastructure at all).
		 */

		$i18nFile = __DIR__ . '/../i18n/LanguageNames/' . $code . '.json';
		if ( file_exists( $i18nFile ) && $code !== 'qqq' ) {
			// phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
			$json = @file_get_contents( $i18nFile );
			if ( $json === false ) {
				wfDebug( __METHOD__ . ": Unable to load language names for $i18nFile\n" );
				return self::$cacheWithoutFallbacks[$code];
			}
			$messages = FormatJson::decode( $json, true );
			if ( $messages === null ) {
				wfDebug( __METHOD__ . ": Unable to load language names for $i18nFile\n" );
				return self::$cacheWithoutFallbacks[$code];
			}
			foreach ( $messages as $key => $message ) {
				if ( !str_starts_with( $key, 'cldr-language-name-' ) ) {
					continue;
				}
				if ( !$message || $message === '-' ) {
					continue;
				}
				$code2 = substr(
					$key,
					// strlen( 'cldr-language-name-' )
					19,
				);
				// TODO do we need to handle !!FUZZY!! ?
				self::$cacheWithoutFallbacks[$code][$code2] = $message;
			}
		}

		// remove falsy language names (LocalNames can override/unset CldrMain this way)
		self::$cacheWithoutFallbacks[$code] = array_filter( self::$cacheWithoutFallbacks[$code] );

		return self::$cacheWithoutFallbacks[$code];
	}

	/**
	 * Load language names localized for a particular language, already applying language fallbacks.
	 * Helper function for getNames.
	 *
	 * @param string $code The language to return the list in
	 * @return array an associative array of language codes and localized language names
	 */
	private static function loadLanguageWithFallbacks( $code ) {
		if ( isset( self::$cacheWithFallbacks[$code] ) ) {
			return self::$cacheWithFallbacks[$code];
		}

		require __DIR__ . '/../CldrLanguagesWithNames.php';
		/** @var string[] $languagesWithNames */
		'@phan-var-force string[] $languagesWithNames';

		self::$cacheWithFallbacks[$code] = [];
		$localisationCache = MediaWikiServices::getInstance()->getLocalisationCache();
		foreach ( $languagesWithNames as $code2 ) {
			self::$cacheWithFallbacks[$code][$code2] =
				$localisationCache->getSubitem( $code, 'messages', "cldr-language-name-$code2" );
		}

		// remove falsy language names (LocalNames can override/unset CldrMain this way)
		self::$cacheWithFallbacks[$code] = array_filter( self::$cacheWithFallbacks[$code] );

		return self::$cacheWithFallbacks[$code];
	}
}

class_alias( LanguageNames::class, 'LanguageNames' );
