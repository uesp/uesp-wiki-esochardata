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
		
		//$wgOut->addHeadItem("uesp-esochardata-css", "<link rel='stylesheet' href='http://content3.uesp.net/esobuilddata/resources/esobuilddata.css' />");
		//$wgOut->addHeadItem("uesp-tablesorter-js", "<script src='http://content3.uesp.net/esobuilddata/resources/jquery.tablesorter.min.js'></script>");
		//$wgOut->addHeadItem("uesp-esochardata-js", "<script src='http://content3.uesp.net/esobuilddata/resources/esobuilddata.js'></script>");
	}
	

	function execute( $par ) 
	{
		$this->buildDataEditor->wikiContext = $this->getContext();
		
		//$this->getOutput()->addModules( 'ext.EsoBuildData' );
		$out = $this->getOutput();
		
		$out->addHeadItem("uesp-esobuildeditor1-css", "<link rel='stylesheet' href='http://esolog.uesp.net/resources/esoItemSearchPopup.css' />");
		$out->addHeadItem("uesp-esobuildeditor1-js", "<script src='http://esolog.uesp.net/resources/esoItemSearchPopup.js'></script>");
		$out->addHeadItem("uesp-esobuildeditor2-css", "<link rel='stylesheet' href='http://esobuilds.uesp.net/resources/esoEditBuild_embed.css' />");
		$out->addHeadItem("uesp-esobuildeditor2-js", "<script src='http://esobuilds.uesp.net/resources/esoEditBuild.js'></script>");
		
		/*
		<link rel="stylesheet" href="http://esolog.uesp.net/resources/esocp_simple_embed.css" />
		<link rel="stylesheet" href="http://esolog.uesp.net/resources/esoskills_embed.css" />
		<link rel="stylesheet" href="http://esolog.uesp.net/resources/esoitemlink_embed.css" />
		<script type="text/javascript" src="http://esolog.uesp.net/resources/esocp_simple.js"></script>
		<script type="text/javascript" src="http://esolog.uesp.net/resources/esoskills.js"></script>
		<script type="text/javascript" src="http://content3.uesp.net/w/extensions/UespEsoItemLink/uespitemlink.js"></script>
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
