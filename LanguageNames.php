<?php
if (!defined('MEDIAWIKI')) die();
/**
 * An extension which provised localised language names for other extensions
 *
 * @addtogroup Extensions
 *
 * @author Niklas Laxström
 * @copyright Copyright © 2007, Niklas Laxström
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['other'][] = array(
	'name' => 'Language names',
	'version' => '1.0',
	'author' => 'Niklas Laxström',
	'description' => 'Extension which provised localised language names'
);

class LanguageNames {

	private static $cache = array();

	const FALLBACK_NATIVE   = 0;
	const FALLBACK_NORMAL   = 1;
	const LIST_MW_SUPPORTED = 0;
	const LIST_MW           = 1;
	const LIST_MW_AND_CLDR  = 2;


	public static function getNames( $code, $fallback = self::FALLBACK_NATIVE, $list = self::LIST_MW ) {

		$xx = self::loadLanguage( $code );
		$native = Language::getLanguageNames( $list === self::LIST_MW_SUPPORTED );

		if ( $fallback === self::FALLBACK_NATIVE ) {
			$names = array_merge( $native, $xx );
		} elseif ( $fallback === self::FALLBACK_NORMAL ) {
			$fallback = $code;
			$fb = $xx;
			while ( $fallback = Language::getFallbackFor( $fallback ) ) {
				/* Over write the things in fallback with what we have already */
				$fb = array_merge( self::loadLanguage( $fallback ), $fb );
			}

			/* And lastly, add native names for codes that are not in cldr */
			$names = array_merge( $native, $fb );
		} else {
			throw new MWException( "Invalid value for 2:\$fallback in ".__METHOD__ );
		}

		switch ( $list ) {
			case self::LIST_MW:
			case self::LIST_MW_SUPPORTED:
				/* Remove entries that are not in fb */
				$names = array_intersect_key( $names, $native );
				/* And fall to the return */
			case self::LIST_MW_AND_CLDR:
				return $names;
			default:
				throw new MWException( "Invalid value for 3:\$list in ".__METHOD__ );
		}

	}

	private static function loadLanguage( $code ) {
		if ( !isset(self::$cache[$code]) ) {
			$filename = dirname(__FILE__) . '/' . self::getFileName( $code );
			if ( file_exists( $filename ) ) {
				$names = false;
				require( $filename );
				if ( is_array( $names ) ) {
					self::$cache[$code] = $names;
				}
			} else {
				wfDebug( __METHOD__ . ": Unable to load language names for $filename\n" );
			}
		}

		return isset( self::$cache[$code] ) ? self::$cache[$code] : array();
	}

	public static function getFileName( $code ) {
		global $IP;
		return Language::getFileName( "LanguageNames", $code, '.php' );
	}

}