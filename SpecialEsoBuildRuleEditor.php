<?php

require_once ("/home/uesp/secrets/esobuilddata.secrets");

class SpecialEsoBuildRuleEditor extends SpecialPage {
	
	
	public $COMPUTED_STAT_CATEGORIES = array (
				"basic" => "Basic Stats",
				"elementresist" => "Elemental Resistances",
				"healing" => "Healing",
				"statrestore" => "Stat Restoration",
				"movement" => "Movement",
				"combat" => "Bash / Block / Dodge / Break Free / Fear",
				"damageshield" => "Damage Shield",
				"damagetaken" => "Damage Taken",
				"damagedone" => "Damage Done",
				"harestore" => "Heavy Attack Restoration",
				"statuseffect" => "Status Effects",
				"lightattack" => "Light Attacks",
				"heavyattack" => "Heavy Attacks",
				"mitigation" => "Mitigation",
				"abilitycost" => "Ability Costs",
				"trait" => "Traits",
				"other" => "Other" 
		);
	
	public $ROUND_OPTIONS = [ 
			'' => 'None',
			'floor' => 'Floor',
			'floor10' => 'Floor10',
			'floor2' => 'Floor2',
			'ceil' => 'Ceil' 
		];
	
	
	public $RULE_TYPE_OPTIONS = [ 
				'' => 'None',
				'buff' => 'BUFF',
				'mundus' => 'MUNDUS',
				'set' => 'SET',
				'active' => 'ACTIVE',
				'passive' => 'PASSIVE',
				'cp' => 'CP',
				'armorEnchant' => 'ARMOR ENCHANTMENT',
				'weaponEnchant' => 'WEAPON ENCHANTMENT',
				'offHandEnchant' => 'OFF-HAND ENCHANTMENT',
				'abilityDesc' => 'ABILITY DESCRIPTION' 
		];
	
	
	public $db = null;
	
	
	function __construct() {
		global $wgOut;
		global $uespIsMobile;
		
		parent::__construct ( 'EsoBuildRuleEditor' );
		
		$wgOut->addModules ( 'ext.EsoBuildData.ruleseditor.scripts' );
		$wgOut->addModuleStyles ( 'ext.EsoBuildData.ruleseditor.styles' );
		
		if ($uespIsMobile || (class_exists ( "MobileContext" ) && MobileContext::singleton ()->isMobileDevice ())) {
			// TODO: Add any mobile specific CSS/scripts resource modules here
		}
		
		$this->InitDatabase ();
	}
	
	
	public static function escapeHtml($html) {
		return htmlspecialchars ( $html );
	}
	
	
	public function canUserEdit() {
		$context = $this->getContext ();
		if ($context == null)
			return false;
		
		$user = $context->getUser ();
		if ($user == null)
			return false;
		
		if (! $user->isLoggedIn ())
			return false;
		
		return $user->isAllowedAny ( 'esochardata_ruleedit' );
	}
	
	
	protected function CreateTables() {
		
		$result = $this->db->query ( "CREATE TABLE IF NOT EXISTS rules (
			id INTEGER AUTO_INCREMENT NOT NULL,
			version TINYTEXT NOT NULL,
			ruleType TINYTEXT NOT NULL,
			nameId TINYTEXT,
			displayName TINYTEXT,
			matchRegex TINYTEXT NOT NULL,
			displayRegex TINYTEXT,
			statRequireId TINYTEXT,
			statRequireValue TINYTEXT,
			factorStatId TINYTEXT,
			isEnabled TINYINT(1) NOT NULL,
			isVisible TINYINT(1) NOT NULL,
			isToggle TINYINT(1) NOT NULL,
			enableOffBar TINYINT(1) NOT NULL,
			originalId TINYTEXT,
			icon TINYTEXT,
			groupName TINYTEXT,
			maxTimes INTEGER,
			comment TINYTEXT NOT NULL,
			description MEDIUMTEXT NOT NULL,
			customData MEDIUMTEXT NOT NULL,
			PRIMARY KEY (id),
			INDEX index_version(version(10)),
			INDEX index_ruleId(originalId(30)));" );
		
		if ($result === false) {
			return $this->reportError ( "Error: failed to create rules table" );
		}
		
		$effects_result = $this->db->query ( "CREATE TABLE IF NOT EXISTS effects (
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
			INDEX index_version(version(10)));" );
		
		if ($effects_result === false) {
			return $this->reportError ( "Error: failed to create effects table" );
		}
		
		$computedStats_result = $this->db->query ( "CREATE TABLE IF NOT EXISTS computedStats (
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
			INDEX index_version(version(10)));" );
		
		if ($computedStats_result === false) {
			return $this->reportError ( "Error: failed to create computed Stats table" );
		}
		
		$deleteRule_result = $this->db->query ( "CREATE TABLE IF NOT EXISTS rulesArchive (
			archiveId INTEGER AUTO_INCREMENT NOT NULL,
			id INTEGER NOT NULL,
			version TINYTEXT NOT NULL,
			ruleType TINYTEXT NOT NULL,
			nameId TINYTEXT,
			displayName TINYTEXT,
			matchRegex TINYTEXT NOT NULL,
			displayRegex TINYTEXT,
			statRequireId TINYTEXT,
			statRequireValue TINYTEXT,
			factorStatId TINYTEXT,
			isEnabled TINYINT(1) NOT NULL,
			isVisible TINYINT(1) NOT NULL,
			isToggle TINYINT(1) NOT NULL,
			enableOffBar TINYINT(1) NOT NULL,
			originalId TINYTEXT,
			icon TINYTEXT,
			groupName TINYTEXT,
			maxTimes INTEGER,
			comment TINYTEXT NOT NULL,
			description MEDIUMTEXT NOT NULL,
			customData MEDIUMTEXT NOT NULL,
			PRIMARY KEY (archiveId),
			INDEX index_version(version(10)),
			INDEX index_ruleId(originalId(30)) );" );
		
		if ($deleteRule_result === false) {
			return $this->reportError ( "Error: failed to create rules archive table" );
		}
		
		$deletedEffects_result = $this->db->query ( "CREATE TABLE IF NOT EXISTS effectsArchive (
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
			INDEX index_version(version(10)) );" );
		
		if ($deletedEffects_result === false) {
			return $this->reportError ( "Error: failed to create effects archive table" );
		}
		
		$DeletedcomputedStats_result = $this->db->query ( "CREATE TABLE IF NOT EXISTS computedStatsArchive (
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
			INDEX index_version(version(10)) ); " );
		
		if ($computedStats_result === false) {
			return $this->reportError ( "Error: failed to create computed Stats archive table" );
		}
		
		$versions_result = $this->db->query ( "CREATE TABLE IF NOT EXISTS versions (
			version TINYTEXT NOT NULL,
			PRIMARY KEY idx_version(version(16)) );" );
		
		if ($computedStats_result === false) {
			return $this->reportError ( "Error: failed to create versions table" );
		}
		
		return true;
	}
	
	
	public function InitDatabase() {
		global $uespEsoBuildDataWriteDBHost, $uespEsoBuildDataWriteUser, $uespEsoBuildDataWritePW, $uespEsoBuildDataDatabase;
		
		$this->db = new mysqli ( $uespEsoBuildDataWriteDBHost, $uespEsoBuildDataWriteUser, $uespEsoBuildDataWritePW, $uespEsoBuildDataDatabase );
		if ($this->db->connect_error) {
			return $this->reportError ( "Error: failed to initialize database" );
			;
		}
		$this->CreateTables ();
		return true;
	}
	
	
	// ------------------ Versions functions ---------------
	public function OutputAddVersionForm() {
		$permission = $this->canUserEdit ();
		
		if ($permission === False) {
			return $this->reportError ( "Error: you have no permission to add versions" );
		}
		
		$output = $this->getOutput ();
		$baselink = $this->GetBaseLink ();
		
		$output->addHTML ( "<h3>Adding New Version</h3>" );
		$output->addHTML ( "<form action='$baselink/saveversion' method='POST'>" );
		
		$output->addHTML ( "<label for='version'>Version: </label>" );
		$output->addHTML ( "<input type='text' id='version' name='version'>" );
		$output->addHTML ( "<p class='errorMsg'></p>" );
		
		$output->addHTML ( "<br><input type='submit' value='Save Version' class='submit_btn'>" );
		$output->addHTML ( "</form>" );
	}
	
	
	public function SaveNewVersion() {
		$permission = $this->canUserEdit ();
		
		if ($permission === False) {
			return $this->reportError ( "Error: you have no permission to add versions" );
		}
		
		$output = $this->getOutput ();
		$baselink = $this->GetBaseLink ();
		$req = $this->getRequest ();
		
		$input_version = $req->getVal ( 'version' );
		
		$this->LoadVersions ();
		
		foreach ( $this->versions as $version ) 
		{
			if ($input_version == $version['version']) 
			{
				$versionOption = $this->escapeHtml( $version['version'] );
				return $this->reportError ( "Error: version $input_version already exists" );
			}
		}
		
		$cols = [ ];
		$values = [ ];
		
		$cols [] = 'version';
		$values [] = "'" . $this->db->real_escape_string( $input_version ) . "'";
		
		$insertResult = $this->InsertQueries ( 'versions', $cols, $values );
		if (!$insertResult) return $this->reportError("Error: Failed to insert record into versions!");
		
		$output->addHTML ( "<p>New version added</p><br>" );
		$output->addHTML ( "<a href='$baselink'>Home</a>" );
	}
	
	
	public function LoadVersions() {
		$query = "SELECT version FROM versions;";
		$result = $this->db->query ( $query );
		
		if ($result === false) {
			return $this->reportError ( "Error: failed to load versions from database" );
		}
		
		$this->versions = [ ];
		
		while ( $row = mysqli_fetch_assoc ( $result ) ) {
			$this->versions [] = $row;
		}
		
		return true;
	}
	
	
	public function OutputVersionListHtml($param, $selectedVersion) {
		$output = $this->getOutput ();
		$this->LoadVersions ();
		
		$selected = "";
		
		$output->addHTML ( "<label for='$param'>version: </label>" );
		$output->addHTML ( "<select id='$param' name='$param'> " );
		
		foreach ( $this->versions as $version ) {
			
			$versionOption = $this->escapeHtml( $version ['version'] );
			
			if ($versionOption == $selectedVersion) {
				$selected = "selected";
			}
			
			if ($versionOption != "") {
				$output->addHTML ( "<option value='$versionOption' $selected >$versionOption</option>" );
			}
		}
		
		$output->addHTML ( "</select><br>" );
	}
	
	
	// -------------------Queries fucntions---------------
	public function InsertQueries($tableName, $cols, $values) {
		$cols = implode ( ',', $cols );
		$values = implode ( ',', $values );
		$query = "INSERT INTO $tableName($cols) VALUES($values);";
		
		$result = $this->db->query ( $query );
		
		if ($result === false) {
			return $this->reportError ( "Error: failed to INSERT data into database" );
		}
		
		return true;
	}
	
	
	public function DeleteQueries($tableName, $conditionName, $value) {
		$value = $this->db->real_escape_string( $value );
		$query = "DELETE FROM $tableName WHERE $conditionName='$value';";
		$result = $this->db->query ( $query );
		
		if ($result === false) {
			return $this->reportError ( "Error: failed to DELETE data from database" );
		}
		
		return true;
	}
	
	
	public function UpdateQueries($tableName, $values, $conditionName, $value) {
		$values = implode ( ',', $values );
		
		$query = "UPDATE $tableName SET $values WHERE $conditionName='$value';";
		
		$result = $this->db->query ( $query );
		
		if ($result === false) {
			return $this->reportError ( "Error: failed to UPDATE data in database" );
		}
		
		return true;
	}
	
	
	// -------------------Rules table functions---------------
	public function rounds($param, $round) {
		$output = $this->getOutput ();

		$output->addHTML ( "<label for='$param'>round </label>" );
		$this->OutputListHtml( $round, $this->ROUND_OPTIONS, $param );
	}
	
	
	public function LoadRules() {
		$query = "SELECT * FROM rules;";
		$result = $this->db->query ( $query );
		
		if ($result === false) {
			return $this->reportError ( "Error: failed to load rules from database" );
		}
		
		$this->rulesDatas = [ ];
		
		while ( $row = mysqli_fetch_assoc ( $result ) ) {
			$this->rulesDatas [] = $row;
		}
		
		return true;
	}
	
	
	public function OutputShowRulesTable() {
		$this->LoadRules ();
		
		$output = $this->getOutput ();
		$baselink = $this->GetBaseLink ();
		
		$output->addHTML ( "<a href='$baselink'>Home</a>" );
		$output->addHTML ( "<h3>Showing All Rules</h3>" );
		
		$output->addHTML ( "<table class='wikitable sortable jquery-tablesorter' id='rules'><thead>" );
		
		$output->addHTML ( "<tr>" );
		$output->addHTML ( "<th>Edit</th>" );
		$output->addHTML ( "<th>Id</th>" );
		$output->addHTML ( "<th>Rule Type</th>" );
		$output->addHTML ( "<th>Name ID</th>" );
		$output->addHTML ( "<th>Display Name</th>" );
		$output->addHTML ( "<th>Match Regex</th>" );
		$output->addHTML ( "<th>statRequireId</th>" );
		$output->addHTML ( "<th>Original Id</th>" );
		$output->addHTML ( "<th>Group Name</th>" );
		$output->addHTML ( "<th>Description</th>" );
		$output->addHTML ( "<th>Version</th>" );
		$output->addHTML ( "<th>Enabled</th>" );
		$output->addHTML ( "<th>Toggle</th>" );
		$output->addHTML ( "<th>Visible</th>" );
		$output->addHTML ( "<th>Enable Off Bar</th>" );
		$output->addHTML ( "<th>Stat Require Value</th>" );
		$output->addHTML ( "<th>Custom Data</th>" );
		$output->addHTML ( "<th>Delete</th>" );
		$output->addHTML ( "</tr></thead><tbody>" );
		
		foreach ( $this->rulesDatas as $rulesData ) {
			
			$id = $this->escapeHtml( $rulesData ['id'] );
			$ruleType = $this->escapeHtml( $rulesData ['ruleType'] );
			$nameId = $this->escapeHtml( $rulesData ['nameId'] );
			$displayName = $this->escapeHtml( $rulesData ['displayName'] );
			$matchRegex = $this->escapeHtml( $rulesData ['matchRegex'] );
			$statRequireId = $this->escapeHtml( $rulesData ['statRequireId'] );
			$originalId = $this->escapeHtml( $rulesData ['originalId'] );
			$groupName = $this->escapeHtml( $rulesData ['groupName'] );
			$description = $this->escapeHtml( $rulesData ['description'] );
			$version = $this->escapeHtml( $rulesData ['version'] );
			$isEnabled = $this->escapeHtml( $rulesData ['isEnabled'] );
			$toggle = $this->escapeHtml( $rulesData ['isToggle'] );
			$isVisible = $this->escapeHtml( $rulesData ['isVisible'] );
			$enableOffBar = $this->escapeHtml( $rulesData ['enableOffBar'] );
			$statRequireValue = $this->escapeHtml( $rulesData ['statRequireValue'] );
			
			$isEnabledDisplay = $this->GetBooleanDispaly ( $isEnabled );
			$toggleDisplay = $this->GetBooleanDispaly ( $toggle );
			$isVisibleDisplay = $this->GetBooleanDispaly ( $isVisible );
			$enableOffBarDisplay = $this->GetBooleanDispaly ( $enableOffBar );
			
			if ($rulesData ['customData'] == '') {
				$data = [ ];
			} else {
				$data = json_decode ( $rulesData['customData'], true );
				if ($data == null) $data = []; // TODO: Error handling?
				if (!is_array($data)) $data = ['Error: Not Array!', $rulesData['customData']];
			}
			
			$rulesData ['customData'] = $data;
			
			$output->addHTML ( "<tr>" );
			$output->addHTML ( "<td><a href='$baselink/editrule?ruleid=$id'>Edit</a></td>" );
			$output->addHTML ( "<td>$id</td>" );
			$output->addHTML ( "<td>$ruleType</td>" );
			$output->addHTML ( "<td>$nameId</td>" );
			$output->addHTML ( "<td>$displayName</td>" );
			$output->addHTML ( "<td>$matchRegex</td>" );
			$output->addHTML ( "<td>$statRequireId</td>" );
			$output->addHTML ( "<td>$originalId</td>" );
			$output->addHTML ( "<td>$groupName</td>" );
			$output->addHTML ( "<td>$description</td>" );
			$output->addHTML ( "<td>$version</td>" );
			$output->addHTML ( "<td>$isEnabledDisplay</td>" );
			$output->addHTML ( "<td>$toggleDisplay</td>" );
			$output->addHTML ( "<td>$isVisibleDisplay</td>" );
			$output->addHTML ( "<td>$enableOffBarDisplay</td>" );
			$output->addHTML ( "<td>$statRequireValue</td>" );
			
			$output->addHTML ( "<td>" );
			
			foreach ( $rulesData ['customData'] as $key => $val ) {
				$output->addHTML ( "$key = $val<br>" );
			}
			
			$output->addHTML ( "</td>" );
			
			$output->addHTML ( "<td><a href='$baselink/deleterule?ruleid=$id'>Delete</a></td>" );
			$output->addHTML ( "</tr>" );
		}
		
		$output->addHTML ( "</table>" );
	}
	
	
	public function OutputDeleteRule() {
		$permission = $this->canUserEdit ();
		
		if ($permission === False) {
			return $this->reportError ( "Error: you have no permission to delete rules" );
		}
		
		$output = $this->getOutput ();
		$baselink = $this->GetBaseLink ();
		
		$id = $this->GetRuleId();
		$id = $this->escapeHtml( $id );
		
		if ($id <= 0) {
			return $this->reportError ( "Error: invalid rule ID" );
		}
		
		$this->LoadRule ( $id );
		
		if ($this->LoadRule ( $id ) == False) {
			return $this->reportError ( "Error: cannot load Rule" );
		}
		
		$ruleType = $this->escapeHtml( $this->rule ['ruleType'] );
		$nameId = $this->escapeHtml( $this->rule ['nameId'] );
		$displayName = $this->escapeHtml( $this->rule ['displayName'] );
		$matchRegex = $this->escapeHtml( $this->rule ['matchRegex'] );
		$displayRegex = $this->escapeHtml( $this->rule ['displayRegex'] );
		$statRequireId = $this->escapeHtml( $this->rule ['statRequireId'] );
		$factorStatId = $this->escapeHtml( $this->rule ['factorStatId'] );
		$originalId = $this->escapeHtml( $this->rule ['originalId'] );
		$version = $this->escapeHtml( $this->rule ['version'] );
		$icon = $this->escapeHtml( $this->rule ['icon'] );
		$groupName = $this->escapeHtml( $this->rule ['groupName'] );
		$maxTimes = $this->escapeHtml( $this->rule ['maxTimes'] );
		$comment = $this->escapeHtml( $this->rule ['comment'] );
		$description = $this->escapeHtml( $this->rule ['description'] );
		$isEnabled = $this->escapeHtml( $this->rule ['isEnabled'] );
		$isVisible = $this->escapeHtml( $this->rule ['isVisible'] );
		$enableOffBar = $this->escapeHtml( $this->rule ['enableOffBar'] );
		$isToggle = $this->escapeHtml( $this->rule ['isToggle'] );
		$statRequireValue = $this->escapeHtml( $this->rule ['statRequireValue'] );
		
		if ($this->rule ['customData'] == '') {
			$data = [ ];
		} else {
			$data = json_decode ( $this->rule['customData'], true );
			if ($data == null) $data = [];	//TODO: Error handling?
			if (!is_array($data)) $data = ['Error: Not Array!', $this->rule['customData']];
		}
		
		$this->rule ['customData'] = $data;
		
		$output->addHTML ( "<h3>Are you sure you want to delete this rule: </h3>" );
		$output->addHTML ( "<label><b>id:</b> $id </label><br>" );
		$output->addHTML ( "<label><b>Rule Type:</b> $ruleType </label><br>" );
		$output->addHTML ( "<label><b>Name Id:</b> $nameId </label><br>" );
		$output->addHTML ( "<label><b>Display Name:</b> $displayName </label><br>" );
		$output->addHTML ( "<label><b>Match Regex:</b> $matchRegex </label><br>" );
		$output->addHTML ( "<label><b>Stat Require Id:</b> $statRequireId </label><br>" );
		$output->addHTML ( "<label><b>Factor Stat Id:</b> $factorStatId </label><br>" );
		$output->addHTML ( "<label><b>Original Id:</b> $originalId </label><br>" );
		$output->addHTML ( "<label><b>Version:</b> $version </label><br>" );
		$output->addHTML ( "<label><b>Icon:</b> $icon </label><br>" );
		$output->addHTML ( "<label><b>Group Name:</b> $groupName </label><br>" );
		$output->addHTML ( "<label><b>Max Times:</b> $maxTimes </label><br>" );
		$output->addHTML ( "<label><b>Comment:</b> $comment </label><br>" );
		$output->addHTML ( "<label><b>Description:</b> $description </label><br>" );
		$output->addHTML ( "<label><b>Enabled:</b> $isEnabled </label><br>" );
		$output->addHTML ( "<label><b>Visible:</b> $isVisible </label><br>" );
		$output->addHTML ( "<label><b>Enable Off Bar:</b> $enableOffBar </label><br>" );
		$output->addHTML ( "<label><b>Stat Require Value:</b> $statRequireValue </label><br>" );
		$output->addHTML ( "<label><b>Toggle:</b> $isToggle </label><br>" );
		
		$output->addHTML ( "<b>custom Data:</b><br>" );
		
		foreach ( $this->rule ['customData'] as $key => $val ) {
			$output->addHTML ( "<li class='costumeDataLi'>$key = $val</li>" );
		}
		
		$output->addHTML ( "<br><a href='$baselink/ruledeleteconfirm?ruleid=$id&confirm=True'>Delete </a>" );
		$output->addHTML ( "<a href='$baselink/ruledeleteconfirm?ruleid=$id&confirm=False'> Cancel</a>" );
	}
	
	
	public function ConfirmDeleteRule() {
		$permission = $this->canUserEdit ();
		
		if ($permission === False) {
			return $this->reportError ( "Error: you have no permission to delete rules" );
		}
		
		$output = $this->getOutput ();
		$baselink = $this->GetBaseLink ();
		$req = $this->getRequest ();
		
		$confirm = $req->getVal ( 'confirm' );
		$id = $this->GetRuleId();
		
		$id = $this->db->real_escape_string( $id );
		
		if ($id <= 0) {
			return $this->reportError ( "Error: invalid rule ID" );
		}
		
		if ($confirm !== 'True') {
			$output->addHTML ( "<p>Delete cancelled</p><br>" );
			$output->addHTML ( "<a href='$baselink'>Home</a>" );
		} else {
			if (! $this->LoadRule ( $id ))
				return $this->reportError ( "Error: cannot load Rule" );
			
			$ruleType = $this->rule ['ruleType'];
			$nameId = $this->rule ['nameId'];
			$displayName = $this->rule ['displayName'];
			$matchRegex = $this->rule ['matchRegex'];
			$displayRegex = $this->rule ['displayRegex'];
			$statRequireId = $this->rule ['statRequireId'];
			$factorStatId = $this->rule ['factorStatId'];
			$originalId = $this->rule ['originalId'];
			$version = $this->rule ['version'];
			$icon = $this->rule ['icon'];
			$groupName = $this->rule ['groupName'];
			$maxTimes = $this->rule ['maxTimes'];
			$comment = $this->rule ['comment'];
			$description = $this->rule ['description'];
			$isEnabled = $this->rule ['isEnabled'];
			$isVisible = $this->rule ['isVisible'];
			$enableOffBar = $this->rule ['enableOffBar'];
			$isToggle = $this->rule ['isToggle'];
			$statRequireValue = $this->rule ['statRequireValue'];
			$customData = $this->rule ['customData'];
			
			$cols = [ ];
			$values = [ ];
			
			$cols [] = 'id';
			$cols [] = 'ruleType';
			$cols [] = 'nameId';
			$cols [] = 'displayName';
			$cols [] = 'matchRegex';
			$cols [] = 'statRequireId';
			$cols [] = 'factorStatId';
			$cols [] = 'originalId';
			$cols [] = 'version';
			$cols [] = 'icon';
			$cols [] = 'groupName';
			$cols [] = 'maxTimes';
			$cols [] = 'comment';
			$cols [] = 'description';
			$cols [] = 'isEnabled';
			$cols [] = 'isVisible';
			$cols [] = 'enableOffBar';
			$cols [] = 'isToggle';
			$cols [] = 'statRequireValue';
			$cols [] = 'customData';
			
			$values [] = "'" . $this->db->real_escape_string( $id ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $ruleType ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $displayName ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $displayName ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $matchRegex ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $statRequireId ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $factorStatId ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $originalId ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $version ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $icon ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $groupName ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $maxTimes ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $comment ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $description ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $isEnabled ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $isVisible ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $enableOffBar ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $isToggle ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $statRequireValue ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $customData ) . "'";
			
			$insertResult = $this->InsertQueries ( 'rulesArchive', $cols, $values );
			if (!$insertResult) return $this->reportError("Error: Failed to insert record into rulesArchive!");
			
			$deleteResult = $this->DeleteQueries ( 'rules', 'id', $id );
			if (!$deleteResult) return $this->reportError("Error: Failed to delete record from rules!");
			
			$this->loadEffects ();
			
			foreach ( $this->effectsDatas as $effectsData ) {
				$effectId = $effectsData ['effectId'];
				$version = $effectsData ['version'];
				$statId = $effectsData ['statId'];
				$value = $effectsData ['value'];
				$display = $effectsData ['display'];
				$category = $effectsData ['category'];
				$combineAs = $effectsData ['combineAs'];
				$roundNum = $effectsData ['roundNum'];
				$factorValue = $effectsData ['factorValue'];
				$statDesc = $effectsData ['statDesc'];
				$buffId = $effectsData ['buffId'];
				
				$cols = [ ];
				$values = [ ];
				
				$cols [] = 'effectId';
				$cols [] = 'ruleId';
				$cols [] = 'version';
				$cols [] = 'statId';
				$cols [] = 'value';
				$cols [] = 'display';
				$cols [] = 'category';
				$cols [] = 'combineAs';
				$cols [] = 'roundNum';
				$cols [] = 'factorValue';
				$cols [] = 'statDesc';
				$cols [] = 'buffId';
				
				$values [] = "'" . $this->db->real_escape_string( $effectId ) . "'";
				$values [] = "'" . $this->db->real_escape_string( $id ) . "'";
				$values [] = "'" . $this->db->real_escape_string( $version ) . "'";
				$values [] = "'" . $this->db->real_escape_string( $statId ) . "'";
				$values [] = "'" . $this->db->real_escape_string( $value ) . "'";
				$values [] = "'" . $this->db->real_escape_string( $display ) . "'";
				$values [] = "'" . $this->db->real_escape_string( $category ) . "'";
				$values [] = "'" . $this->db->real_escape_string( $combineAs ) . "'";
				$values [] = "'" . $this->db->real_escape_string( $roundNum ) . "'";
				$values [] = "'" . $this->db->real_escape_string( $factorValue ) . "'";
				$values [] = "'" . $this->db->real_escape_string( $statDesc ) . "'";
				$values [] = "'" . $this->db->real_escape_string( $buffId ) . "'";
				
				$insertResult = $this->InsertQueries ( 'effectsArchive', $cols, $values );
				if (!$insertResult) return $this->reportError("Error: Failed to insert record into effectsArchive!");
			}
			
			$deleteResult = $this->DeleteQueries ( 'effects', 'ruleId', $id );
			if (!$deleteResult) return $this->reportError("Error: Failed to delete record from effects!");
			
			$output->addHTML ( "<p>Rule deleted</p><br>" );
			$output->addHTML ( "<a href='$baselink'>Home</a>" );
		}
	}
	
	
	public function LoadRule($primaryKey) {
		$primaryKey = $this->db->real_escape_string( $primaryKey );
		$query = "SELECT * FROM rules WHERE id= '$primaryKey';";
		$result = $this->db->query ( $query );
		
		if ($result === false) {
			return $this->reportError ( "Error: failed to load rule from database" );
		}
		
		$row = [ ];
		$row [] = $result->fetch_assoc ();
		$this->rule = $row [0];
		
		return true;
	}
	
	
	public function OutputEditRuleForm()
	{
		$permission = $this->canUserEdit ();
		
		if ($permission === False) {
			return $this->reportError ( "Error: you have no permission to edit rules" );
		}
		
		$output = $this->getOutput ();
		$baselink = $this->GetBaseLink ();
		
		$id = $this->GetRuleId();
		
		$this->LoadRule ( $id );
		
		$ruleType = $this->escapeHtml( $this->rule ['ruleType'] );
		$nameId = $this->escapeHtml( $this->rule ['nameId'] );
		$displayName = $this->escapeHtml( $this->rule ['displayName'] );
		$matchRegex = $this->escapeHtml( $this->rule ['matchRegex'] );
		$displayRegex = $this->escapeHtml( $this->rule ['displayRegex'] );
		$statRequireId = $this->escapeHtml( $this->rule ['statRequireId'] );
		$factorStatId = $this->escapeHtml( $this->rule ['factorStatId'] );
		$originalId = $this->escapeHtml( $this->rule ['originalId'] );
		$version = $this->escapeHtml( $this->rule ['version'] );
		$icon = $this->escapeHtml( $this->rule ['icon'] );
		$groupName = $this->escapeHtml( $this->rule ['groupName'] );
		$maxTimes = $this->escapeHtml( $this->rule ['maxTimes'] );
		$comment = $this->escapeHtml( $this->rule ['comment'] );
		$description = $this->escapeHtml( $this->rule ['description'] );
		$isEnabled = $this->escapeHtml( $this->rule ['isEnabled'] );
		$isVisible = $this->escapeHtml( $this->rule ['isVisible'] );
		$enableOffBar = $this->escapeHtml( $this->rule ['enableOffBar'] );
		$toggle = $this->escapeHtml( $this->rule ['isToggle'] );
		$statRequireValue = $this->escapeHtml( $this->rule ['statRequireValue'] );
		
		if ($this->rule ['customData'] == '') {
			$data = [ ];
		} else {
			$data = json_decode ( $this->rule['customData'], true );
			if ($data == null) $data = [];	//TODO: Error handling?
			if (!is_array($data)) $data = ['Error: Not Array!', $this->rule['customData']];
		}
		
		$this->rule ['customData'] = $data;
		
		$output->addHTML ( "<a href='$baselink/showrules'>Show Rules</a><br>" );
		$output->addHTML ( "<h3>Editing Rule #$id</h3>" );
		$output->addHTML ( "<form action='$baselink/saveeditruleform?ruleid=$id' method='POST'>" );
		
		$output->addHTML ( "<label for='ruleType'>Rule Type: </label>" );
		$this->OutputListHtml( $ruleType, $this->RULE_TYPE_OPTIONS, 'ruleType' );
		
		$output->addHTML ( "<label for='edit_nameId'>Name ID </label>" );
		$output->addHTML ( "<input type='text' id='edit_nameId' name='edit_nameId' value='$nameId' size='60'>" );
		$output->addHTML ( "<p class='errorMsg'></p>" );
		
		$output->addHTML ( "<label for='edit_displayName'>Display Name </label>" );
		$output->addHTML ( "<input type='text' id='edit_displayName' name='edit_displayName' value='$displayName' size='60'><br>" );
		
		$output->addHTML ( "<label for='edit_matchRegex'>Match Regex </label>" );
		$output->addHTML ( "<input type='text' id='edit_matchRegex' name='edit_matchRegex' value='$matchRegex' size='60'>" );
		$output->addHTML ( "<p class='errorMsg'></p>" );
		$output->addHTML ( "<p class='warningErr'></p>" );
		
		$output->addHTML ( "<label for='edit_displayRegex'>Display Regex </label>" );
		$output->addHTML ( "<input type='text' id='edit_displayRegex' name='edit_displayRegex' value='$displayRegex' size='60'>" );
		$output->addHTML ( "<p class='errorMsg'></p>" );
		
		$output->addHTML ( "<label for='edit_statRequireId'>Stat Require Id </label>" );
		$output->addHTML ( "<input type='text' id='edit_statRequireId' name='edit_statRequireId' value='$statRequireId'><br>" );
		$output->addHTML ( "<label for='edit_factorStatId'>Factor Stat Id </label>" );
		$output->addHTML ( "<input type='text' id='edit_factorStatId' name='edit_factorStatId' value='$factorStatId'><br>" );
		$output->addHTML ( "<label for='edit_originalId'>Original Id </label>" );
		$output->addHTML ( "<input type='text' id='edit_originalId' name='edit_originalId' value='$originalId'><br>" );
		$output->addHTML ( "<label for='edit_statRequireValue'>Stat Require Value </label>" );
		$output->addHTML ( "<input type='text' id='edit_statRequireValue' name='edit_statRequireValue' value='$statRequireValue'><br>" );
		
		$this->OutputVersionListHtml( 'edit_version', $version );
		
		$output->addHTML ( "<label for='edit_icon'>Icon </label>" );
		$output->addHTML ( "<input type='text' id='edit_icon' size='60' name='edit_icon' value='$icon'><br>" );
		$output->addHTML ( "<label for='edit_groupName'>Group Name </label>" );
		$output->addHTML ( "<input type='text' id='edit_groupName' name='edit_groupName' value='$groupName'><br>" );
		$output->addHTML ( "<label for='edit_maxTimes'>Maximum Times </label>" );
		$output->addHTML ( "<input type='text' id='edit_maxTimes' name='edit_maxTimes' value='$maxTimes'><br>" );
		$output->addHTML ( "<label for='edit_comment'>Comment </label>" );
		$output->addHTML ( "<input type='text' id='edit_comment' name='edit_comment' value='$comment' size='60'><br>" );
		
		$output->addHTML ( "<label for='edit_description'>Description </label>" );
		$output->addHTML ( "<textarea id='edit_description' name='edit_description' class='txtArea' rows='4' cols='50'>$description</textarea><br>" );
		
		$output->addHTML ( "<label for='edit_customData'>Custom Data </label><br />" );
		foreach ( $this->rule ['customData'] as $key => $val ) {
			$output->addHTML ( "<input type='text' id='edit_customName' name='edit_customName[]' class='custCol' value='$key'>   </input>" );
			$output->addHTML ( "<input type='text' id='edit_customValue' name='edit_customValue[]' value='$val'></input><br>" );
		}
		$output->addHTML ( "<input type='text' id='edit_customName' name='edit_customName[]' class='custCol'>   </input>" );
		$output->addHTML ( "<input type='text' id='edit_customValue' name='edit_customValue[]'></input><br>" );
		$output->addHTML ( "<input type='text' id='edit_customName' name='edit_customName[]' class='custCol'>   </input>" );
		$output->addHTML ( "<input type='text' id='edit_customValue' name='edit_customValue[]'></input><br>" );
		
		$isEnabledBoxCheck = $this->GetCheckboxState ( $isEnabled );
		$isVisibleBoxCheck = $this->GetCheckboxState ( $isVisible );
		$enableOffBarBoxCheck = $this->GetCheckboxState ( $enableOffBar );
		$isEnabledBoxCheck = $this->GetCheckboxState ( $isEnabled );
		$toggleBoxCheck = $this->GetCheckboxState ( $toggle );
		
		$output->addHTML ( "<br><label for='edit_isEnabled'>Enabled</label>" );
		$output->addHTML ( "<input $isEnabledBoxCheck type='checkbox' id='edit_isEnabled' name='edit_isEnabled' value='1'><br> " );
		$output->addHTML ( "<label for='edit_isVisible'>Visible</label>" );
		$output->addHTML ( "<input $isVisibleBoxCheck type='checkbox' id='edit_isVisible' name='edit_isVisible' value='1'><br>" );
		$output->addHTML ( "<label for='edit_enableOffBar'>Enable Off Bar</label>" );
		$output->addHTML ( "<input $enableOffBarBoxCheck type='checkbox' id='edit_enableOffBar' name='edit_enableOffBar' value='1'><br>" );
		$output->addHTML ( "<label for='edit_toggle'>Toggle</label>" );
		$output->addHTML ( "<input $toggleBoxCheck type='checkbox' id='edit_toggle' name='edit_toggle' value='1'><br>" );
		
		$output->addHTML ( "<br><input type='submit' value='Save Edits' class='submit_btn'>" );
		$output->addHTML ( "</form><br>" );
		
		$this->OutputShowEffectsTable ();
	}
	
	
	public function OutputAddRuleForm() {
		$permission = $this->canUserEdit ();
		
		if ($permission === False) {
			return $this->reportError ( "Error: you have no permission to add rules" );
		}
		$output = $this->getOutput ();
		
		$baselink = $this->GetBaseLink ();
		
		$output->addHTML ( "<h3>Adding New Rule</h3>" );
		$output->addHTML ( "<form action='$baselink/saverule' method='POST'>" );
		
		$output->addHTML ( "<label for='ruleType'>Rule Type: </label>" );
		$this->OutputListHtml( $ruleType, $this->RULE_TYPE_OPTIONS, 'ruleType' );
		
		$output->addHTML ( "<label for='nameId'>Name Id </label>" );
		$output->addHTML ( "<input type='text' id='nameId' name='nameId' size='60'>" );
		$output->addHTML ( "<p class='errorMsg'></p>" );
		
		$output->addHTML ( "<label for='displayName'>Display Name </label>" );
		$output->addHTML ( "<input type='text' id='displayName' name='displayname' size='60'><br>" );
		
		$output->addHTML ( "<label for='matchRegex'>Match Regex </label>" );
		$output->addHTML ( "<input type='text' id='matchRegex' name='matchRegex' size='60'>" );
		$output->addHTML ( "<p class='errorMsg'></p>" );
		$output->addHTML ( "<p class='warningErr'></p>" );
		
		$output->addHTML ( "<label for='displayRegex'>Display Regex </label>" );
		$output->addHTML ( "<input type='text' id='displayRegex' name='displayRegex' size='60'>" );
		$output->addHTML ( "<p class='errorMsg'></p>" );
		
		$output->addHTML ( "<label for='statRequireId'>Stat Require Id </label>" );
		$output->addHTML ( "<input type='text' id='statRequireId' name='statRequireId'><br>" );
		$output->addHTML ( "<label for='factorStatId'>Factor Stat Id </label>" );
		$output->addHTML ( "<input type='text' id='factorStatId' name='factorStatId'><br>" );
		$output->addHTML ( "<label for='originalId'>Original Id </label>" );
		$output->addHTML ( "<input type='text' id='originalId' name='originalId'><br>" );
		$output->addHTML ( "<label for='statRequireValue'>Stat Require Value </label>" );
		$output->addHTML ( "<input type='text' id='statRequireValue' name='statRequireValue'><br>" );
		
		$this->OutputVersionListHtml( 'version', '1' );
		
		$output->addHTML ( "<label for='icon'>Icon </label>" );
		$output->addHTML ( "<input type='text' id='icon' name='icon' size='60'><br>" );
		$output->addHTML ( "<label for='groupName'>Group Name </label>" );
		$output->addHTML ( "<input type='text' id='groupName' name='groupName'><br>" );
		$output->addHTML ( "<label for='maxTimes'>Maximum Times </label>" );
		$output->addHTML ( "<input type='text' id='maxTimes' name='maxTimes'><br>" );
		$output->addHTML ( "<label for='comment'>Comment </label>" );
		$output->addHTML ( "<input type='text' id='comment' name='comment' size='60'><br>" );
		$output->addHTML ( "<label for='description'>Description </label>" );
		$output->addHTML ( "<textarea id='description' name='description' class='txtArea' rows='4' cols='50'></textarea><br>" );
		
		$output->addHTML ( "<label for='customData'>Custom Data </label><br />" );
		$output->addHTML ( "<input type='text' id='customNames' name='customNames[]' class='custCol'></input>  " );
		$output->addHTML ( "<input type='text' id='customValues' name='customValues[]'></input><br>" );
		$output->addHTML ( "<input type='text' id='customNames' name='customNames[]'class='custCol'></input>  " );
		$output->addHTML ( "<input type='text' id='customValues' name='customValues[]'></input><br>" );
		$output->addHTML ( "<input type='text' id='customNames' name='customNames[]'class='custCol'></input>  " );
		$output->addHTML ( "<input type='text' id='customValues' name='customValues[]'></input><br>" );
		
		// could only be true or false (1 or 0)
		$output->addHTML ( "<br><label for='isEnabled'>Enabled</label>" );
		$output->addHTML ( "<input type='checkbox' id='isEnabled' name='isEnabled' value='1'><br>" );
		$output->addHTML ( "<label for='isVisible'>Visible</label>" );
		$output->addHTML ( "<input type='checkbox' id='isVisible' name='isVisible' value='1' checked><br>" );
		$output->addHTML ( "<label for='enableOffBar'>Enable Off Bar</label>" );
		$output->addHTML ( "<input type='checkbox' id='enableOffBar' name='enableOffBar' value='1'><br>" );
		$output->addHTML ( "<label for='toggle'>Toggle</label>" );
		$output->addHTML ( "<input type='checkbox' id='toggle' name='toggle' value='1'><br>" );
		
		$output->addHTML ( "<br><input type='submit' value='Save Rule' class='submit_btn'>" );
		$output->addHTML ( "</form>" );
	}
	
	
	public function GetCheckboxState($boolValue)
	{
		if ($boolValue === '1') return "checked";
		return "";
	}
	
	
	public function OutputListHtml($option, $array, $listName)
	{
		$output = $this->getOutput ();
		
		$output->addHTML ( "<select id='$listName' name='$listName'>" );
		
		foreach ( $array as $key => $value ) 
		{
			$selected = "";
			if ($key === $option) {
				$selected = "selected";
			}
			$output->addHTML ( "<option value='$key' $selected >$value</option>" );
		}
		$output->addHTML ( "</select><br>" );
	}
	
	
	public function GetBooleanDispaly($boolValue)
	{
		if ($boolValue === '1') return "Yes";
		return "";
	}
	
	
	public function ReportError($msg)
	{
		$output = $this->getOutput ();
		
		$output->addHTML ( $msg . "<br/>" );
		$output->addHTML ( $this->db->error );	//TODO: Only output if present?
		
		error_log ( $msg );
		
		return false;
	}
	
	
	public function SaveNewRule() {
		$permission = $this->canUserEdit ();
		
		if ($permission === False) {
			return $this->reportError ( "Error: you have no permission to add rules" );
		}
		
		$output = $this->getOutput ();
		$baselink = $this->GetBaseLink ();
		$req = $this->getRequest ();
		
		$input_ruleType = $req->getVal ( 'ruleType' );
		$input_nameId = $req->getVal ( 'nameId' );
		$input_displayName = $req->getVal ( 'displayName' );
		$input_matchRegex = $req->getVal ( 'matchRegex' );
		$input_statRequireId = $req->getVal ( 'statRequireId' );
		$input_factorStatId = $req->getVal ( 'factorStatId' );
		$input_originalId = $req->getVal ( 'originalId' );
		$input_version = $req->getVal ( 'version' );
		$input_icon = $req->getVal ( 'icon' );
		$input_groupName = $req->getVal ( 'groupName' );
		$input_maxTimes = $req->getVal ( 'maxTimes' );
		$input_comment = $req->getVal ( 'comment' );
		$input_description = $req->getVal ( 'description' );
		$input_isEnabled = $req->getVal ( 'isEnabled' );
		$input_isVisible = $req->getVal ( 'isVisible' );
		$input_enableOffBar = $req->getVal ( 'enableOffBar' );
		$input_toggle = $req->getVal ( 'toggle' );
		$input_statRequireValue = $req->getVal ( 'statRequireValue' );
		
		$customNames = $req->getArray ( 'customNames' );
		$customValues = $req->getArray ( 'customValues' );
		$input_customData = [ ];
		
		foreach ( $customNames as $i => $name ) {
			$name = trim ( $name );
			$value = $customValues [$i];
			
			if ($name == '')
				continue;
			if ($value === undefined)
				continue;
			
			$input_customData [$name] = $value;
		}
		
		$input_customData = json_encode ( $input_customData );
		
		$cols = [ ];
		$values = [ ];
		$cols [] = 'ruleType';
		$cols [] = 'nameId';
		$cols [] = 'displayName';
		$cols [] = 'matchRegex';
		$cols [] = 'statRequireId';
		$cols [] = 'factorStatId';
		$cols [] = 'originalId';
		$cols [] = 'version';
		$cols [] = 'icon';
		$cols [] = 'groupName';
		$cols [] = 'maxTimes';
		$cols [] = 'comment';
		$cols [] = 'description';
		$cols [] = 'isEnabled';
		$cols [] = 'isVisible';
		$cols [] = 'enableOffBar';
		$cols [] = 'isToggle';
		$cols [] = 'statRequireValue';
		$cols [] = 'customData';
		
		$values [] = "'" . $this->db->real_escape_string( $input_ruleType ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_nameId ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_displayName ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_matchRegex ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_statRequireId ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_factorStatId ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_originalId ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_version ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_icon ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_groupName ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_maxTimes ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_comment ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_description ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_isEnabled ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_isVisible ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_enableOffBar ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_toggle ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_statRequireValue ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_customData ) . "'";
		
		$insertResult = $this->InsertQueries ( 'rules', $cols, $values );
		$lastId = $this->db->insert_id;
		
		if ($insertResult) 
			header ( "Location: $baselink/editrule?ruleid=$lastId" );
		else
			$this->reportError("Error: Failed to insert record into rules!");
		
		return $insertResult;
	}
	
	
	public function SaveEditRuleForm() {
		$permission = $this->canUserEdit ();
		
		if ($permission === False) {
			return $this->reportError ( "Error: you have no permission to edit rules" );
		}
		
		$output = $this->getOutput ();
		$baselink = $this->GetBaseLink ();
		$req = $this->getRequest ();
		
		$id = $this->GetRuleId();
		$id = $this->db->real_escape_string( $id );
		
		if ($id <= 0) {
			return $this->reportError ( "Error: invalid rule ID" );
		}
		
		$new_ruleType = $req->getVal ( 'ruleType' );
		$new_nameId = $req->getVal ( 'edit_nameId' );
		$new_displayName = $req->getVal ( 'edit_displayName' );
		$new_matchRegex = $req->getVal ( 'edit_matchRegex' );
		$new_statRequireId = $req->getVal ( 'edit_statRequireId' );
		$new_factorStatId = $req->getVal ( 'edit_factorStatId' );
		$new_originalId = $req->getVal ( 'edit_originalId' );
		$new_version = $req->getVal ( 'edit_version' );
		$new_icon = $req->getVal ( 'edit_icon' );
		$new_groupName = $req->getVal ( 'edit_groupName' );
		$new_maxTimes = $req->getVal ( 'edit_maxTimes' );
		$new_comment = $req->getVal ( 'edit_comment' );
		$new_description = $req->getVal ( 'edit_description' );
		$new_isEnabled = $req->getVal ( 'edit_isEnabled' );
		$new_isVisible = $req->getVal ( 'edit_isVisible' );
		$new_enableOffBar = $req->getVal ( 'edit_enableOffBar' );
		$new_toggle = $req->getVal ( 'edit_toggle' );
		$new_statRequireValue = $req->getVal ( 'edit_statRequireValue' );
		
		$customNames = $req->getArray ( 'edit_customName' );
		$customValues = $req->getArray ( 'edit_customValue' );
		$new_customData = [ ];
		
		foreach ( $customNames as $i => $name ) {
			$name = trim ( $name );
			$value = $customValues [$i];
			
			if ($name == '')
				continue;
			if ($value === undefined)
				continue;
			
			$new_customData [$name] = $value;
		}
		$new_customData = json_encode ( $new_customData );
		
		$values = [ ];
		
		$values [] = "ruleType='" . $this->db->real_escape_string( $new_ruleType ) . "'";
		$values [] = "nameId='" . $this->db->real_escape_string( $new_nameId ) . "'";
		$values [] = "displayName='" . $this->db->real_escape_string( $new_displayName ) . "'";
		$values [] = "matchRegex='" . $this->db->real_escape_string( $new_matchRegex ) . "'";
		$values [] = "statRequireId='" . $this->db->real_escape_string( $new_statRequireId ) . "'";
		$values [] = "factorStatId='" . $this->db->real_escape_string( $new_factorStatId ) . "'";
		$values [] = "originalId='" . $this->db->real_escape_string( $new_originalId ) . "'";
		$values [] = "version='" . $this->db->real_escape_string( $new_version ) . "'";
		$values [] = "icon='" . $this->db->real_escape_string( $new_icon ) . "'";
		$values [] = "groupName='" . $this->db->real_escape_string( $new_groupName ) . "'";
		$values [] = "maxTimes='" . $this->db->real_escape_string( $new_maxTimes ) . "'";
		$values [] = "comment='" . $this->db->real_escape_string( $new_comment ) . "'";
		$values [] = "description='" . $this->db->real_escape_string( $new_description ) . "'";
		$values [] = "isEnabled='" . $this->db->real_escape_string( $new_isEnabled ) . "'";
		$values [] = "isVisible='" . $this->db->real_escape_string( $new_isVisible ) . "'";
		$values [] = "enableOffBar='" . $this->db->real_escape_string( $new_enableOffBar ) . "'";
		$values [] = "isToggle='" . $this->db->real_escape_string( $new_toggle ) . "'";
		$values [] = "statRequireValue='" . $this->db->real_escape_string( $new_statRequireValue ) . "'";
		$values [] = "customData='" . $this->db->real_escape_string( $new_customData ) . "'";
		
		$updateResult = $this->UpdateQueries ( 'rules', $values, 'id', $id );
		if (!$updateResult) return $this->reportError("Error: Failed to update record in rules!");
		
		$output->addHTML ( "<p>Edits saved for rule #$id</p><br>" );
		$output->addHTML ( "<a href='$baselink'>Home</a>" );
	}
	
	
	public function GetRuleId() {
		$req = $this->getRequest ();
		$ruleId = $req->getVal ( 'ruleid' );
		
		return $ruleId;
	}
	
	
	public static function GetBaseLink() {
		$link = "https://dev.uesp.net/wiki/Special:EsoBuildRuleEditor";
		
		return ($link);
	}
	
	
	// -------------------Effects table functions---------------
	public function loadEffects() {
		$id = $this->GetRuleId();
		$id = $this->db->real_escape_string( $id );
		$query = "SELECT * FROM effects where ruleId =$id;";
		$effects_result = $this->db->query ( $query );
		
		if ($effects_result === false) {
			return $this->reportError ( "Error: failed to load effects from database" );
		}
		
		$this->effectsDatas = [ ];
		
		while ( $row = mysqli_fetch_assoc ( $effects_result ) ) {
			$this->effectsDatas [] = $row;
		}
		
		return true;
	}
	
	
	public function OutputShowEffectsTable() {
		$output = $this->getOutput ();
		$baselink = $this->GetBaseLink ();
		$this->loadEffects ();
		$req = $this->getRequest ();
		
		$id = $this->GetRuleId();
		$effectId = $req->getVal ( 'effectid' );
		
		$output->addHTML ( "<hr><h3>All Rule Effects</h3>" );
		$output->addHTML ( "<a href='$baselink/addneweffect?ruleid=$id'>Add new effect</a>" );
		
		$output->addHTML ( "<table class='wikitable sortable jquery-tablesorter' id='effects'><thead>" );
		$output->addHTML ( "<tr>" );
		$output->addHTML ( "<th>Edit</th>" );
		$output->addHTML ( "<th>Id</th>" );
		$output->addHTML ( "<th>version</th>" );
		$output->addHTML ( "<th>statId</th>" );
		$output->addHTML ( "<th>value</th>" );
		$output->addHTML ( "<th>display</th>" );
		$output->addHTML ( "<th>category</th>" );
		$output->addHTML ( "<th>combineAs</th>" );
		$output->addHTML ( "<th>round</th>" );
		$output->addHTML ( "<th>factorValue</th>" );
		$output->addHTML ( "<th>statDesc</th>" );
		$output->addHTML ( "<th>buffId</th>" );
		$output->addHTML ( "<th>regexVar</th>" );
		$output->addHTML ( "<th>Delete</th>" );
		$output->addHTML ( "</tr></thead><tbody>" );
		
		foreach ( $this->effectsDatas as $effectsData ) {
			
			$effectId = $this->escapeHtml( $effectsData ['effectId'] );
			$version = $this->escapeHtml( $effectsData ['version'] );
			$statId = $this->escapeHtml( $effectsData ['statId'] );
			$value = $this->escapeHtml( $effectsData ['value'] );
			$display = $this->escapeHtml( $effectsData ['display'] );
			$category = $this->escapeHtml( $effectsData ['category'] );
			$combineAs = $this->escapeHtml( $effectsData ['combineAs'] );
			$round = $this->escapeHtml( $effectsData ['roundNum'] );
			$factorValue = $this->escapeHtml( $effectsData ['factorValue'] );
			$statDesc = $this->escapeHtml( $effectsData ['statDesc'] );
			$buffId = $this->escapeHtml( $effectsData ['buffId'] );
			$regexVar = $this->escapeHtml( $effectsData ['regexVar'] );
			
			$output->addHTML ( "<tr>" );
			$output->addHTML ( "<td><a href='$baselink/editeffect?effectid=$effectId&ruleid=$id'>Edit</a></td>" );
			$output->addHTML ( "<td>$effectId</td>" );
			$output->addHTML ( "<td>$version</td>" );
			$output->addHTML ( "<td>$statId</td>" );
			$output->addHTML ( "<td>$value</td>" );
			$output->addHTML ( "<td>$display</td>" );
			$output->addHTML ( "<td>$category</td>" );
			$output->addHTML ( "<td>$combineAs</td>" );
			$output->addHTML ( "<td>$round</td>" );
			$output->addHTML ( "<td>$factorValue</td>" );
			$output->addHTML ( "<td>$statDesc</td>" );
			$output->addHTML ( "<td>$buffId</td>" );
			$output->addHTML ( "<td>$regexVar</td>" );
			$output->addHTML ( "<td><a href='$baselink/deleteeffect?effectid=$effectId&ruleid=$id' >Delete</a></td>" );
		}
		
		$output->addHTML ( "</table>" );
		$jsonEffects = json_encode ( $this->effectsDatas );
		$output->addHTML ( "<script>window.g_RuleEffectData = $jsonEffects;</script>" );
	}
	
	
	public function OutputDeleteEffect() {
		$permission = $this->canUserEdit ();
		
		if ($permission === False) {
			return $this->reportError ( "Error: you have no permission to delete effects" );
		}
		
		$output = $this->getOutput ();
		$baselink = $this->GetBaseLink ();
		$this->loadEffects ();
		$req = $this->getRequest ();
		
		$id = $this->GetRuleId();
		$effectId = $req->getVal ( 'effectid' );
		
		$this->loadEffect ( $effectId );
		
		if ($this->loadEffect ( $effectId ) == False) {
			return $this->reportError ( "Error: cannot load effect" );
		}
		
		$version = $this->escapeHtml( $this->effect ['version'] );
		$statId = $this->escapeHtml( $this->effect ['statId'] );
		$value = $this->escapeHtml( $this->effect ['value'] );
		$display = $this->escapeHtml( $this->effect ['display'] );
		$category = $this->escapeHtml( $this->effect ['category'] );
		$combineAs = $this->escapeHtml( $this->effect ['combineAs'] );
		$round = $this->escapeHtml( $this->effect ['roundNum'] );
		$factorValue = $this->escapeHtml( $this->effect ['factorValue'] );
		$statDesc = $this->escapeHtml( $this->effect ['statDesc'] );
		$buffId = $this->escapeHtml( $this->effect ['buffId'] );
		$regexVar = $this->escapeHtml( $this->effect ['regexVar'] );
		
		$output->addHTML ( "<h3>Are you sure you want to delete this effect: </h3>" );
		$output->addHTML ( "<label><b>Id</b> $effectId </label><br>" );
		$output->addHTML ( "<label><b>Version</b> $version </label><br>" );
		$output->addHTML ( "<label><b>Stat Id</b> $statId </label><br>" );
		$output->addHTML ( "<label><b>Value</b> $value </label><br>" );
		$output->addHTML ( "<label><b>Display</b> $display </label><br>" );
		$output->addHTML ( "<label><b>Category</b> $category </label><br>" );
		$output->addHTML ( "<label><b>Combine As</b> $combineAs </label><br>" );
		$output->addHTML ( "<label><b>Round</b> $round </label><br>" );
		$output->addHTML ( "<label><b>Factor Value</b> $factorValue </label><br>" );
		$output->addHTML ( "<label><b>Stat Desc</b> $statDesc </label><br>" );
		$output->addHTML ( "<label><b>Buff Id</b> $buffId </label><br>" );
		$output->addHTML ( "<label><b>Regex Var</b> $regexVar </label><br>" );
		
		$output->addHTML ( "<br><a href='$baselink/effectdeleteconfirm?ruleid=$id&effectid=$effectId&confirm=True'>Delete </a>" );
		$output->addHTML ( "<a href='$baselink/effectdeleteconfirm?effectid=$effectId&confirm=False'> Cancel</a>" );
	}
	
	
	public function ConfirmDeleteEffect() {
		$permission = $this->canUserEdit ();
		
		if ($permission === False) {
			return $this->reportError ( "Error: you have no permission to delete effects" );
		}
		
		$output = $this->getOutput ();
		$baselink = $this->GetBaseLink ();
		$req = $this->getRequest ();
		
		$confirm = $req->getVal ( 'confirm' );
		$effectId = $req->getVal ( 'effectid' );
		
		$effectId = $this->db->real_escape_string( $effectId );
		$id = $this->GetRuleId();
		
		if ($effectId <= 0) {
			return $this->reportError ( "Error: invalid stat ID" );
		}
		
		if ($confirm !== 'True') {
			$output->addHTML ( "<p>Delete cancelled</p><br>" );
			$output->addHTML ( "<a href='$baselink'>Home</a>" );
		} else {
			$this->loadEffect ( $effectId );
			
			if ($this->loadEffect ( $effectId ) == False) {
				return $this->reportError ( "Error: cannot load effect" );
			}
			
			$version = $this->escapeHtml( $this->effect ['version'] );
			$statId = $this->escapeHtml( $this->effect ['statId'] );
			$value = $this->escapeHtml( $this->effect ['value'] );
			$display = $this->escapeHtml( $this->effect ['display'] );
			$category = $this->escapeHtml( $this->effect ['category'] );
			$combineAs = $this->escapeHtml( $this->effect ['combineAs'] );
			$round = $this->escapeHtml( $this->effect ['roundNum'] );
			$factorValue = $this->escapeHtml( $this->effect ['factorValue'] );
			$statDesc = $this->escapeHtml( $this->effect ['statDesc'] );
			$buffId = $this->escapeHtml( $this->effect ['buffId'] );
			$regexVar = $this->escapeHtml( $this->effect ['regexVar'] );
			
			$cols = [ ];
			$values = [ ];
			$cols [] = 'ruleId';
			$cols [] = 'version';
			$cols [] = 'statId';
			$cols [] = 'value';
			$cols [] = 'display';
			$cols [] = 'category';
			$cols [] = 'combineAs';
			$cols [] = 'roundNum';
			$cols [] = 'factorValue';
			$cols [] = 'statDesc';
			$cols [] = 'buffId';
			$cols [] = 'regexVar';
			
			$values [] = "'" . $this->db->real_escape_string( $id ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $version ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $statId ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $value ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $display ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $category ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $combineAs ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $round ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $factorValue ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $statDesc ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $buffId ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $regexVar ) . "'";
			
			$insertResult = $this->InsertQueries ( 'effectsArchive', $cols, $values );
			if (!$insertResult) return $this->reportError("Error: Failed to insert record into effectsArchive!");
			
			$deleteResult = $this->DeleteQueries ( 'effects', 'effectId', $effectId );
			if (!$deleteResult) return $this->reportError("Error: Failed to delete record from effects!");
			
			$output->addHTML ( "<p>Effect deleted</p><br>" );
			$output->addHTML ( "<a href='$baselink'>Home : </a>" );
			$output->addHTML ( "<a href='$baselink/editrule?ruleid=$id'>Rule #$id</a>" );
		}
	}
	
	
	public function SaveNewEffect() {
		$permission = $this->canUserEdit ();
		
		if ($permission === False) {
			return $this->reportError ( "Error: you have no permission to add effects" );
		}
		
		$output = $this->getOutput ();
		$baselink = $this->GetBaseLink ();
		$req = $this->getRequest ();
		
		$id = $this->GetRuleId();
		$input_version = $req->getVal ( 'version' );
		$input_statId = $req->getVal ( 'statId' );
		$input_value = $req->getVal ( 'value' );
		$input_display = $req->getVal ( 'display' );
		$input_category = $req->getVal ( 'category' );
		$input_combineAs = $req->getVal ( 'combineAs' );
		$input_round = $req->getVal ( 'round' );
		$input_factorValue = $req->getVal ( 'factorValue' );
		$input_statDesc = $req->getVal ( 'statDesc' );
		$input_buffId = $req->getVal ( 'buffId' );
		$input_regexVar = $req->getVal ( 'regexVar' );
		
		$cols = [ ];
		$values = [ ];
		$cols [] = 'ruleId';
		$cols [] = 'version';
		$cols [] = 'statId';
		$cols [] = 'value';
		$cols [] = 'display';
		$cols [] = 'category';
		$cols [] = 'combineAs';
		$cols [] = 'roundNum';
		$cols [] = 'factorValue';
		$cols [] = 'statDesc';
		$cols [] = 'buffId';
		$cols [] = 'regexVar';
		
		$values [] = "'" . $this->db->real_escape_string( $id ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_version ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_statId ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_value ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_display ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_category ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_combineAs ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_round ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_factorValue ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_statDesc ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_buffId ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_regexVar ) . "'";
		
		$insertResult = $this->InsertQueries ( 'effects', $cols, $values );
		if (!$insertResult) return $this->reportError("Error: Failed to insert record into effects!");
		
		$output->addHTML ( "<p>New effect added</p><br>" );
		$output->addHTML ( "<a href='$baselink'>Home : </a>" );
		$output->addHTML ( "<a href='$baselink/editrule?ruleid=$id'>Rule #$id</a>" );
	}
	
	
	public function OutputAddtEffectForm() {
		$permission = $this->canUserEdit ();
		
		if ($permission === False) {
			return $this->reportError ( "Error: you have no permission to add effects" );
		}
		
		$output = $this->getOutput ();
		$baselink = $this->GetBaseLink ();
		$id = $this->GetRuleId();
		
		$output->addHTML ( "<a href='$baselink/editrule?ruleid=$id'>Rule #$id</a>" );
		$output->addHTML ( "<h3>Adding New Effect For Rule #$id</h3>" );
		$output->addHTML ( "<form action='$baselink/savenewffect?ruleid=$id' method='POST'>" );
		
		$this->OutputVersionListHtml( 'version', '1' );
		
		$this->laodStatIds ();
		$output->addHTML ( "<label for='statId'>Stat Id </label>" );
		$output->addHTML ( "<input list='statIds' id='statId' name='statId'>" );
		$output->addHTML ( "<datalist id='statIds' name='statId'>" );
		foreach ( $this->ids as $id ) {
			$statIdVal = $this->escapeHtml( $id ['statId'] );
			$output->addHTML ( "<option value='$statIdVal'>$statIdVal</option>" );
		}
		$output->addHTML ( "</datalist><br />" );
		
		$output->addHTML ( "<label for='value'>Value </label>" );
		$output->addHTML ( "<input type='text' id='value' name='value'><br>" );
		$output->addHTML ( "<label for='display'>Display </label>" );
		$output->addHTML ( "<input type='text' id='display' name='display'><br>" );
		$output->addHTML ( "<label for='category'>Category </label>" );
		$output->addHTML ( "<input type='text' id='category' name='category'><br>" );
		$output->addHTML ( "<label for='combineAs'>Combine As </label>" );
		$output->addHTML ( "<input type='text' id='combineAs' name='combineAs'><br>" );
		
		$this->rounds ( 'round', '' );
		
		$output->addHTML ( "<label for='factorValue'>Factor Value </label>" );
		$output->addHTML ( "<input type='text' id='factorValue' name='factorValue'><br>" );
		$output->addHTML ( "<label for='statDesc'>Stat Desc </label>" );
		$output->addHTML ( "<input type='text' id='statDesc' name='statDesc'><br>" );
		
		$this->loadBuffIds ();
		$output->addHTML ( "<label for='buffId'>Buff Id </label>" );
		$output->addHTML ( "<select name='buffId' id='buffId'>" );
		foreach ( $this->buffIds as $buffIdVal ) {
			$optionVal = $this->escapeHtml( $buffIdVal ['nameId'] );
			if ($optionVal != '') {
				$output->addHTML ( "<option value='$optionVal'>$optionVal</option>" );
			}
		}
		$output->addHTML ( "</select><br />" );
		
		$output->addHTML ( "<label for='regexVar'>Regex Var </label>" );
		$output->addHTML ( "<input type='text' id='regexVar' name='regexVar' size='60'>" );
		$output->addHTML ( "<p class='errorMsg'></p>" );
		
		$output->addHTML ( "<br><input type='submit' value='Save Effect' class='submit_btn'>" );
		
		$output->addHTML ( "</form>" );
	}
	
	
	public function loadEffect($effectId) {
		$effectId = $this->db->real_escape_string( $effectId );
		$query = "SELECT * FROM effects WHERE effectId = '$effectId';";
		$effects_result = $this->db->query ( $query );
		
		if ($effects_result === false) {
			return $this->reportError ( "Error: failed to load effect from database" );
		}
		
		$row = [ ];
		$row [] = $effects_result->fetch_assoc ();
		$this->effect = $row [0];
		
		return true;
	}
	
	
	public function loadBuffIds() {
		$query = "SELECT nameId FROM rules where ruleType='buff';";
		$result = $this->db->query ( $query );
		
		if ($result === false) {
			return $this->reportError ( "Error: failed to load buffs from database" );
		}
		
		$this->buffIds = [ ];
		
		while ( $data = mysqli_fetch_assoc ( $result ) ) {
			$this->buffIds [] = $data;
		}
		
		return true;
	}
	
	
	public function OutputEditEffectForm() {
		$permission = $this->canUserEdit ();
		
		if ($permission === False) {
			return $this->reportError ( "Error: you have no permission to edit effects" );
		}
		
		$output = $this->getOutput ();
		$baselink = $this->GetBaseLink ();
		$req = $this->getRequest ();
		
		$effectId = $req->getVal ( 'effectid' );
		$ruleId = $this->GetRuleId();
		
		$this->loadEffect ( $effectId );
		
		$version = $this->escapeHtml( $this->effect ['version'] );
		$statId = $this->escapeHtml( $this->effect ['statId'] );
		$value = $this->escapeHtml( $this->effect ['value'] );
		$display = $this->escapeHtml( $this->effect ['display'] );
		$category = $this->escapeHtml( $this->effect ['category'] );
		$combineAs = $this->escapeHtml( $this->effect ['combineAs'] );
		$round = $this->escapeHtml( $this->effect ['roundNum'] );
		$factorValue = $this->escapeHtml( $this->effect ['factorValue'] );
		$statDesc = $this->escapeHtml( $this->effect ['statDesc'] );
		$buffId = $this->escapeHtml( $this->effect ['buffId'] );
		$regexVar = $this->escapeHtml( $this->effect ['regexVar'] );
		
		$output->addHTML ( "<a href='$baselink/showrules'>Show Rules : </a>" );
		$output->addHTML ( "<a href='$baselink/editrule?ruleid=$ruleId'>Rule #$ruleId</a><br>" );
		$output->addHTML ( "<h3>Editing Effect #$effectId for Rule #$ruleId</h3>" );
		$output->addHTML ( "<form action='$baselink/saveediteffectform?effectid=$effectId&ruleid=$ruleId' method='POST'>" );
		
		$this->OutputVersionListHtml( 'edit_version', $version );
		
		$this->laodStatIds ();
		$output->addHTML ( "<label for='edit_statId'>Stat Id </label>" );
		$output->addHTML ( "<input list='edit_statIds' id='edit_statId' name='edit_statId' value='$statId'>" );
		$output->addHTML ( "<datalist id='edit_statIds' name='edit_statId'>" );
		foreach ( $this->ids as $id ) {
			$statIdVal = $this->escapeHtml( $id ['statId'] );
			$output->addHTML ( "<option value='$statIdVal'>$statIdVa</option>" );
		}
		$output->addHTML ( "</datalist><br />" );
		
		$output->addHTML ( "<label for='edit_value'>Value </label>" );
		$output->addHTML ( "<input type='text' id='edit_value' name='edit_value' value='$value'><br>" );
		$output->addHTML ( "<label for='edit_display'>Display </label>" );
		$output->addHTML ( "<input type='text' id='edit_display' name='edit_display' value='$display'><br>" );
		$output->addHTML ( "<label for='edit_category'>Category </label>" );
		$output->addHTML ( "<input type='text' id='edit_category' name='edit_category' value='$category'><br>" );
		$output->addHTML ( "<label for='edit_combineAs'>CombineAs </label>" );
		$output->addHTML ( "<input type='text' id='edit_combineAs' name='edit_combineAs' value='$combineAs'><br>" );
		
		$this->rounds ( 'edit_round', $round );
		
		$output->addHTML ( "<label for='edit_factorValue'>Factor Value </label>" );
		$output->addHTML ( "<input type='text' id='edit_factorValue' name='edit_factorValue' value='$factorValue'><br>" );
		$output->addHTML ( "<label for='edit_statDesc'>Stat Desc </label>" );
		$output->addHTML ( "<input type='text' id='edit_statDesc' name='edit_statDesc' value='$statDesc'><br>" );
		
		$this->loadBuffIds ();
		$output->addHTML ( "<label for='edit_buffId'>Buff Id </label>" );
		$output->addHTML ( "<select name='edit_buffId' id='edit_buffId'>" );
		foreach ( $this->buffIds as $buffIdVal ) {
			$optionVal = $this->escapeHtml( $buffIdVal ['nameId'] );
			if ($optionVal != '') {
				$output->addHTML ( "<option value='$optionVal'>$optionVal</option>" );
			}
		}
		$output->addHTML ( "</select><br />" );
		
		$output->addHTML ( "<label for='edit_regexVar'>Regex Var </label>" );
		$output->addHTML ( "<input type='text' id='edit_regexVar' name='edit_regexVar' value='$regexVar' size='60'>" );
		$output->addHTML ( "<p class='errorMsg'></p>" );
		
		$output->addHTML ( "<br><input type='submit' value='Save Edits' class='submit_btn'>" );
		$output->addHTML ( "</form><br>" );
	}
	
	
	public function SaveEditEffectForm() {
		$permission = $this->canUserEdit ();
		
		if ($permission === False) {
			return $this->reportError ( "Error: you have no permission to edit effects" );
		}
		
		$output = $this->getOutput ();
		$baselink = $this->GetBaseLink ();
		$req = $this->getRequest ();
		
		$ruleId = $this->GetRuleId();
		$effectId = $req->getVal ( 'effectid' );
		
		$effectId = $this->db->real_escape_string( $effectId );
		
		if ($effectId <= 0) {
			return $this->reportError ( "Error: invalid effect ID" );
		}
		
		$new_version = $req->getVal ( 'edit_version' );
		$new_statId = $req->getVal ( 'edit_statId' );
		$new_value = $req->getVal ( 'edit_value' );
		$new_display = $req->getVal ( 'edit_display' );
		$new_category = $req->getVal ( 'edit_category' );
		$new_combineAs = $req->getVal ( 'edit_combineAs' );
		$new_round = $req->getVal ( 'edit_round' );
		$new_factorValue = $req->getVal ( 'edit_factorValue' );
		$new_statDesc = $req->getVal ( 'edit_statDesc' );
		$new_buffId = $req->getVal ( 'edit_buffId' );
		$new_regexVar = $req->getVal ( 'edit_regexVar' );
		
		$values = [ ];
		
		$values [] = "version='" . $this->db->real_escape_string( $new_version ) . "'";
		$values [] = "statId='" . $this->db->real_escape_string( $new_statId ) . "'";
		$values [] = "value='" . $this->db->real_escape_string( $new_value ) . "'";
		$values [] = "display='" . $this->db->real_escape_string( $new_display ) . "'";
		$values [] = "category='" . $this->db->real_escape_string( $new_category ) . "'";
		$values [] = "combineAs='" . $this->db->real_escape_string( $new_combineAs ) . "'";
		$values [] = "roundNum='" . $this->db->real_escape_string( $new_round ) . "'";
		$values [] = "factorValue='" . $this->db->real_escape_string( $new_factorValue ) . "'";
		$values [] = "statDesc='" . $this->db->real_escape_string( $new_statDesc ) . "'";
		$values [] = "buffId='" . $this->db->real_escape_string( $new_buffId ) . "'";
		$values [] = "regexVar='" . $this->db->real_escape_string( $new_regexVar ) . "'";
		
		$updateResult = $this->UpdateQueries ( 'effects', $values, 'effectId', $effectId );
		if (!$updateResult) return $this->reportError("Error: Failed to update record in effects!");
		
		$output->addHTML ( "<p>Edits saved for effect #$effectId</p><br>" );
		$output->addHTML ( "<a href='$baselink/editrule?ruleid=$ruleId'>Rule #$ruleId</a><br>" );
	}
	
	
	public function GetEffectId() {
		$req = $this->getRequest ();
		$effectId = $req->getVal ( 'effectid' );
		return $effectId;
	}
	
	
	// -------------------computedStats functions---------------
	public function loadComputedStats() {
		$query = "SELECT * FROM computedStats;";
		$computedStats_result = $this->db->query ( $query );
		
		if ($computedStats_result === false) {
			return $this->reportError ( "Error: failed to load computed Stats from database" );
		}
		
		$this->computedStatsDatas = [ ];
		
		while ( $row = mysqli_fetch_assoc ( $computedStats_result ) ) {
			$this->computedStatsDatas [] = $row;
		}
		
		return true;
	}
	
	
	public function OutputShowComputedStatsTable() {
		$this->loadComputedStats ();
		
		$output = $this->getOutput ();
		$baselink = $this->GetBaseLink ();
		
		$output->addHTML ( "<a href='$baselink'>Home</a>" );
		$output->addHTML ( "<h3>Showing All Computed Stats</h3>" );
		
		$output->addHTML ( "<table class='wikitable sortable jquery-tablesorter' id='computedStats'><thead>" );
		
		$output->addHTML ( "<tr>" );
		$output->addHTML ( "<th>Edit</th>" );
		$output->addHTML ( "<th>Stat Id</th>" );
		$output->addHTML ( "<th>Version</th>" );
		$output->addHTML ( "<th>Round</th>" );
		$output->addHTML ( "<th>Class</th>" );
		$output->addHTML ( "<th>Comment</th>" );
		$output->addHTML ( "<th>Min Value</th>" );
		$output->addHTML ( "<th>Max Value</th>" );
		$output->addHTML ( "<th>Defer Level</th>" );
		$output->addHTML ( "<th>Display</th>" );
		$output->addHTML ( "<th>Compute</th>" );
		$output->addHTML ( "<th>Idx</th>" );
		$output->addHTML ( "<th>Category</th>" );
		$output->addHTML ( "<th>Suffix</th>" );
		$output->addHTML ( "<th>Depends On</th>" );
		$output->addHTML ( "<th>Delete</th>" );
		$output->addHTML ( "</tr></thead><tbody>" );
		
		foreach ( $this->computedStatsDatas as $computedStatsData ) {
			
			$statId = $this->escapeHtml( $computedStatsData ['statId'] );
			$version = $this->escapeHtml( $computedStatsData ['version'] );
			$roundNum = $this->escapeHtml( $computedStatsData ['roundNum'] );
			$addClass = $this->escapeHtml( $computedStatsData ['addClass'] );
			$comment = $this->escapeHtml( $computedStatsData ['comment'] );
			$minimumValue = $this->escapeHtml( $computedStatsData ['minimumValue'] );
			$maximumValue = $this->escapeHtml( $computedStatsData ['maximumValue'] );
			$deferLevel = $this->escapeHtml( $computedStatsData ['deferLevel'] );
			$display = $this->escapeHtml( $computedStatsData ['display'] );
			$idx = $this->escapeHtml( $computedStatsData ['idx'] );
			$category = $this->escapeHtml( $computedStatsData ['category'] );
			$suffix = $this->escapeHtml( $computedStatsData ['suffix'] );
			
			if ($computedStatsData['compute'] == '') {
				$data = [ ];
			} else {
				$data = json_decode ( $computedStatsData['compute'], true );
				if ($data == null) $data = [];	//TODO: Error handling?
				if (!is_array($data)) $data = ['Error: Not Array!', $computedStatsData['compute']];
			}
			
			$computedStatsData['compute'] = $data;
			
			if ($computedStatsData['dependsOn'] == '') {
				$datas = [ ];
			} else {
				$datas = json_decode ( $computedStatsData['dependsOn'], true );
				if ($datas == null) $datas = [];	//TODO: Error handling?
				if (!is_array($datas)) $datas = ['Error: Not Array!', $computedStatsData['dependsOn']];
			}
			
			$computedStatsData['dependsOn'] = $datas;
			
			$output->addHTML ( "<tr>" );
			$output->addHTML ( "<td><a href='$baselink/editcomputedstat?statid=$statId'>Edit</a></td>" );
			$output->addHTML ( "<td>$statId</td>" );
			$output->addHTML ( "<td>$version</td>" );
			$output->addHTML ( "<td>$roundNum</td>" );
			$output->addHTML ( "<td>$addClass</td>" );
			$output->addHTML ( "<td>$comment</td>" );
			$output->addHTML ( "<td>$minimumValue</td>" );
			$output->addHTML ( "<td>$maximumValue</td>" );
			$output->addHTML ( "<td>$deferLevel</td>" );
			$output->addHTML ( "<td>$display</td>" );
			
			$output->addHTML ( "<td>" );
			
			foreach ( $computedStatsData['compute'] as $key => $val ) {
				$output->addHTML ( "$val <br />" );
			}
			
			$output->addHTML ( "</td>" );
			
			$output->addHTML ( "<td>$idx</td>" );
			$output->addHTML ( "<td>$category</td>" );
			$output->addHTML ( "<td>$suffix</td>" );
			
			$output->addHTML ( "<td>" );
			
			foreach ( $computedStatsData['dependsOn'] as $key => $val ) {
				$output->addHTML ( "$val <br />" );
			}
			
			$output->addHTML ( "</td>" );
			
			$output->addHTML ( "<td><a href='$baselink/deletcomputedstat?statid=$statId'>Delete</a></td>" );
		}
		
		$output->addHTML ( "</table>" );
	}
	
	
	public function OutputAddComputedStatsForm() {
		$permission = $this->canUserEdit ();
		
		if ($permission === False) {
			return $this->reportError ( "Error: you have no permission to add computed stats" );
		}
		$output = $this->getOutput ();
		
		$baselink = $this->GetBaseLink ();
		
		$output->addHTML ( "<h3>Adding New Computed Stat</h3>" );
		$output->addHTML ( "<form action='$baselink/savenewcomputedstat' method='POST'>" );
		
		$output->addHTML ( "<label for='statId'>Stat Id </label>" );
		$output->addHTML ( "<input type='text' id='statId' name='statId'><br>" );
		
		$this->OutputVersionListHtml( 'version', '1' );
		$this->rounds ( 'round', '' );
		
		$output->addHTML ( "<label for='addClass'>Class </label>" );
		$output->addHTML ( "<input type='text' id='addClass' name='addClass'><br>" );
		$output->addHTML ( "<label for='comment'>Comment </label>" );
		$output->addHTML ( "<input type='text' id='comment' name='comment'><br>" );
		$output->addHTML ( "<label for='minimumValue'>Min Value </label>" );
		$output->addHTML ( "<input type='number' id='minimumValue' name='minimumValue'><br>" );
		$output->addHTML ( "<label for='maximumValue'>Max Value </label>" );
		$output->addHTML ( "<input type='number' id='maximumValue' name='maximumValue'><br>" );
		$output->addHTML ( "<label for='deferLevel'>Defer Level </label>" );
		$output->addHTML ( "<input type='text' id='deferLevel' name='deferLevel'><br>" );
		$output->addHTML ( "<label for='display'>Display </label>" );
		$output->addHTML ( "<input type='text' id='display' name='display'><br>" );
		
		$output->addHTML ( "<label for='compute'>Compute </label>" );
		$output->addHTML ( "<textarea id='compute' name='compute' class='txtArea' rows='15' cols='50'></textarea><br>" );
		
		$output->addHTML ( "<label for='idx'>Idx </label>" );
		$output->addHTML ( "<input type='text' id='idx' name='idx'><br>" );
		
		$output->addHTML ( "<label for='category'>Category </label>" );
		$this->OutputListHtml( '', $this->COMPUTED_STAT_CATEGORIES, 'category' );
		
		$output->addHTML ( "<label for='suffix'>Suffix </label>" );
		$output->addHTML ( "<input type='text' id='suffix' name='suffix'><br>" );
		$output->addHTML ( "<label for='dependsOn'>Depends On </label>" );
		$output->addHTML ( "<textarea id='dependsOn' name='dependsOn' class='txtArea' rows='4' cols='50'></textarea><br>" );
		
		$output->addHTML ( "<br><input type='submit' value='Save computed Stat'>" );
		$output->addHTML ( "</form>" );
	}
	
	
	public function laodStatIds() {
		$query = "SELECT statId FROM computedStats;";
		$result = $this->db->query ( $query );
		
		if ($result === false) {
			return $this->reportError ( "Error: failed to load stat IDs from database" );
		}
		
		$this->ids = [ ];
		
		while ( $row = mysqli_fetch_assoc ( $result ) ) {
			$this->ids [] = $row;
		}
		
		return true;
	}
	
	
	public function SaveNewComputedStat() {
		$permission = $this->canUserEdit ();
		
		if ($permission === False) {
			return $this->reportError ( "Error: you have no permission to add computed stats" );
		}
		
		$output = $this->getOutput ();
		$baselink = $this->GetBaseLink ();
		$req = $this->getRequest ();
		
		$input_statId = $req->getVal ( 'statId' );
		
		$this->laodStatIds ();
		
		foreach ( $this->ids as $id ) {
			$usedId = $this->escapeHtml( $id ['statId'] );
			
			if ($input_statId === $usedId) {
				return $this->reportError ( "Error: statId '$input_statId' is already used" );
			}
		}
		
		$input_version = $req->getVal ( 'version' );
		$input_roundNum = $req->getVal ( 'round' );
		$input_addClass = $req->getVal ( 'addClass' );
		$input_comment = $req->getVal ( 'comment' );
		$input_minimumValue = $req->getVal ( 'minimumValue' );
		$input_maximumValue = $req->getVal ( 'maximumValue' );
		$input_deferLevel = $req->getVal ( 'deferLevel' );
		$input_display = $req->getVal ( 'display' );
		
		$compute = $req->getVal ( 'compute' );
		$compute_strings = explode ( "\n", $compute );
		$trimedStrings = array_map ( 'trim', $compute_strings );
		$input_compute = json_encode ( $trimedStrings );
		
		$input_idx = $req->getVal ( 'idx' );
		$input_category = $req->getVal ( 'category' );
		$input_suffix = $req->getVal ( 'suffix' );
		
		$dependsOn = $req->getVal ( 'dependsOn' );
		$dependsOn_strings = explode ( "\n", $dependsOn );
		$input_dependsOn = json_encode ( $dependsOn_strings );
		
		$cols = [ ];
		$values = [ ];
		$cols [] = 'statId';
		$cols [] = 'version';
		$cols [] = 'roundNum';
		$cols [] = 'addClass';
		$cols [] = 'comment';
		$cols [] = 'minimumValue';
		$cols [] = 'maximumValue';
		$cols [] = 'deferLevel';
		$cols [] = 'display';
		$cols [] = 'compute';
		$cols [] = 'idx';
		$cols [] = 'category';
		$cols [] = 'suffix';
		$cols [] = 'dependsOn';
		
		$values [] = "'" . $this->db->real_escape_string( $input_statId ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_version ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_roundNum ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_addClass ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_comment ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_minimumValue ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_maximumValue ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_deferLevel ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_display ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_compute ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_idx ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_category ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_suffix ) . "'";
		$values [] = "'" . $this->db->real_escape_string( $input_dependsOn ) . "'";
		
		$insertResult = $this->InsertQueries ( 'computedStats', $cols, $values );
		if (!$insertResult) return $this->reportError("Error: Failed to insert record into computedStats!");
		
		$output->addHTML ( "<p>New computed Stat added</p><br>" );
		$output->addHTML ( "<a href='$baselink'>Home</a>" );
	}
	
	
	public function LoadComputedStat($primaryKey) {
		$primaryKey = $this->db->real_escape_string( $primaryKey );
		$query = "SELECT * FROM computedStats WHERE statId= '$primaryKey';";
		$computedStats_result = $this->db->query ( $query );
		
		if ($computedStats_result === false) {
			return $this->reportError ( "Error: failed to load computed Stat from database" );
		}
		
		$row = [ ];
		$row [] = $computedStats_result->fetch_assoc ();
		$this->computedStat = $row [0];
		
		return true;
	}
	
	
	public function OutputEditComputedStatForm() {
		$permission = $this->canUserEdit ();
		
		if ($permission === False) {
			return $this->reportError ( "Error: you have no permission to edit computed stats" );
		}
		
		$output = $this->getOutput ();
		$baselink = $this->GetBaseLink ();
		$req = $this->getRequest ();
		
		$statId = $req->getVal ( 'statid' );
		
		$this->LoadComputedStat ( $statId );
		
		$version = $this->escapeHtml( $this->computedStat ['version'] );
		$round = $this->escapeHtml( $this->computedStat ['roundNum'] );
		$addClass = $this->escapeHtml( $this->computedStat ['addClass'] );
		$comment = $this->escapeHtml( $this->computedStat ['comment'] );
		$minimumValue = $this->escapeHtml( $this->computedStat ['minimumValue'] );
		$maximumValue = $this->escapeHtml( $this->computedStat ['maximumValue'] );
		$deferLevel = $this->escapeHtml( $this->computedStat ['deferLevel'] );
		$display = $this->escapeHtml( $this->computedStat ['display'] );
		$idx = $this->escapeHtml( $this->computedStat ['idx'] );
		$category = $this->escapeHtml( $this->computedStat ['category'] );
		$suffix = $this->escapeHtml( $this->computedStat ['suffix'] );
		
		if ($this->computedStat ['compute'] == '') {
			$data = [ ];
		} else {
			$data = json_decode ( $this->computedStat['compute'], true );
			if ($data == null) $data = [];	//TODO: Error handling?
			if (!is_array($data)) $data = ['Error: Not Array!', $this->computedStat['compute']];
		}
		
		$this->computedStat['compute'] = $data;
		
		if ($this->computedStat ['dependsOn'] == '') {
			$data = [ ];
		} else {
			$data = json_decode ( $this->computedStat['dependsOn'], true );
			if ($data == null) $data = [];	//TODO: Error handling?
			if (!is_array($data)) $data = ['Error: Not Array!', $computedStatsData['dependsOn']];
		}
		
		$this->computedStat ['dependsOn'] = $data;
		
		$output->addHTML ( "<a href='$baselink/showcomputedstats'>Show Computed Stats</a><br>" );
		$output->addHTML ( "<h3>Editing Computed Stat $statId</h3>" );
		$output->addHTML ( "<form action='$baselink/saveeditcomputedstatsform?statid=$statId' method='POST'>" );
		
		$this->OutputVersionListHtml( 'edit_version', $version );
		$roundOptions = $this->rounds ( 'edit_round', $round );
		
		$output->addHTML ( "<label for='edit_addClass'>Class </label>" );
		$output->addHTML ( "<input type='text' id='edit_addClass' name='edit_addClass' value='$addClass'><br>" );
		$output->addHTML ( "<label for='edit_comment'>Comment </label>" );
		$output->addHTML ( "<input type='text' id='edit_comment' name='edit_comment' value='$comment'><br>" );
		$output->addHTML ( "<label for='edit_minimumValue'>Min Value </label>" );
		$output->addHTML ( "<input type='text' id='edit_minimumValue' name='edit_minimumValue' value='$minimumValue'><br>" );
		$output->addHTML ( "<label for='edit_maximumValue'>Max Value </label>" );
		$output->addHTML ( "<input type='text' id='edit_maximumValue' name='edit_maximumValue' value='$maximumValue'><br>" );
		$output->addHTML ( "<label for='edit_deferLevel'>Defer Level </label>" );
		$output->addHTML ( "<input type='text' id='edit_deferLevel' name='edit_deferLevel' value='$deferLevel'><br>" );
		$output->addHTML ( "<label for='edit_display'>Display </label>" );
		$output->addHTML ( "<input type='text' id='edit_display' name='edit_display' value='$display'><br>" );
		
		$output->addHTML ( "<label for='edit_compute'>Compute </label>" );
		$output->addHTML ( "<textarea id='edit_compute' name='edit_compute' class='txtArea' rows='15' cols='50'>" );
		
		foreach ( $this->computedStat ['compute'] as $key => $val ) {
			$output->addHTML ( "$val \n" );
		}
		$output->addHTML ( "</textarea><br>" );
		
		$output->addHTML ( "<label for='edit_idx'>Idx </label>" );
		$output->addHTML ( "<input type='text' id='edit_idx' name='edit_idx' value='$idx'><br>" );
		
		$output->addHTML ( "<label for='edit_category'>Category </label>" );
		$this->OutputListHtml( $category, $this->COMPUTED_STAT_CATEGORIES, 'edit_category' );
		
		$output->addHTML ( "<label for='edit_suffix'>Suffix </label>" );
		$output->addHTML ( "<input type='text' id='edit_suffix' name='edit_suffix' value='$suffix'><br>" );
		
		$output->addHTML ( "<label for='edit_dependsOn'>Depends On </label>" );
		$output->addHTML ( "<textarea id='edit_dependsOn' name='edit_dependsOn' class='txtArea' rows='4' cols='50'>" );
		
		foreach ( $this->computedStat ['dependsOn'] as $key => $val ) {
			$output->addHTML ( "$val \n" );
		}
		$output->addHTML ( "</textarea><br>" );
		
		$output->addHTML ( "<br><input class='btn' type='submit' value='Save Edits'>" );
		$output->addHTML ( "</form><br>" );
	}
	
	
	public function SaveEditComputedStatsForm() {
		$permission = $this->canUserEdit ();
		
		if ($permission === False) {
			return $this->reportError ( "Error: you have no permission to edit computed stats" );
		}
		
		$output = $this->getOutput ();
		$baselink = $this->GetBaseLink ();
		$req = $this->getRequest ();
		
		$statId = $req->getVal ( 'statid' );
		$statId = $this->db->real_escape_string( $statId );
		
		$new_version = $req->getVal ( 'edit_version' );
		$new_roundNum = $req->getVal ( 'edit_round' );
		$new_addClass = $req->getVal ( 'edit_addClass' );
		$new_comment = $req->getVal ( 'edit_comment' );
		$new_minimumValue = $req->getVal ( 'edit_minimumValue' );
		$new_maximumValue = $req->getVal ( 'edit_maximumValue' );
		$new_deferLevel = $req->getVal ( 'edit_deferLevel' );
		$new_display = $req->getVal ( 'edit_display' );
		
		$compute = $req->getVal ( 'edit_compute' );
		$compute_strings = explode ( "\n", $compute );
		$trimedStrings = array_map ( 'trim', $compute_strings );
		$new_compute = json_encode ( $trimedStrings );
		
		$new_idx = $req->getVal ( 'edit_idx' );
		$new_category = $req->getVal ( 'edit_category' );
		$new_suffix = $req->getVal ( 'edit_suffix' );
		
		$dependsOn = $req->getVal ( 'edit_dependsOn' );
		$dependsOn_strings = explode ( "\n", $dependsOn );
		$new_dependsOn = json_encode ( $dependsOn_strings );
		
		$values = [ ];
		
		$values [] = "version='" . $this->db->real_escape_string( $new_version ) . "'";
		$values [] = "roundNum='" . $this->db->real_escape_string( $new_roundNum ) . "'";
		$values [] = "addClass='" . $this->db->real_escape_string( $new_addClass ) . "'";
		$values [] = "comment='" . $this->db->real_escape_string( $new_comment ) . "'";
		$values [] = "minimumValue='" . $this->db->real_escape_string( $new_minimumValue ) . "'";
		$values [] = "maximumValue='" . $this->db->real_escape_string( $new_maximumValue ) . "'";
		$values [] = "deferLevel='" . $this->db->real_escape_string( $new_deferLevel ) . "'";
		$values [] = "display='" . $this->db->real_escape_string( $new_display ) . "'";
		$values [] = "compute='" . $this->db->real_escape_string( $new_compute ) . "'";
		$values [] = "idx='" . $this->db->real_escape_string( $new_idx ) . "'";
		$values [] = "category='" . $this->db->real_escape_string( $new_category ) . "'";
		$values [] = "suffix='" . $this->db->real_escape_string( $new_suffix ) . "'";
		$values [] = "dependsOn='" . $this->db->real_escape_string( $new_dependsOn ) . "'";
		
		$updateResult = $this->UpdateQueries ( 'computedStats', $values, 'statId', $statId );
		if (!$updateResult) return $this->reportError("Error: Failed to update record in computedStats!");
		
		$output->addHTML ( "<p>Edits saved for computed Stat #$statId</p><br>" );
		$output->addHTML ( "<a href='$baselink'>Home</a>" );
	}
	
	
	public function OutputDeleteComputedStat() {
		$permission = $this->canUserEdit ();
		
		if ($permission === False) {
			return $this->reportError ( "Error: you have no permission to delete computed stats" );
		}
		
		$output = $this->getOutput ();
		$baselink = $this->GetBaseLink ();
		$req = $this->getRequest ();
		
		$statId = $req->getVal ( 'statid' );
		$statId = $this->escapeHtml( $statId );
		
		$this->LoadComputedStat ( $statId );
		
		if ($this->LoadComputedStat ( $statId ) == False) {
			return $this->reportError ( "Error: cannot load stat" );
		}
		
		$version = $this->escapeHtml( $this->computedStat ['version'] );
		$roundNum = $this->escapeHtml( $this->computedStat ['roundNum'] );
		$addClass = $this->escapeHtml( $this->computedStat ['addClass'] );
		$comment = $this->escapeHtml( $this->computedStat ['comment'] );
		$minimumValue = $this->escapeHtml( $this->computedStat ['minimumValue'] );
		$maximumValue = $this->escapeHtml( $this->computedStat ['maximumValue'] );
		$deferLevel = $this->escapeHtml( $this->computedStat ['deferLevel'] );
		$display = $this->escapeHtml( $this->computedStat ['display'] );
		$compute = $this->escapeHtml( $this->computedStat ['compute'] );
		$idx = $this->escapeHtml( $this->computedStat ['idx'] );
		$category = $this->escapeHtml( $this->computedStat ['category'] );
		$suffix = $this->escapeHtml( $this->computedStat ['suffix'] );
		$dependsOn = $this->escapeHtml( $this->computedStat ['dependsOn'] );
		
		$output->addHTML ( "<h3>Are you sure you want to delete this computed Stat: </h3>" );
		$output->addHTML ( "<label><b>Id:</b> $statId </label><br>" );
		$output->addHTML ( "<label><b>Version:</b> $version </label><br>" );
		$output->addHTML ( "<label><b>Round:</b> $roundNum </label><br>" );
		$output->addHTML ( "<label><b>Class:</b> $addClass </label><br>" );
		$output->addHTML ( "<label><b>Comment:</b> $comment </label><br>" );
		$output->addHTML ( "<label><b>Min Value:</b> $minimumValue </label><br>" );
		$output->addHTML ( "<label><b>Max Value:</b> $maximumValue </label><br>" );
		$output->addHTML ( "<label><b>Defer Level:</b> $deferLevel </label><br>" );
		$output->addHTML ( "<label><b>Display:</b> $display </label><br>" );
		$output->addHTML ( "<label><b>Compute:</b> $compute </label><br>" );
		$output->addHTML ( "<label><b>Idx:</b> $idx </label><br>" );
		$output->addHTML ( "<label><b>Category:</b> $category </label><br>" );
		$output->addHTML ( "<label><b>Suffix:</b> $suffix </label><br>" );
		$output->addHTML ( "<label><b>Depends On:</b> $dependsOn </label><br>" );
		
		$output->addHTML ( "<br><a href='$baselink/statdeleteconfirm?statid=$statId&confirm=True'>Delete </a>" );
		$output->addHTML ( "<a href='$baselink/statdeleteconfirm?statid=$statId&confirm=False'> Cancel</a>" );
	}
	
	
	public function ConfirmDeleteStat() {
		$permission = $this->canUserEdit ();
		
		if ($permission === False) {
			return $this->reportError ( "Error: you have no permission to delete computed stats" );
		}
		
		$output = $this->getOutput ();
		$baselink = $this->GetBaseLink ();
		$req = $this->getRequest ();
		
		$confirm = $req->getVal ( 'confirm' );
		$statId = $req->getVal ( 'statid' );
		$statId = $this->db->real_escape_string( $statId );
		
		if ($confirm !== 'True') {
			$output->addHTML ( "<p>Delete cancelled</p><br>" );
			$output->addHTML ( "<a href='$baselink'>Home</a>" );
		} else {
			$this->LoadComputedStat ( $statId );
			
			if ($this->LoadComputedStat ( $statId ) == False) {
				return $this->reportError ( "Error: cannot load stat" );
			}
			
			$version = $this->escapeHtml( $this->computedStat ['version'] );
			$round = $this->escapeHtml( $this->computedStat ['roundNum'] );
			$addClass = $this->escapeHtml( $this->computedStat ['addClass'] );
			$comment = $this->escapeHtml( $this->computedStat ['comment'] );
			$minimumValue = $this->escapeHtml( $this->computedStat ['minimumValue'] );
			$maximumValue = $this->escapeHtml( $this->computedStat ['maximumValue'] );
			$deferLevel = $this->escapeHtml( $this->computedStat ['deferLevel'] );
			$display = $this->escapeHtml( $this->computedStat ['display'] );
			$compute = $this->escapeHtml( $this->computedStat ['compute'] );
			$idx = $this->escapeHtml( $this->computedStat ['idx'] );
			$category = $this->escapeHtml( $this->computedStat ['category'] );
			$suffix = $this->escapeHtml( $this->computedStat ['suffix'] );
			$dependsOn = $this->escapeHtml( $this->computedStat ['dependsOn'] );
			
			$cols = [ ];
			$values = [ ];
			$cols [] = 'statId';
			$cols [] = 'version';
			$cols [] = 'roundNum';
			$cols [] = 'addClass';
			$cols [] = 'comment';
			$cols [] = 'minimumValue';
			$cols [] = 'maximumValue';
			$cols [] = 'deferLevel';
			$cols [] = 'display';
			$cols [] = 'compute';
			$cols [] = 'idx';
			$cols [] = 'category';
			$cols [] = 'suffix';
			$cols [] = 'dependsOn';
			
			$values [] = "'" . $this->db->real_escape_string( $statId ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $version ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $round ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $addClass ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $comment ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $minimumValue ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $maximumValue ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $deferLevel ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $display ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $compute ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $idx ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $category ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $suffix ) . "'";
			$values [] = "'" . $this->db->real_escape_string( $dependsOn ) . "'";
			
			$insertResult = $this->InsertQueries ( 'computedStatsArchive', $cols, $values );
			if (!$insertResult) return $this->reportError("Error: Failed to insert record into computedStatsArchive!");
			
			$deleteResult = $this->DeleteQueries ( 'computedStats', 'statId', $statId );
			if (!$deleteResult) return $this->reportError("Error: Failed to delete record from computedStats!");
			
			$output->addHTML ( "<p>computed Stat deleted</p><br>" );
			$output->addHTML ( "<a href='$baselink'>Home</a>" );
		}
	}
	
	
	// -------------------Main page---------------
	public function OutputTableOfContents() {
		$output = $this->getOutput ();
		
		$baselink = $this->GetBaseLink ();
		
		$output->addHTML ( "<ul>" );
		$output->addHTML ( "<li><a href='$baselink/showrules'>Show Rules</a></li>" );
		$output->addHTML ( "<li><a href='$baselink/addrule'>Add Rule</a></li>" );
		$output->addHTML ( "<br>" );
		$output->addHTML ( "<li><a href='$baselink/showcomputedstats'>Show Computed Stats</a></li>" );
		$output->addHTML ( "<li><a href='$baselink/addcomputedstat'>Add Computed Stat</a></li>" );
		$output->addHTML ( "<br>" );
		$output->addHTML ( "<li><a href='$baselink/addversion'>Add Version</a></li>" );
		$output->addHTML ( "</ul>" );
	}
	
	
	function execute($parameter) {
		$request = $this->getRequest ();
		$output = $this->getOutput ();
		$this->setHeaders ();
		
		// TODO: Remove after testing
		/*
		 * if ($this->canUserEdit())
		 * $output->addHTML("Use can edit</br>");
		 * else
		 * $output->addHTML("Use CANNOT edit</br>");
		 */
		
		// TODO: Determine action/output based on the input $parameter
		
		if ($parameter == "showrules")
			$this->OutputShowRulesTable ();
		elseif ($parameter == "addrule")
			$this->OutputAddRuleForm ();
		elseif ($parameter == "editrule")
			$this->OutputEditRuleForm ();
		elseif ($parameter == "saverule")
			$this->SaveNewRule ();
		elseif ($parameter == "saveeditruleform")
			$this->SaveEditRuleForm ();
		elseif ($parameter == "addneweffect")
			$this->OutputAddtEffectForm ();
		elseif ($parameter == "savenewffect")
			$this->SaveNewEffect ();
		elseif ($parameter == "saveediteffectform")
			$this->SaveEditEffectForm ();
		elseif ($parameter == "editeffect")
			$this->OutputEditEffectForm ();
		elseif ($parameter == "showcomputedstats")
			$this->OutputShowComputedStatsTable ();
		elseif ($parameter == "addcomputedstat")
			$this->OutputAddComputedStatsForm ();
		elseif ($parameter == "savenewcomputedstat")
			$this->SaveNewComputedStat ();
		elseif ($parameter == "editcomputedstat")
			$this->OutputEditComputedStatForm ();
		elseif ($parameter == "saveeditcomputedstatsform")
			$this->SaveEditComputedStatsForm ();
		elseif ($parameter == "deleterule")
			$this->OutputDeleteRule ();
		elseif ($parameter == "ruledeleteconfirm")
			$this->ConfirmDeleteRule ();
		elseif ($parameter == "deletcomputedstat")
			$this->OutputDeleteComputedStat ();
		elseif ($parameter == "statdeleteconfirm")
			$this->ConfirmDeleteStat ();
		elseif ($parameter == "deleteeffect")
			$this->OutputDeleteEffect ();
		elseif ($parameter == "effectdeleteconfirm")
			$this->ConfirmDeleteEffect ();
		elseif ($parameter == "addversion")
			$this->OutputAddVersionForm ();
		elseif ($parameter == "saveversion")
			$this->SaveNewVersion ();
		else
			$this->OutputTableOfContents ();
	}
	
	
	function getgroupName() {
		return 'wiki';
	}
};

