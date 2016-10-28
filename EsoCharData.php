<?php
/*
 * UespEsoCharData -- by Dave Humphrey, dave@uesp.net, December 2015
 * 
 * Adds the Special:EsoBuildData and Special:EsoCharData pages to MediaWiki 
 * for displaying ESO character and build data.
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
require_once('/home/uesp/www/esobuilddata/viewCharData.class.php');
require_once('/home/uesp/www/esobuilddata/editBuild.class.php');


$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'EsoCharData',
	'author' => 'Dave Humphrey (dave@uesp.net)',
	'url' => 'http://www.uesp.net/wiki/UESPWiki:EsoCharData',
	'descriptionmsg' => 'esochardata-desc',
	'version' => '0.2.0',
);

$wgAutoloadClasses['SpecialEsoBuildData'] = __DIR__ . '/SpecialEsoBuildData.php';
$wgAutoloadClasses['SpecialEsoBuildEditor'] = __DIR__ . '/SpecialEsoBuildEditor.php';
$wgAutoloadClasses['SpecialEsoCharData']  = __DIR__ . '/SpecialEsoCharData.php';
$wgMessagesDirs['EsoCharData'] = __DIR__ . "/i18n";
$wgExtensionMessagesFiles['EsoCharDataAlias'] = __DIR__ . '/EsoCharData.alias.php';
$wgSpecialPages['EsoBuildEditor'] = 'SpecialEsoBuildEditor';
$wgSpecialPages['EsoBuildData'] = 'SpecialEsoBuildData';
$wgSpecialPages['EsoCharData']  = 'SpecialEsoCharData';

$wgHooks['BeforePageDisplay'][] = 'uespEsoCharData_beforePageDisplay';

/*
$wgResourceModules['ext.EsoBuildData'] = array(
		'scripts' => 'esobuilddata.js',
		'styles' => 'esobuilddata.css',
		'position' => 'top',
		'localBasePath' => dirname( __FILE__ ) . '/modules',
		'remoteExtPath' => 'EsoCharData/modules'
); //*/


$wgGroupPermissions['*']['esochardata_edit'] = false;
$wgGroupPermissions['*']['esochardata_delete'] = false;
$wgGroupPermissions['sysop']['esochardata_edit'] = true;
$wgGroupPermissions['sysop']['esochardata_delete'] = true;


function uespEsoCharData_beforePageDisplay(&$out) 
{
	global $wgScriptPath;
	
	$out->addHeadItem("uesp-esochardata-css", "<link rel='stylesheet' href='http://esobuilds-static.uesp.net/resources/esobuilddata.css?version=27Oct2016' />");
	$out->addHeadItem("uesp-tablesorter-js", "<script src='http://esobuilds-static.uesp.net/resources/jquery.tablesorter.min.js'></script>");
	$out->addHeadItem("uesp-esochardata-js", "<script src='http://esobuilds-static.uesp.net/resources/esobuilddata.js?version=27Oct2016'></script>");
	
	return true;
}





