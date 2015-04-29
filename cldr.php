<?php
if ( !defined( 'MEDIAWIKI' ) ) die();
/**
 * An extension which provides localised language names for other extensions.
 *
 * @file
 * @ingroup Extensions
 * @author Niklas Laxström
 * @copyright Copyright © 2007-2014, Niklas Laxström
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
define( 'CLDR_VERSION', '4.1.0 (CLDR 27.0.1)' );

$GLOBALS['wgExtensionCredits']['other'][] = array(
	'path' => __FILE__,
	'name' => 'Language Names',
	'version' => CLDR_VERSION,
	'author' => array( 'Niklas Laxström', 'Siebrand Mazeland', 'Ryan Kaldari', 'Sam Reed' ),
	'url' => 'https://www.mediawiki.org/wiki/Extension:CLDR',
	'descriptionmsg' => 'cldr-desc',
);

$GLOBALS['wgMessagesDirs']['cldr'] = __DIR__ . '/i18n';
$GLOBALS['wgExtensionMessagesFiles']['cldr'] = __DIR__ . '/cldr.i18n.php';
$GLOBALS['wgAutoloadClasses']['CldrNames'] = __DIR__ . '/CldrNames.php';
$GLOBALS['wgAutoloadClasses']['LanguageNames'] = __DIR__ . '/LanguageNames.body.php';
$GLOBALS['wgAutoloadClasses']['CountryNames'] = __DIR__ . '/CountryNames.body.php';
$GLOBALS['wgAutoloadClasses']['CurrencyNames'] = __DIR__ . '/CurrencyNames.body.php';
$GLOBALS['wgAutoloadClasses']['TimeUnits'] = __DIR__ . '/TimeUnits.body.php';
$GLOBALS['wgHooks']['LanguageGetTranslatedLanguageNames'][] = 'LanguageNames::coreHook';
$GLOBALS['wgHooks']['GetHumanTimestamp'][] = 'TimeUnits::onGetHumanTimestamp';
