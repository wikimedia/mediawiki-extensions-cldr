# mediawiki/extensions/cldr

This extension extracts a subset of core CLDR data and makes it available to PHP.

Currently, it provides the following:

- Country names
- Currency names and symbols
- Language names
- Units of time

## Installation

Clone the source to MediaWiki `extensions/` and enable it in `LocalSettings.php`:

    wfLoadExtension( 'cldr' );

## Updating data

Look up the latest release of [CLDR](https://www.unicode.org/cldr/repository_access.html) and note the version number.

Update the version number in `CLDR_CORE_URL` in `Makefile`.  Run `make`:

    make all

## Example usage

```php
	if ( ExtensionRegistry::isLoaded( 'cldr' ) ) {
		// Get the English translation of all available language names.
		$languages = LanguageNames::getNames( 'en' ,
			LanguageNames::FALLBACK_NATIVE,
			LanguageNames::LIST_MW_AND_CLDR
		);
	} else {
		// Fall back to the MediaWiki core function without CLDR.
		$languages = Language::getLanguageNames( false );
	}
```
