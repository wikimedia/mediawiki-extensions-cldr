<?php

/**
 * A class for querying translated country names from CLDR data.
 *
 * @author Niklas Laxström
 * @copyright Copyright © 2007-2008, Niklas Laxström
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
class CountryNames {
	// TODO: build this class

	private static $cache = array();

	public static function getNames( $code ) {
		return false;
	}

	public static function getFileName( $code ) {
		return Language::getFileName( "CldrNames", $code, '.php' );
	}

	public static function getOverrideFileName( $code ) {
		return Language::getFileName( "LocalNames", $code, '.php' );
	}
}
