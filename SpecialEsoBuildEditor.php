<?php


class SpecialEsoBuildEditor extends SpecialPage 
{
	public $buildDataEditor = null;
	
	
	function __construct()
	{
		global $wgOut;
		global $uespIsMobile;
		
		parent::__construct( 'EsoBuildEditor' );
		
		$wgOut->addModules( 'ext.EsoBuildData.itemsearchpopup.scripts' );
		$wgOut->addModuleStyles( 'ext.EsoBuildData.itemsearchpopup.styles' );
		
		$wgOut->addModules( 'ext.EsoBuildData.viewer.scripts' );
		$wgOut->addModuleStyles( 'ext.EsoBuildData.viewer.styles' );
		
		$wgOut->addModules( 'ext.EsoBuildData.editor.scripts' );
		$wgOut->addModuleStyles( 'ext.EsoBuildData.editor.styles' );
		
		if ($uespIsMobile || (class_exists("MobileContext") && MobileContext::singleton()->isMobileDevice()))
		{
			$wgOut->addModuleStyles( 'ext.EsoBuildData.editor.mobilestyles' );
		}
		
		$this->buildDataEditor = new EsoBuildDataEditor();
		$this->buildDataEditor->baseUrl = "/wiki/Special:EsoBuildEditor";
		$this->buildDataEditor->baseResourceUrl = "/esobuilddata/";
	}
	
	
	function execute( $par )
	{
		global $uespIsMobile;
		
		$this->buildDataEditor->wikiContext = $this->getContext();
		$this->buildDataEditor->buildDataViewer->wikiContext = $this->getContext();
		
		$out = $this->getOutput();
		
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
