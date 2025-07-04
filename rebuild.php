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

		$this->requireExtension( 'CLDR' );
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

				$res = $writer->savephp(
					$p->parseMain( $input ),
					"$OUTPUT/CldrMain/$outputFileName"
				);

				// If savephp didn't save a PHP file, we don't want to register it as an available code
				if ( !$res ) {
					$this->output( "File $input contained no useful data\n" );
					continue;
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
}

$maintClass = CLDRRebuild::class;
require_once RUN_MAINTENANCE_IF_MAIN;
