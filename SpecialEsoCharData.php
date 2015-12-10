<?php


class SpecialEsoCharData extends SpecialPage 
{
	public $charDataViewer = null;
	
	
	function __construct() 
	{
		global $wgOut;
				
		parent::__construct( 'EsoCharData' );
		
		$wgOut->addModules( 'ext.EsoCharData' );
		
		$this->charDataViewer = new EsoCharDataViewer();
		$this->charDataViewer->baseUrl = "/wiki/Special:EsoCharData";
		$this->charDataViewer->baseResourceUrl = "/esochardata/";
	}
	

	function execute( $par ) 
	{
		$this->getOutput()->addModules( 'ext.EsoCharData' );
		
		$request = $this->getRequest();
		$output = $this->getOutput();
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
