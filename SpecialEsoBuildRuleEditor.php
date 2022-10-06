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


/*	function execute( $parameter )
	{
		$request = $this->getRequest();
		$output = $this->getOutput();
		$this->setHeaders();

			// TODO: Check permission for "esochardata_ruleedit"

			// TODO: Determine action/output based on the input $parameter
		$output->addHTML("TODO: ESO Build Rule Editor");
	}
	*/

	private function getBreadcrumbHtml() {
	$html = "<div class='uesppatBreadcrumb'>";
	$index = 0;

	foreach ($this->breadcrumb as $breadcrumb) {
		if ($index != 0) $html .= " : ";

		$link = $breadcrumb['link'];
		$title = $breadcrumb['title'];

		if ($link == null)
			$html .= "$title";
		else
			$html .= "<a href='$link'>$title</a>";

		++$index;
	}

	$html .= "</div>";
	return $html;
}

	private function addBreadcrumb($title, $link = null) {
		$newCrumb = array();
		$newCrumb['title'] = $title;
		$newCrumb['link'] = $link;
		$this->breadcrumb[] = $newCrumb;
	}

	public function loadInfo() {

		/*note: check if db is same*/
		$db = wfGetDB(DB_SLAVE);

		$res = $db->select('rules_table', '*');

		while ($row = $res->fetchRow()) {
			//TODO: fetch row info from table
		}

		return true;
	}


	private function loadAllRulesDataDB($useActive = true, $useInactive = true, $includeFollowers = false)
	{
		//TODO: load data from table
		$db = wfGetDB(DB_SLAVE);
		$res = $db->select('rules_table', '*');


		return $this->rules;
	}

	//complete when adding filter search features
	private function getShowListTierOptionHtml($onlyTiers = false, $targetName = "list") {

	}


//all that is needed to get the count of rules is fetching the last row's id
private function countRulesOutput()
{
	if ($rules == null) return 0;

	$outputCount = 0;


	return $outputCount;
}

private function getLastUpdateFormat(){
	//TODO: calculate when was lastUpdate
	$lastUpdate = "";

	return $lastUpdate;
}


private function outputPatronTable($patrons) {
		global $wgOut;

		$wgOut->addHTML("<table class='wikitable sortable jquery-tablesorter' id='uesprules'><thead>");

		$wgOut->addHTML("<tr>");
		$wgOut->addHTML("<th>id</th>");
		$wgOut->addHTML("<th>ruleType</th>");
		$wgOut->addHTML("<th>buffId</th>");
		$wgOut->addHTML("<th>nameId</th>");
		$wgOut->addHTML("<th>originalId</th>");
		$wgOut->addHTML("<th>statId</th>");
		$wgOut->addHTML("<th>statRequireId</th>");
		$wgOut->addHTML("<th>factorStatId</th>");
		$wgOut->addHTML("<th>version</th>");
		$wgOut->addHTML("<th>displayRegex</th>");
		$wgOut->addHTML("<th>matchRegex</th>");
		$wgOut->addHTML("<th>icon</th>");
		$wgOut->addHTML("<th>displayName</th>");
		$wgOut->addHTML("<th>group</th>");
		$wgOut->addHTML("<th category</th>");
		$wgOut->addHTML("<th description</th>");
		$wgOut->addHTML("<th value</th>");
		$wgOut->addHTML("<th factorValue</th>");
		$wgOut->addHTML("</tr></thead><tbody>");

	}

private function showList{

	$this->addBreadcrumb("Home", $this->getLink());

/*note: to be used when adding the filter search features*/
//	$this->addBreadcrumb($this->getShowListTierOptionHtml());
		$this->loadInfo();
		$rules = $this->loadAllRulesDataDB();

		if ($rules == null || count($rules) == 0) {
			$wgOut->addHTML("No rules found!");
			return;
		}

		$count = $this->countRulesOutput($rules);

		$wgOut->addHTML("Showing $count rules.");

		$lastUpdate = $this->getLastUpdateFormat();
		$wgOut->addHTML(" Patron data last updated $lastUpdate ago. ");


		$this->outputRulesTable($rules);

		$wgOut->addHTML("</form>");

}


































	function getGroupName()
	{
		return 'wiki';
	}

};
