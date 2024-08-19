<?php

namespace MediaWiki\Extension\CLDR;

use MediaWiki\MediaWikiServices;

/**
 * A class for querying translated time units from CLDR data.
 *
 * @author Niklas Laxström
 * @author Ryan Kaldari
 * @copyright Copyright © 2007-2013
 * @license GPL-2.0-or-later
 */
class TimeUnits {

	/** @var array */
	private static $cache = [];

	/**
	 * Get localized time units for a particular language, using fallback languages for missing
	 * items. The time units are returned as an associative array. The keys are of the form:
	 * <unit>-<tense>-<ordinality> (for example, 'hour-future-two'). The values include a placeholder
	 * for the number (for example, '{0} months ago').
	 *
	 * @param string $code The language to return the list in
	 * @return array an associative array of time unit codes and localized time units
	 */
	public static function getUnits( $code ) {
		// Load time units localized for the requested language
		$units = self::loadLanguage( $code );

		if ( $units ) {
			return $units;
		}
		// Load missing time units from fallback languages
		$fallbacks = MediaWikiServices::getInstance()->getLanguageFallback()->getAll( $code );
		foreach ( $fallbacks as $fallback ) {
			if ( $units ) {
				break;
			}
			// Get time units from a fallback language
			$units = self::loadLanguage( $fallback );
		}

		return $units;
	}

	/**
	 * Load time units localized for a particular language. Helper function for getUnits.
	 *
	 * @param string $code The language to return the list in
	 * @return array an associative array of time unit codes and localized time units
	 */
	private static function loadLanguage( $code ) {
		if ( !isset( self::$cache[$code] ) ) {
			self::$cache[$code] = [];

			$langNameUtils = MediaWikiServices::getInstance()->getLanguageNameUtils();

			if ( !$langNameUtils->isValidBuiltInCode( $code ) ) {
				return [];
			}

			/* Load override for wrong or missing entries in cldr */
			$override = __DIR__ . '/../LocalNames/' .
				$langNameUtils->getFileName( 'LocalNames', $code, '.php' );
			if ( file_exists( $override ) ) {
				$timeUnits = false;

				require $override;

				// @phan-suppress-next-line PhanImpossibleCondition
				if ( is_array( $timeUnits ) ) {
					self::$cache[$code] = $timeUnits;
				}
			}

			$filename = __DIR__ . '/../CldrNames/' .
				$langNameUtils->getFileName( 'CldrNames', $code, '.php' );
			if ( file_exists( $filename ) ) {
				$timeUnits = false;
				require $filename;
				// @phan-suppress-next-line PhanImpossibleCondition
				if ( is_array( $timeUnits ) ) {
					self::$cache[$code] += $timeUnits;
				}
			} else {
				wfDebug( __METHOD__ . ": Unable to load time units for $filename\n" );
			}
		}

		return self::$cache[$code];
	}
}
