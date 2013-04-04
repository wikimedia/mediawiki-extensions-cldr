<?php
/**
 * Extract data from cldr XML.
 *
 * @author Niklas Laxström
 * @author Ryan Kaldari
 * @author Santhosh Thottingal
 * @copyright Copyright © 2007-2012
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

// Standard boilerplate to define $IP
if ( getenv( 'MW_INSTALL_PATH' ) !== false ) {
	$IP = getenv( 'MW_INSTALL_PATH' );
} else {
	$dir = dirname( __FILE__ );
	$IP = "$dir/../..";
}
require_once( "$IP/maintenance/Maintenance.php" );

class CLDRRebuild extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Extract data from CLDR XML' );
		$this->addOption(
			'datadir',
			'Directory containing CLDR data. Default is core/common/main',
			/*required*/false,
			/*param*/true
		);
		$this->addOption(
			'outputdir',
			'Output directory. Default is current directory',
			/*required*/false,
			/*param*/true
	);
	}

	public function execute() {
		$dir = dirname( __FILE__ );
		require_once( "$dir/cldr.php" );

		$DATA = $this->getOption( 'datadir', "$dir/core/common/main" );
		$OUTPUT = $this->getOption( 'outputdir', $dir );

		if ( !file_exists( $DATA ) ) {
			$this->error( "CLDR data not found at $DATA\n", 1 );
		}

		// Get an array of all MediaWiki languages ( $wgLanguageNames + $wgExtraLanguageNames )
		$languages = Language::fetchLanguageNames();
		# hack to get pt-pt too
		$languages['pt-pt'] = 'Foo';
		ksort( $languages );

		foreach ( $languages as $code => $name ) {

			// Construct the correct name for the input file
			unset( $codeParts );
			$codeParts = explode( '-', $code );
			if ( count( $codeParts ) > 1 ) {

				// ISO 15924 alpha-4 script code
				if ( strlen( $codeParts[1] ) == 4 ) {
					$codeParts[1] = ucfirst( $codeParts[1] );
				}

				// ISO 3166-1 alpha-2 country code
				if ( strlen( $codeParts[1] ) == 2 ) {
					$codeParts[2] = $codeParts[1];
					unset( $codeParts[1] );
				}
				if ( isset( $codeParts[2] ) && strlen( $codeParts[2] ) == 2 ) {
					$codeParts[2] = strtoupper( $codeParts[2] );
				}
				$codeCLDR = implode( '_', $codeParts );
			} else {
				$codeCLDR = $code;
			}
			$input = "$DATA/$codeCLDR.xml";

			// If the file exists, parse it, otherwise display an error
			if ( file_exists( $input ) ) {
				$outputFileName = Language::getFileName( "CldrNames", getRealCode ( $code ), '.php' );
				$p = new CLDRParser();
				$p->parse( $input, "$OUTPUT/CldrNames/$outputFileName" );
			} else {
				$this->output( "File $input not found\n" );
			}
		}
	}
}

class CLDRParser {
	/**
	 * @param string $input filename
	 * @param string $output filename
	 */
	function parse( $inputFile, $outputFile ) {
		// Open the input file for reading

		$contents = file_get_contents( $inputFile );
		$doc = new SimpleXMLElement( $contents );

		$data = array();

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
		}

		foreach ( $doc->xpath( '//territories/territory' ) as $elem ) {
			if ( (string)$elem['alt'] !== '' && (string)$elem['alt'] !== 'short'  ) {
				continue;
			}

			if ( (string)$elem['type'] === 'ZZ' || !preg_match( '/^[A-Z][A-Z]$/', $elem['type'] ) ) {
				continue;
			}

			$data['countryNames'][(string)$elem['type']] = (string)$elem;
		}

		foreach ( $doc->xpath( '//units/unit' ) as $elem ) {
			foreach ( $elem->unitPattern as $pattern ) {
				if ( (string)$pattern['alt'] !== '' ) {
					continue;
				}
				$data['timeUnits'][(string)$elem['type'] . '-' . (string)$pattern['count']] = (string)$pattern;
			}
		}

		if ( !count( $data ) ) {
			return;
		}

		$output = "<?php\n";
		foreach ( $data as $varname => $values ) {
			$output .= "\n\$$varname = array(\n";
			foreach( $values as $key => $value ) {
				$key = addcslashes( $key, "'" );
				$value = addcslashes( $value, "'" );
				$output .= "'$key' => '$value',\n";
			}
			$output .= ");\n";
		}

		#$output = UtfNormal::cleanUp( $output );

		file_put_contents( $outputFile, $output );
	}
}

/**
 * Get the code for the MediaWiki localisation,
 * these are same as the fallback.
 *
 * @param $code string
 * @return string
 */
function getRealCode( $code ) {
	$realCode = $code;
	if ( !strcmp( $code, 'kk' ) )
		$realCode = 'kk-cyrl';
	elseif ( !strcmp( $code, 'ku' ) )
		$realCode = 'ku-arab';
	elseif ( !strcmp( $code, 'sr' ) )
		$realCode = 'sr-ec';
	elseif ( !strcmp( $code, 'tg' ) )
		$realCode = 'tg-cyrl';
	elseif ( !strcmp( $code, 'zh' ) )
		$realCode = 'zh-hans';
	elseif ( !strcmp( $code, 'pt' ) )
		$realCode = 'pt-br';
	elseif ( !strcmp( $code, 'pt-pt' ) )
		$realCode = 'pt';
	return $realCode;
}

$maintClass = 'CLDRRebuild';
require_once( RUN_MAINTENANCE_IF_MAIN );
