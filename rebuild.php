<?php
/**
 * Extract language names from cldr xml.
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
		$en = Language::factory('en');
		$p = new CLDRParser();
		$p->parse( $input, "$OUTPUT/" . LanguageNames::getFileName( getRealCode ( $code ) ) );
		while ($p->getAlias() != false) {
			$codeCLDR = $p->getAlias();
			$input = "$DATA/$codeCLDR.xml";
			echo "Alias $codeCLDR found for $code\n";
			$p->setAlias( false );
			$p->parse( $input, "$OUTPUT/" . LanguageNames::getFileName( getRealCode( $code ) ) );
		}
	} elseif ( isset( $options['verbose'] ) ) {
		echo "File $input not found\n";
	}
}


class CLDRParser {
	private $ok = true;
	private $languages = false;
	private $alias = false;
	private $output = "<?php\n\$names = array(\n";
	private $count = 0;

	function start( $parser, $name, $attrs ) {
		if ( $name === 'LANGUAGES' ) {
			$this->languages = true;
		}
		if ( $name === 'ALIAS') {
			$this->alias = $attrs["SOURCE"];
		}

		$this->ok = false;
		if ( $this->languages && $name === 'LANGUAGE' ) {
			if ( !isset($attrs["ALT"] ) && !isset( $attrs["DRAFT"] ) ) {
				$this->ok = true;
				$type = str_replace( '_', '-', strtolower($attrs['TYPE'] ) );
				$this->output .= "'$type' => '";
			}
		}
	}

	function end( $parser, $name ) {
		if ( $name === 'LANGUAGES' ) {
			$this->languages = false;
			$this->ok = false;
			return;
		}
		if ( $name === 'ALIAS' ) {
			return;
		}
		if ( !$this->ok ) return;
		$this->output .= "',\n";
	}

	function contents( $parser, $data ) {
		if ( !$this->ok ) return;
		if ( trim( $data ) === '' ) return;
		$this->output .= preg_replace( "/(?<!\\\\)'/", "\'", trim( $data ) );
		$this->count++;
	}

	function parse( $input, $output ) {

		$xml_parser = xml_parser_create();
		xml_set_element_handler( $xml_parser, array( $this,'start' ), array( $this,'end' ) );
		xml_set_character_data_handler( $xml_parser, array($this,'contents' ) );
		if ( !( $fileHandle = fopen( $input, "r" ) ) ) {
			die( "could not open XML input" );
		}

		while ( $data = fread( $fileHandle, filesize( $input ) ) ) {
			if ( !xml_parse( $xml_parser, $data, feof( $fileHandle ) ) ) {
				die( sprintf( "XML error: %s at line %d",
					xml_error_string(xml_get_error_code( $xml_parser ) ),
					xml_get_current_line_number( $xml_parser ) ) );
			}
		}
		xml_parser_free( $xml_parser );

		fclose( $fileHandle );

		if ( !$this->count ) { return; }

		if ( $this->alias === false ) $this->output .= ");\n";
		if ( $this->count == 1 ) {
			echo "Wrote $this->count entry to $output\n";
		} else {
			echo "Wrote $this->count entries to $output\n";
		}
		if ( !( $fileHandle = fopen( $output, "w+" ) ) ) {
			die( "could not open output file" );
		}

		// 
		fwrite( $fileHandle, $this->output );
		fclose( $fileHandle );

	}

	function getAlias() {
		return $this->alias;
	}
	function setAlias( $code ) {
		$this->alias = $code;
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
