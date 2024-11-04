<?php

namespace MediaWiki\Extension\CLDR;

use RuntimeException;
use SimpleXMLElement;

/**
 * Extract data from cldr XML.
 *
 * @author Niklas Laxström
 * @author Ryan Kaldari
 * @author Santhosh Thottingal
 * @author Sam Reed
 * @copyright Copyright © 2007-2015
 * @license GPL-2.0-or-later
 */
class CLDRParser {

	public const LOCALITY_DEFAULT = '!DEFAULT';
	public const LANGUAGE_DEFAULT = '!root';
	public const CURRENCY_DEFAULT = '!DEFAULT';

	/**
	 * Read the main/<locale>.xml file from CLDR core and convert to PHP
	 *
	 * @param string $inputFile filename
	 */
	public function parseMain( $inputFile ): array {
		$contents = file_get_contents( $inputFile );
		$doc = new SimpleXMLElement( $contents );

		$data = [
			'languageNames' => [],
			'currencyNames' => [],
			'currencySymbols' => [],
			'countryNames' => [],
			'timeUnits' => [],
		];

		foreach ( $doc->xpath( '//languages/language' ) as $elem ) {
			if ( (string)$elem['alt'] !== '' ) {
				continue;
			}

			if ( (string)$elem['type'] === 'root' ) {
				continue;
			}

			$key = str_replace( '_', '-', strtolower( $elem['type'] ) );

			$data['languageNames'][$key] = (string)$elem;
		}

		foreach ( $doc->xpath( '//currencies/currency' ) as $elem ) {
			if ( (string)$elem->displayName[0] === '' ) {
				continue;
			}

			$data['currencyNames'][(string)$elem['type']] = (string)$elem->displayName[0];
			if ( (string)$elem->symbol[0] !== '' ) {
				$data['currencySymbols'][(string)$elem['type']] = (string)$elem->symbol[0];
			}
		}

		foreach ( $doc->xpath( '//territories/territory' ) as $elem ) {
			if ( (string)$elem['alt'] !== '' && (string)$elem['alt'] !== 'short' ) {
				continue;
			}

			if ( (string)$elem['type'] === 'ZZ' ||
				!preg_match( '/^[A-Z][A-Z]$/', $elem['type'] )
			) {
				continue;
			}

			$data['countryNames'][(string)$elem['type']] = (string)$elem;
		}
		foreach ( $doc->xpath( '//units/unitLength' ) as $unitLength ) {
			if ( (string)$unitLength['type'] !== 'long' ) {
				continue;
			}
			foreach ( $unitLength->unit as $elem ) {
				$type = (string)$elem['type'];
				$pos = strpos( $type, 'duration' );
				if ( $pos === false ) {
					continue;
				}
				$type = substr( $type, strlen( 'duration-' ) );
				foreach ( $elem->unitPattern as $pattern ) {
					$data['timeUnits'][$type . '-' . (string)$pattern['count']] = (string)$pattern;
				}
			}
		}
		foreach ( $doc->xpath( '//fields/field' ) as $field ) {
			$fieldType = (string)$field['type'];

			foreach ( $field->relativeTime as $relative ) {
				$type = (string)$relative['type'];
				foreach ( $relative->relativeTimePattern as $pattern ) {
					$data['timeUnits'][$fieldType . '-' . $type
					. '-' . (string)$pattern['count']] = (string)$pattern;
				}
			}
		}

		ksort( $data['timeUnits'] );
		return $data;
	}

	/**
	 * Parse method for the file structure found in common/supplemental/supplementalData.xml
	 * @param string $inputFile
	 */
	public function parseSupplemental( $inputFile ): array {
		// Open the input file for reading

		$contents = file_get_contents( $inputFile );
		$doc = new SimpleXMLElement( $contents );

		$data = [
			'currencyFractions' => [],
			'localeCurrencies' => [],
		];

		// Pull currency attributes - digits, rounding, and cashRounding.
		// This will tell us how many decmal places make sense to use with any currency,
		// or if the currency is totally non-fractional
		foreach ( $doc->xpath( '//currencyData/fractions/info' ) as $elem ) {
			$iso4217 = (string)$elem['iso4217'];
			if ( $iso4217 === '' ) {
				continue;
			}
			if ( $iso4217 === 'DEFAULT' ) {
				$iso4217 = self::CURRENCY_DEFAULT;
			}

			$attributes = [ 'digits', 'rounding', 'cashDigits', 'cashRounding' ];
			foreach ( $attributes as $att ) {
				if ( (string)$elem[$att] !== '' ) {
					$data['currencyFractions'][$iso4217][$att] = (string)$elem[$att];
				}
			}
		}

		ksort( $data['currencyFractions'] );

		// Pull a map of regions to currencies in order of preference.
		foreach ( $doc->xpath( '//currencyData/region' ) as $elem ) {
			if ( (string)$elem['iso3166'] === '' ) {
				continue;
			}

			$region = (string)$elem['iso3166'];

			foreach ( $elem->currency as $currencynode ) {
				if ( (string)$currencynode['to'] === '' && (string)$currencynode['tender'] !== 'false' ) {
					$data['localeCurrencies'][$region][] = (string)$currencynode['iso4217'];
				}
			}
		}

		ksort( $data['localeCurrencies'] );
		return $data;
	}

