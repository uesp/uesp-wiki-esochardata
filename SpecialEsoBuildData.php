<?php

class SpecialEsoBuildData extends SpecialPage
{
	public $buildDataViewer = null;
	
	
	function __construct()
	{
		global $wgOut;
		
		parent::__construct( 'EsoBuildData' );
		
		$wgOut->addModules( 'ext.EsoBuildData.viewer.scripts' );
		$wgOut->addModuleStyles( 'ext.EsoBuildData.viewer.styles' );
		
		$this->buildDataViewer = new EsoBuildDataViewer();
		$this->buildDataViewer->baseUrl = "/wiki/Special:EsoBuildData";
		$this->buildDataViewer->baseResourceUrl = "/esobuilddata/";
		$this->buildDataViewer->includeSetSkillData = true;
	}
	
	
	function execute( $par )
	{
		$this->buildDataViewer->wikiContext = $this->getContext();
		
		$request = $this->getRequest();
		$output = $this->getOutput();
		
		$charId = $request->getText( 'id' );
		$raw = $request->getText( 'raw' );
		
		$html = $this->buildDataViewer->getOutput();
		
		if ($this->buildDataViewer->shouldOutputJson())
		{
			$this->outputJsonHeader();
			print($this->buildDataViewer->outputJson);
			die();
		}
		
		$this->setHeaders();
		
		$output->addHTML($html);
	}
	
	
	function outputJsonHeader()
	{
		ob_start("ob_gzhandler");
		
		header("Expires: 0");
		header("Pragma: no-cache");
		header("Cache-Control: no-cache, no-store, must-revalidate");
		header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN'] . "");
		header("content-type: application/json");
	}
	
	
	function getGroupName()
	{
		return 'wiki';
	}

};
