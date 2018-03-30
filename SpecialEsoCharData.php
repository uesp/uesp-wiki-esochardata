<?php


class SpecialEsoCharData extends SpecialPage 
{
	public $charDataViewer = null;
	
	
	function __construct() 
	{
		global $wgOut;
				
		parent::__construct( 'EsoCharData' );
		
		//$wgOut->addModules( 'ext.EsoBuildData' );
		
		$this->charDataViewer = new EsoCharDataViewer();
		$this->charDataViewer->baseUrl = "/wiki/Special:EsoCharData";
		$this->charDataViewer->baseResourceUrl = "/esobuilddata/";
	}
	

	function execute( $par ) 
	{
		$this->charDataViewer->wikiContext = $this->getContext();
		
		//$this->getOutput()->addModules( 'ext.EsoBuildData' );
		
		$request = $this->getRequest();
		$output = $this->getOutput();
		
		$output->addHeadItem("uesp-esochardata-css", "<link rel='stylesheet' href='//esobuilds-static.uesp.net/resources/esobuilddata.css?version=29Mar2018' />");
		$output->addHeadItem("uesp-tablesorter-js", "<script src='//esobuilds-static.uesp.net/resources/jquery.tablesorter.min.js'></script>");
		$output->addHeadItem("uesp-tablesorter-js", "<script src='//esobuilds-static.uesp.net/resources/jquery.visible.js'></script>");
		$output->addHeadItem("uesp-esochardata-js", "<script src='//esobuilds-static.uesp.net/resources/esobuilddata.js?version=29Mar2018'></script>");
		
		$this->setHeaders();

		$charId = $request->getText( 'id' );
		$raw = $request->getText( 'raw' );

		$output->addHTML($this->charDataViewer->getOutput());
	}
	
	
	function getGroupName() 
	{
		return 'wiki';
	}
	
};
