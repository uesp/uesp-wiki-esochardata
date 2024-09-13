<?php


class SpecialEsoCharData extends SpecialPage 
{
	public $charDataViewer = null;
	
	
	function __construct()
	{
		global $wgOut;
		
		parent::__construct( 'EsoCharData' );
		
		$wgOut->addModules( 'ext.EsoBuildData.viewer.scripts' );
		$wgOut->addModuleStyles( 'ext.EsoBuildData.viewer.styles' );
		
		$this->charDataViewer = new EsoCharDataViewer();
		$this->charDataViewer->baseUrl = "/wiki/Special:EsoCharData";
		$this->charDataViewer->baseResourceUrl = "/esobuilddata/";
		$this->charDataViewer->includeSetSkillData = true;
	}
	
	
	function execute( $par )
	{
		$this->charDataViewer->wikiContext = $this->getContext();
		
		$request = $this->getRequest();
		$output = $this->getOutput();
		
		$charId = $request->getText( 'id' );
		$raw = $request->getText( 'raw' );
		
		$html = $this->charDataViewer->getOutput();
		
		if ($this->charDataViewer->shouldOutputJson())
		{
			$this->outputJsonHeader();
			print($this->charDataViewer->outputJson);
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
