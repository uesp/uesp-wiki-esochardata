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
		$this->getOutput()->addModules( 'ext.EsoBuildData' );
		
		$request = $this->getRequest();
		$output = $this->getOutput();
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
