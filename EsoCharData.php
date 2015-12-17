<?php
/*
 * UespEsoCharData -- by Dave Humphrey, dave@uesp.net, December 2015
 * 
 * Adds the Special:EsoCharData page to MediaWiki for displaying ESO characte data.
 *
 * TODO:
 *
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	echo <<<EOT
To install my extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/EsoCharData/EsoCharData.php" );
EOT;
	exit( 1 );
}

require_once("/home/uesp/secrets/esochardata.secrets");
require_once('/home/uesp/www/esobuilddata/viewBuildData.class.php');


$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'EsoCharData',
	'author' => 'Dave Humphrey (dave@uesp.net)',
	'url' => 'http://www.uesp.net/wiki/UESPWiki:EsoCharData',
	'descriptionmsg' => 'esochardata-desc',
	'version' => '0.1.0',
);

$wgAutoloadClasses['SpecialEsoBuildData'] = __DIR__ . '/SpecialEsoBuildData.php';
$wgMessagesDirs['EsoCharData'] = __DIR__ . "/i18n";
$wgExtensionMessagesFiles['EsoCharDataAlias'] = __DIR__ . '/EsoCharData.alias.php';
$wgSpecialPages['EsoBuildData'] = 'SpecialEsoBuildData';

$wgHooks['BeforePageDisplay'][] = 'uespEsoCharData_beforePageDisplay';

$wgResourceModules['ext.EsoBuildData'] = array(
		'scripts' => 'esobuilddata.js',
		'styles' => 'esobuilddata.css',
		'position' => 'top',
		'localBasePath' => dirname( __FILE__ ) . '/modules',
		'remoteExtPath' => 'EsoCharData/modules'
);


function uespEsoCharData_beforePageDisplay(&$out) 
{
	global $wgScriptPath;
	
	$out->addHeadItem("uesp-esochardata-css", "<link rel='stylesheet' href='http://content3.uesp.net/esobuilddata/resources/esobuilddata.css' />");
	$out->addHeadItem("uesp-esochardata-js", "<script src='http://content3.uesp.net/esobuilddata/resources/esobuilddata.js'></script>");
	
	return true;
}





