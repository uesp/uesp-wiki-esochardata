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

		$this->rulesData = [];

		if ($result->num_rows >0){
			while($row = mysqli_fetch_assoc($result)) {
				$rulesData[] = $row;
			}
		}
	}


	public function OutputShowRulesTable()
	{
		$this->LoadRules();

		$output = $this->getOutput();

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
		$output->addHTML("<th>Visible</th>");
		$output->addHTML("<th>Enable Off Bar</th>");
		$output->addHTML("<th>Match Skill Name</th>");
		$output->addHTML("<th>Update Buff Value</th>");

		$output->addHTML("</table>");
	}


	public function OutputAddRuleForm()
	{
		$output = $this->getOutput();

		$baselink = $this->GetBaseLink();

		$output->addHTML("<h3>Add New Rule</h3>");
		$output->addHTML("<form action='$baselink/saverule' method:'POST'>");
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

		$ruleType = $_POST['ruleType'];
		$nameId = $_POST['nameId'];
		$displayName = $_POST['displayName'];
		$matchRegex =$_POST['matchRegex'];
		$requireSkillLine = $_POST['requireSkillLine'];
		$statRequireId = $_POST['statRequireId'];
		$factorStatId = $_POST['factorStatId'];
		$originalId = $_POST['originalId'];
		$version = $_POST['version'];
		$icon = $_POST['icon'];
		$group= $_POST['groupName'];
		$maxTimes = $_POST['maxTimes'];
		$comment = $_POST['comment'];
		$description = $_POST['description'];
		$disableIds = $_POST['disableIds'];
		$isEnabled = $_POST['isEnabled'];
		$isVisible = $_POST['isVisible'];
		$enableOffBar = $_POST['enableOffBar'];
		$matchSkillName = $_POST['matchSkillName'];
		$updateBuffValue = $_POST['updateBuffValue'];

		$query = "INSERT into rules(ruleType, nameId, displayName, matchRegex, requireSkillLine, statRequireId, factorStatId, originalId, version, icon, group, maxTimes, comment, description, disableIds, isEnabled, isVisible, enableOffBar, matchSkillName, updateBuffValue)
													VALUES('$ruleType', '$nameId', '$displayName', '$matchRegex', '$requireSkillLine', '$statRequireId', '$factorStatId', '$originalId', '$version', '$icon', '$group', '$maxTimes', '$comment', '$description', '$disableIds', '$isEnabled', '$isVisible', '$enableOffBar', '$matchSkillName', '$updateBuffValue');";
		$result = $this->db->query($query);

		$output->addHTML("<p>new rule saved</p><br>");
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
