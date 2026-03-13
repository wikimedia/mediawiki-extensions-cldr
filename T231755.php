<?php

declare( strict_types = 1 );

/**
 * A temporary script for T231755.
 *
 * The goal of the task is to migrate language names from
 * PHP files in CldrMain/CldrMain$code.php and LocalNames/LocalNames$code.php
 * to interface messages in the i18n/$code.json files.
 * (Afterwards, the LocalNames files become obsolete, while CldrMain is kept.)
 *
 * This script reads the CldrMain and LocalNames files,
 * assembles the interface messages from them and adds those to the JSON files.
 * It is run directly as `php T231755.php`, not as a maintenance script or anything.
 */

/* phpcs:disable MediaWiki.NamingConventions.PrefixedGlobalFunctions.allowedPrefix */

/**
 * Write the given messages to the i18n file for the given language code,
 * creating it if necessary.
 */
function addToJson( string $code, array $messages ): void {
	$jsonFileName = __DIR__ . '/i18n/' . $code . '.json';
	// phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
	$jsonText = @file_get_contents( $jsonFileName );
	if ( $jsonText === false ) {
		// empty stub file
		$jsonText = '{ "@metadata": { "authors": [] } }';
	}
	$json = json_decode( $jsonText, true );
	if ( $json === null ) {
		throw new RuntimeException( 'Unable to decode file: ' . $jsonFileName );
	}
	$json += $messages;
	ksort( $json );
	$jsonText = json_encode( $json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
	if ( $jsonText === false ) {
		throw new RuntimeException( 'Unable to encode data for file: ' . $jsonFileName );
	}
	// fix indentation (regex taken from FormatJson::encode())
	$jsonText = preg_replace( '/ {4}|.*+\n\K {4}/A', "\t", $jsonText );
	$success = file_put_contents( $jsonFileName, $jsonText . PHP_EOL );
	if ( $success === false ) {
		throw new RuntimeException( 'Unable to write file: ' . $jsonFileName );
	}
}

/**
 * Inverse {@link \MediaWiki\Languages\LanguageNameUtils::getFileName()}.
 */
function fileNamePartToCode( string $fileNamePart ): string {
	return str_replace( '_', '-', lcfirst( $fileNamePart ) );
}

/**
 * Load all the names from the CldrMain files.
 *
 * @return string[][] The first key is the language code the names are in,
 * the second key is the language code the name refers to.
 */
function loadCldrNames(): array {
	$cldrLanguageNames = [];

	foreach ( scandir( __DIR__ . '/CldrMain' ) as $entry ) {
		$path = __DIR__ . '/CldrMain/' . $entry;
		if ( !is_file( $path ) ) {
			continue;
		}
		$languageNames = false;
		require $path;
		if ( is_array( $languageNames ) ) {
			$code = fileNamePartToCode( substr(
				$entry,
				strlen( 'CldrMain' ),
				-strlen( '.php' ),
			) );
			$cldrLanguageNames[$code] = $languageNames;
		}
	}

	return $cldrLanguageNames;
}

/**
 * Load all the names from the LocalNames files.
 *
 * @return string[][] The first key is the language code the names are in,
 * the second key is the language code the name refers to.
 */
function loadLocalNames(): array {
	$localLanguageNames = [];

	foreach ( scandir( __DIR__ . '/LocalNames' ) as $entry ) {
		$path = __DIR__ . '/LocalNames/' . $entry;
		if ( !is_file( $path ) ) {
			continue;
		}
		$languageNames = false;
		require $path;
		if ( is_array( $languageNames ) ) {
			$code = fileNamePartToCode( substr(
				$entry,
				strlen( 'LocalNames' ),
				-strlen( '.php' ),
			) );
			$localLanguageNames[$code] = $languageNames;
		}
	}

	return $localLanguageNames;
}

$allCldrNames = loadCldrNames();
$allLocalNames = loadLocalNames();

$allMessages = [];
foreach ( $allCldrNames as $code => $cldrNames ) {
	foreach ( $cldrNames as $code2 => $cldrName ) {
		$allMessages[$code]["cldr-language-name-$code2"] = $cldrName;
	}
}
foreach ( $allLocalNames as $code => $localNames ) {
	foreach ( $localNames as $code2 => $localName ) {
		if ( $localName ) {
			$allMessages[$code]["cldr-language-name-$code2"] = $localName;
		} else {
			unset( $allMessages[$code]["cldr-language-name-$code2"] );
		}
	}
}

foreach ( $allMessages as $code => $messages ) {
	foreach ( $messages as $key => $message ) {
		$code2 = substr( $key, strlen( 'cldr-language-name-' ) );
		$allMessages['qqq'][$key] =
			"Language name of the [https://hub.toolforge.org/P305:$code2 '$code2'] language code.";
		// TODO improve qqq; maybe just a template on twn? {{doc-cldr-language-name|$code2}}

		if ( !array_key_exists( $key, $allMessages['en'] ) ) {
			fwrite(
				STDERR,
				"Language code '$code2' has a name in '$code' but not in English!" . PHP_EOL
			);
		}
	}
}

foreach ( $allMessages as $code => $messages ) {
	addToJson( $code, $messages );
}
