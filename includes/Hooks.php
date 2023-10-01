<?php

namespace MediaWiki\Extension\CLDR;

use Language;
use MediaWiki\Hook\GetHumanTimestampHook;
use MediaWiki\Languages\Hook\LanguageGetTranslatedLanguageNamesHook;
use MWTimestamp;
use User;

/**
 * Hooks for integration into MediaWiki language system
 *
 * @license GPL-2.0-or-later
 */
class Hooks implements
	LanguageGetTranslatedLanguageNamesHook,
	GetHumanTimestampHook
{

	/**
	 * @param array &$names
	 * @param string $code
	 */
	public function onLanguageGetTranslatedLanguageNames( &$names, $code ): void {
		$names += LanguageNames::getNames( $code, LanguageNames::FALLBACK_NORMAL, LanguageNames::LIST_MW_AND_CLDR );
	}

	/**
	 * Handler for GetHumanTimestamp hook.
	 * Converts the given time into a human-friendly relative format, for
	 * example, '6 days ago', 'In 10 months'.
	 *
	 * @param string &$output The output timestamp
	 * @param MWTimestamp $timestamp The current (user-adjusted) timestamp
	 * @param MWTimestamp $relativeTo The relative (user-adjusted) timestamp
	 * @param User $user User whose preferences are being used to make timestamp
	 * @param Language $lang Language that will be used to render the timestamp
	 * @return bool False means the timestamp was overridden so stop further
	 *     processing. True means the timestamp was not overridden.
	 */
	public function onGetHumanTimestamp( &$output, $timestamp, $relativeTo, $user, $lang ): bool {
		// Map PHP's DateInterval property codes to CLDR unit names.
		$units = [
			's' => 'second',
			'i' => 'minute',
			'h' => 'hour',
			'd' => 'day',
			'm' => 'month',
			'y' => 'year',
		];

		// Get the difference between the two timestamps (as a DateInterval object).
		$timeDifference = $timestamp->diff( $relativeTo );

		// Figure out if the timestamp is in the future or the past.
		if ( $timeDifference->invert ) {
			$tense = 'future';
		} else {
			$tense = 'past';
		}

		// Figure out which unit (days, months, etc.) it makes sense to display
		// the timestamp in, and get the number of that unit to use.
		$unit = null;
		$number = 0;
		foreach ( $units as $code => $testUnit ) {
			$testNumber = (int)$timeDifference->format( '%' . $code );
			if ( $testNumber > 0 ) {
				$unit = $testUnit;
				$number = $testNumber;
			}
		}

		// If it occurred less than 1 second ago, output 'just now' message.
		if ( !$unit || !$number ) {
			$output = wfMessage( 'just-now' )->inLanguage( $lang )->text();
			return false;
		}

		// Get the CLDR time unit strings for the user's language.
		// If no strings are returned, abandon the timestamp override.
		$timeUnits = TimeUnits::getUnits( $lang->getCode() );
		if ( !$timeUnits ) {
			return true;
		}

		// Figure out which grammatical number to use.
		// If the template doesn't exist, fall back to 'other' as the default.
		$grammaticalNumber = $lang->getPluralRuleType( $number );
		$timeUnitKey = "{$unit}-{$tense}-{$grammaticalNumber}";
		if ( !isset( $timeUnits[$timeUnitKey] ) ) {
			$timeUnitKey = "{$unit}-{$tense}-other";
		}

		// Not all languages have translations for everything
		if ( !isset( $timeUnits[$timeUnitKey] ) ) {
			return true;
		}

		// Select the appropriate template for the timestamp.
		$timeUnit = $timeUnits[$timeUnitKey];
		// Replace the placeholder with the number.
		$output = str_replace( '{0}', $lang->formatNum( $number ), $timeUnit );

		return false;
	}
}
