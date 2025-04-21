<?php

namespace MediaWiki\Extension\CLDR;

/**
 * List all language codes for which some data has been made available via CLDR.
 *
 * @license GPL-2.0-or-later
 */
class AvailableCodes {

	/** @var string[]|null */
	private static ?array $cache;

	/**
	 * Get the list of available files in CldrMain/CldrMain*.php, built by rebuild.php.
	 *
	 * @return string[] An array of locale codes in MediaWiki form.  These can be passed to
	 * {@see LanguageNameUtils::getFileName} as in rebuild.php if calling code needs the data file
	 * names.
	 */
	public static function getCodes(): array {
		if ( !isset( self::$cache ) ) {
			$availableCodes = [];
			require __DIR__ . '/../CldrAvailableCodes.php';
			self::$cache = $availableCodes;
		}
		return self::$cache;
	}

}
