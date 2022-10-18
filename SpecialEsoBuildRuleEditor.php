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
			return $this->reportError("Error: failed to initialize database");;
		}
		$this->CreateTables();
		return true;
	}


	public function LoadRules()
	{
		$query = "SELECT * FROM rules;";
		$result = $this->db->query($query);

		if ($result === false) {
			return $this->reportError("Error: failed to load rules from database");
		}

		$this->rulesDatas =[];

		while($row = mysqli_fetch_assoc($result)) {
				$this->rulesDatas[] = $row;
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
		$output->addHTML("<th>Edit</th>");
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


		foreach ($this->rulesDatas as $rulesData) {

			$ruleType = $this->escapeHtml($rulesData['ruleType']);
			$nameId = $this->escapeHtml($rulesData['nameId']);
			$displayName = $this->escapeHtml($rulesData['displayName']);
			$matchRegex = $this->escapeHtml($rulesData['matchRegex']);
			$statRequireId = $this->escapeHtml($rulesData['statRequireId']);
			$originalId = $this->escapeHtml($rulesData['originalId']);
			$groupName = $this->escapeHtml($rulesData['groupName']);
			$description = $this->escapeHtml($rulesData['description']);
			$isEnabled = $this->escapeHtml($rulesData['isEnabled']);
			$toggleVisible = $this->escapeHtml($rulesData['toggleVisible']);
			$isVisible = $this->escapeHtml($rulesData['isVisible']);
			$enableOffBar = $this->escapeHtml($rulesData['enableOffBar']);
			$matchSkillName = $this->escapeHtml($rulesData['matchSkillName']);
			$updateBuffValue = $this->escapeHtml($rulesData['updateBuffValue']);

			$output->addHTML("<tr>");
			$output->addHTML("<td><a href='$baselink/editrule'>Edit</a></td>");
			$output->addHTML("<td>$ruleType</td>");
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
		}

		$output->addHTML("</table>");
	}


	public function OutputAddRuleForm()
	{
		$output = $this->getOutput();

		$baselink = $this->GetBaseLink();

		$output->addHTML("<h3>Add New Rule</h3>");
		$output->addHTML("<form action='$baselink/saverule' method='POST'>");

		$output->addHTML("<label for='ruleType'>Rule Type: </label>");
		$output->addHTML("<select id='ruleType' name='ruleType'>");
		$output->addHTML("<option value='buff'>buff</option>");
		$output->addHTML("<option value='mundus'>mundus</option>");
		$output->addHTML("<option value='set'>set</option>");
		$output->addHTML("<option value='active'>active</option>");
		$output->addHTML("<option value='passive'>passive</option>");
		$output->addHTML("<option value='cp'>cp</option>");
		$output->addHTML("<option value='armorEnchant'>armorEnchant</option>");
		$output->addHTML("<option value='weaponEnchant'>weaponEnchant</option>");
		$output->addHTML("<option value='offHandEnchant'>offHandEnchant</option>");
		$output->addHTML("<option value='abilityDesc'>abilityDesc</option>");
		$output->addHTML("</select><br>");

		$output->addHTML("<label for='nameId'>Name ID: </label>");
		$output->addHTML("<input type='text' id='nameID' name='nameID'><br>");
		$output->addHTML("<label for='displayName'>Display Name: </label>");
		$output->addHTML("<input type='text' id='displayName' name='displayname'><br>");
		$output->addHTML("<label for='matchRegex'>Match Regex: </label>");
		$output->addHTML("<input type='text' id='matchRegex' name='MatchRegex'><br>");
		$output->addHTML("<label for='displayRegex'>Display Regex: </label>");
		$output->addHTML("<input type='text' id='displayRegex' name='displayRegex'><br>");
		$output->addHTML("<label for='requireSkillLine'>requireSkillLine: </label>");
		$output->addHTML("<input type='text' id='requireSkillLine' name='requireSkillLine'><br>");
		$output->addHTML("<label for='statRequireId'>statRequireId: </label>");
		$output->addHTML("<input type='text' id='statRequireId' name='statRequireId'><br>");
		$output->addHTML("<label for='factorStatId'>factorStatId: </label>");
		$output->addHTML("<input type='text' id='factorStatId' name='factorStatId'><br>");
		$output->addHTML("<label for='originalId'>Original ID: </label>");
		$output->addHTML("<input type='text' id='originalId' name='originalId'><br>");
		$output->addHTML("<label for='version'>Version: </label>");
		$output->addHTML("<input type='number' id='version' name='version'><br>");
		$output->addHTML("<label for='icon'>Icon: </label>");
		$output->addHTML("<input type='text' id='icon' name='icon'><br>");
		$output->addHTML("<label for='groupName'>Group: </label>");
		$output->addHTML("<input type='text' id='groupName' name='groupName'><br>");
		$output->addHTML("<label for='maxTimes'>Maximum Times: </label>");
		$output->addHTML("<input type='text' id='maxTimes' name='maxTimes'><br>");
		$output->addHTML("<label for='comment'>Comment: </label>");
		$output->addHTML("<input type='text' id='comment' name='comment'><br>");
		$output->addHTML("<label for='description'>Description: </label>");
		$output->addHTML("<input type='text' id='description' name='description'><br>");
		$output->addHTML("<label for='disableIds'>Disable IDs: </label>");
		$output->addHTML("<input type='text' id='disableIds' name='disableIds'><br>");

		//could only be true or false (1 or 0)
		$output->addHTML("<br><label for='isEnabled'>Enabled:</label>");
		$output->addHTML("<input type='checkbox' id='isEnabled' name='isEnabled' value='true'> ");
		$output->addHTML("<label for='isEnabled'>TRUE </label>");
		$output->addHTML("<input type='checkbox' id='isEnabled' name='isEnabled' value='false'> ");
		$output->addHTML("<label for='isEnabled'>FALSE </label><br>");

		$output->addHTML("<label for='isVisible'>Visible:</label>");
		$output->addHTML("<input type='checkbox' id='isVisible' name='isVisible' value='true'> ");
		$output->addHTML("<label for='isVisible'>TRUE </label>");
		$output->addHTML("<input type='checkbox' id='isVisible' name='isVisible' value='false'> ");
		$output->addHTML("<label for='isVisible'>FALSE </label><br>");


		$output->addHTML("<label for='enableOffBar'>Enable Off Bar:</label>");
		$output->addHTML("<input type='checkbox' id='enableOffBar' name='enableOffBar' value='true'> ");
		$output->addHTML("<label for='enableOffBar'>TRUE </label>");
		$output->addHTML("<input type='checkbox' id='enableOffBar' name='enableOffBar' value='false'> ");
		$output->addHTML("<label for='enableOffBar'>FALSE </label><br>");

		$output->addHTML("<label for='matchSkillName'>Match Skill Name:</label>");
		$output->addHTML("<input type='checkbox' id='matchSkillName' name='matchSkillName' value='true'> ");
		$output->addHTML("<label for='matchSkillName'>TRUE </label>");
		$output->addHTML("<input type='checkbox' id='matchSkillName' name='matchSkillName' value='false'> ");
		$output->addHTML("<label for='matchSkillName'>FALSE </label><br>");

		$output->addHTML("<label for='updateBuffValue'>Update Buff Value:</label>");
		$output->addHTML("<input type='checkbox' id='updateBuffValue' name='updateBuffValue' value='true'> ");
		$output->addHTML("<label for='updateBuffValue'>TRUE </label>");
		$output->addHTML("<input type='checkbox' id='updateBuffValue' name='updateBuffValue' value='false'> ");
		$output->addHTML("<label for='updateBuffValue'>FALSE </label><br>");

		$output->addHTML("<label for='toggleVisible'>Toggle Visible:</label>");
		$output->addHTML("<input type='checkbox' id='toggleVisible' name='toggleVisible' value='true'> ");
		$output->addHTML("<label for='toggleVisible'>TRUE </label>");
		$output->addHTML("<input type='checkbox' id='toggleVisible' name='toggleVisible' value='false'> ");
		$output->addHTML("<label for='toggleVisible'>FALSE </label><br>");

		$output->addHTML("<label for='toggle'>Toggle:</label>");
		$output->addHTML("<input type='checkbox' id='toggle' name='toggle' value='true'> ");
		$output->addHTML("<label for='toggle'>TRUE </label>");
		$output->addHTML("<input type='checkbox' id='toggle' name='toggle' value='false'> ");
		$output->addHTML("<label for='toggle'>FALSE </label><br>");

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
		$input_group= $req->getVal('groupName');
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

		//$query = "INSERT into rules(ruleType, nameId, displayName, matchRegex, requireSkillLine, statRequireId, factorStatId, originalId, version, icon, groupName, maxTimes, comment, description, disableIds, isEnabled, isVisible, enableOffBar, matchSkillName, updateBuffValue, toggleVisible, isToggle)
			//				VALUES('$input_ruleType', '$input_nameId', '$input_displayName', '$input_matchRegex', '$input_requireSkillLine', '$input_statRequireId', '$input_factorStatId', '$input_originalId', '$input_version', '$input_icon', '$input_group', '$input_maxTimes', '$input_comment', '$input_description', '$input_disableIds', '$input_isEnabled', '$input_isVisible', '$input_enableOffBar', '$input_matchSkillName', '$input_updateBuffValue', '$input_toggleVisible', '$input_toggle');";

		$cols = [];
		$values = [];
		$cols[] = 'ruleType';
		$cols[] = 'nameId';
		$cols[] = 'displayName';
		$cols[] = 'matchRegex';
		$cols[] = 'requireSkillLine';
		$cols[] = 'statRequireId';
		$cols[] = 'factorStatId';
		$cols[] = 'originalId';
		$cols[] = 'version';
		$cols[] = 'icon';
		$cols[] = 'groupName';
		$cols[] = 'maxTimes';
		$cols[] = 'comment';
		$cols[] = 'description';
		$cols[] = 'disableIds';
		$cols[] = 'isEnabled';
		$cols[] = 'isVisible';
		$cols[] = 'enableOffBar';
		$cols[] = 'matchSkillName';
		$cols[] = 'updateBuffValue';
		$cols[] = 'toggleVisible';
		$cols[] = 'isToggle';

		$values[] = "'" . $this->db->real_escape_string($input_ruleType) . "'";
		$values[] = "'" . $this->db->real_escape_string($input_nameId). "'";
		$values[] = "'" . $this->db->real_escape_string($input_displayName). "'";
		$values[] = "'" . $this->db->real_escape_string($input_matchRegex). "'";
		$values[] = "'" . $this->db->real_escape_string($input_requireSkillLine). "'";
		$values[] = "'" . $this->db->real_escape_string($input_statRequireId). "'";
		$values[] = "'" . $this->db->real_escape_string($input_factorStatId). "'";
		$values[] = "'" . $this->db->real_escape_string($input_originalId). "'";
		$values[] = "'" . $this->db->real_escape_string($input_version). "'";
		$values[] = "'" . $this->db->real_escape_string($input_icon). "'";
		$values[] = "'" . $this->db->real_escape_string($input_groupName). "'";
		$values[] = "'" . $this->db->real_escape_string($input_maxTimes). "'";
		$values[] = "'" . $this->db->real_escape_string($input_comment). "'";
		$values[] = "'" . $this->db->real_escape_string($input_description). "'";
		$values[] = "'" . $this->db->real_escape_string($input_disableIds). "'";
		$values[] = "'" . $this->db->real_escape_string($input_isEnabled). "'";
		$values[] = "'" . $this->db->real_escape_string($input_isVisible). "'";
		$values[] = "'" . $this->db->real_escape_string($input_enableOffBar). "'";
		$values[] = "'" . $this->db->real_escape_string($input_matchSkillName). "'";
		$values[] = "'" . $this->db->real_escape_string($input_updateBuffValue). "'";
		$values[] = "'" . $this->db->real_escape_string($input_toggleVisible). "'";
		$values[] = "'" . $this->db->real_escape_string($input_toggle). "'";

		$cols = implode(',', $cols);
		$values = implode(',', $values);
		$query = "INSERT INTO rules($cols) VALUES($values);";


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
