<?php


class SpecialEsoBuildEditor extends SpecialPage 
{
	public $buildDataEditor = null;
	
	
	function __construct() 
	{
		global $wgOut;
				
		parent::__construct( 'EsoBuildEditor' );
		
		//$wgOut->addModules( 'ext.EsoBuildData' );
		
		$this->buildDataEditor = new EsoBuildDataEditor();
		$this->buildDataEditor->baseUrl = "/wiki/Special:EsoBuildEditor";
		$this->buildDataEditor->baseResourceUrl = "/esobuilddata/";
		
		//$wgOut->addHeadItem("uesp-esochardata-css", "<link rel='stylesheet' href='//content3.uesp.net/esobuilddata/resources/esobuilddata.css' />");
		//$wgOut->addHeadItem("uesp-tablesorter-js", "<script src='//content3.uesp.net/esobuilddata/resources/jquery.tablesorter.min.js'></script>");
		//$wgOut->addHeadItem("uesp-esochardata-js", "<script src='//content3.uesp.net/esobuilddata/resources/esobuilddata.js'></script>");
	}
	

	function execute( $par ) 
	{
		global $uespIsMobile;
		
		$this->buildDataEditor->wikiContext = $this->getContext();
		$this->buildDataEditor->buildDataViewer->wikiContext = $this->getContext();
		
		//$this->getOutput()->addModules( 'ext.EsoBuildData' );
		$out = $this->getOutput();
		
		$out->addHeadItem("uesp-esochardata-css", "<link rel='stylesheet' href='//esobuilds-static.uesp.net/resources/esobuilddata.css?version=2May2018' />");
		$out->addHeadItem("uesp-tablesorter-js", "<script src='//esobuilds-static.uesp.net/resources/jquery.tablesorter.min.js'></script>");
		$out->addHeadItem("uesp-tablesorter-js", "<script src='//esobuilds-static.uesp.net/resources/jquery.visible.js'></script>");
		$out->addHeadItem("uesp-esochardata-js", "<script src='//esobuilds-static.uesp.net/resources/esobuilddata.js?version=2May2018'></script>");
		$out->addHeadItem("uesp-esobuildeditor3-js", "<script src='//esobuilds-static.uesp.net/resources/json2.js'></script>");
		$out->addHeadItem("uesp-esobuildeditor1-css", "<link rel='stylesheet' href='//esolog-static.uesp.net/resources/esoItemSearchPopup.css?version=2May2018' />");
		$out->addHeadItem("uesp-esobuildeditor1-js", "<script src='//esolog-static.uesp.net/resources/esoItemSearchPopup.js?version=2May2018'></script>");
		$out->addHeadItem("uesp-esobuildeditor2-css", "<link rel='stylesheet' href='//esobuilds-static.uesp.net/resources/esoEditBuild_embed.css?version=2May2018' />");
		//$out->addHeadItem("uesp-esobuildeditor4-js", "<script src='//esobuilds-static.uesp.net/resources/jquery-ui.min.js?version=28Mar2017'></script>");
		//$out->addHeadItem("uesp-esobuildeditor5-js", "<script src='//esobuilds-static.uesp.net/resources/jquery.ui.touch-punch.min.js?version=28Mar2017'></script>");
		$out->addHeadItem("uesp-esobuildeditor2-js", "<script src='//esobuilds-static.uesp.net/resources/esoEditBuild.js?version=30May2018'></script>");
		$out->addHeadItem("uesp-esobuildeditor3-js", "<script src='//esobuilds-static.uesp.net/resources/esoBuildCombat.js?version=2May2018'></script>");
		
		if ($uespIsMobile || (class_exists("MobileContext") && MobileContext::singleton()->isMobileDevice()))
		{
			$out->addHeadItem("uesp-esobuildeditor3-css", "<link rel='stylesheet' href='//esobuilds-static.uesp.net/resources/esoEditBuild_mobile.css?version=2May2018' />");
		}
		
		/*
		<link rel="stylesheet" href="//esolog.uesp.net/resources/esocp_simple_embed.css" />
		<link rel="stylesheet" href="//esolog.uesp.net/resources/esoskills_embed.css" />
		<link rel="stylesheet" href="//esolog.uesp.net/resources/esoitemlink_embed.css" />
		<script type="text/javascript" src="//esolog.uesp.net/resources/esocp_simple.js"></script>
		<script type="text/javascript" src="//esolog.uesp.net/resources/esoskills.js"></script>
		<script type="text/javascript" src="//content3.uesp.net/w/extensions/UespEsoItemLink/uespitemlink.js"></script>
		*/
		
		$request = $this->getRequest();
		$output = $this->getOutput();
		$this->setHeaders();

		$charId = $request->getText( 'id' );
		$raw = $request->getText( 'raw' );

		$output->addHTML($this->buildDataEditor->GetOutputHtml());
	}
	
	
	function getGroupName() 
	{
		return 'wiki';
	}
	
};
