<?php

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

use MediaWiki\Extension\CLDR\CLDRParser;
use MediaWiki\Extension\CLDR\PhpFileWriter;
use MediaWiki\Json\FormatJson;
use MediaWiki\MediaWikiServices;

// Standard boilerplate to define $IP
if ( getenv( 'MW_INSTALL_PATH' ) !== false ) {
	$IP = getenv( 'MW_INSTALL_PATH' );
} else {
	$dir = __DIR__;
	$IP = "$dir/../..";
}
require_once "$IP/maintenance/Maintenance.php";

class CLDRRebuild extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Extract data from CLDR XML' );
		$this->addOption(
			'datadir', 'Directory containing CLDR data. Default is core/common/main',
			false,
			true
		);
		$this->addOption(
			'outputdir', 'Output directory. Default is current directory',
			false,
			true
		);

		$this->requireExtension( 'cldr' );
	}

	public function execute() {
		$dir = __DIR__;

		$DATA = $this->getOption( 'datadir', "$dir/core/common/main" );
		$OUTPUT = $this->getOption( 'outputdir', $dir );

		if ( !file_exists( $DATA ) ) {
			$this->fatalError( "CLDR data not found at $DATA\n" );
		}

		$langNameUtils = MediaWikiServices::getInstance()->getLanguageNameUtils();

		$p = new CLDRParser();
		$writer = new PhpFileWriter();

		// T414677: Compute language codes whose English name equals the code itself.
		// Since CLDR 46, these are no longer listed in locale files (LDML inheritance).
		$inheritedLanguageNames = $p->getInheritedLanguageNames( "$DATA/en.xml" );

		// Get an array of all MediaWiki languages ( $wgLanguageNames + $wgExtraLanguageNames )
		$languages = $langNameUtils->getLanguageNames();
		// hack to get Konkani, until CLDR renames it from 'kok' to 'gom-deva' (T347625)
		$languages['kok'] = 'Foo';
		// T378214
		$languages['kok_Latn'] = 'Foo';
		// hack to get pt-pt too
		$languages['pt-pt'] = 'Foo';
		// hack to get the correct script for mni (T313883)
		$languages['mni-mtei'] = 'Foo';
		ksort( $languages );

		$availableCodes = [];
		foreach ( $languages as $code => $name ) {
			// Construct the correct name for the input file
			$codeParts = explode( '-', $code );
			if ( count( $codeParts ) > 1 ) {
				// ISO 15924 alpha-4 script code
				if ( strlen( $codeParts[1] ) === 4 ) {
					$codeParts[1] = ucfirst( $codeParts[1] );
				}

				// ISO 3166-1 alpha-2 country code
				if ( strlen( $codeParts[1] ) === 2 ) {
					$codeParts[2] = $codeParts[1];
					unset( $codeParts[1] );
				}
				if ( isset( $codeParts[2] ) && strlen( $codeParts[2] ) === 2 ) {
					$codeParts[2] = strtoupper( $codeParts[2] );
				}
				if ( isset( $codeParts[1] ) && $codeParts[1] === 'tarask' ) {
					// hack to get be-tarask
					$codeParts[1] = 'TARASK';
				}
				$codeCLDR = implode( '_', $codeParts );
			} else {
				$codeCLDR = $code;
			}
			$input = "$DATA/$codeCLDR.xml";

			// If the file exists, parse it, otherwise display an error
			if ( file_exists( $input ) ) {
				$mwCode = $this->getRealCode( $code );
				if ( !$mwCode ) {
					continue;
				}
				$outputFileName = $langNameUtils->getFileName( 'CldrMain', $mwCode );
				$outputLocation = "$OUTPUT/CldrMain/$outputFileName";
				$newData = $p->parseMain( $input, $inheritedLanguageNames );

				if ( $code === 'lzz' && isset( $newData['languageNames']['laz'] ) ) {
					// hack: fix https://unicode-org.atlassian.net/browse/CLDR-19316
					$newData['languageNames']['lzz'] = $newData['languageNames']['laz'];
					unset( $newData['languageNames']['laz'] );
				}

				$newCldrLanguageNames = $newData['languageNames'] ?? [];
				$oldCldrLanguageNames = [];

				// also load the old data for comparison
				if ( file_exists( $outputLocation ) ) {
					$languageNames = false;
					require $outputLocation;
					if ( $languageNames !== false ) {
						$oldCldrLanguageNames = $languageNames;
					}
				}

				// update the messages of the language names, which may include overrides of CLDR
				$oldJsonMessages = $this->readJsonMessages( $OUTPUT, $mwCode );
				$newJsonMessages = $oldJsonMessages;
				foreach ( $newCldrLanguageNames as $code2 => $newCldrLanguageName ) {
					$oldCldrLanguageName = $oldCldrLanguageNames[$code2] ?? null;
					$oldJsonLanguageName = $oldJsonMessages["cldr-language-name-$code2"] ?? null;
					if (
						// old message did not exist
						!$oldJsonLanguageName ||
						$oldJsonLanguageName === '-' ||
						// or matched the old CLDR name
						$oldJsonLanguageName === $oldCldrLanguageName
					) {
						// change the message to the new CLDR name
						$newJsonMessages["cldr-language-name-$code2"] = $newCldrLanguageName;
					} elseif (
						$oldJsonLanguageName !== $oldCldrLanguageName &&
						$oldCldrLanguageName !== $newCldrLanguageName &&
						$oldJsonLanguageName !== $newCldrLanguageName
					) {
						// CLDR changed the language name but the messages were already overriding it differently
						// for now, do nothing; TODO mark the message as fuzzy to inform translators
					}
				}

				$res = $writer->savephp(
					$newData,
					$outputLocation
				);

				// If savephp didn't save a PHP file, we don't want to register it as an available code
				if ( !$res ) {
					$this->output( "File $input contained no useful data\n" );
					continue;
				}

				if ( count( array_keys( $newJsonMessages ) ) > 1 ) {
					// only write $newJsonMessages if it contains keys beyond the "@metadata"
					$this->writeJsonMessages( $OUTPUT, $mwCode, $newJsonMessages );
				}

				$availableCodes[] = $mwCode;
			} else {
				$this->output( "File $input not found\n" );
			}
		}

		$writer->savephp(
			[ 'availableCodes' => array_values( array_unique( $availableCodes ) ) ],
			"$OUTPUT/CldrAvailableCodes.php"
		);

		// Now parse out what we want from the supplemental file
		$this->output( "Parsing Supplemental Data...\n" );
		// argh! If $DATA defaulted to something slightly more general in the
		// CLDR dump, this wouldn't have to be this way.
		$input = "$DATA/../supplemental/supplementalData.xml";
		if ( file_exists( $input ) ) {
			$writer->savephp(
				$p->parseSupplemental( $input ),
				"$OUTPUT/CldrSupplemental/Supplemental.php"
			);
		} else {
			$this->output( "File $input not found\n" );
		}
		$this->output( "Done parsing supplemental data.\n" );

		$this->output( "Parsing Currency Symbol Data...\n" );
		$writer->savephp(
			$p->parseCurrencySymbols( $DATA ),
			"$OUTPUT/CldrCurrency/Symbols.php"
		);
		$this->output( "Done parsing currency symbols.\n" );
	}

	/**
	 * Get the language code for the MediaWiki localisation, these are the same as the fallback.
	 */
	private function getRealCode( string $code ): ?string {
		switch ( $code ) {
			case 'az-arab':
				return 'azb';
			case 'kk':
				return 'kk-cyrl';
			case 'kok':
				// T347625
				return 'gom-deva';
			case 'kok_Latn':
				// T378214
				return 'gom-latn';
			case 'ku':
				return 'ku-latn';
			case 'mni':
				return 'mni-beng';
			case 'mni-mtei':
				return 'mni';
			case 'pt':
				return 'pt-br';
			case 'pt-br':
				// Skip empty pt_BR.xml in favor of pt.xml (see above)
				return null;
			case 'pt-pt':
				return 'pt';
			case 'sr':
				return 'sr-cyrl';
			case 'tg':
				return 'tg-cyrl';
			case 'zh':
				return 'zh-hans';
			case 'zh-hans':
				// Skip empty zh_Hans.xml in favor of zh.xml (see above)
				return null;
			default:
				return $code;
		}
	}

	private function readJsonMessages( string $dir, string $mwCode ): array {
		$jsonFileName = $dir . '/i18n/LanguageNames/' . $mwCode . '.json';
		// phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
		$jsonText = @file_get_contents( $jsonFileName );
		if ( $jsonText === false ) {
			// empty stub file
			$jsonText = '{ "@metadata": { "authors": [] } }';
		}
		$jsonStatus = FormatJson::parse( $jsonText, FormatJson::FORCE_ASSOC );
		if ( !$jsonStatus->isGood() ) {
			$this->fatalError( $jsonStatus );
		}
		return $jsonStatus->getValue();
	}

	private function writeJsonMessages( string $dir, string $mwCode, array $messages ): void {
		$jsonFileName = $dir . '/i18n/LanguageNames/' . $mwCode . '.json';
		$jsonText = FormatJson::encode( $messages, "\t", FormatJson::ALL_OK );
		if ( $jsonText === false ) {
			$this->fatalError( "Unable to encode data for $jsonFileName" );
		}
		$success = file_put_contents( $jsonFileName, $jsonText . PHP_EOL );
		if ( !$success ) {
			$this->fatalError( "Unable to write data to $jsonFileName" );
		}
	}
}

$maintClass = CLDRRebuild::class;
require_once RUN_MAINTENANCE_IF_MAIN;
