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
	 * CLDR inheritance marker (U+2191 × 3). When this value appears as the text
	 * content of an element, it means "inherit from parent locale". For language
	 * names this resolves to the language code itself (as defined in root.xml).
	 */
	private const INHERITANCE_MARKER = "\u{2191}\u{2191}\u{2191}";

	/**
	 * Read the main/<locale>.xml file from CLDR core and convert to PHP
	 *
	 * @param string $inputFile filename
	 * @param array $inheritedLanguageNames Language codes whose name equals the code
	 *   itself (from LDML inheritance). These are added to the output when not already
	 *   present in the locale file. Since CLDR 46, such entries are no longer explicitly
	 *   listed in locale XML files. (T414677)
	 */
	public function parseMain( $inputFile, array $inheritedLanguageNames = [] ): array {
		$contents = file_get_contents( $inputFile );
		$doc = new SimpleXMLElement( $contents );

		$data = [
			'indexCharacters' => [],
			'languageNames' => [],
			'currencyNames' => [],
			'currencySymbols' => [],
			'countryNames' => [],
			'timeUnits' => [],
		];

		// Take a Unicode Set for an alphabet and extract simple example characters.
		// For example, "[aàâ b {ch}]" is extracted as `["a", "b", "ch"]`.
		// TODO: Unicode Set allows for more complex syntax, but we support only
		// the subset currently used here. Should rely on a library instead.
		$indexCharacters = $doc->xpath( '//characters/exemplarCharacters[@type="index"]' );
		if ( $indexCharacters && count( $indexCharacters ) === 1 ) {
			[ $characters ] = $indexCharacters;
			$splitSequence = preg_split( '/\s/',
				trim( (string)$characters, '[]' ) );
			$data['indexCharacters'] = array_map(
				static fn ( $letter ) => preg_replace_callback_array( [
					// Convert unicode literals to characters.
					'/^\\\\u([\da-f]{4})/i' => static fn ( $m ) => mb_chr( hexdec( $m[1] ) ),

					// Take only the first character from a set like "aàâ".
					// When the character is made up of multiple symbols, it
					// will be enclosed in curly braces like "{ch}", and in this
					// case we want the entire group.  It's possible that the
					// two cases are combined like "{ch}ç".
					'/^(?:([^{])|\{([^}]+)\}).*$/u' => static fn ( $m ) => $m[2] ?? $m[1],
				], $letter ),
				$splitSequence
			);
		}

		foreach ( $doc->xpath( '//languages/language' ) as $elem ) {
			if ( (string)$elem['alt'] !== '' ) {
				continue;
			}

			if ( (string)$elem['menu'] !== '' ) {
				continue;
			}

			if ( (string)$elem['type'] === 'root' ) {
				continue;
			}

			$key = str_replace( '_', '-', strtolower( $elem['type'] ) );
			$value = (string)$elem;

			// CLDR uses ↑↑↑ as an inheritance marker meaning "inherit from parent".
			// For language names, this resolves to the language code itself.
			if ( $value === self::INHERITANCE_MARKER ) {
				$value = $key;
			}

			$data['languageNames'][$key] = $value;
		}

		// T414677: Since CLDR 46, language names that are identical to their code
		// are no longer explicitly listed in locale files (LDML inheritance).
		// Add them back so they remain available in MediaWiki.
		foreach ( $inheritedLanguageNames as $code ) {
			if ( !isset( $data['languageNames'][$code] ) ) {
				$data['languageNames'][$code] = $code;
			}
		}

		foreach ( $doc->xpath( '//currencies/currency' ) as $elem ) {
			$displayName = (string)$elem->displayName[0];
			if ( $displayName === '' || $displayName === self::INHERITANCE_MARKER ) {
				continue;
			}

			$data['currencyNames'][(string)$elem['type']] = $displayName;
			$symbol = (string)$elem->symbol[0];
			if ( $symbol !== '' && $symbol !== self::INHERITANCE_MARKER ) {
				$data['currencySymbols'][(string)$elem['type']] = $symbol;
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

			$value = (string)$elem;

			// Skip inheritance markers for territory names — unlike language names,
			// the territory code itself is not a useful display name.
			if ( $value === self::INHERITANCE_MARKER ) {
				continue;
			}

			$data['countryNames'][(string)$elem['type']] = $value;
		}
		foreach ( $doc->xpath( '//units/unitLength' ) as $unitLength ) {
			if ( (string)$unitLength['type'] !== 'long' ) {
				continue;
			}
			foreach ( $unitLength->unit as $elem ) {
				$type = (string)$elem['type'];
				if ( !str_starts_with( $type, 'duration-' ) ) {
					continue;
				}
				$type = substr( $type, strlen( 'duration-' ) );
				foreach ( $elem->unitPattern as $pattern ) {
					$value = (string)$pattern;
					if ( $value === self::INHERITANCE_MARKER ) {
						continue;
					}
					$data['timeUnits'][$type . '-' . (string)$pattern['count']] = $value;
				}
			}
		}
		foreach ( $doc->xpath( '//fields/field' ) as $field ) {
			$fieldType = (string)$field['type'];

			foreach ( $field->relativeTime as $relative ) {
				$type = (string)$relative['type'];
				foreach ( $relative->relativeTimePattern as $pattern ) {
					$value = (string)$pattern;
					if ( $value === self::INHERITANCE_MARKER ) {
						continue;
					}
					$data['timeUnits'][$fieldType . '-' . $type
					. '-' . (string)$pattern['count']] = $value;
				}
			}
		}

		ksort( $data['timeUnits'] );
		return $data;
	}

	/**
	 * Find language codes whose English name equals the code itself (case-insensitive).
	 *
	 * Since CLDR 46, these entries are no longer explicitly listed in non-English locale
	 * files because LDML inheritance resolves them to the code. This method identifies
	 * them so they can be passed to parseMain() as $inheritedLanguageNames. (T414677)
	 *
	 * @param string $enFile Path to en.xml from CLDR
	 * @return string[] Language codes whose name is the code itself
	 */
	public function getInheritedLanguageNames( $enFile ): array {
		$contents = file_get_contents( $enFile );
		$doc = new SimpleXMLElement( $contents );

		$inherited = [];
		foreach ( $doc->xpath( '//languages/language' ) as $elem ) {
			if ( (string)$elem['alt'] !== '' ) {
				continue;
			}
			$type = (string)$elem['type'];
			$name = (string)$elem;
			$key = str_replace( '_', '-', strtolower( $type ) );
			if ( strtolower( $name ) === $key ) {
				$inherited[] = $key;
			}
		}

		return $inherited;
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
			if ( !str_ends_with( $inputFile, '.xml' ) ) {
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
				$symbol = (string)$elem->symbol[0];
				if ( $symbol !== '' && $symbol !== self::INHERITANCE_MARKER ) {
					$data['currencySymbols'][(string)$elem['type']][$language][$territory] =
						$symbol;
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
