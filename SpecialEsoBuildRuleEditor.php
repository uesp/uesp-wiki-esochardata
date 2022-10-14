<?php


require_once("/home/uesp/secrets/esobuilddata.secrets");


class SpecialEsoBuildRuleEditor extends SpecialPage
{


	public $db = null;


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

		$this->InitDatabase();
	}


	public static function escapeHtml($html) {
		return htmlspecialchars($html);
	}

	protected function CreateTables()
	{
		//TODO:

		$result = $this->db->query("CREATE TABLE IF NOT EXISTS rules (
                        id INTEGER AUTO_INCREMENT NOT NULL,
                        version TINYTEXT NOT NULL,
                        ruleType TINYTEXT NOT NULL,
                        nameId TINYTEXT,
                        displayName TINYTEXT,
                        matchRegex TINYTEXT NOT NULL,
                        displayRegex TINYTEXT,
                        requireSkillLine TINYTEXT,
                        statRequireId TINYTEXT,
                        statRequireValue TINYTEXT,
                        factorStatId TINYTEXT,
                        isEnabled TINYINT(1) NOT NULL,
                        isVisible TINYINT(1) NOT NULL,
                        toggleVisible TINYINT(1) NOT NULL,
                        isToggle TINYINT(1) NOT NULL,
                        enableOffBar TINYINT(1) NOT NULL,
                        matchSkillName TINYINT(1) NOT NULL,
                        updateBuffValue TINYINT(1) NOT NULL,
                        originalId TINYTEXT,
                        icon TINYTEXT,
                        groupName TINYTEXT,
                        maxTimes INTEGER,
                        comment TINYTEXT NOT NULL,
                        description TINYTEXT NOT NULL,
                        disableIds TINYTEXT,
                        PRIMARY KEY (id),
                        INDEX index_version(version(10)),
                        INDEX index_ruleId(originalId(30))
                    	);"

										);

		if ($result === false) {
			return $this->reportError("Error: failed to create table");
		}


		return true;
	}


	public function InitDatabase()
	{
		global $uespEsoBuildDataWriteDBHost, $uespEsoBuildDataWriteUser, $uespEsoBuildDataWritePW, $uespEsoBuildDataDatabase;

		$this->db = new mysqli($uespEsoBuildDataWriteDBHost, $uespEsoBuildDataWriteUser, $uespEsoBuildDataWritePW, $uespEsoBuildDataDatabase);
		if ($this->db->connect_error) {
			return $this->reportError("Error: failed to initialize database!");;
		}
		$this->CreateTables();
		return true;
	}


	public function LoadRules()
	{
		$query = "SELECT * FROM rules;";
		$result = $this->db->query($query);

		$this->rulesDatas = [];

		if ($result->num_rows >0){
			while($row = mysqli_fetch_assoc($result)) {
				$rulesDatas[] = $row;
			}
		}

		return true;
	}


	public function OutputShowRulesTable()
	{
		$this->LoadRules();

		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();

		$output->addHTML("<a href='$baselink'>Go Back to Table Of Content</a>");


		$output->addHTML("<table class='wikitable sortable jquery-tablesorter' id='rules'><thead>");

		$output->addHTML("<tr>");
		$output->addHTML("<th>Rule Type</th>");
		$output->addHTML("<th>Name ID</th>");
		$output->addHTML("<th>Display Name</th>");
		$output->addHTML("<th>Match Regex</th>");
		$output->addHTML("<th>statRequireId</th>");
		$output->addHTML("<th>Original Id</th>");
		$output->addHTML("<th>Group</th>");
		$output->addHTML("<th>Description</th>");
		$output->addHTML("<th>Version</th>");
		$output->addHTML("<th>Enabled</th>");
		$output->addHTML("<th>Toggle</th>");
		$output->addHTML("<th>Toggle Visible</th>");
		$output->addHTML("<th>Visible</th>");
		$output->addHTML("<th>Enable Off Bar</th>");
		$output->addHTML("<th>Match Skill Name</th>");
		$output->addHTML("<th>Update Buff Value</th>");
		$output->addHTML("</tr></thead><tbody>");


		foreach ($rulesDatas as $rulesData) {

			//don't think we need to show the artificial ID
			//$ID = $rulesData['id'];

			$ruleType = $rulesData['ruleType'];
			$nameId = $rulesData['nameId'];
			$displayName = $rulesData['displayName'];
			$matchRegex = $rulesData['matchRegex'];
			$statRequireId = $rulesData['statRequireId'];
			$originalId = $rulesData['originalId'];
			$groupName = $rulesData['groupName'];
			$description = $rulesData['description'];
			$version = $rulesData['version'];
			$isEnabled = $rulesData['isEnabled'];
			$toggle = $rulesData['toggle'];
			$toggleVisible = $rulesData['toggleVisible'];
			$isVisible = $rulesData['isVisible'];
			$enableOffBar = $rulesData['enableOffBar'];
			$matchSkillName = $rulesData['matchSkillName'];
			$updateBuffValue = $rulesData['updateBuffValue'];

			$output->addHTML("$ruleType");
			/* commented out for debugging
			$output->addHTML("<tr>");
			$output->addHTML("<td>$nameId</td>");
			$output->addHTML("<td>$displayName</td>");
			$output->addHTML("<td>$matchRegex</td>");
			$output->addHTML("<td>$statRequireId</td>");
			$output->addHTML("<td>$originalId</td>");
			$output->addHTML("<td>$groupName</td>");
			$output->addHTML("<td>$description</td>");
			$output->addHTML("<td>$version</td>");
			$output->addHTML("<td>$isEnabled</td>");
			$output->addHTML("<td>$toggle</td>");
			$output->addHTML("<td>$toggleVisible</td>");
			$output->addHTML("<td>$isVisible</td>");
			$output->addHTML("<td>$enableOffBar</td>");
			$output->addHTML("<td>$matchSkillName</td>");
			$output->addHTML("<td>$updateBuffValue</td>");
			$output->addHTML("</tr>");
			*/
		}


		$output->addHTML("</table>");
	}


	public function OutputAddRuleForm()
	{
		$output = $this->getOutput();

		$baselink = $this->GetBaseLink();

		$output->addHTML("<h3>Add New Rule</h3>");
		$output->addHTML("<form action='$baselink/saverule' method='POST'>");
		$output->addHTML("<label for='ruleType'>Rule Type:</label>");
		$output->addHTML("<input type='text' id='ruleType' name='ruleType'><br>");
		$output->addHTML("<label for='nameId'>Name ID:</label>");
		$output->addHTML("<input type='text' id='nameID' name='nameID'><br>");
		$output->addHTML("<label for='displayName'>Display Name:</label>");
		$output->addHTML("<input type='text' id='displayName' name='displayname'><br>");
		$output->addHTML("<label for='matchRegex'>Match Regex:</label>");
		$output->addHTML("<input type='text' id='matchRegex' name='MatchRegex'><br>");
		$output->addHTML("<label for='displayRegex'>Display Regex:</label>");
		$output->addHTML("<input type='text' id='displayRegex' name='displayRegex'><br>");
		$output->addHTML("<label for='requireSkillLine'>requireSkillLine:</label>");
		$output->addHTML("<input type='text' id='requireSkillLine' name='requireSkillLine'><br>");
		$output->addHTML("<label for='statRequireId'>statRequireId:</label>");
		$output->addHTML("<input type='text' id='statRequireId' name='statRequireId'><br>");
		$output->addHTML("<label for='factorStatId'>factorStatId:</label>");
		$output->addHTML("<input type='text' id='factorStatId' name='factorStatId'><br>");
		$output->addHTML("<label for='originalId'>Original ID:</label>");
		$output->addHTML("<input type='text' id='originalId' name='originalId'><br>");
		$output->addHTML("<label for='version'>Version:</label>");
		$output->addHTML("<input type='number' id='version' name='version'><br>");
		$output->addHTML("<label for='icon'>Icon:</label>");
		$output->addHTML("<input type='text' id='icon' name='icon'><br>");
		$output->addHTML("<label for='groupName'>Group:</label>");
		$output->addHTML("<input type='text' id='groupName' name='groupName'><br>");
		$output->addHTML("<label for='maxTimes'>Maximum Times:</label>");
		$output->addHTML("<input type='text' id='maxTimes' name='maxTimes'><br>");
		$output->addHTML("<label for='comment'>Comment:</label>");
		$output->addHTML("<input type='text' id='comment' name='comment'><br>");
		$output->addHTML("<label for='description'>Description:</label>");
		$output->addHTML("<input type='text' id='description' name='description'><br>");
		$output->addHTML("<label for='disableIds'>Disable IDs:</label>");
		$output->addHTML("<input type='text' id='disableIds' name='disableIds'><br>");

		//could only be true or false (1 or 0)
		$output->addHTML("<br><h5>For the following inputs, enter 1 for TRUE and 0 for FALSE</h5>");
		$output->addHTML("<label for='isEnabled'>Enabled:</label>");
		$output->addHTML("<input type='number' id='isEnabled' name='isEnabled'><br>");
		$output->addHTML("<label for='isVisible'>Visible:</label>");
		$output->addHTML("<input type='number' id='isVisible' name='isVisible'><br>");
		$output->addHTML("<label for='enableOffBar'>Enable Off Bar:</label>");
		$output->addHTML("<input type='number' id='enableOffBar' name='enableOffBar'><br>");
		$output->addHTML("<label for='matchSkillName'>Match Skill Name:</label>");
		$output->addHTML("<input type='number' id='matchSkillName' name='matchSkillName'><br>");
		$output->addHTML("<label for='updateBuffValue'>Update Buff Value:</label>");
		$output->addHTML("<input type='number' id='updateBuffValue' name='updateBuffValue'><br>");
		$output->addHTML("<label for='toggleVisible'>Toggle Visible:</label>");
		$output->addHTML("<input type='number' id='toggleVisible' name='toggleVisible'><br>");
		$output->addHTML("<label for='toggle'>Toggle:</label>");
		$output->addHTML("<input type='number' id='toggle' name='toggle'><br>");

		$output->addHTML("<br><input type='submit' value='Save Rule'>");

		$output->addHTML("</form>");

	}


	public function OutputEditRuleForm()
	{
		//....
		//$this->LoadRule(ruleId);
	}

	public function ReportError ($msg)
{
    $output = $this->getOutput();
    $output->addHTML($msg . "<br/>");
		$output->addHTML($this->db->error);
    error_log($msg);
    return false;
}

	public function SaveNewRule()
	{
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$req = $this->getRequest();


		$input_ruleType = $req->getVal('ruleType');
		$input_nameId = $req->getVal('nameId');
		$input_displayName = $req->getVal('displayName');
		$input_matchRegex =$req->getVal('matchRegex');
		$input_requireSkillLine = $req->getVal('requireSkillLine');
		$input_statRequireId = $req->getVal('statRequireId');
		$input_factorStatId = $req->getVal('factorStatId');
		$input_originalId = $req->getVal('originalId');
		$input_version = $req->getVal('version');
		$input_icon =$req->getVal('icon');
		$input_group= $req->getVal('group');
		$input_maxTimes = $req->getVal('maxTimes');
		$input_comment = $req->getVal('comment');
		$input_description = $req->getVal('description');
		$input_disableIds = $req->getVal('disableIds');
		$input_isEnabled = $req->getVal('isEnabled');
		$input_isVisible = $req->getVal('isVisible');
		$input_enableOffBar = $req->getVal('enableOffBar');
		$input_matchSkillName = $req->getVal('matchSkillName');
		$input_updateBuffValue = $req->getVal('updateBuffValue');
		$input_toggleVisible = $req->getVal('toggleVisible');
		$input_toggle = $req->getVal('toggle');

		$query = "INSERT into rules(ruleType, nameId, displayName, matchRegex, requireSkillLine, statRequireId, factorStatId, originalId, version, icon, groupName, maxTimes, comment, description, disableIds, isEnabled, isVisible, enableOffBar, matchSkillName, updateBuffValue, toggleVisible, isToggle)
							VALUES('$input_ruleType', '$input_nameId', '$input_displayName', '$input_matchRegex', '$input_requireSkillLine', '$input_statRequireId', '$input_factorStatId', '$input_originalId', '$input_version', '$input_icon', '$input_group', '$input_maxTimes', '$input_comment', '$input_description', '$input_disableIds', '$input_isEnabled', '$input_isVisible', '$input_enableOffBar', '$input_matchSkillName', '$input_updateBuffValue', '$input_toggleVisible', '$input_toggle');";
		$result = $this->db->query($query);

		if ($result === false) {
			return $this->reportError("Error: failed to INSERT into database");
		}

		$output->addHTML("<p>New rule added</p><br>");
		$output->addHTML("<a href='$baselink'>Go Back to Table Of Content</a>");
	}

	public function SaveChanges()
	{
		//....
	}

	public static function GetBaseLink()
	{
		$link = "https://dev.uesp.net/wiki/Special:EsoBuildRuleEditor";

		return($link);
	}

	public function OutputTableOfContents()
	{
		$output = $this->getOutput();

		$baselink = $this->GetBaseLink();

		$output->addHTML("<ul>");
		$output->addHTML("<li><a href='$baselink/showrules'>Show Rules</a></li>");
		$output->addHTML("<li><a href='$baselink/addrule'>Add Rule</a></li>");
		$output->addHTML("</ul>");
	}


	function execute( $parameter )
	{
		$request = $this->getRequest();
		$output = $this->getOutput();
		$this->setHeaders();

			// TODO: Check permission for "esochardata_ruleedit"

			// TODO: Determine action/output based on the input $parameter

		if ($parameter == "showrules")
			$this->OutputShowRulesTable();
		elseif ($parameter == "addrule")
			$this->OutputAddRuleForm();
		elseif ($parameter == "editrule")
			$this->OutputEditRuleForm();
		elseif ($parameter == "saverule")
			$this->SaveNewRule();
		elseif ($parameter == "savechanges")
			$this->SaveChanges();
		else
			$this->OutputTableOfContents();
	}


	function getGroupName()
	{
		return 'wiki';
	}

};
