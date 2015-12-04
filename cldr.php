<?php
/**
 * An extension which provides localised language names for other extensions.
 *
 * @file
 * @ingroup Extensions
 * @author Niklas Laxström
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
define( 'CLDR_VERSION', '4.1.1 (CLDR 28)' );

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'Language Names',
	'version' => CLDR_VERSION,
	'author' => array( 'Niklas Laxström', 'Siebrand Mazeland', 'Ryan Kaldari', 'Sam Reed' ),
	'url' => 'https://www.mediawiki.org/wiki/Extension:CLDR',
	'descriptionmsg' => 'cldr-desc',
);

$wgMessagesDirs['cldr'] = __DIR__ . '/i18n';
$wgAutoloadClasses['CldrNames'] = __DIR__ . '/CldrNames.php';
$wgAutoloadClasses['LanguageNames'] = __DIR__ . '/LanguageNames.body.php';
$wgAutoloadClasses['CountryNames'] = __DIR__ . '/CountryNames.body.php';
$wgAutoloadClasses['CurrencyNames'] = __DIR__ . '/CurrencyNames.body.php';
$wgAutoloadClasses['TimeUnits'] = __DIR__ . '/TimeUnits.body.php';
$wgHooks['LanguageGetTranslatedLanguageNames'][] = 'LanguageNames::coreHook';
$wgHooks['GetHumanTimestamp'][] = 'TimeUnits::onGetHumanTimestamp';
