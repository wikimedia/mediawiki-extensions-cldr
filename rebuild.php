<?php
/**
 * Extract data from cldr xml.
 * @author Niklas Laxström
 */

/**
 * Assuming default location $IP/extensions/cldr
 */
$IP = "../..";
require_once( "$IP/maintenance/commandLine.inc" );

$dir = dirname(__FILE__);
require_once( "$dir/cldr.php" );

$DATA = "$dir/core/common/main";
$OUTPUT = $dir;

if ( isset( $options['datadir'] ) ) {
	$DATA = $options['datadir'];
}

if ( isset( $options['outputdir'] ) ) {
	$OUTPUT = $options['outputdir'];
}

// Get an array of all MediaWiki languages ( $wgLanguageNames + $wgExtraLanguageNames )
$languages = Language::getLanguageNames( false );
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
		if ( isset( $codeParts[2] ) ) {
			if ( strlen( $codeParts[2] ) == 2 ) {
				$codeParts[2] = strtoupper( $codeParts[2] );
			}
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
		$p->parse( $input, "$OUTPUT/$outputFileName" );
	} elseif ( isset( $options['verbose'] ) ) {
		echo "File $input not found\n";
	}
}


class CLDRParser {
	private $ok = true;
	private $languages = false;
	private $output = "<?php\n";
	private $languageCount = 0;
	private $currencyCount = 0;
	private $countryCount = 0;

	function langStart( $parser, $name, $attrs ) {
		if ( $name === 'LANGUAGES' ) {
			$this->languages = true;
		}
		$this->ok = false;
		if ( $this->languages && $name === 'LANGUAGE' ) {
			if ( !isset($attrs["ALT"] ) && !isset( $attrs["DRAFT"] ) ) {
				$this->ok = true;
				$type = str_replace( '_', '-', strtolower( $attrs['TYPE'] ) );
				$this->output .= "'$type' => '";
			}
		}
	}

	function langEnd( $parser, $name ) {
		if ( $name === 'LANGUAGES' ) {
			$this->languages = false;
			$this->ok = false;
			return;
		}
		if ( !$this->ok ) return;
		$this->output .= "',\n";
	}

	function langContents( $parser, $data ) {
		if ( !$this->ok ) return;
		if ( trim( $data ) === '' ) return;
		// Trim data and escape quote marks, but don't double escape.
		$this->output .= preg_replace( "/(?<!\\\\)'/", "\'", trim( $data ) );
		$this->languageCount++;
	}
	
	function parseLanguages( $xml_parser, $input, $output, $fileHandle ) {
	
		// Set up the handler functions for the XML parser
		xml_set_element_handler( $xml_parser, array( $this, 'langStart' ), array( $this, 'langEnd' ) );
		xml_set_character_data_handler( $xml_parser, array( $this, 'langContents' ) );
		
		$this->output .= "\$languageNames = array(\n"; // Open the languageNames array
		
		// Populate the array with the XML data
		while ( $data = fread( $fileHandle, filesize( $input ) ) ) {
			if ( !xml_parse( $xml_parser, $data, feof( $fileHandle ) ) ) {
				die( sprintf( "XML error: %s at line %d",
					xml_error_string( xml_get_error_code( $xml_parser ) ),
					xml_get_current_line_number( $xml_parser ) ) );
			}
		}
		
		$this->output .= ");\n"; // Close the languageNames array
		
		// Give a status update
		if ( $this->languageCount == 1 ) {
			echo "Wrote 1 entry to $output\n";
		} else {
			echo "Wrote $this->languageCount entries to $output\n";
		}
		
	}

	function parse( $input, $output ) {

		// Open the input file for reading
		if ( !( $fileHandle = fopen( $input, "r" ) ) ) {
			die( "could not open XML input" );
		}
		
		$xml_parser = xml_parser_create(); // Create a new parser
		
		$this->parseLanguages( $xml_parser, $input, $output, $fileHandle ); // Parse the language names
		#$this->parseCurrencies( $xml_parser, $input, $output, $fileHandle ); // Parse the currency names
		#$this->parseCountries( $xml_parser, $input, $output, $fileHandle ); // Parse the country names
		
		xml_parser_free( $xml_parser ); // Free the XML parser

		fclose( $fileHandle ); // Close the input file
		
		// If there is nothing to write to the file, end early.
		if ( !$this->languageCount && !$this->currencyCount && !$this->countryCount ) return;
		
		// Open the output file for writing
		if ( !( $fileHandle = fopen( $output, "w+" ) ) ) {
			die( "could not open output file" );
		}

		// Write the output to the output file
		fwrite( $fileHandle, $this->output );
		fclose( $fileHandle );

	}
}

// Get the code for the MediaWiki localisation,
// these are same as the fallback.
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
