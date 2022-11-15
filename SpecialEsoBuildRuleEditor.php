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

		$wgOut->addModules( 'ext.EsoBuildData.ruleseditor.scripts' );
		$wgOut->addModuleStyles( 'ext.EsoBuildData.ruleseditor.styles' );

		if ($uespIsMobile || (class_exists("MobileContext") && MobileContext::singleton()->isMobileDevice()))
		{
			// TODO: Add any mobile specific CSS/scripts resource modules here
		}

		$this->InitDatabase();
	}


	public static function escapeHtml($html) {
		return htmlspecialchars($html);
	}


	public function canUserEdit()
	{
		$context = $this->getContext();
		if ($context == null) return false;

		$user = $context->getUser();
		if ($user == null) return false;

		if (!$user->isLoggedIn()) return false;

		return $user->isAllowedAny('esochardata_ruleedit');
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
	                        description MEDIUMTEXT NOT NULL,
	                        disableIds TINYTEXT,
	                        PRIMARY KEY (id),
	                        INDEX index_version(version(10)),
	                        INDEX index_ruleId(originalId(30))
	                    	);"

											);

			if ($result === false) {
				return $this->reportError("Error: failed to create rules table");
			}

			$effects_result = $this->db->query("CREATE TABLE IF NOT EXISTS effects (
													effectId INTEGER AUTO_INCREMENT NOT NULL,
	                        ruleId INTEGER NOT NULL,
	                        version TINYTEXT NOT NULL,
	                        statId TINYTEXT NOT NULL,
	                        value TINYTEXT,
	                        display TINYTEXT,
	                        category TINYTEXT,
	                        combineAs TINYTEXT,
	                        roundNum TINYTEXT,
	                        factorValue FLOAT,
	                        statDesc TINYTEXT,
	                        buffId TINYTEXT,
													regexVar TINYTEXT NOT NULL,
													PRIMARY KEY (effectId),
	                        INDEX index_ruleId(ruleId),
	                        INDEX index_stat(statId(32)),
	                        INDEX index_version(version(10))
	                    );

									 ");

			if ($effects_result === false) {
				return $this->reportError("Error: failed to create effects table");
			}

			$computedStats_result = $this->db->query("CREATE TABLE IF NOT EXISTS computedStats (
                        statId TINYTEXT NOT NULL,
                        version TINYTEXT NOT NULL,
                        title TINYTEXT NOT NULL,
                        roundNum TINYTEXT,
                        addClass TINYTEXT,
                        comment TINYTEXT,
                        minimumValue FLOAT,
                        maximumValue FLOAT,
                        deferLevel TINYINT,
                        display TINYTEXT,
                        compute TEXT NOT NULL,
												idx TINYINT NOT NULL,
						            category TINYTEXT NOT NULL,
						            suffix TINYTEXT NOT NULL,
						            dependsOn MEDIUMTEXT NOT NULL,
                        PRIMARY KEY (statId(32)),
                        INDEX index_version(version(10))
                    );

								");

			if ($computedStats_result === false) {
				return $this->reportError("Error: failed to create computed Stats table");
			}

			$deleteRule_result = $this->db->query("CREATE TABLE IF NOT EXISTS rulesArchive (
	                        archiveId INTEGER AUTO_INCREMENT NOT NULL,
													id INTEGER NOT NULL,
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
	                        description MEDIUMTEXT NOT NULL,
	                        disableIds TINYTEXT,
	                        PRIMARY KEY (archiveId),
	                        INDEX index_version(version(10)),
	                        INDEX index_ruleId(originalId(30))
	                    	);"

											);

			if ($deleteRule_result === false) {
				return $this->reportError("Error: failed to create rules archive table");
			}

			$deletedEffects_result = $this->db->query("CREATE TABLE IF NOT EXISTS effectsArchive (
													archiveId INTEGER AUTO_INCREMENT NOT NULL,
													effectId INTEGER NOT NULL,
													ruleId INTEGER NOT NULL,
													version TINYTEXT NOT NULL,
													statId TINYTEXT NOT NULL,
													value TINYTEXT,
													display TINYTEXT,
													category TINYTEXT,
													combineAs TINYTEXT,
													roundNum TINYTEXT,
													factorValue FLOAT,
													statDesc TINYTEXT,
													buffId TINYTEXT,
													regexVar TINYTEXT NOT NULL,
													PRIMARY KEY (archiveId),
													INDEX index_ruleId(ruleId),
													INDEX index_stat(statId(32)),
													INDEX index_version(version(10))
											);

									 ");

			if ($deletedEffects_result === false) {
				return $this->reportError("Error: failed to create effects archive table");
			}

			$DeletedcomputedStats_result = $this->db->query("CREATE TABLE IF NOT EXISTS computedStatsArchives (
                        id TINYTEXT NOT NULL,
												statId INTEGER NOT NULL,
                        version TINYTEXT NOT NULL,
                        title TINYTEXT NOT NULL,
                        roundNum TINYTEXT,
                        addClass TINYTEXT,
                        comment TINYTEXT,
                        minimumValue FLOAT,
                        maximumValue FLOAT,
                        deferLevel TINYINT,
                        display TINYTEXT,
                        compute TEXT NOT NULL,
												idx TINYINT NOT NULL,
						            category TINYTEXT NOT NULL,
						            suffix TINYTEXT NOT NULL,
						            dependsOn MEDIUMTEXT NOT NULL,
                        PRIMARY KEY (id(32)),
                        INDEX index_version(version(10))
                    );

								");

			if ($computedStats_result === false) {
				return $this->reportError("Error: failed to create computed Stats archive table");
			}


			$versions_result = $this->db->query("CREATE TABLE IF NOT EXISTS versions (
												version TINYTEXT NOT NULL,
												PRIMARY KEY idx_version(version(16))
									  );

								");

    	if ($computedStats_result === false) {
				return $this->reportError("Error: failed to create versions table");
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


	//------------------ Versions functions ---------------
	public function OutputAddVersionForm()
	{
		$permission = $this->canUserEdit();

		if($permission === False) {
			return $this->reportError("Error: you have no permission to add versions");
		}

		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();

		$output->addHTML("<h3>Add New Version</h3>");
		$output->addHTML("<form action='$baselink/saveversion' method='POST'>");

		$output->addHTML("<label for='version'>Version: </label>");
		$output->addHTML("<input type='text' id='version' name='version'><br>");

		$output->addHTML("<br><input type='submit' value='Save Version'>");
		$output->addHTML("</form>");
	}

	public function SaveNewVersion()
	{
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$req = $this->getRequest();

		$input_version = $req->getVal('version');

		$cols = [];
		$values = [];

		$cols[] = 'version';
		$values[] = "'" . $this->db->real_escape_string($input_version) . "'";

		$this->InsertQueries('versions', $cols, $values);

		$output->addHTML("<p>New version added</p><br>");
		$output->addHTML("<a href='$baselink'>Home</a>");

	}

	public function loadVersions()
	{
		$query = "SELECT version FROM versions;";
		$result = $this->db->query($query);

		if ($result === false) {
			return $this->reportError("Error: failed to load versions from database");
		}

		$this->versions =[];

		while($row = mysqli_fetch_assoc($result)) {
				$this->versions[] = $row;
		}

		return true;
	}

	public function versionsList($param, $selectedVersion)
	{
		$output = $this->getOutput();
		$this->loadVersions();

		$selected = "";

		$output->addHTML("<label for='$param'>version: </label>");
		$output->addHTML("<select id='$param' name='$param'>");

		foreach ($this->versions as $version) {

			$versionOption = $this->escapeHtml($version['version']);

			if($versionOption == $selectedVersion)
			{
				$selected = "selected";
			}
			if($versionOption != "")
			{
				$output->addHTML("<option value='$versionOption' $selected >$versionOption</option>");
			}

		}

		$output->addHTML("</select><br>");

		//$this->OutputLists($version, $versionOptions, $param);

	}

	//-------------------Queries fucntions---------------
	public function InsertQueries($tableName, $cols, $values)
	{
		$cols = implode(',', $cols);
		$values = implode(',', $values);
		$query = "INSERT INTO $tableName($cols) VALUES($values);";

		$result = $this->db->query($query);

		if ($result === false) {
			return $this->reportError("Error: failed to INSERT deleted data into database");
		}
	}

	public function DeleteQueries($tableName, $conditionName, $value)
	{
		$query = "DELETE FROM $tableName WHERE $conditionName=$value;";
		$result = $this->db->query($query);

		if ($result === false) {
			return $this->reportError("Error: failed to DELETE rule from database");
		}
	}

	public function UpdateQueries($tableName, $values, $conditionName, $value)
	{
		$values = implode(',', $values);

		$query = "UPDATE $tableName SET $values WHERE $conditionName='$value';";

		$result = $this->db->query($query);

		if ($result === false) {
			return $this->reportError("Error: failed to UPDATE data in database");
		}
	}

	//-------------------Rules table functions---------------
	public function rounds($param, $round)
	{
		$output = $this->getOutput();

		$roundOptions=[
			'' => 'None',
			'floor' => 'Floor',
			'floor10' => 'Floor10',
			'floor2' => 'Floor2',
			'ceil' => 'Ceil'
      ];

		//	$output->addHTML("<p>Test: $round</p>");
			$output->addHTML("<label for='$param'>round </label>");
			$this->OutputLists($round, $roundOptions, $param);
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

			$output->addHTML("<a href='$baselink'>Home</a>");

			$output->addHTML("<table class='wikitable sortable jquery-tablesorter' id='rules'><thead>");

			$output->addHTML("<tr>");
			$output->addHTML("<th>Edit</th>");
			$output->addHTML("<th>Id</th>");
			$output->addHTML("<th>Rule Type</th>");
			$output->addHTML("<th>Name ID</th>");
			$output->addHTML("<th>Display Name</th>");
			$output->addHTML("<th>Match Regex</th>");
			$output->addHTML("<th>statRequireId</th>");
			$output->addHTML("<th>Original Id</th>");
			$output->addHTML("<th>groupName</th>");
			$output->addHTML("<th>Description</th>");
			$output->addHTML("<th>Version</th>");
			$output->addHTML("<th>Enabled</th>");
			$output->addHTML("<th>Toggle</th>");
			$output->addHTML("<th>Toggle Visible</th>");
			$output->addHTML("<th>Visible</th>");
			$output->addHTML("<th>Enable Off Bar</th>");
			$output->addHTML("<th>Match Skill Name</th>");
			$output->addHTML("<th>Update Buff Value</th>");
			$output->addHTML("<th>Delete</th>");
			$output->addHTML("</tr></thead><tbody>");


			foreach ($this->rulesDatas as $rulesData) {

				$id = $this->escapeHtml($rulesData['id']);
				$ruleType = $this->escapeHtml($rulesData['ruleType']);
				$nameId = $this->escapeHtml($rulesData['nameId']);
				$displayName = $this->escapeHtml($rulesData['displayName']);
				$matchRegex = $this->escapeHtml($rulesData['matchRegex']);
				$statRequireId = $this->escapeHtml($rulesData['statRequireId']);
				$originalId = $this->escapeHtml($rulesData['originalId']);
				$groupName = $this->escapeHtml($rulesData['groupName']);
				$description = $this->escapeHtml($rulesData['description']);
				$version = $this->escapeHtml($rulesData['version']);
				$isEnabled = $this->escapeHtml($rulesData['isEnabled']);
				$toggleVisible = $this->escapeHtml($rulesData['toggleVisible']);
				$toggle = $this->escapeHtml($rulesData['isToggle']);
				$isVisible = $this->escapeHtml($rulesData['isVisible']);
				$enableOffBar = $this->escapeHtml($rulesData['enableOffBar']);
				$matchSkillName = $this->escapeHtml($rulesData['matchSkillName']);
				$updateBuffValue = $this->escapeHtml($rulesData['updateBuffValue']);


				$isEnabledDisplay = $this->GetBooleanDispaly($isEnabled);
				$toggleVisibleDisplay = $this->GetBooleanDispaly($toggleVisible);
				$toggleDisplay = $this->GetBooleanDispaly($toggle);
				$isVisibleDisplay = $this->GetBooleanDispaly($isVisible);
				$enableOffBarDisplay = $this->GetBooleanDispaly($enableOffBar);
				$matchSkillNameDisplay = $this->GetBooleanDispaly($matchSkillName);
				$updateBuffValueDisplay = $this->GetBooleanDispaly($updateBuffValue);

				$output->addHTML("<tr>");
				$output->addHTML("<td><a href='$baselink/editrule?ruleid=$id'>Edit</a></td>");
				$output->addHTML("<td>$id</td>");
				$output->addHTML("<td>$ruleType</td>");
				$output->addHTML("<td>$nameId</td>");
				$output->addHTML("<td>$displayName</td>");
				$output->addHTML("<td>$matchRegex</td>");
				$output->addHTML("<td>$statRequireId</td>");
				$output->addHTML("<td>$originalId</td>");
				$output->addHTML("<td>$groupName</td>");
				$output->addHTML("<td>$description</td>");
				$output->addHTML("<td>$version</td>");
				$output->addHTML("<td>$isEnabledDisplay</td>");
				$output->addHTML("<td>$toggleDisplay</td>");
				$output->addHTML("<td>$toggleVisibleDisplay</td>");
				$output->addHTML("<td>$isVisibleDisplay</td>");
				$output->addHTML("<td>$enableOffBarDisplay</td>");
				$output->addHTML("<td>$matchSkillNameDisplay</td>");
				$output->addHTML("<td>$updateBuffValueDisplay</td>");
				$output->addHTML("<td><a href='$baselink/deleterule?ruleid=$id'>Delete</a></td>");
				$output->addHTML("</tr>");
				}

			$output->addHTML("</table>");
	}

	public function OutputDeleteRule()
	{
		$permission = $this->canUserEdit();

		if($permission === False) {
			return $this->reportError("Error: you have no permission to delete rules");
		}

		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();

		$id = $this->GetRowId();
		$id = $this->escapeHtml($id);

		if ($id <= 0) {
			return $this->reportError("Error: invalid rule ID");
		}

		$this->LoadRule($id);

		if ($this->LoadRule($id) == False){
			return $this->reportError("Error: cannot load Rule");
		}

		$ruleType = $this->escapeHtml($this->rule['ruleType']);
		$nameId = $this->escapeHtml($this->rule['nameId']);
		$displayName = $this->escapeHtml($this->rule['displayName']);
		$matchRegex = $this->escapeHtml($this->rule['matchRegex']);
		$displayRegex = $this->escapeHtml($this->rule['displayRegex']);
		$requireSkillLine = $this->escapeHtml($this->rule['requireSkillLine']);
		$statRequireId = $this->escapeHtml($this->rule['statRequireId']);
		$factorStatId = $this->escapeHtml($this->rule['factorStatId']);
		$originalId = $this->escapeHtml($this->rule['originalId']);
		$version = $this->escapeHtml($this->rule['version']);
		$icon = $this->escapeHtml($this->rule['icon']);
		$groupName = $this->escapeHtml($this->rule['groupName']);
		$maxTimes = $this->escapeHtml($this->rule['maxTimes']);
		$comment = $this->escapeHtml($this->rule['comment']);
		$description = $this->escapeHtml($this->rule['description']);
		$disableIds = $this->escapeHtml($this->rule['disableIds']);
		$isEnabled = $this->escapeHtml($this->rule['isEnabled']);
		$isVisible = $this->escapeHtml($this->rule['isVisible']);
		$enableOffBar = $this->escapeHtml($this->rule['enableOffBar']);
		$matchSkillName = $this->escapeHtml($this->rule['matchSkillName']);
		$updateBuffValue = $this->escapeHtml($this->rule['updateBuffValue']);
		$toggleVisible = $this->escapeHtml($this->rule['toggleVisible']);
		$toggle = $this->escapeHtml($this->rule['isToggle']);

		$output->addHTML("<h3>Are you sure you want to delete this rule: </h3>");
		$output->addHTML("<label><b>id:</b> $id </label><br>");
		$output->addHTML("<label><b>Rule Type:</b> $ruleType </label><br>");
		$output->addHTML("<label><b>nameId:</b> $nameId </label><br>");
		$output->addHTML("<label><b>displayName:</b> $displayName </label><br>");
		$output->addHTML("<label><b>matchRegex:</b> $matchRegex </label><br>");
		$output->addHTML("<label><b>requireSkillLine:</b> $requireSkillLine </label><br>");
		$output->addHTML("<label><b>statRequireId:</b> $statRequireId </label><br>");
		$output->addHTML("<label><b>factorStatId:</b> $factorStatId </label><br>");
		$output->addHTML("<label><b>originalId:</b> $originalId </label><br>");
		$output->addHTML("<label><b>originalId:</b> $version </label><br>");
		$output->addHTML("<label><b>icon:</b> $icon </label><br>");
		$output->addHTML("<label><b>groupName:</b> $groupName </label><br>");
		$output->addHTML("<label><b>maxTimes:</b> $maxTimes </label><br>");
		$output->addHTML("<label><b>comment:</b> $comment </label><br>");
		$output->addHTML("<label><b>description:</b> $description </label><br>");
		$output->addHTML("<label><b>disableIds:</b> $disableIds </label><br>");
		$output->addHTML("<label><b>isEnabled:</b> $isEnabled </label><br>");
		$output->addHTML("<label><b>isVisible:</b> $isVisible </label><br>");
		$output->addHTML("<label><b>enableOffBar:</b> $enableOffBar </label><br>");
		$output->addHTML("<label><b>matchSkillName:</b> $matchSkillName </label><br>");
		$output->addHTML("<label><b>updateBuffValue:</b> $updateBuffValue </label><br>");
		$output->addHTML("<label><b>toggleVisible:</b> $toggleVisible </label><br>");
		$output->addHTML("<label><b>isToggle:</b> $isToggle </label><br>");

		$output->addHTML("<br><a href='$baselink/ruledeleteconfirm?ruleid=$id&confirm=True'>Delete </a>");
		$output->addHTML("<a href='$baselink/ruledeleteconfirm?ruleid=$id&confirm=False'> Cancel</a>");

	}

	public function ConfirmDeleteRule()
	{
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$req = $this->getRequest();

		$confirm = $req->getVal('confirm');
		$id = $this->GetRowId();

		$id = $this->db->real_escape_string($id);

		if ($id <= 0) {
			return $this->reportError("Error: invalid rule ID");
		}

		if ($confirm !== 'True')
		{
			$output->addHTML("<p>Delete cancelled</p><br>");
			$output->addHTML("<a href='$baselink'>Home</a>");
		}
		else {
			$this->LoadRule($id);

			if ($this->LoadRule($id) == False){
				return $this->reportError("Error: cannot load Rule");
			}

			$ruleType = $this->escapeHtml($this->rule['ruleType']);
			$nameId = $this->escapeHtml($this->rule['nameId']);
			$displayName = $this->escapeHtml($this->rule['displayName']);
			$matchRegex = $this->escapeHtml($this->rule['matchRegex']);
			$displayRegex = $this->escapeHtml($this->rule['displayRegex']);
			$requireSkillLine = $this->escapeHtml($this->rule['requireSkillLine']);
			$statRequireId = $this->escapeHtml($this->rule['statRequireId']);
			$factorStatId = $this->escapeHtml($this->rule['factorStatId']);
			$originalId = $this->escapeHtml($this->rule['originalId']);
			$version = $this->escapeHtml($this->rule['version']);
			$icon = $this->escapeHtml($this->rule['icon']);
			$groupName = $this->escapeHtml($this->rule['groupName']);
			$maxTimes = $this->escapeHtml($this->rule['maxTimes']);
			$comment = $this->escapeHtml($this->rule['comment']);
			$description = $this->escapeHtml($this->rule['description']);
			$disableIds = $this->escapeHtml($this->rule['disableIds']);
			$isEnabled = $this->escapeHtml($this->rule['isEnabled']);
			$isVisible = $this->escapeHtml($this->rule['isVisible']);
			$enableOffBar = $this->escapeHtml($this->rule['enableOffBar']);
			$matchSkillName = $this->escapeHtml($this->rule['matchSkillName']);
			$updateBuffValue = $this->escapeHtml($this->rule['updateBuffValue']);
			$toggleVisible = $this->escapeHtml($this->rule['toggleVisible']);
			$toggle = $this->escapeHtml($this->rule['isToggle']);


			$cols = [];
			$values = [];
			$cols[] = 'id';
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

			$values[] = "'" . $this->db->real_escape_string($id) . "'";
			$values[] = "'" . $this->db->real_escape_string($ruleType) . "'";
			$values[] = "'" . $this->db->real_escape_string($displayName) . "'";
			$values[] = "'" . $this->db->real_escape_string($displayName) . "'";
			$values[] = "'" . $this->db->real_escape_string($matchRegex) . "'";
			$values[] = "'" . $this->db->real_escape_string($requireSkillLine) . "'";
			$values[] = "'" . $this->db->real_escape_string($statRequireId) . "'";
			$values[] = "'" . $this->db->real_escape_string($factorStatId) . "'";
			$values[] = "'" . $this->db->real_escape_string($originalId) . "'";
			$values[] = "'" . $this->db->real_escape_string($version) . "'";
			$values[] = "'" . $this->db->real_escape_string($icon) . "'";
			$values[] = "'" . $this->db->real_escape_string($groupName) . "'";
			$values[] = "'" . $this->db->real_escape_string($maxTimes) . "'";
			$values[] = "'" . $this->db->real_escape_string($comment) . "'";
			$values[] = "'" . $this->db->real_escape_string($description) . "'";
			$values[] = "'" . $this->db->real_escape_string($disableIds) . "'";
			$values[] = "'" . $this->db->real_escape_string($isEnabled) . "'";
			$values[] = "'" . $this->db->real_escape_string($isVisible) . "'";
			$values[] = "'" . $this->db->real_escape_string($enableOffBar) . "'";
			$values[] = "'" . $this->db->real_escape_string($matchSkillName) . "'";
			$values[] = "'" . $this->db->real_escape_string($updateBuffValue) . "'";
			$values[] = "'" . $this->db->real_escape_string($toggleVisible) . "'";
			$values[] = "'" . $this->db->real_escape_string($isToggle) . "'";


			$this->InsertQueries('rulesArchive', $cols, $values);
			$this->DeleteQueries('rules', 'id', $id);

			$this->loadEffects();
			foreach ($this->effectsDatas as $effectsData) {

				$effectId = $this->escapeHtml($effectsData['effectId']);
				$version = $this->escapeHtml($effectsData['version']);
				$statId = $this->escapeHtml($effectsData['statId']);
				$value = $this->escapeHtml($effectsData['value']);
				$display = $this->escapeHtml($effectsData['display']);
				$category = $this->escapeHtml($effectsData['category']);
				$combineAs = $this->escapeHtml($effectsData['combineAs']);
				$roundNum = $this->escapeHtml($effectsData['roundNum']);
				$factorValue = $this->escapeHtml($effectsData['factorValue']);
				$statDesc = $this->escapeHtml($effectsData['statDesc']);
				$buffId = $this->escapeHtml($effectsData['buffId']);

				$cols = [];
				$values = [];
				$cols[] = 'effectId';
				$cols[] = 'ruleId';
				$cols[] = 'version';
				$cols[] = 'statId';
				$cols[] = 'value';
				$cols[] = 'display';
				$cols[] = 'category';
				$cols[] = 'combineAs';
				$cols[] = 'roundNum';
				$cols[] = 'factorValue';
				$cols[] = 'statDesc';
				$cols[] = 'buffId';

				$values[] = "'" . $this->db->real_escape_string($effectId) . "'";
				$values[] = "'" . $this->db->real_escape_string($id) . "'";
				$values[] = "'" . $this->db->real_escape_string($version) . "'";
				$values[] = "'" . $this->db->real_escape_string($statId) . "'";
				$values[] = "'" . $this->db->real_escape_string($value) . "'";
				$values[] = "'" . $this->db->real_escape_string($display) . "'";
				$values[] = "'" . $this->db->real_escape_string($category) . "'";
				$values[] = "'" . $this->db->real_escape_string($combineAs) . "'";
				$values[] = "'" . $this->db->real_escape_string($roundNum) . "'";
				$values[] = "'" . $this->db->real_escape_string($factorValue) . "'";
				$values[] = "'" . $this->db->real_escape_string($statDesc) . "'";
				$values[] = "'" . $this->db->real_escape_string($buffId) . "'";

				$this->InsertQueries('effectsArchive', $cols, $values);
			}

			$this->DeleteQueries('effects', 'ruleId', $id);

			$output->addHTML("<p>Rule deleted</p><br>");
			$output->addHTML("<a href='$baselink'>Home</a>");

		}


	}

	public function LoadRule($primaryKey)
	{
			$primaryKey = $this->db->real_escape_string($primaryKey);
			$query = "SELECT * FROM rules WHERE id= '$primaryKey';";
			$result = $this->db->query($query);

			if ($result === false) {
				return $this->reportError("Error: failed to load rule from database");
			}

			$row=[];
			$row[] = $result->fetch_assoc();
			$this->rule = $row[0];

			return true;
	}

	public function OutputEditRuleForm()
	{
			$permission = $this->canUserEdit();

			if($permission === False) {
				return $this->reportError("Error: you have no permission to edit rules");
			}

			$output = $this->getOutput();
			$baselink = $this->GetBaseLink();

			$id = $this->GetRowId();

		  $this->LoadRule($id);

			$ruleType = $this->escapeHtml($this->rule['ruleType']);
			$nameId = $this->escapeHtml($this->rule['nameId']);
			$displayName = $this->escapeHtml($this->rule['displayName']);
			$matchRegex = $this->escapeHtml($this->rule['matchRegex']);
			$displayRegex = $this->escapeHtml($this->rule['displayRegex']);
			$requireSkillLine = $this->escapeHtml($this->rule['requireSkillLine']);
			$statRequireId = $this->escapeHtml($this->rule['statRequireId']);
			$factorStatId = $this->escapeHtml($this->rule['factorStatId']);
			$originalId = $this->escapeHtml($this->rule['originalId']);
			$version = $this->escapeHtml($this->rule['version']);
			$icon = $this->escapeHtml($this->rule['icon']);
			$groupName = $this->escapeHtml($this->rule['groupName']);
			$maxTimes = $this->escapeHtml($this->rule['maxTimes']);
			$comment = $this->escapeHtml($this->rule['comment']);
			$description = $this->escapeHtml($this->rule['description']);
			$disableIds = $this->escapeHtml($this->rule['disableIds']);
			$isEnabled = $this->escapeHtml($this->rule['isEnabled']);
			$isVisible = $this->escapeHtml($this->rule['isVisible']);
			$enableOffBar = $this->escapeHtml($this->rule['enableOffBar']);
			$matchSkillName = $this->escapeHtml($this->rule['matchSkillName']);
			$updateBuffValue = $this->escapeHtml($this->rule['updateBuffValue']);
			$toggleVisible = $this->escapeHtml($this->rule['toggleVisible']);
			$toggle = $this->escapeHtml($this->rule['isToggle']);

			$output->addHTML("<a href='$baselink/showrules'>Show Rules</a><br>");
			$output->addHTML("<h3>Edit Rule: $id</h3>");
			$output->addHTML("<form action='$baselink/saveeditruleform?ruleid=$id' method='POST'>");

			$ruleTypeOptions=[
                '' => 'None',
                'buff' => 'Buff',
                'mundus' => 'Mundus',
								'set' => 'Set',
								'active' => 'Active',
								'passive' => 'Passive',
								'cp' => 'CP',
								'armorEnchant' => 'Armor Enchantment',
								'weaponEnchant' => 'Weapon Enchantment',
								'offHandEnchant' => 'Off-Hand Enchantment',
								'abilityDesc' => 'Ability Description'
      ];

			$output->addHTML("<label for='edit_ruleType'>Rule Type: </label>");
			$this->OutputLists($ruleType, $ruleTypeOptions, 'edit_ruleType');


			$output->addHTML("<label for='edit_nameId'>Name ID: </label>");
			$output->addHTML("<input type='text' id='edit_nameId' name='edit_nameId' value='$nameId'><br>");
			$output->addHTML("<label for='edit_displayName'>Display Name: </label>");
			$output->addHTML("<input type='text' id='edit_displayName' name='edit_displayName' value='$displayName'><br>");
			$output->addHTML("<label for='edit_matchRegex'>Match Regex: </label>");
			$output->addHTML("<input type='text' id='edit_matchRegex' name='edit_matchRegex' value='$matchRegex'><br>");
			$output->addHTML("<label for='edit_displayRegex'>Display Regex: </label>");
			$output->addHTML("<input type='text' id='edit_displayRegex' name='edit_displayRegex' value='$displayRegex'><br>");
			$output->addHTML("<label for='edit_requireSkillLine'>requireSkillLine: </label>");
			$output->addHTML("<input type='text' id='edit_requireSkillLine' name='edit_requireSkillLine' value='$requireSkillLine'><br>");
			$output->addHTML("<label for='edit_statRequireId'>statRequireId: </label>");
			$output->addHTML("<input type='text' id='edit_statRequireId' name='edit_statRequireId' value='$statRequireId'><br>");
			$output->addHTML("<label for='edit_factorStatId'>factorStatId: </label>");
			$output->addHTML("<input type='text' id='edit_factorStatId' name='edit_factorStatId' value='$factorStatId'><br>");
			$output->addHTML("<label for='edit_originalId'>Original ID: </label>");
			$output->addHTML("<input type='text' id='edit_originalId' name='edit_originalId' value='$originalId'><br>");

			$this->versionsList('edit_version', $version);

			$output->addHTML("<label for='edit_icon'>Icon </label>");
			$output->addHTML("<input type='text' id='edit_icon' class='iconFld' name='edit_icon' value='$icon'><br>");
			$output->addHTML("<label for='edit_groupName'>groupName </label>");
			$output->addHTML("<input type='text' id='edit_groupName' name='edit_groupName' value='$groupName'><br>");
			$output->addHTML("<label for='edit_maxTimes'>Maximum Times </label>");
			$output->addHTML("<input type='text' id='edit_maxTimes' name='edit_maxTimes' value='$maxTimes'><br>");
			$output->addHTML("<label for='edit_comment'>Comment </label>");
			$output->addHTML("<input type='text' id='edit_comment' name='edit_comment' value='$comment'><br>");

			$output->addHTML("<label for='edit_description'>Description </label>");
			$output->addHTML("<textarea id='edit_description' name='edit_description' class='txtArea' rows='4' cols='50'>$description</textarea><br>");

			$output->addHTML("<label for='edit_disableIds'>Disable IDs </label>");
			$output->addHTML("<input type='text' id='edit_disableIds' name='edit_disableIds' value='$disableIds'><br>");


			$isEnabledBoxCheck = $this->GetCheckboxState($isEnabled);
			$isVisibleBoxCheck = $this->GetCheckboxState($isVisible);
			$enableOffBarBoxCheck = $this->GetCheckboxState($enableOffBar);
			$matchSkillNameBoxCheck = $this->GetCheckboxState($matchSkillName);
			$updateBuffValueBoxCheck = $this->GetCheckboxState($updateBuffValue);
			$toggleVisibleBoxCheck = $this->GetCheckboxState($toggleVisible);
			$isEnabledBoxCheck = $this->GetCheckboxState($isEnabled);
			$toggleBoxCheck = $this->GetCheckboxState($toggle);

			$output->addHTML("<br><label for='edit_isEnabled'>Enabled:</label>");
			$output->addHTML("<input $isEnabledBoxCheck type='checkbox' id='edit_isEnabled' name='edit_isEnabled' value='1'><br> ");
			$output->addHTML("<label for='edit_isVisible'>Visible:</label>");
			$output->addHTML("<input $isVisibleBoxCheck type='checkbox' id='edit_isVisible' name='edit_isVisible' value='1'><br>");
			$output->addHTML("<label for='edit_enableOffBar'>Enable Off Bar:</label>");
			$output->addHTML("<input $enableOffBarBoxCheck type='checkbox' id='edit_enableOffBar' name='edit_enableOffBar' value='1'><br>");
			$output->addHTML("<label for='edit_matchSkillName'>Match Skill Name:</label>");
			$output->addHTML("<input $matchSkillNameBoxCheck type='checkbox' id='edit_matchSkillName' name='edit_matchSkillName' value='1'><br>");
			$output->addHTML("<label for='edit_updateBuffValue'>Update Buff Value:</label>");
			$output->addHTML("<input $updateBuffValueBoxCheck type='checkbox' id='edit_updateBuffValue' name='edit_updateBuffValue' value='1'><br>");
			$output->addHTML("<label for='edit_toggleVisible'>Toggle Visible:</label>");
			$output->addHTML("<input $toggleVisibleBoxCheck type='checkbox' id='edit_toggleVisible' name='edit_toggleVisible' value='1'><br>");
			$output->addHTML("<label for='edit_toggle'>Toggle:</label>");
			$output->addHTML("<input $toggleBoxCheck type='checkbox' id='edit_toggle' name='edit_toggle' value='1'><br>");

			$output->addHTML("<br><input type='submit' value='Save Edits'>");
			$output->addHTML("</form><br>");

			$this->OutputShowEffectsTable();

	}


	public function OutputAddRuleForm()
	{
			$permission = $this->canUserEdit();

			if($permission === False) {
				return $this->reportError("Error: you have no permission to add rules");
			}
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
			$output->addHTML("<input type='text' id='nameId' name='nameId'><br>");
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

			$this->versionsList('version', '1');

			$output->addHTML("<label for='icon'>Icon: </label>");
			$output->addHTML("<input type='text' id='icon' name='icon'><br>");
			$output->addHTML("<label for='groupName'>groupName: </label>");
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
			$output->addHTML("<input type='checkbox' id='isEnabled' name='isEnabled' value='1'><br>");
			$output->addHTML("<label for='isVisible'>Visible:</label>");
			$output->addHTML("<input type='checkbox' id='isVisible' name='isVisible' value='1'><br>");
			$output->addHTML("<label for='enableOffBar'>Enable Off Bar:</label>");
			$output->addHTML("<input type='checkbox' id='enableOffBar' name='enableOffBar' value='1'><br>");
			$output->addHTML("<label for='matchSkillName'>Match Skill Name:</label>");
			$output->addHTML("<input type='checkbox' id='matchSkillName' name='matchSkillName' value='1'><br>");
			$output->addHTML("<label for='updateBuffValue'>Update Buff Value:</label>");
			$output->addHTML("<input type='checkbox' id='updateBuffValue' name='updateBuffValue' value='1'><br>");
			$output->addHTML("<label for='toggleVisible'>Toggle Visible:</label>");
			$output->addHTML("<input type='checkbox' id='toggleVisible' name='toggleVisible' value='1'><br>");
			$output->addHTML("<label for='toggle'>Toggle:</label>");
			$output->addHTML("<input type='checkbox' id='toggle' name='toggle' value='1'><br>");

			$output->addHTML("<br><input type='submit' value='Save Rule'>");
			$output->addHTML("</form>");

	}


	public function GetCheckboxState ($boolValue)
	{
			$returnVal = "";

			if($boolValue === '1') {
				$returnVal = "checked";
			}
			return $returnVal;
	}

	public function OutputLists($option, $array, $listName)
	{
		$output = $this->getOutput();

		$output->addHTML("<select id='$listName' name='$listName'>");
		foreach ($array as $key => $value)
		{
				$selected = "";
				if ($key == $option) $selected = "selected";
				$output->addHTML("<option value='$key' $selected >$value</option>");
		}
		$output->addHTML("</select><br>");

	}

	public function GetBooleanDispaly ($boolValue)
	{
			$returnVal = "";

			if($boolValue === '1') {
				$returnVal = "Yes";
			}
			return $returnVal;
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
		$input_groupName= $req->getVal('groupName');
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


		$this->InsertQueries('rules', $cols, $values);

		$output->addHTML("<p>New rule added</p><br>");
		$output->addHTML("<a href='$baselink'>Home</a>");
	}

	public function SaveEditRuleForm()
	{
			$output = $this->getOutput();
			$baselink = $this->GetBaseLink();
			$req = $this->getRequest();

			$id = $this->GetRowId();
			$id = $this->db->real_escape_string($id);

			if ($id <= 0) {
				return $this->reportError("Error: invalid rule ID");
			}

			$new_ruleType = $req->getVal('edit_ruleType');
			$new_nameId = $req->getVal('edit_nameId');
			$new_displayName = $req->getVal('edit_displayName');
			$new_matchRegex =$req->getVal('edit_matchRegex');
			$new_requireSkillLine = $req->getVal('edit_requireSkillLine');
			$new_statRequireId = $req->getVal('edit_statRequireId');
			$new_factorStatId = $req->getVal('edit_factorStatId');
			$new_originalId = $req->getVal('edit_originalId');
			$new_version = $req->getVal('edit_version');
			$new_icon =$req->getVal('edit_icon');
			$new_groupName= $req->getVal('edit_groupName');
			$new_maxTimes = $req->getVal('edit_maxTimes');
			$new_comment = $req->getVal('edit_comment');
			$new_description = $req->getVal('edit_description');
			$new_disableIds = $req->getVal('edit_disableIds');
			$new_isEnabled = $req->getVal('edit_isEnabled');
			$new_isVisible = $req->getVal('edit_isVisible');
			$new_enableOffBar = $req->getVal('edit_enableOffBar');
			$new_matchSkillName = $req->getVal('edit_matchSkillName');
			$new_updateBuffValue = $req->getVal('edit_updateBuffValue');
			$new_toggleVisible = $req->getVal('edit_toggleVisible');
			$new_toggle = $req->getVal('edit_toggle');

			$values = [];

			$values[] = "ruleType='" . $this->db->real_escape_string($new_ruleType) . "'";
			$values[] = "nameId='" . $this->db->real_escape_string($new_nameId). "'";
			$values[] = "displayName='" . $this->db->real_escape_string($new_displayName). "'";
			$values[] = "matchRegex='" . $this->db->real_escape_string($new_matchRegex). "'";
			$values[] = "requireSkillLine='" . $this->db->real_escape_string($new_requireSkillLine). "'";
			$values[] = "statRequireId='" . $this->db->real_escape_string($new_statRequireId). "'";
			$values[] = "factorStatId='" . $this->db->real_escape_string($new_factorStatId). "'";
			$values[] = "originalId='" . $this->db->real_escape_string($new_originalId). "'";
			$values[] = "version='" . $this->db->real_escape_string($new_version). "'";
			$values[] = "icon='" . $this->db->real_escape_string($new_icon). "'";
			$values[] = "groupName='" . $this->db->real_escape_string($new_groupName). "'";
			$values[] = "maxTimes='" . $this->db->real_escape_string($new_maxTimes). "'";
			$values[] = "comment='" . $this->db->real_escape_string($new_comment). "'";
			$values[] = "description='" . $this->db->real_escape_string($new_description). "'";
			$values[] = "disableIds='" . $this->db->real_escape_string($new_disableIds). "'";
			$values[] = "isEnabled='" . $this->db->real_escape_string($new_isEnabled). "'";
			$values[] = "isVisible='" . $this->db->real_escape_string($new_isVisible). "'";
			$values[] = "enableOffBar='" . $this->db->real_escape_string($new_enableOffBar). "'";
			$values[] = "matchSkillName='" . $this->db->real_escape_string($new_matchSkillName). "'";
			$values[] = "updateBuffValue='" . $this->db->real_escape_string($new_updateBuffValue). "'";
			$values[] = "toggleVisible='" . $this->db->real_escape_string($new_toggleVisible). "'";
			$values[] = "isToggle='" . $this->db->real_escape_string($new_toggle). "'";

			$this->UpdateQueries('rules', $values, 'id', $id);

			$output->addHTML("<p>Edits saved for rule #$id</p><br>");
			$output->addHTML("<a href='$baselink'>Home</a>");

	}

	public function GetRowId()
	{

		$req = $this->getRequest();
		$ruleId = $req->getVal('ruleid');

		return $ruleId;

		//$url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		//$rowId = substr($url, strpos($url, "=") + 1);

		//return $rowId;
	}

	public static function GetBaseLink()
	{
		$link = "https://dev.uesp.net/wiki/Special:EsoBuildRuleEditor";

		return($link);
	}

//-------------------Effects table functions---------------

	public function loadEffects()
	{

		$id = $this->GetRowId();
		$id = $this->db->real_escape_string($id);
		$query = "SELECT * FROM effects where ruleId =$id;";
		$effects_result = $this->db->query($query);

		if ($effects_result === false) {
			return $this->reportError("Error: failed to load effects from database");
		}

		$this->effectsDatas =[];

		while($row = mysqli_fetch_assoc($effects_result)) {
				$this->effectsDatas[] = $row;
		}

		return true;
	}

	public function OutputShowEffectsTable()
	{
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$this->loadEffects();
		$req = $this->getRequest();

		$id = $this->GetRowId();
		$effectId = $req->getVal('effectid');

		$output->addHTML("<hr><h3>Rule Effects:</h3>");
		$output->addHTML("<a href='$baselink/addneweffect?ruleid=$id'>Add new effect</a>");

		$output->addHTML("<table class='wikitable sortable jquery-tablesorter' id='effects'><thead>");
		$output->addHTML("<tr>");
		$output->addHTML("<th>Edit</th>");
		$output->addHTML("<th>Id</th>");
		$output->addHTML("<th>version</th>");
		$output->addHTML("<th>statId</th>");
		$output->addHTML("<th>value</th>");
		$output->addHTML("<th>display</th>");
		$output->addHTML("<th>category</th>");
		$output->addHTML("<th>combineAs</th>");
		$output->addHTML("<th>round</th>");
		$output->addHTML("<th>factorValue</th>");
		$output->addHTML("<th>statDesc</th>");
		$output->addHTML("<th>buffId</th>");
		$output->addHTML("<th>regexVar</th>");
		$output->addHTML("<th>Delete</th>");
		$output->addHTML("</tr></thead><tbody>");

		foreach ($this->effectsDatas as $effectsData) {

			$effectId = $this->escapeHtml($effectsData['effectId']);
			$version = $this->escapeHtml($effectsData['version']);
			$statId = $this->escapeHtml($effectsData['statId']);
			$value = $this->escapeHtml($effectsData['value']);
			$display = $this->escapeHtml($effectsData['display']);
			$category = $this->escapeHtml($effectsData['category']);
			$combineAs = $this->escapeHtml($effectsData['combineAs']);
			$round = $this->escapeHtml($effectsData['roundNum']);
			$factorValue = $this->escapeHtml($effectsData['factorValue']);
			$statDesc = $this->escapeHtml($effectsData['statDesc']);
			$buffId = $this->escapeHtml($effectsData['buffId']);
			$regexVar = $this->escapeHtml($effectsData['regexVar']);

			$output->addHTML("<tr>");
			$output->addHTML("<td><a href='$baselink/editeffect?effectid=$effectId&ruleid=$id'>Edit</a></td>");
			$output->addHTML("<td>$effectId</td>");
			$output->addHTML("<td>$version</td>");
			$output->addHTML("<td>$statId</td>");
			$output->addHTML("<td>$value</td>");
			$output->addHTML("<td>$display</td>");
			$output->addHTML("<td>$category</td>");
			$output->addHTML("<td>$combineAs</td>");
			$output->addHTML("<td>$round</td>");
			$output->addHTML("<td>$factorValue</td>");
			$output->addHTML("<td>$statDesc</td>");
			$output->addHTML("<td>$buffId</td>");
			$output->addHTML("<td>$regexVar</td>");
			$output->addHTML("<td><a href='$baselink/deleteeffect?effectid=$effectId&ruleid=$id' >Delete</a></td>");
		}

		$output->addHTML("</table>");

	}

	public function OutputDeleteEffect()
	{
		$permission = $this->canUserEdit();

		if($permission === False) {
			return $this->reportError("Error: you have no permission to delete effects");
		}

		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$this->loadEffects();
		$req = $this->getRequest();

		$id = $this->GetRowId();
		$effectId = $req->getVal('effectid');

		$this->loadEffect($effectId);

		if ($this->loadEffect($effectId) == False){
			return $this->reportError("Error: cannot load effect");
		}

		$version = $this->escapeHtml($this->effect['version']);
		$statId = $this->escapeHtml($this->effect['statId']);
		$value = $this->escapeHtml($this->effect['value']);
		$display = $this->escapeHtml($this->effect['display']);
		$category = $this->escapeHtml($this->effect['category']);
		$combineAs = $this->escapeHtml($this->effect['combineAs']);
		$round = $this->escapeHtml($this->effect['roundNum']);
		$factorValue = $this->escapeHtml($this->effect['factorValue']);
		$statDesc = $this->escapeHtml($this->effect['statDesc']);
		$buffId = $this->escapeHtml($this->effect['buffId']);
		$regexVar = $this->escapeHtml($this->effect['regexVar']);

		$output->addHTML("<h3>Are you sure you want to delete this effect: </h3>");
		$output->addHTML("<label><b>id:</b> $effectId </label><br>");
		$output->addHTML("<label><b>version:</b> $version </label><br>");
		$output->addHTML("<label><b>statId:</b> $statId </label><br>");
		$output->addHTML("<label><b>value:</b> $value </label><br>");
		$output->addHTML("<label><b>display:</b> $display </label><br>");
		$output->addHTML("<label><b>category:</b> $category </label><br>");
		$output->addHTML("<label><b>combineAs:</b> $combineAs </label><br>");
		$output->addHTML("<label><b>round:</b> $round </label><br>");
		$output->addHTML("<label><b>factorValue:</b> $factorValue </label><br>");
		$output->addHTML("<label><b>statDesc:</b> $statDesc </label><br>");
		$output->addHTML("<label><b>buffId:</b> $buffId </label><br>");
		$output->addHTML("<label><b>buffId:</b> $regexVar </label><br>");

		$output->addHTML("<br><a href='$baselink/effectdeleteconfirm?ruleid=$id&effectid=$effectId&confirm=True'>Delete </a>");
		$output->addHTML("<a href='$baselink/effectdeleteconfirm?effectid=$effectId&confirm=False'> Cancel</a>");
	}

	public function ConfirmDeleteEffect()
	{
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$req = $this->getRequest();

		$confirm = $req->getVal('confirm');
		$effectId = $req->getVal('effectid');

		$effectId = $this->db->real_escape_string($effectId);
		$id = $this->GetRowId();

		if ($effectId <= 0) {
			return $this->reportError("Error: invalid stat ID");
		}

		if ($confirm !== 'True')
		{
			$output->addHTML("<p>Delete cancelled</p><br>");
			$output->addHTML("<a href='$baselink'>Home</a>");
		}
		else
		{
			$this->loadEffect($effectId);

			if ($this->loadEffect($effectId) == False){
				return $this->reportError("Error: cannot load effect");
			}

			$version = $this->escapeHtml($this->effect['version']);
			$statId = $this->escapeHtml($this->effect['statId']);
			$value = $this->escapeHtml($this->effect['value']);
			$display = $this->escapeHtml($this->effect['display']);
			$category = $this->escapeHtml($this->effect['category']);
			$combineAs = $this->escapeHtml($this->effect['combineAs']);
			$round = $this->escapeHtml($this->effect['roundNum']);
			$factorValue = $this->escapeHtml($this->effect['factorValue']);
			$statDesc = $this->escapeHtml($this->effect['statDesc']);
			$buffId = $this->escapeHtml($this->effect['buffId']);
			$regexVar = $this->escapeHtml($this->effect['regexVar']);

			$cols = [];
			$values = [];
			$cols[] = 'ruleId';
			$cols[] = 'version';
			$cols[] = 'statId';
			$cols[] = 'value';
			$cols[] = 'display';
			$cols[] = 'category';
			$cols[] = 'combineAs';
			$cols[] = 'roundNum';
			$cols[] = 'factorValue';
			$cols[] = 'statDesc';
			$cols[] = 'buffId';
			$cols[] = 'regexVar';

			$values[] = "'" . $this->db->real_escape_string($id). "'";
			$values[] = "'" . $this->db->real_escape_string($version). "'";
			$values[] = "'" . $this->db->real_escape_string($statId). "'";
			$values[] = "'" . $this->db->real_escape_string($value). "'";
			$values[] = "'" . $this->db->real_escape_string($display). "'";
			$values[] = "'" . $this->db->real_escape_string($category). "'";
			$values[] = "'" . $this->db->real_escape_string($combineAs). "'";
			$values[] = "'" . $this->db->real_escape_string($round). "'";
			$values[] = "'" . $this->db->real_escape_string($factorValue). "'";
			$values[] = "'" . $this->db->real_escape_string($statDesc). "'";
			$values[] = "'" . $this->db->real_escape_string($buffId). "'";
			$values[] = "'" . $this->db->real_escape_string($regexVar). "'";


			$this->InsertQueries('effectsArchive', $cols, $values);
			$this->DeleteQueries('effects', 'effectId', $effectId);

			$output->addHTML("<p>Effect deleted</p><br>");
			$output->addHTML("<a href='$baselink'>Home : </a>");
			$output->addHTML("<a href='$baselink/editrule?ruleid=$id'>Rule #$id</a>");

		}

	}

  public function SaveNewEffect()
	{

		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$req = $this->getRequest();

		$id = $this->GetRowId();
		$input_version = $req->getVal('version');
		$input_statId = $req->getVal('statId');
		$input_value = $req->getVal('value');
		$input_display = $req->getVal('display');
		$input_category = $req->getVal('category');
		$input_combineAs = $req->getVal('combineAs');
		$input_round = $req->getVal('round');
		$input_factorValue = $req->getVal('factorValue');
		$input_statDesc = $req->getVal('statDesc');
		$input_buffId = $req->getVal('buffId');
		$input_regexVar = $req->getVal('regexVar');

		$cols = [];
		$values = [];
		$cols[] = 'ruleId';
		$cols[] = 'version';
		$cols[] = 'statId';
		$cols[] = 'value';
		$cols[] = 'display';
		$cols[] = 'category';
		$cols[] = 'combineAs';
		$cols[] = 'roundNum';
		$cols[] = 'factorValue';
		$cols[] = 'statDesc';
		$cols[] = 'buffId';
		$cols[] = 'regexVar';

		$values[] = "'" . $this->db->real_escape_string($id). "'";
		$values[] = "'" . $this->db->real_escape_string($input_version). "'";
		$values[] = "'" . $this->db->real_escape_string($input_statId). "'";
		$values[] = "'" . $this->db->real_escape_string($input_value). "'";
		$values[] = "'" . $this->db->real_escape_string($input_display). "'";
		$values[] = "'" . $this->db->real_escape_string($input_category). "'";
		$values[] = "'" . $this->db->real_escape_string($input_combineAs). "'";
		$values[] = "'" . $this->db->real_escape_string($input_round). "'";
		$values[] = "'" . $this->db->real_escape_string($input_factorValue). "'";
		$values[] = "'" . $this->db->real_escape_string($input_statDesc). "'";
		$values[] = "'" . $this->db->real_escape_string($input_buffId). "'";
		$values[] = "'" . $this->db->real_escape_string($input_regexVar). "'";

		$this->InsertQueries('effects', $cols, $values);

		$output->addHTML("<p>New effect added</p><br>");
		$output->addHTML("<a href='$baselink'>Home : </a>");
		$output->addHTML("<a href='$baselink/editrule?ruleid=$id'>Rule #$id</a>");


	}

  public function OutpuAddtEffectForm()
	{
		$permission = $this->canUserEdit();

		if($permission === False) {
			return $this->reportError("Error: you have no permission to add effects");
		}

		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$id = $this->GetRowId();


		$output->addHTML("<a href='$baselink/editrule?ruleid=$id'>Rule #$id</a>");
		$output->addHTML("<h3>Add New Effect For Rule: $id</h3>");
		$output->addHTML("<form action='$baselink/savenewffect?ruleid=$id' method='POST'>");

		$this->versionsList('version', '1');

		$output->addHTML("<label for='statId'>statId: </label>");
		$output->addHTML("<input type='text' id='statId' name='statId'><br>");
		$output->addHTML("<label for='value'>value: </label>");
		$output->addHTML("<input type='text' id='value' name='value'><br>");
		$output->addHTML("<label for='display'>display: </label>");
		$output->addHTML("<input type='text' id='display' name='display'><br>");
		$output->addHTML("<label for='category'>category: </label>");
		$output->addHTML("<input type='text' id='category' name='category'><br>");
		$output->addHTML("<label for='combineAs'>combineAs: </label>");
		$output->addHTML("<input type='text' id='combineAs' name='combineAs'><br>");

		$this->rounds('round', '');

		$output->addHTML("<label for='factorValue'>factorValue: </label>");
		$output->addHTML("<input type='text' id='factorValue' name='factorValue'><br>");
		$output->addHTML("<label for='statDesc'>statDesc: </label>");
		$output->addHTML("<input type='text' id='statDesc' name='statDesc'><br>");
		$output->addHTML("<label for='buffId'>buffId: </label>");
		$output->addHTML("<input type='text' id='buffId' name='buffId'><br>");
		$output->addHTML("<label for='regexVar'>regexVar: </label>");
		$output->addHTML("<input type='text' id='regexVar' name='regexVar'><br>");

		$output->addHTML("<br><input type='submit' value='Save Effect'>");

		$output->addHTML("</form>");
	}

  public function loadEffect($effectId)
	{

	   $effectId = $this->db->real_escape_string($effectId);
		 $query = "SELECT * FROM effects WHERE effectId = '$effectId';";
		 $effects_result = $this->db->query($query);

		 if ($effects_result === false) {
			 return $this->reportError("Error: failed to load effect from database");
		 }

		 $row=[];
		 $row[] = $effects_result->fetch_assoc();
		 $this->effect = $row[0];

		 return true;
	 }

	public function OutputEditEffectForm()
	{
		$permission = $this->canUserEdit();

		if($permission === False) {
			return $this->reportError("Error: you have no permission to edit effects");
		}

		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$req = $this->getRequest();

		$effectId = $req->getVal('effectid');
		$ruleId = $this->GetRowId();

		$this->loadEffect($effectId);

		$version = $this->escapeHtml($this->effect['version']);
		$statId = $this->escapeHtml($this->effect['statId']);
		$value = $this->escapeHtml($this->effect['value']);
		$display = $this->escapeHtml($this->effect['display']);
		$category = $this->escapeHtml($this->effect['category']);
		$combineAs = $this->escapeHtml($this->effect['combineAs']);
		$round = $this->escapeHtml($this->effect['roundNum']);
		$factorValue = $this->escapeHtml($this->effect['factorValue']);
		$statDesc = $this->escapeHtml($this->effect['statDesc']);
		$buffId = $this->escapeHtml($this->effect['buffId']);
		$regexVar = $this->escapeHtml($this->effect['regexVar']);

		$output->addHTML("<a href='$baselink/showrules'>Show Rules : </a>");
		$output->addHTML("<a href='$baselink/editrule?ruleid=$ruleId'>Rule #$ruleId</a><br>");
		$output->addHTML("<h3>Edit Effect: $effectId</h3>");
		$output->addHTML("<form action='$baselink/saveediteffectform?effectid=$effectId&ruleid=$ruleId' method='POST'>");

		$this->versionsList('edit_version', $version);

		$output->addHTML("<label for='edit_statId'>statId </label>");
		$output->addHTML("<input type='text' id='edit_statId' name='edit_statId' value='$statId'><br>");
		$output->addHTML("<label for='edit_value'>value </label>");
		$output->addHTML("<input type='text' id='edit_value' name='edit_value' value='$value'><br>");
		$output->addHTML("<label for='edit_display'>display </label>");
		$output->addHTML("<input type='text' id='edit_display' name='edit_display' value='$display'><br>");
		$output->addHTML("<label for='edit_category'>category </label>");
		$output->addHTML("<input type='text' id='edit_category' name='edit_category' value='$category'><br>");
		$output->addHTML("<label for='edit_combineAs'>combineAs </label>");
		$output->addHTML("<input type='text' id='edit_combineAs' name='edit_combineAs' value='$combineAs'><br>");

		$this->rounds('edit_round', $round);

		$output->addHTML("<label for='edit_factorValue'>factorValue </label>");
		$output->addHTML("<input type='text' id='edit_factorValue' name='edit_factorValue' value='$factorValue'><br>");
		$output->addHTML("<label for='edit_statDesc'>statDesc </label>");
		$output->addHTML("<input type='text' id='edit_statDesc' name='edit_statDesc' value='$statDesc'><br>");
		$output->addHTML("<label for='edit_buffId'>buffId </label>");
		$output->addHTML("<input type='text' id='edit_buffId' name='edit_buffId' value='$buffId'><br>");
		$output->addHTML("<label for='edit_regexVar'>regexVar: </label>");
		$output->addHTML("<input type='text' id='edit_regexVar' name='edit_regexVar' value='$regexVar'><br>");

		$output->addHTML("<br><input type='submit' value='Save Edits'>");
		$output->addHTML("</form><br>");
	}

	public function SaveEditEffectForm()
	{

		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$req = $this->getRequest();

		$ruleId = $this->GetRowId();
		$effectId = $req->getVal('effectid');

		$effectId = $this->db->real_escape_string($effectId);

		if ($effectId <= 0) {
			return $this->reportError("Error: invalid effect ID");
		}

		$new_version = $req->getVal('edit_version');
		$new_statId = $req->getVal('edit_statId');
		$new_value = $req->getVal('edit_value');
		$new_display = $req->getVal('edit_display');
		$new_category = $req->getVal('edit_category');
		$new_combineAs = $req->getVal('edit_combineAs');
		$new_round = $req->getVal('edit_round');
		$new_factorValue = $req->getVal('edit_factorValue');
		$new_statDesc = $req->getVal('edit_statDesc');
		$new_buffId = $req->getVal('edit_buffId');
		$new_regexVar = $req->getVal('edit_regexVar');


		$values = [];

		$values[] = "version='" . $this->db->real_escape_string($new_version) . "'";
		$values[] = "statId='" . $this->db->real_escape_string($new_statId) . "'";
		$values[] = "value='" . $this->db->real_escape_string($new_value) . "'";
		$values[] = "display='" . $this->db->real_escape_string($new_display) . "'";
		$values[] = "category='" . $this->db->real_escape_string($new_category) . "'";
		$values[] = "combineAs='" . $this->db->real_escape_string($new_combineAs) . "'";
		$values[] = "roundNum='" . $this->db->real_escape_string($new_round) . "'";
		$values[] = "factorValue='" . $this->db->real_escape_string($new_factorValue) . "'";
		$values[] = "statDesc='" . $this->db->real_escape_string($new_statDesc) . "'";
		$values[] = "buffId='" . $this->db->real_escape_string($new_buffId) . "'";
		$values[] = "regexVar='" . $this->db->real_escape_string($new_regexVar) . "'";

		$this->UpdateQueries('effects', $values, 'effectId', $effectId);


		$output->addHTML("<p>Edits saved for effect #$effectId</p><br>");
		$output->addHTML("<a href='$baselink/editrule?ruleid=$ruleId'>Rule #$ruleId</a><br>");

	}

	public function GetEffectId()
	{

		$req = $this->getRequest();
		$effectId = $req->getVal('effectid');
		return $effectId;

		//$url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		//$rowId = substr($url, strpos($url, "=") + 1);

		//return $rowId;
	}


//-------------------computedStats functions---------------

	public function loadComputedStats()
	{
		$query = "SELECT * FROM computedStats;";
		$computedStats_result = $this->db->query($query);

		if ($computedStats_result === false) {
			return $this->reportError("Error: failed to load computed Stats from database");
		}

		$this->computedStatsDatas = [];

		while($row = mysqli_fetch_assoc($computedStats_result)) {
				$this->computedStatsDatas[] = $row;
		}

		return true;
	}


	public function OutputShowComputedStatsTable()
	{
		$this->loadComputedStats();

		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();

		$output->addHTML("<a href='$baselink'>Home</a>");

		$output->addHTML("<table class='wikitable sortable jquery-tablesorter' id='computedStats'><thead>");

		$output->addHTML("<tr>");
		$output->addHTML("<th>Edit</th>");
		$output->addHTML("<th>statId</th>");
		$output->addHTML("<th>version</th>");
		$output->addHTML("<th>round</th>");
		$output->addHTML("<th>addClass</th>");
		$output->addHTML("<th>comment</th>");
		$output->addHTML("<th>minimumValue</th>");
		$output->addHTML("<th>maximumValue</th>");
		$output->addHTML("<th>deferLevel</th>");
		$output->addHTML("<th>display</th>");
		$output->addHTML("<th>compute</th>");
		$output->addHTML("<th>idx</th>");
		$output->addHTML("<th>category</th>");
		$output->addHTML("<th>suffix</th>");
		$output->addHTML("<th>dependsOn</th>");
		$output->addHTML("<th>Delete</th>");
		$output->addHTML("</tr></thead><tbody>");

		foreach ($this->computedStatsDatas as $computedStatsData) {

			$statId = $this->escapeHtml($computedStatsData['statId']);
			$version = $this->escapeHtml($computedStatsData['version']);
			$roundNum = $this->escapeHtml($computedStatsData['roundNum']);
			$addClass = $this->escapeHtml($computedStatsData['addClass']);
			$comment = $this->escapeHtml($computedStatsData['comment']);
			$minimumValue = $this->escapeHtml($computedStatsData['minimumValue']);
			$maximumValue = $this->escapeHtml($computedStatsData['maximumValue']);
			$deferLevel = $this->escapeHtml($computedStatsData['deferLevel']);
			$display = $this->escapeHtml($computedStatsData['display']);
			$compute = $this->escapeHtml($computedStatsData['compute']);
			$idx = $this->escapeHtml($computedStatsData['idx']);
			$category = $this->escapeHtml($computedStatsData['category']);
			$suffix = $this->escapeHtml($computedStatsData['suffix']);
			$dependsOn = $this->escapeHtml($computedStatsData['dependsOn']);

			$output->addHTML("<tr>");
			$output->addHTML("<td><a href='$baselink/editcomputedstat?statid=$statId'>Edit</a></td>");
			$output->addHTML("<td>$statId</td>");
			$output->addHTML("<td>$version</td>");
			$output->addHTML("<td>$roundNum</td>");
			$output->addHTML("<td>$addClass</td>");
			$output->addHTML("<td>$comment</td>");
			$output->addHTML("<td>$minimumValue</td>");
			$output->addHTML("<td>$maximumValue</td>");
			$output->addHTML("<td>$deferLevel</td>");
			$output->addHTML("<td>$display</td>");
			$output->addHTML("<td>$compute</td>");
			$output->addHTML("<td>$idx</td>");
			$output->addHTML("<td>$category</td>");
			$output->addHTML("<td>$suffix</td>");
			$output->addHTML("<td>$dependsOn</td>");
			$output->addHTML("<td><a href='$baselink/deletcomputedstat?statid=$statId'>Delete</a></td>");

		}

		$output->addHTML("</table>");
	}


	public function OutputAddComputedStatsForm()
	{
		$permission = $this->canUserEdit();

		if($permission === False) {
			return $this->reportError("Error: you have no permission to add computed stats");
		}
		$output = $this->getOutput();

		$baselink = $this->GetBaseLink();

		$output->addHTML("<h3>Add New computed Stat</h3>");
		$output->addHTML("<form action='$baselink/savenewcomputedstat' method='POST'>");

		$output->addHTML("<label for='statId'>statId: </label>");
		$output->addHTML("<input type='text' id='statId' name='statId'><br>");

		$this->versionsList('version', '1');
		$this->rounds('round', '');

		$output->addHTML("<label for='addClass'>addClass: </label>");
		$output->addHTML("<input type='text' id='addClass' name='addClass'><br>");
		$output->addHTML("<label for='comment'>comment: </label>");
		$output->addHTML("<input type='text' id='comment' name='comment'><br>");
		$output->addHTML("<label for='minimumValue'>minimumValue: </label>");
		$output->addHTML("<input type='number' id='minimumValue' name='minimumValue'><br>");
		$output->addHTML("<label for='maximumValue'>maximumValue: </label>");
		$output->addHTML("<input type='number' id='maximumValue' name='maximumValue'><br>");
		$output->addHTML("<label for='deferLevel'>deferLevel: </label>");
		$output->addHTML("<input type='text' id='deferLevel' name='deferLevel'><br>");
		$output->addHTML("<label for='display'>display: </label>");
		$output->addHTML("<input type='text' id='display' name='display'><br>");
		$output->addHTML("<label for='compute'>compute: </label>");
		$output->addHTML("<input type='text' id='compute' name='compute'><br>");
		$output->addHTML("<label for='idx'>idx: </label>");
		$output->addHTML("<input type='text' id='idx' name='idx'><br>");
		$output->addHTML("<label for='category'>category: </label>");
		$output->addHTML("<input type='text' id='category' name='category'><br>");
		$output->addHTML("<label for='suffix'>suffix: </label>");
		$output->addHTML("<input type='text' id='suffix' name='suffix'><br>");
		$output->addHTML("<label for='dependsOn'>dependsOn: </label>");
		$output->addHTML("<input type='text' id='dependsOn' name='dependsOn'><br>");

		$output->addHTML("<br><input type='submit' value='Save computed Stat'>");
		$output->addHTML("</form>");
	}

	public function laodStatIds()
	{
		$query = "SELECT statId FROM computedStats;";
		$result = $this->db->query($query);

		if ($result === false) {
			return $this->reportError("Error: failed to load stat ids from database");
		}

		$col=[];
		$col[] = $result->fetch_assoc();
		$this->stat = $col['statId'];

		return true;
	}

	public function SaveNewComputedStat()
	{
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$req = $this->getRequest();

		$input_statId = $req->getVal('statId');

		$this->laodStatIds();

		if(in_array($input_statId, $this->stat) === True) {
			return $this->reportError("Error: statId = $input_statId is already used");
		}

		$input_version = $req->getVal('version');
		$input_roundNum = $req->getVal('round');
		$input_addClass = $req->getVal('addClass');
		$input_comment = $req->getVal('comment');
		$input_minimumValue = $req->getVal('minimumValue');
		$input_maximumValue = $req->getVal('maximumValue');
		$input_deferLevel = $req->getVal('deferLevel');
		$input_display = $req->getVal('display');
		$input_compute = $req->getVal('compute');
		$input_idx = $req->getVal('idx');
		$input_category = $req->getVal('category');
		$input_suffix = $req->getVal('suffix');
		$input_dependsOn = $req->getVal('dependsOn');

		$cols = [];
		$values = [];
		$cols[] = 'statId';
		$cols[] = 'version';
		$cols[] = 'roundNum';
		$cols[] = 'addClass';
		$cols[] = 'comment';
		$cols[] = 'minimumValue';
		$cols[] = 'maximumValue';
		$cols[] = 'deferLevel';
		$cols[] = 'display';
		$cols[] = 'compute';
		$cols[] = 'idx';
		$cols[] = 'category';
		$cols[] = 'suffix';
		$cols[] = 'dependsOn';

		$values[] = "'" . $this->db->real_escape_string($input_statId) . "'";
		$values[] = "'" . $this->db->real_escape_string($input_version) . "'";
		$values[] = "'" . $this->db->real_escape_string($input_roundNum) . "'";
		$values[] = "'" . $this->db->real_escape_string($input_addClass) . "'";
		$values[] = "'" . $this->db->real_escape_string($input_comment) . "'";
		$values[] = "'" . $this->db->real_escape_string($input_minimumValue) . "'";
		$values[] = "'" . $this->db->real_escape_string($input_maximumValue) . "'";
		$values[] = "'" . $this->db->real_escape_string($input_deferLevel) . "'";
		$values[] = "'" . $this->db->real_escape_string($input_display) . "'";
		$values[] = "'" . $this->db->real_escape_string($input_compute) . "'";
		$values[] = "'" . $this->db->real_escape_string($input_idx) . "'";
		$values[] = "'" . $this->db->real_escape_string($input_category) . "'";
		$values[] = "'" . $this->db->real_escape_string($input_suffix) . "'";
		$values[] = "'" . $this->db->real_escape_string($input_dependsOn) . "'";


		$this->InsertQueries('computedStats', $cols, $values);

		$output->addHTML("<p>New computed Stat added</p><br>");
		$output->addHTML("<a href='$baselink'>Home</a>");
	}

	public function LoadComputedStat($primaryKey)
	{
		$primaryKey = $this->db->real_escape_string($primaryKey);
		$query = "SELECT * FROM computedStats WHERE statId= '$primaryKey';";
		$computedStats_result = $this->db->query($query);

		if ($computedStats_result === false) {
			return $this->reportError("Error: failed to load computed Stat from database");
		}

		$row=[];
		$row[] = $computedStats_result->fetch_assoc();
		$this->computedStat = $row[0];

		return true;
	}

  public function OutputEditComputedStatForm()
	{
		$permission = $this->canUserEdit();

		if($permission === False) {
			return $this->reportError("Error: you have no permission to edit computed stats");
		}

		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$req = $this->getRequest();

		$statId = $req->getVal('statid');

		$this->LoadComputedStat($statId);

		$version = $this->escapeHtml($this->computedStat['version']);
		$round = $this->escapeHtml($this->computedStat['roundNum']);
		$addClass = $this->escapeHtml($this->computedStat['addClass']);
		$comment = $this->escapeHtml($this->computedStat['comment']);
		$minimumValue = $this->escapeHtml($this->computedStat['minimumValue']);
		$maximumValue = $this->escapeHtml($this->computedStat['maximumValue']);
		$deferLevel = $this->escapeHtml($this->computedStat['deferLevel']);
		$display = $this->escapeHtml($this->computedStat['display']);
		$compute = $this->escapeHtml($this->computedStat['compute']);
		$idx = $this->escapeHtml($this->computedStat['idx']);
		$category = $this->escapeHtml($this->computedStat['category']);
		$suffix = $this->escapeHtml($this->computedStat['suffix']);
		$dependsOn = $this->escapeHtml($this->computedStat['dependsOn']);


		$output->addHTML("<a href='$baselink/showcomputedstats'>Show Computed Stats</a><br>");
		$output->addHTML("<h3>Edit Computed Stat: $statId</h3>");
		$output->addHTML("<form action='$baselink/saveeditcomputedstatsform?statid=$statId' method='POST'>");

		$this->versionsList('edit_version', $version);
		$roundOptions=$this->rounds('edit_round', $round);

		$output->addHTML("<label for='edit_addClass'>addClass </label>");
		$output->addHTML("<input type='text' id='edit_addClass' name='edit_addClass' value='$addClass'><br>");
		$output->addHTML("<label for='edit_comment'>comment </label>");
		$output->addHTML("<input type='text' id='edit_comment' name='edit_comment' value='$comment'><br>");
		$output->addHTML("<label for='edit_minimumValue'>minimumValue </label>");
		$output->addHTML("<input type='text' id='edit_minimumValue' name='edit_minimumValue' value='$minimumValue'><br>");
		$output->addHTML("<label for='edit_maximumValue'>maximumValue </label>");
		$output->addHTML("<input type='text' id='edit_maximumValue' name='edit_maximumValue' value='$maximumValue'><br>");
		$output->addHTML("<label for='edit_deferLevel'>deferLevel </label>");
		$output->addHTML("<input type='text' id='edit_deferLevel' name='edit_deferLevel' value='$deferLevel'><br>");
		$output->addHTML("<label for='edit_display'>display </label>");
		$output->addHTML("<input type='text' id='edit_display' name='edit_display' value='$display'><br>");
		$output->addHTML("<label for='edit_compute'>compute </label>");
		$output->addHTML("<textarea id='edit_compute' name='edit_compute' class='txtArea' rows='4' cols='50'>$compute</textarea><br>");
		$output->addHTML("<label for='edit_idx'>idx </label>");
		$output->addHTML("<input type='text' id='edit_idx' name='edit_idx' value='$idx'><br>");
		$output->addHTML("<label for='edit_category'>category </label>");
		$output->addHTML("<input type='text' id='edit_category' name='edit_category' value='$category'><br>");
		$output->addHTML("<label for='edit_suffix'>suffix </label>");
		$output->addHTML("<input type='text' id='edit_suffix' name='edit_suffix' value='$suffix'><br>");
		$output->addHTML("<label for='edit_dependsOn'>dependsOn </label>");
		$output->addHTML("<input type='text' id='edit_dependsOn' name='edit_dependsOn' value='$dependsOn'><br>");

		$output->addHTML("<br><input class='btn' type='submit' value='Save Edits'>");
		$output->addHTML("</form><br>");

  }

	public function SaveEditComputedStatsForm()
	{
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$req = $this->getRequest();

		$statId = $req->getVal('statid');
		$statId = $this->db->real_escape_string($statId);

		$new_version = $req->getVal('edit_version');
		$new_roundNum = $req->getVal('edit_round');
		$new_addClass = $req->getVal('edit_addClass');
		$new_comment = $req->getVal('edit_comment');
		$new_minimumValue = $req->getVal('edit_minimumValue');
		$new_maximumValue = $req->getVal('edit_maximumValue');
		$new_deferLevel = $req->getVal('edit_deferLevel');
		$new_display = $req->getVal('edit_display');
		$new_compute = $req->getVal('edit_compute');
		$new_idx = $req->getVal('edit_idx');
		$new_category = $req->getVal('edit_category');
		$new_suffix = $req->getVal('edit_suffix');
		$new_dependsOn = $req->getVal('edit_dependsOn');

		$values = [];

		$values[] = "version='" . $this->db->real_escape_string($new_version) . "'";
		$values[] = "roundNum='" . $this->db->real_escape_string($new_roundNum) . "'";
		$values[] = "addClass='" . $this->db->real_escape_string($new_addClass) . "'";
		$values[] = "comment='" . $this->db->real_escape_string($new_comment) . "'";
		$values[] = "minimumValue='" . $this->db->real_escape_string($new_minimumValue) . "'";
		$values[] = "maximumValue='" . $this->db->real_escape_string($new_maximumValue) . "'";
		$values[] = "deferLevel='" . $this->db->real_escape_string($new_deferLevel) . "'";
		$values[] = "display='" . $this->db->real_escape_string($new_display) . "'";
		$values[] = "compute='" . $this->db->real_escape_string($new_compute) . "'";
		$values[] = "idx='" . $this->db->real_escape_string($new_idx) . "'";
		$values[] = "category='" . $this->db->real_escape_string($new_category) . "'";
		$values[] = "suffix='" . $this->db->real_escape_string($new_suffix) . "'";
		$values[] = "dependsOn='" . $this->db->real_escape_string($new_dependsOn) . "'";

		$this->UpdateQueries('computedStats', $values, 'statId', $statId);

		$output->addHTML("<p>Edits saved for computed Stat #$statId</p><br>");
		$output->addHTML("<a href='$baselink'>Home</a>");

	}

	public function OutputDeleteComputedStat()
	{
		$permission = $this->canUserEdit();

		if($permission === False) {
			return $this->reportError("Error: you have no permission to delete computed stats");
		}

		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$req = $this->getRequest();

		$statId = $req->getVal('statid');
		$statId = $this->escapeHtml($statId);

		$this->LoadComputedStat($statId);

		if ($this->LoadComputedStat($statId) == False)
		{
			return $this->reportError("Error: cannot load stat");
		}

		$version = $this->escapeHtml($this->computedStat['version']);
		$roundNum = $this->escapeHtml($this->computedStat['roundNum']);
		$addClass = $this->escapeHtml($this->computedStat['addClass']);
		$comment = $this->escapeHtml($this->computedStat['comment']);
		$minimumValue = $this->escapeHtml($this->computedStat['minimumValue']);
		$maximumValue = $this->escapeHtml($this->computedStat['maximumValue']);
		$deferLevel = $this->escapeHtml($this->computedStat['deferLevel']);
		$display = $this->escapeHtml($this->computedStat['display']);
		$compute = $this->escapeHtml($this->computedStat['compute']);
		$idx = $this->escapeHtml($this->computedStat['idx']);
		$category = $this->escapeHtml($this->computedStat['category']);
		$suffix = $this->escapeHtml($this->computedStat['suffix']);
		$dependsOn = $this->escapeHtml($this->computedStat['dependsOn']);

		$output->addHTML("<h3>Are you sure you want to delete this computed Stat: </h3>");
		$output->addHTML("<label><b>id:</b> $statId </label><br>");
		$output->addHTML("<label><b>version:</b> $version </label><br>");
		$output->addHTML("<label><b>roundNum:</b> $roundNum </label><br>");
		$output->addHTML("<label><b>addClass:</b> $addClass </label><br>");
		$output->addHTML("<label><b>comment:</b> $comment </label><br>");
		$output->addHTML("<label><b>minimumValue:</b> $minimumValue </label><br>");
		$output->addHTML("<label><b>maximumValue:</b> $maximumValue </label><br>");
		$output->addHTML("<label><b>deferLevel:</b> $deferLevel </label><br>");
		$output->addHTML("<label><b>display:</b> $display </label><br>");
		$output->addHTML("<label><b>compute:</b> $compute </label><br>");
		$output->addHTML("<label><b>idx:</b> $idx </label><br>");
		$output->addHTML("<label><b>category:</b> $category </label><br>");
		$output->addHTML("<label><b>suffix:</b> $suffix </label><br>");
		$output->addHTML("<label><b>dependsOn:</b> $dependsOn </label><br>");

		$output->addHTML("<br><a href='$baselink/statdeleteconfirm?statid=$statId&confirm=True'>Delete </a>");
		$output->addHTML("<a href='$baselink/statdeleteconfirm?statid=$statId&confirm=False'> Cancel</a>");
	}

	public function ConfirmDeleteStat()
	{
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$req = $this->getRequest();

		$confirm = $req->getVal('confirm');
		$statId = $req->getVal('statid');
		$statId = $this->db->real_escape_string($statId);

		if ($confirm !== 'True')
		{
			$output->addHTML("<p>Delete cancelled</p><br>");
			$output->addHTML("<a href='$baselink'>Home</a>");
		}
		else
		{
			$this->LoadComputedStat($statId);

			if ($this->LoadComputedStat($statId) == False){
				return $this->reportError("Error: cannot load stat");
			}

			$version = $this->escapeHtml($this->computedStat['version']);
			$round = $this->escapeHtml($this->computedStat['roundNum']);
			$addClass = $this->escapeHtml($this->computedStat['addClass']);
			$comment = $this->escapeHtml($this->computedStat['comment']);
			$minimumValue = $this->escapeHtml($this->computedStat['minimumValue']);
			$maximumValue = $this->escapeHtml($this->computedStat['maximumValue']);
			$deferLevel = $this->escapeHtml($this->computedStat['deferLevel']);
			$display = $this->escapeHtml($this->computedStat['display']);
			$compute = $this->escapeHtml($this->computedStat['compute']);
			$idx = $this->escapeHtml($this->computedStat['idx']);
			$category = $this->escapeHtml($this->computedStat['category']);
			$suffix = $this->escapeHtml($this->computedStat['suffix']);
			$dependsOn = $this->escapeHtml($this->computedStat['dependsOn']);

			$cols = [];
			$values = [];
			$cols[] = 'statId';
			$cols[] = 'version';
			$cols[] = 'roundNum';
			$cols[] = 'addClass';
			$cols[] = 'comment';
			$cols[] = 'minimumValue';
			$cols[] = 'maximumValue';
			$cols[] = 'deferLevel';
			$cols[] = 'display';
			$cols[] = 'compute';
			$cols[] = 'idx';
			$cols[] = 'category';
			$cols[] = 'suffix';
			$cols[] = 'dependsOn';

			$values[] = "'" . $this->db->real_escape_string($statId) . "'";
			$values[] = "'" . $this->db->real_escape_string($version) . "'";
			$values[] = "'" . $this->db->real_escape_string($round) . "'";
			$values[] = "'" . $this->db->real_escape_string($addClass) . "'";
			$values[] = "'" . $this->db->real_escape_string($comment) . "'";
			$values[] = "'" . $this->db->real_escape_string($minimumValue) . "'";
			$values[] = "'" . $this->db->real_escape_string($maximumValue) . "'";
			$values[] = "'" . $this->db->real_escape_string($deferLevel) . "'";
			$values[] = "'" . $this->db->real_escape_string($display) . "'";
			$values[] = "'" . $this->db->real_escape_string($compute) . "'";
			$values[] = "'" . $this->db->real_escape_string($idx) . "'";
			$values[] = "'" . $this->db->real_escape_string($category) . "'";
			$values[] = "'" . $this->db->real_escape_string($suffix) . "'";
			$values[] = "'" . $this->db->real_escape_string($dependsOn) . "'";

			$this->InsertQueries('computedStatsArchives', $cols, $values);
			$this->DeleteQueries('computedStats', 'statId', $statId);

			$output->addHTML("<p>computed Stat deleted</p><br>");
			$output->addHTML("<a href='$baselink'>Home</a>");

		}

	}

//-------------------Main page---------------


	public function OutputTableOfContents()
	{
		$output = $this->getOutput();

		$baselink = $this->GetBaseLink();

		$output->addHTML("<ul>");
		$output->addHTML("<li><a href='$baselink/showrules'>Show Rules</a></li>");
		$output->addHTML("<li><a href='$baselink/addrule'>Add Rule</a></li>");
		$output->addHTML("<br>");
		$output->addHTML("<li><a href='$baselink/showcomputedstats'>Show Computed Stats</a></li>");
		$output->addHTML("<li><a href='$baselink/addcomputedstat'>Add Computed Stat</a></li>");
		$output->addHTML("<br>");
		$output->addHTML("<li><a href='$baselink/addversion'>Add Version</a></li>");
		$output->addHTML("</ul>");
	}


	function execute( $parameter )
	{
		$request = $this->getRequest();
		$output = $this->getOutput();
		$this->setHeaders();

			// TODO: Remove after testing
		/*if ($this->canUserEdit())
			$output->addHTML("Use can edit</br>");
		else
			$output->addHTML("Use CANNOT edit</br>");
		*/

			// TODO: Determine action/output based on the input $parameter

		if ($parameter == "showrules")
			$this->OutputShowRulesTable();
		elseif ($parameter == "addrule")
			$this->OutputAddRuleForm();
		elseif ($parameter == "editrule")
			$this->OutputEditRuleForm();
		elseif ($parameter == "saverule")
			$this->SaveNewRule();
		elseif ($parameter == "saveeditruleform")
			$this->SaveEditRuleForm();
		elseif ($parameter == "addneweffect")
			$this->OutpuAddtEffectForm();
		elseif ($parameter == "savenewffect")
			$this->SaveNewEffect();
		elseif ($parameter == "saveediteffectform")
			$this->SaveEditEffectForm();
		elseif($parameter == "editeffect")
			$this->OutputEditEffectForm();
		elseif($parameter == "showcomputedstats")
			$this->OutputShowComputedStatsTable();
		elseif($parameter == "addcomputedstat")
			$this->OutputAddComputedStatsForm();
		elseif($parameter == "savenewcomputedstat")
			$this->SaveNewComputedStat();
		elseif($parameter == "editcomputedstat")
		  $this->OutputEditComputedStatForm();
		elseif($parameter == "saveeditcomputedstatsform")
			$this->SaveEditComputedStatsForm();
		elseif($parameter == "deleterule")
			$this->OutputDeleteRule();
		elseif($parameter == "ruledeleteconfirm")
			$this->ConfirmDeleteRule();
		elseif($parameter == "deletcomputedstat")
			$this->OutputDeleteComputedStat();
		elseif($parameter == "statdeleteconfirm")
			$this->ConfirmDeleteStat();
		elseif($parameter == "deleteeffect")
			$this->OutputDeleteEffect();
		elseif($parameter == "effectdeleteconfirm")
			$this->ConfirmDeleteEffect();
		elseif($parameter == "addversion")
			$this->OutputAddVersionForm();
		elseif($parameter == "saveversion")
			$this->SaveNewVersion();
		else
			$this->OutputTableOfContents();
	}


	function getgroupName()
	{
		return 'wiki';
	}

};
