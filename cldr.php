<?php
if (!defined('MEDIAWIKI')) die();
/**
 * An extension which provised localised language names for other extensions.
 *
 * @file
 * @ingroup Extensions
 * @author Niklas Laxström
 * @copyright Copyright © 2007-2010, Niklas Laxström
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'Language Names',
	'version' => '1.8.0 (CLDR 1.8.0)',
	'author' => 'Niklas Laxström',
	'url' => 'http://unicode.org/cldr/repository_access.html',
	'descriptionmsg' => 'cldr-desc',
);

$dir = dirname(__FILE__) . '/';
$wgExtensionMessagesFiles['cldr'] = $dir . 'cldr.i18n.php';
$wgAutoloadClasses['LanguageNames'] = $dir . 'LanguageNames.body.php';
