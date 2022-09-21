<?php


class SpecialEsoBuildRuleEditor extends SpecialPage 
{
	
	
	function __construct()
	{
		global $wgOut;
		global $uespIsMobile;
		
		parent::__construct( 'EsoBuildRuleEditor' );
		
			// TODO: Add style/script modules as needed in this format
		//$wgOut->addModules( 'ext.EsoBuildData.itemsearchpopup.scripts' );
		//$wgOut->addModuleStyles( 'ext.EsoBuildData.itemsearchpopup.styles' );
		
		if ($uespIsMobile || (class_exists("MobileContext") && MobileContext::singleton()->isMobileDevice()))
		{
			// TODO: Add any mobile specific CSS/scripts resource modules here
		}
		
	}
	
	
	function execute( $parameter )
	{
		$request = $this->getRequest();
		$output = $this->getOutput();
		$this->setHeaders();
		
			// TODO: Check permission for "esochardata_ruleedit"
		
			// TODO: Determine action/output based on the input $parameter
		$output->addHTML("TODO: ESO Build Rule Editor");
	}
	
	
	function getGroupName()
	{
		return 'wiki';
	}
	
};