	/**
	 * Parse method for the currency section in the names files.
	 * This is separate from the regular parse function, because we need all of
	 * the currency locale information, even if mediawiki doesn't support the language.
	 * (For instance: en_AU uses '$' for AUD, not USD, but it's not a supported mediawiki locality)
	 * @param string $inputDir the directory, in which we will parse everything.
	 */
	public function parseCurrencySymbols( $inputDir ): array {
		if ( !file_exists( $inputDir ) ) {
			throw new RuntimeException( 'Input directory not found.' );
		}
		$files = scandir( $inputDir );

		$data = [
			'currencySymbols' => [],
		];

		// Foreach files!
		foreach ( $files as $inputFile ) {
			if ( strpos( $inputFile, '.xml' ) < 1 ) {
				continue;
			}

			$contents = file_get_contents( $inputDir . '/' . $inputFile );
			$doc = new SimpleXMLElement( $contents );

			// Tags in the <identity> section are guaranteed to appear once
			$languages = $doc->xpath( '//identity/language/@type' );
			$language = $languages
				? (string)$languages[0]
				: pathinfo( $inputFile, PATHINFO_FILENAME );

			// The <script> element is optional
			$scripts = $doc->xpath( '//identity/script/@type' );
			$script = $scripts ? (string)$scripts[0] : '';
			// expand the language
			if ( $script !== '' ) {
				$language .= '-' . strtolower( $script );
			}

			// The <territory> element is optional
			$territories = $doc->xpath( '//identity/territory/@type' );
			$territory = $territories ? (string)$territories[0] : self::LOCALITY_DEFAULT;

			if ( $language === 'root' ) {
				$language = self::LANGUAGE_DEFAULT;
			}

			foreach ( $doc->xpath( '//currencies/currency' ) as $elem ) {
				if ( (string)$elem->symbol[0] !== '' ) {
					$data['currencySymbols'][(string)$elem['type']][$language][$territory] =
						(string)$elem->symbol[0];
				}
			}
		}

		// now massage the data somewhat. It's pretty blown up at this point.

		/**
		 * Part 1: Stop blowing up on defaults.
		 * Defaults apparently come in many forms. Listed below in order of scope
		 * (widest to narrowest)
		 * 1) The ISO code itself, in the absence of any other defaults
		 * 2) The 'root' language file definition
		 * 3) Language with no locality - locality will come in as 'DEFAULT'
		 *
		 * Intended behavior:
		 * From narrowest scope to widest, collapse the defaults
		 */
		foreach ( $data['currencySymbols'] as $currency => $language ) {
			// get the currency default symbol. This will either be defined in the
			// 'root' language file, or taken from the ISO code.
			$default = $language[self::LANGUAGE_DEFAULT][self::LOCALITY_DEFAULT] ?? $currency;

			foreach ( $language as $lang => $territories ) {
				if ( is_array( $territories ) ) {
					// Collapse a language (no locality) array if it's just the default. One value will do fine.
					if ( count( $territories ) === 1 && array_key_exists( self::LOCALITY_DEFAULT, $territories ) ) {
						$data['currencySymbols'][$currency][$lang] = $territories[self::LOCALITY_DEFAULT];
						if ( $territories[self::LOCALITY_DEFAULT] === $default
							&& $lang !== self::LANGUAGE_DEFAULT
						) {
							unset( $data['currencySymbols'][$currency][$lang] );
						}
					} else {
						// Collapse a language (with locality) array if it's default is just the default
						if ( !array_key_exists( self::LOCALITY_DEFAULT, $territories )
							|| ( $territories[self::LOCALITY_DEFAULT] === $default
								&& $lang !== self::LANGUAGE_DEFAULT )
						) {
							foreach ( $territories as $territory => $symbol ) {
								if ( $symbol === $default ) {
									unset( $data['currencySymbols'][$currency][$lang][$territory] );
								}
							}
						}
						ksort( $data['currencySymbols'][$currency][$lang] );
					}
				}
			}

			ksort( $data['currencySymbols'][$currency] );
		}

		ksort( $data['currencySymbols'] );
		return $data;
	}

}
