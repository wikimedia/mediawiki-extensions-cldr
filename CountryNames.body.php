<?php

/**
 * A class for querying translated country names from CLDR data.
 *
 * @author Niklas Laxström, Ryan Kaldari
 * @copyright Copyright © 2007-2011
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
class CountryNames {

	private static $cache = array();

	const LIST_CLDR         = 0; // Return all countries listed in CLDR
	const LIST_FUNDRAISING  = 1; // Return only countries that are not embargoed

	/**
	 * Get localized country names for a particular language, using fallback languages for missing
	 * items.
	 *
	 * @param string $code The language to return the list in
	 * @param int $fbMethod The fallback method
	 * @param int $list Which type of list to return
	 * @return an associative array of country codes and localized country names
	 */
	public static function getNames( $code, $list = self::LIST_CLDR ) {
		// Load country names localized for the requested language
		$names = self::loadLanguage( $code );
		
		// Load missing country names from fallback languages
		$fallbacks = Language::getFallbacksFor( $code );
		foreach ( $fallbacks as $fallback ) {
			// Overwrite the things in fallback with what we have already
			$names = array_merge( self::loadLanguage( $fallback ), $names );
		}
		
		if ( $list == self::LIST_FUNDRAISING ) {
			// Remove embargoed countries
			unset( $names['CU'] ); // Cuba
			unset( $names['IR'] ); // Iran
			unset( $names['SY'] ); // Syria
		}
	}
	
	/**
	 * Load country names localized for a particular language.
	 *
	 * @param string $code The language to return the list in
	 * @return an associative array of country codes and localized country names
	 */
	private static function loadLanguage( $code ) {
		// TODO: Build this.
	}
	
	/**
	 * @param string $code
	 * @return string
	 */
	public static function getFileName( $code ) {
		return Language::getFileName( "CldrNames", $code, '.php' );
	}

	/**
	 * @param string $code
	 * @return string
	 */
	public static function getOverrideFileName( $code ) {
		return Language::getFileName( "LocalNames", $code, '.php' );
	}
}
