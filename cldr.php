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
define( 'CLDR_VERSION', '4.1.0 (CLDR 26)' );

$GLOBALS['wgExtensionCredits']['other'][] = array(
	'path' => __FILE__,
	'name' => 'Language Names',
	'version' => CLDR_VERSION,
	'author' => array( 'Niklas Laxström', 'Siebrand Mazeland', 'Ryan Kaldari', 'Sam Reed' ),
	'url' => 'https://www.mediawiki.org/wiki/Extension:CLDR',
	'descriptionmsg' => 'cldr-desc',
);

$dir = dirname( __FILE__ ) . '/';
$GLOBALS['wgMessagesDirs']['cldr'] = __DIR__ . '/i18n';
$GLOBALS['wgExtensionMessagesFiles']['cldr'] = $dir . 'cldr.i18n.php';
$GLOBALS['wgAutoloadClasses']['CldrNames'] = $dir . 'CldrNames.php';
$GLOBALS['wgAutoloadClasses']['LanguageNames'] = $dir . 'LanguageNames.body.php';
$GLOBALS['wgAutoloadClasses']['CountryNames'] = $dir . 'CountryNames.body.php';
$GLOBALS['wgAutoloadClasses']['CurrencyNames'] = $dir . 'CurrencyNames.body.php';
$GLOBALS['wgAutoloadClasses']['TimeUnits'] = $dir . 'TimeUnits.body.php';
$GLOBALS['wgHooks']['LanguageGetTranslatedLanguageNames'][] = 'LanguageNames::coreHook';
$GLOBALS['wgHooks']['GetHumanTimestamp'][] = 'TimeUnits::onGetHumanTimestamp';
