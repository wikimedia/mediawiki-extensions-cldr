<?php

/**
 * A class for querying translated time units from CLDR data.
 *
 * @author Niklas Laxström
 * @author Ryan Kaldari
 * @copyright Copyright © 2007-2013
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
class TimeUnits extends CldrNames {

	private static $cache = array();

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

		// Load missing time units from fallback languages
		if ( is_callable( array( 'Language', 'getFallbacksFor' ) ) ) {
			// MediaWiki 1.19
			$fallbacks = Language::getFallbacksFor( $code );
			foreach ( $fallbacks as $fallback ) {
				// Overwrite the things in fallback with what we have already
				$units = array_merge( self::loadLanguage( $fallback ), $units );
			}
		} else {
			// MediaWiki 1.18 or earlier
			$fallback = $code;
			while ( $fallback = Language::getFallbackFor( $fallback ) ) {
				// Overwrite the things in fallback with what we have already
				$units = array_merge( self::loadLanguage( $fallback ), $units );
			}
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
			wfProfileIn( __METHOD__ . '-recache' );

			/* Load override for wrong or missing entries in cldr */
			$override = dirname( __FILE__ ) . '/LocalNames/' . self::getOverrideFileName( $code );
			if ( Language::isValidBuiltInCode( $code ) && file_exists( $override ) ) {
				$timeUnits = false;

				require $override;

				if ( is_array( $timeUnits ) ) {
					self::$cache[$code] = $timeUnits;
				}
			}

			$filename = dirname( __FILE__ ) . '/CldrNames/' . self::getFileName( $code );
			if ( Language::isValidBuiltInCode( $code ) && file_exists( $filename ) ) {
				$timeUnits = false;
				require $filename;
				if ( is_array( $timeUnits ) ) {
					if ( isset( self::$cache[$code] ) ) {
						// Add to existing list of localized time units
						self::$cache[$code] = self::$cache[$code] + $timeUnits;
					} else {
						// No list exists, so create it
						self::$cache[$code] = $timeUnits;
					}
				}
			} else {
				wfDebug( __METHOD__ . ": Unable to load time units for $filename\n" );
			}
			if ( !isset( self::$cache[$code] ) ) {
				self::$cache[$code] = array();
			}
			wfProfileOut( __METHOD__ . '-recache' );
		}

		return self::$cache[$code];
	}

}
