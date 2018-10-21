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
	'url' => '//www.uesp.net/wiki/UESPWiki:EsoCharData',
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

$wgResourceModules['ext.EsoBuildData.viewer.styles'] = array(
	'position' => 'top',
	'styles' => array( 'esobuilddata.css' ),
	'localBasePath' => '/home/uesp/www/esobuilddata/resources/',
	'remoteBasePath' => '//esobuilds-static.uesp.net/resources/',
	'targets' => array( 'desktop', 'mobile' ),
);

$wgResourceModules['ext.EsoBuildData.viewer.scripts'] = array(
	'position' => 'top',
	'scripts' => array( 'jquery.tablesorter.min.js', 'jquery.visible.js', 'esobuilddata.js' ),
	'localBasePath' => '/home/uesp/www/esobuilddata/resources/',
	'remoteBasePath' => '//esobuilds-static.uesp.net/resources/',
	'targets' => array( 'desktop', 'mobile' ),
);

$wgResourceModules['ext.EsoBuildData.editor.styles'] = array(
	'position' => 'top',
	'styles' => array( 'esoEditBuild_embed.css' ),
	'localBasePath' => '/home/uesp/www/esobuilddata/resources/',
	'remoteBasePath' => '//esobuilds-static.uesp.net/resources/',
	'targets' => array( 'desktop', 'mobile' ),
);

$wgResourceModules['ext.EsoBuildData.editor.mobilestyles'] = array(
	'position' => 'top',
	'styles' => array( 'esoEditBuild_mobile.css' ),
	'localBasePath' => '/home/uesp/www/esobuilddata/resources/',
	'remoteBasePath' => '//esobuilds-static.uesp.net/resources/',
	'targets' => array( 'mobile' ),
	'dependencies' => array( 'ext.EsoBuildData.editor.styles' ),
);

$wgResourceModules['ext.EsoBuildData.editor.scripts'] = array(
	'position' => 'top',
	'scripts' => array( 'json2.js', 'esoEditBuild.js', 'esoBuildCombat.js' ),
	'localBasePath' => '/home/uesp/www/esobuilddata/resources/',
	'remoteBasePath' => '//esobuilds-static.uesp.net/resources/',
	'targets' => array( 'desktop', 'mobile' ),
);

$wgResourceModules['ext.EsoBuildData.itemsearchpopup.styles'] = array(
	'position' => 'top',
	'styles' => array( 'esoItemSearchPopup.css' ),
	'localBasePath' => '/home/uesp/esolog.static/resources/',
	'remoteBasePath' => '//esolog-static.uesp.net/resources/',
	'targets' => array( 'desktop', 'mobile' ),
);

$wgResourceModules['ext.EsoBuildData.itemsearchpopup.scripts'] = array(
	'position' => 'top',
	'scripts' => array( 'esoItemSearchPopup.js' ),
	'localBasePath' => '/home/uesp/esolog.static/resources/',
	'remoteBasePath' => '//esolog-static.uesp.net/resources/',
	'targets' => array( 'desktop', 'mobile' ),
);

$wgGroupPermissions['*']['esochardata_edit'] = false;
$wgGroupPermissions['*']['esochardata_delete'] = false;
$wgGroupPermissions['sysop']['esochardata_edit'] = true;
$wgGroupPermissions['sysop']['esochardata_delete'] = true;







