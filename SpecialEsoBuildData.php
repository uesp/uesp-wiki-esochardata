<?php


class SpecialEsoBuildData extends SpecialPage 
{
	public $buildDataViewer = null;
	
	
	function __construct() 
	{
		global $wgOut;
				
		parent::__construct( 'EsoBuildData' );
		
		//$wgOut->addModules( 'ext.EsoBuildData' );
		
		$this->buildDataViewer = new EsoBuildDataViewer();
		$this->buildDataViewer->baseUrl = "/wiki/Special:EsoBuildData";
		$this->buildDataViewer->baseResourceUrl = "/esobuilddata/";
	}
	

	function execute( $par ) 
	{
		$this->buildDataViewer->wikiContext = $this->getContext();
		
		//$this->getOutput()->addModules( 'ext.EsoBuildData' );
				
		$request = $this->getRequest();
		$output = $this->getOutput();
				
		$output->addHeadItem("uesp-esochardata-css", "<link rel='stylesheet' href='//esobuilds-static.uesp.net/resources/esobuilddata.css?version=29Mar2018' />");
		$output->addHeadItem("uesp-tablesorter-js", "<script src='//esobuilds-static.uesp.net/resources/jquery.tablesorter.min.js'></script>");
		$output->addHeadItem("uesp-esochardata-js", "<script src='//esobuilds-static.uesp.net/resources/esobuilddata.js?version=29Mar2018'></script>");
		
		$this->setHeaders();

		$charId = $request->getText( 'id' );
		$raw = $request->getText( 'raw' );

		$output->addHTML($this->buildDataViewer->getOutput());
	}
	
	
	function getGroupName() 
	{
		return 'wiki';
	}
	
};
