<?php

require_once ("/home/uesp/secrets/esobuilddata.secrets");
require_once ("/home/uesp/secrets/esolog.secrets");
require_once ("/home/uesp/esolog.static/esoCommon.php");

class SpecialEsoBuildRuleEditor extends SpecialPage {
	
	
	public $COMPUTED_STAT_CATEGORIES = [
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
				"other" => "Other", 
		];
	
	public $ROUND_OPTIONS = [ 
			'' => 'None',
			'ceil' => 'Ceil', 
			'floor' => 'Floor',
			'floor2' => 'Floor2',
			'floor10' => 'Floor10',
			'round' => 'Round',
		];
	
	public $RULE_TYPE_OPTIONS = [ 
				'' => 'None',
				'abilitydesc' => 'Ability Description',
				'active' => 'Active',
				'buff' => 'Buff',
				'cp' => 'CP',
				'armorenchant' => 'Enchantment (Armor)',
				//'offhandenchant' => 'Enchantment (Off-Hand Weapon)',
				'offhandweaponenchant' => 'Enchantment (Off-Hand Weapon)',
				'weaponenchant' => 'Enchantment (Weapon)',
				'mundus' => 'Mundus',
				'passive' => 'Passive',
				'set' => 'Set',
		];
	
	public $RULE_TYPE_OPTIONS_ANY = [];
	
	public $db = null;
	public $logdb = null;
	public $hasLoadedBuffIds = false;
	
	public $rulesDatas = [];
	public $hasFilteredRules = false;
	public $totalRuleCount = 0;
	
	public $hasFilteredStats = false;
	public $totalStatsCount = 0;
	
	public $testSetData = [];
	public $testCpData = [];
	public $testSkillData = [];
	public $testMatchData = [];
	
	public $statIds = [];
	
	
	function __construct()
	{
		global $wgOut;
		global $uespIsMobile;
		
		parent::__construct( 'EsoBuildRuleEditor' );
		
		$this->RULE_TYPE_OPTIONS_ANY = $this->RULE_TYPE_OPTIONS;
		$this->RULE_TYPE_OPTIONS_ANY[''] = 'Any';
		
		$wgOut->addModules( 'ext.EsoBuildData.ruleseditor.scripts' );
		$wgOut->addModuleStyles( 'ext.EsoBuildData.ruleseditor.styles' );
		
		if ($uespIsMobile || (class_exists( "MobileContext" ) && MobileContext::singleton()->isMobileDevice()))
		{
			// TODO: Add any mobile specific CSS/scripts resource modules here
		}
		
		$this->InitDatabase();
	}
	
	
	public static function escapeHtml($html)
	{
		return htmlspecialchars( $html,  ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 );
	}
	
	
	public function canUserEdit()
	{
		$context = $this->getContext();
		if ($context == null) return false;
		
		$user = $context->getUser ();
		if ($user == null) return false;
		
		if (! $user->isLoggedIn()) return false;
		
		return $user->isAllowedAny( 'esochardata_ruleedit' );
	}
	
	
	public function isUserAdmin()
	{
		$context = $this->getContext();
		if ($context == null) return false;
		
		$user = $context->getUser ();
		if ($user == null) return false;
		
		if (! $user->isLoggedIn()) return false;
		
		return $user->isAllowedAny( 'esochardata_ruleadmin' );
	}
	
	
	protected function CreateTables()
	{
		
		$result = $this->db->query( "CREATE TABLE IF NOT EXISTS rules (
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
			originalId TINYTEXT NOT NULL,
			icon TINYTEXT NOT NULL,
			groupName TINYTEXT NOT NULL,
			maxTimes INTEGER,
			comment TINYTEXT NOT NULL,
			description MEDIUMTEXT NOT NULL,
			customData MEDIUMTEXT NOT NULL,
			PRIMARY KEY (id),
			INDEX index_version(version(10)),
			INDEX index_ruleId(originalId(30)));" );
		
		if ($result === false) {
			return $this->reportError( "Error: failed to create rules table" );
		}
		
		$effects_result = $this->db->query( "CREATE TABLE IF NOT EXISTS effects (
			id INTEGER AUTO_INCREMENT NOT NULL,
			ruleId INTEGER NOT NULL,
			version TINYTEXT NOT NULL,
			statId TINYTEXT NOT NULL,
			value TINYTEXT NOT NULL,
			display TINYTEXT NOT NULL,
			category TINYTEXT NOT NULL,
			combineAs TINYTEXT NOT NULL,
			roundNum TINYTEXT NOT NULL,
			factorValue FLOAT,
			statDesc TINYTEXT NOT NULL,
			buffId TINYTEXT NOT NULL,
			regexVar TINYTEXT NOT NULL,
			PRIMARY KEY (id),
			INDEX index_ruleId(ruleId),
			INDEX index_stat(statId(32)),
			INDEX index_version(version(10)));" );
		
		if ($effects_result === false) {
			return $this->reportError( "Error: failed to create effects table" );
		}
		
		$computedStats_result = $this->db->query( "CREATE TABLE IF NOT EXISTS computedStats (
			id INTEGER AUTO_INCREMENT NOT NULL,
			statId TINYTEXT NOT NULL,
			version TINYTEXT NOT NULL,
			title TINYTEXT NOT NULL,
			roundNum TINYTEXT NOT NULL,
			addClass TINYTEXT NOT NULL,
			comment TINYTEXT NOT NULL,
			minimumValue FLOAT,
			maximumValue FLOAT,
			deferLevel TINYINT,
			display TINYTEXT NOT NULL,
			compute TEXT NOT NULL,
			idx TINYINT NOT NULL,
			category TINYTEXT NOT NULL,
			suffix TINYTEXT NOT NULL,
			dependsOn MEDIUMTEXT NOT NULL,
			PRIMARY KEY (id),
			INDEX index_statId(statId(32)),
			INDEX index_version(version(10)));" );
		
		if ($computedStats_result === false) {
			return $this->reportError( "Error: failed to create computed Stats table" );
		}
		
		$deleteRule_result = $this->db->query( "CREATE TABLE IF NOT EXISTS rulesArchive (
			archiveId INTEGER AUTO_INCREMENT NOT NULL,
			id INTEGER NOT NULL,
			version TINYTEXT NOT NULL,
			ruleType TINYTEXT NOT NULL,
			nameId TINYTEXT NOT NULL,
			displayName TINYTEXT NOT NULL,
			matchRegex TINYTEXT NOT NULL,
			displayRegex TINYTEXT NOT NULL,
			statRequireId TINYTEXT NOT NULL,
			statRequireValue TINYTEXT NOT NULL,
			factorStatId TINYTEXT NOT NULL,
			isEnabled TINYINT(1) NOT NULL,
			isVisible TINYINT(1) NOT NULL,
			isToggle TINYINT(1) NOT NULL,
			enableOffBar TINYINT(1) NOT NULL,
			originalId TINYTEXT NOT NULL,
			icon TINYTEXT NOT NULL,
			groupName TINYTEXT NOT NULL,
			maxTimes INTEGER,
			comment TINYTEXT NOT NULL,
			description MEDIUMTEXT NOT NULL,
			customData MEDIUMTEXT NOT NULL,
			PRIMARY KEY (archiveId),
			INDEX index_version(version(10)),
			INDEX index_ruleId(originalId(30)) );" );
		
		if ($deleteRule_result === false) {
			return $this->reportError( "Error: failed to create rules archive table" );
		}
		
		$deletedEffects_result = $this->db->query( "CREATE TABLE IF NOT EXISTS effectsArchive (
			archiveId INTEGER AUTO_INCREMENT NOT NULL,
			id INTEGER NOT NULL,
			ruleId INTEGER NOT NULL,
			version TINYTEXT NOT NULL,
			statId TINYTEXT NOT NULL,
			value TINYTEXT NOT NULL,
			display TINYTEXT NOT NULL,
			category TINYTEXT NOT NULL,
			combineAs TINYTEXT NOT NULL,
			roundNum TINYTEXT NOT NULL,
			factorValue FLOAT,
			statDesc TINYTEXT NOT NULL,
			buffId TINYTEXT NOT NULL,
			regexVar TINYTEXT NOT NULL,
			PRIMARY KEY (archiveId),
			INDEX index_ruleId(ruleId),
			INDEX index_stat(statId(32)),
			INDEX index_version(version(10)) );" );
		
		if ($deletedEffects_result === false) {
			return $this->reportError( "Error: failed to create effects archive table" );
		}
		
		$DeletedcomputedStats_result = $this->db->query( "CREATE TABLE IF NOT EXISTS computedStatsArchive (
			archiveId INTEGER AUTO_INCREMENT NOT NULL,
			id INTEGER NOT NULL,
			statId TINYTEXT NOT NULL,
			version TINYTEXT NOT NULL,
			title TINYTEXT NOT NULL,
			roundNum TINYTEXT NOT NULL,
			addClass TINYTEXT NOT NULL,
			comment TINYTEXT NOT NULL,
			minimumValue FLOAT,
			maximumValue FLOAT,
			deferLevel TINYINT,
			display TINYTEXT NOT NULL,
			compute TEXT NOT NULL,
			idx TINYINT NOT NULL,
			category TINYTEXT NOT NULL,
			suffix TINYTEXT NOT NULL,
			dependsOn MEDIUMTEXT NOT NULL,
			PRIMARY KEY (archiveId),
			INDEX index_version(version(10)) ); " );
		
		if ($computedStats_result === false) {
			return $this->reportError( "Error: failed to create computed Stats archive table" );
		}
		
		$versions_result = $this->db->query( "CREATE TABLE IF NOT EXISTS versions (
			version TINYTEXT NOT NULL,
			PRIMARY KEY idx_version(version(16)) );" );
		
		if ($computedStats_result === false) {
			return $this->reportError( "Error: failed to create versions table" );
		}
		
		return true;
	}
	
	
	public function InitDatabase()
	{
		global $uespEsoBuildDataWriteDBHost, $uespEsoBuildDataWriteUser, $uespEsoBuildDataWritePW, $uespEsoBuildDataDatabase;
		
		if ($this->db) return true;
		
		$this->db = new mysqli( $uespEsoBuildDataWriteDBHost, $uespEsoBuildDataWriteUser, $uespEsoBuildDataWritePW, $uespEsoBuildDataDatabase );
		if ($this->db->connect_error) return $this->reportError( "Error: failed to initialize database!" );
		
		$this->CreateTables();
		return true;
	}
	
	
	public function InitLogDatabase()
	{
		global $uespEsoLogReadDBHost, $uespEsoLogReadUser, $uespEsoLogReadPW, $uespEsoLogDatabase;
		
		if ($this->logdb) return true;
		
		$this->logdb = new mysqli( $uespEsoLogReadDBHost, $uespEsoLogReadUser, $uespEsoLogReadPW, $uespEsoLogDatabase );
		if ($this->logdb->connect_error) return $this->reportError( "Error: failed to initialize log database!" );
		
		return true;
	}
	
	
	public function OutputAddVersionForm()
	{
		$permission = $this->isUserAdmin();
		if ($permission === false) return $this->reportError( "Error: you have no permission to add versions!" );
		
		$output = $this->getOutput ();
		$baselink = $this->GetBaseLink ();
		
		$output->addHTML( "<h3>Adding New Version</h3>" );
		$output->addHTML( "<form action='$baselink/saveversion' method='POST'>" );
		
		$output->addHTML( "<label for='version'>Source Version</label>" );
		$output->addHTML( "<input type='text' id='sourceversion' name='sourceversion'>" );
		$output->addHTML( "<p class='errorMsg'></p>" );
		$output->addHTML( "<p>If you specify an existing source version all rules, effects, and computed stats will be copied from the source to the new version.</p>" );
		
		$output->addHTML( "<label for='version'>New Version</label>" );
		$output->addHTML( "<input type='text' id='version' name='version'>" );
		$output->addHTML( "<p class='errorMsg'></p>" );
		
		$output->addHTML( "<br><input type='submit' value='Save Version' class='submit_btn'>" );
		$output->addHTML( "</form>" );
	}
	
	
	public function SaveNewVersion()
	{
		$permission = $this->isUserAdmin();
		if ($permission === false) return $this->reportError( "Error: you have no permission to add versions" );
		
		$output = $this->getOutput ();
		$baselink = $this->GetBaseLink ();
		$req = $this->getRequest ();
		
		$output->addHTML( "<a href='$baselink'>Home</a><br/>" );
		
		$input_version = trim($req->getVal( 'version' ));
		$input_sourceVersion = trim($req->getVal( 'sourceversion' ));
		if ($input_sourceVersion == null) $input_sourceVersion = '';
		
		if (!preg_match('/^[0-9]+(pts)?$/', $input_version)) return $this->reportError( "Error: New version '$input_version' does not match the expected format of '##' or '##pts'!" );
		if ($input_sourceVersion && !preg_match('/^[0-9]+(pts)?$/', $input_sourceVersion)) return $this->reportError( "Error: Source version '$input_sourceVersion' does not match the expected format of '##' or '##pts'!" );
		
		$this->LoadVersions();
		
		$versionList = [];
		
		foreach ( $this->versions as $version )
		{
			$versionList[$version] = true;
			
			if ($input_version == $version)
			{
				$versionOption = $this->escapeHtml( $version );
				return $this->reportError( "Error: version $input_version already exists" );
			}
		}
		
		if ($input_sourceVersion && !array_key_exists($input_sourceVersion, $versionList)) return $this->reportError("Error: Source version $input_sourceVersion is not currently in use!");
		
		$cols = [ ];
		$values = [ ];
		
		$cols[] = 'version';
		$values[] = "'" . $this->db->real_escape_string( $input_version ) . "'";
		
		$insertResult = $this->InsertQueries ( 'versions', $cols, $values );
		if (!$insertResult) return $this->reportError("Error: Failed to insert record into versions!");
		
		if ($input_sourceVersion)
		{
			$copyResult = $this->CopyVersions($input_sourceVersion, $input_version);
			if (!$copyResult) return $this->reportError("Error: Failed to copy data from version $input_version to $input_sourceVersion!");
			
			$output->addHTML( "<p>New version '$input_version' added using source data from '$input_sourceVersion'!</p><br>" );
		}
		else
		{
			$output->addHTML( "<p>New version '$input_version' added!</p><br>" );
		}
	}
	
	
	public function CopyVersions($sourceVersion, $destVersion)
	{
		if ($sourceVersion == null || $sourceVersion == "") return true;
		
		$output = $this->getOutput();
		
		$safeSource = $this->db->real_escape_string($sourceVersion);
		$safeDest   = $this->db->real_escape_string($destVersion);
		
		$newRules = 0;
		$newEffects = 0;
		$newStats = 0;
		
		if (!$this->LoadRules($sourceVersion)) return false;
		
		foreach ($this->rulesDatas as $rule)
		{
			$ruleId = $rule['id'];
			
			$cols = [ ];
			$values = [ ];
			$cols[] = 'ruleType';
			$cols[] = 'nameId';
			$cols[] = 'displayName';
			$cols[] = 'matchRegex';
			$cols[] = 'statRequireId';
			$cols[] = 'factorStatId';
			$cols[] = 'originalId';
			$cols[] = 'version';
			$cols[] = 'icon';
			$cols[] = 'groupName';
			$cols[] = 'maxTimes';
			$cols[] = 'comment';
			$cols[] = 'description';
			$cols[] = 'isEnabled';
			$cols[] = 'isVisible';
			$cols[] = 'enableOffBar';
			$cols[] = 'isToggle';
			$cols[] = 'statRequireValue';
			$cols[] = 'customData';
			
			$values[] = "'" . $this->db->real_escape_string( $rule['ruleType'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $rule['nameId'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $rule['displayName'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $rule['matchRegex'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $rule['statRequireId'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $rule['factorStatId'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $rule['originalId'] ) . "'";
			$values[] = "'" . $safeDest . "'";
			$values[] = "'" . $this->db->real_escape_string( $rule['icon'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $rule['groupName'] ) . "'";
			
			if ($rule['maxTimes'] == null || $rule['maxTimes'] == '')
				$values[] = "NULL";
			else
				$values[] = "'" . $this->db->real_escape_string( $rule['maxTimes'] ) . "'";
			
			$values[] = "'" . $this->db->real_escape_string( $rule['comment'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $rule['description'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $rule['isEnabled'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $rule['isVisible'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $rule['enableOffBar'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $rule['isToggle'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $rule['statRequireValue'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $rule['customData'] ) . "'";
			
			$insertResult = $this->InsertQueries( 'rules', $cols, $values );
			if (!$insertResult) return $this->ReportError("Error: Failed to copy rule #$ruleId from version $sourceVersion to $destVersion!");
			
			++$newRules;
			$newRuleId = $this->db->insert_id;
			
			$copyResult = $this->CopyRuleEffects($ruleId, $newRuleId, $sourceVersion, $destVersion);
			if (!$copyResult) return $this->ReportError("Error: Failed to copy effects for rule #$ruleId from version $sourceVersion to $destVersion!");
			
			$newEffects += $this->newEffects;
		}
		
		if (!$this->LoadComputedStats($sourceVersion)) return $this->reportError("Error: Failed to load computed stats!");
		
		foreach ($this->computedStatsDatas as $computedStat)
		{
			$cols = [];
			$values = [];
			
			$cols[] = 'statId';
			$cols[] = 'version';
			$cols[] = 'roundNum';
			$cols[] = 'addClass';
			$cols[] = 'comment';
			$cols[] = 'title';
			$cols[] = 'minimumValue';
			$cols[] = 'maximumValue';
			$cols[] = 'deferLevel';
			$cols[] = 'display';
			$cols[] = 'compute';
			$cols[] = 'idx';
			$cols[] = 'category';
			$cols[] = 'suffix';
			$cols[] = 'dependsOn';
			
			$values[] = "'" . $this->db->real_escape_string( $computedStat['statId'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $destVersion ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $computedStat['roundNum'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $computedStat['addClass'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $computedStat['comment'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $computedStat['title'] ) . "'";
			
			if ($computedStat['minimumValue'] == null || $computedStat['minimumValue'] == '')
				$values[] = "NULL";
			else
				$values[] = "'" . $this->db->real_escape_string( $computedStat['minimumValue'] ) . "'";
			
			if ($computedStat['maximumValue'] == null || $computedStat['maximumValue'] == '')
				$values[] = "NULL";
			else
				$values[] = "'" . $this->db->real_escape_string( $computedStat['maximumValue'] ) . "'";
			
			if ($computedStat['deferLevel'] == null || $computedStat['deferLevel'] == '')
				$values[] = "NULL";
			else
				$values[] = "'" . $this->db->real_escape_string( $computedStat['deferLevel'] ) . "'";
			
			$values[] = "'" . $this->db->real_escape_string( $computedStat['display'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $computedStat['compute'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $computedStat['idx'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $computedStat['category'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $computedStat['suffix'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $computedStat['dependsOn'] ) . "'";
			
			$insertResult = $this->InsertQueries( 'computedStats', $cols, $values );
			if (!$insertResult) return $this->reportError("Error: Failed to insert record into computedStats!");
			
			++$newStats;
		}
		
		$output->addHTML("Copied $newRules rules, $newEffects effects, and $newStats computed stats!\n<br/>");
		
		return true;
	}
	
	
	public function CopyRuleEffects($ruleId, $newRuleId, $sourceVersion, $destVersion)
	{
		$this->newEffects = 0;
		
		if (!$this->LoadEffects($ruleId)) return $this->reportError("Error: Failed to load effects for rtule $ruleId!");
		
		foreach ($this->effectsDatas as $effect)
		{
			$cols = [ ];
			$values = [ ];
			
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
			
			$values[] = "'" . $this->db->real_escape_string( $newRuleId ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $destVersion ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $effect['statId'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $effect['value'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $effect['display'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $effect['category'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $effect['combineAs'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $effect['roundNum'] ) . "'";
			
			if ($effect['factorValue'] == null || $effect['factorValue'] == '')
				$values[] = 'NULL';
			else
				$values[] = "'" . $this->db->real_escape_string( $effect['factorValue'] ) . "'";
			
			$values[] = "'" . $this->db->real_escape_string( $effect['statDesc'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $effect['buffId'] ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $effect['regexVar'] ) . "'";
			
			$insertResult = $this->InsertQueries ( 'effects', $cols, $values );
			if (!$insertResult) return $this->reportError("Error: Failed to insert record into effects!");
			
			++$this->newEffects;
		}
		
		return true;
	}
	
	
	public function LoadVersions()
	{
		$query = "SELECT version FROM versions;";
		$result = $this->db->query( $query );
		
		if ($result === false) {
			return $this->reportError( "Error: failed to load versions from database" );
		}
		
		$this->versions = [];
		
		while ( $row = $result->fetch_assoc() )
		{
			$this->versions[] = $row['version'];
		}
		
		natsort($this->versions);
		
		return true;
	}
	
	
	public function OutputBuffListHtml ($elementId, $selected = '')
	{
		$this->LoadBuffIds();
		
		$output = $this->getOutput();
		
		$output->addHTML( "<label for='$elementId'>Buff ID</label>" );
		$output->addHTML( "<select name='$elementId' id='$elementId'>" );
		
		$output->addHTML( "<option value=''></option>" );
		
		foreach ( $this->buffIds as $buffIdVal )
		{
			$optionVal = $buffIdVal['nameId'];
			
			$selectAttr = '';
			if ($optionVal == $selected) $selectAttr = "selected";
			
			if ($optionVal != '') 
			{
				$optionVal = $this->escapeHtml( $buffIdVal['nameId'] );
				$output->addHTML( "<option value='$optionVal' $selectAttr>$optionVal</option>" );
			}
		}
		
		$output->addHTML( "</select>" );
	}
	
	
	public function OutputVersionListHtml($elementId, $selectedVersion, $includeAny = false, $omitVersion = null)
	{
		$this->LoadVersions();
		
		$output = $this->getOutput();
		
		$output->addHTML( "<label for='$elementId'>Version</label>" );
		$output->addHTML( "<select id='$elementId' name='$elementId'> " );
		
		if ($includeAny) $output->addHTML( "<option value=''>Any</option>" );
		
		foreach ( $this->versions as $version )
		{
			$selected = '';
			$versionOption = $this->escapeHtml( $version );
			
			if ($omitVersion == $versionOption) continue;
			
			if ($versionOption == $selectedVersion) $selected = "selected";
			if ($versionOption != "") $output->addHTML( "<option value='$versionOption' $selected >$versionOption</option>" );
		}
		
		$output->addHTML( "</select>" );
	}
	
	
	public function InsertQueries($tableName, $cols, $values)
	{
		$cols = implode( ',', $cols );
		$values = implode( ',', $values );
		$query = "INSERT INTO $tableName($cols) VALUES($values);";
		
		$result = $this->db->query( $query );
		
		if ($result === false) return $this->reportError( "Error: failed to INSERT data into database! $query" );
		return true;
	}
	
	
	public function DeleteQueries($tableName, $conditionName, $value)
	{
		$value = $this->db->real_escape_string( $value );
		$query = "DELETE FROM $tableName WHERE $conditionName='$value';";
		$result = $this->db->query( $query );
		
		if ($result === false) return $this->reportError( "Error: failed to DELETE data from database!" );
		return true;
	}
	
	
	public function UpdateQueries($tableName, $values, $conditionName, $value)
	{
		$values = implode( ',', $values );
		
		$query = "UPDATE $tableName SET $values WHERE $conditionName='$value';";
		
		error_log($query);
		
		$result = $this->db->query( $query );
		
		if ($result === false) return $this->reportError( "Error: failed to UPDATE data in database!" );
		return true;
	}
	
	
	public function OutputRoundsListHtml($param, $round)
	{
		$output = $this->getOutput();
		
		$output->addHTML( "<label for='$param'>Round</label>" );
		$this->OutputListHtml( $round, $this->ROUND_OPTIONS, $param );
		$output->addHTML( "<br/>" );
	}
	
	
	public function LoadTotalRuleCount()
	{
		$query = "SELECT count(*) as c FROM rules;";
		
		$result = $this->db->query( $query );
		if ($result === false) return $this->reportError( "Error: Failed to load total rule count from database!" );
		
		$row = $result->fetch_assoc();
		$this->totalRuleCount = intval($row['c']);
		
		return $this->totalRuleCount;
	}
	
	
	public function LoadTotalStatsCount()
	{
		$query = "SELECT count(*) as c FROM computedStats;";
		
		$result = $this->db->query( $query );
		if ($result === false) return $this->reportError( "Error: Failed to load total computed stats count from database!" );
		
		$row = $result->fetch_assoc();
		$this->totalStatsCount = intval($row['c']);
		
		return $this->totalStatsCount;
	}
	
	
	public function LoadRules($version = '')
	{
		$this->LoadTotalRuleCount();
		
		if ($version == '')
		{
			$query = "SELECT * FROM rules;";
		}
		else
		{
			$safeVersion = $this->db->real_escape_string($version);
			$query = "SELECT * FROM rules WHERE version='$safeVersion';";
		}
		
		$result = $this->db->query( $query );
		if ($result === false) return $this->reportError( "Error: Failed to load rules from database!" );
		
		$this->rulesDatas = [];
		
		while ( $row = $result->fetch_assoc() )
		{
			$this->rulesDatas[] = $row;
		}
		
		return true;
	}
	
	
	public function MakeFilteredRuleQuery($filters)
	{
		$query = "";
		$where = [];
		$req = $this->getRequest();
		
		$version = $req->getVal('version');
		if ($version === null) $version = GetEsoUpdateVersion();
		if ($version) $where[] = $this->MakeSafeMatchQuery('version', $version);
		
		$ruleType = $req->getVal('ruletype');
		if ($ruleType) $where[] = $this->MakeSafeMatchQuery('ruleType', $ruleType);
		
		$searchText = $req->getVal('searchtext');
		if ($searchText) $where[] = $this->MakeSafeLikeQuery(['displayName', 'nameId', 'matchRegex', 'displayRegex', 'comment', 'description', 'customData'], $searchText);
		
		$where = implode(' AND ', $where);
		
		if ($where)
		{
			$this->hasFilteredRules = true;
			$query = "SELECT * FROM rules WHERE $where;";
		}
		else
		{
			$query = "SELECT * FROM rules;";
			$this->hasFilteredRules = false;
		}
		
		return $query;
	}
	
	
	public function MakeFilteredStatsQuery($filters)
	{
		$query = "";
		$where = [];
		$req = $this->getRequest();
		
		$version = $req->getVal('version');
		if ($version) $where[] = $this->MakeSafeMatchQuery('version', $version);
		
		$searchText = $req->getVal('searchtext');
		if ($searchText) $where[] = $this->MakeSafeLikeQuery(['statId', 'compute', 'title', 'comment', 'addClass', 'dependsOn'], $searchText);
		
		$where = implode(' AND ', $where);
		
		if ($where)
		{
			$this->hasFilteredStats = true;
			$query = "SELECT * FROM computedStats WHERE $where;";
		}
		else
		{
			$query = "SELECT * FROM computedStats;";
			$this->hasFilteredStats = false;
		}
		
		return $query;
	}
	
	
	public function MakeSafeMatchQuery($col, $value)
	{
		$safeValue = $this->db->real_escape_string($value);
		return "`$col`='$safeValue'";
	}
	
	
	public function MakeSafeLikeQuery($cols, $value)
	{
		$safeValue = $this->db->real_escape_string($value);
		if (gettype($cols) == "string") return "`$col` LIKE '%$safeValue%'";
		
		$where = [];
		
		foreach ($cols as $col)
		{
			$where[] = "`$col` LIKE '%$safeValue%'";
		}
		
		if (count($where) == 0) return "1";
		return "(" . implode(" OR ", $where) . ")";
	}
	
	
	public function LoadEffectsForRules(&$rules)
	{
		
		foreach ($rules as $i => $rule)
		{
			$ruleId = $rule['id'];
			
			if (!$this->LoadEffects($ruleId))
			{
				$rules[$i]['effects'] = [];
				continue;
			}
			
			$rules[$i]['effects'] = $this->effectsDatas;
		}
	}
	
	
	public function LoadFilteredRules($filters = [])
	{
		$this->rulesDatas = [];
		$this->LoadTotalRuleCount();
		
		$query = $this->MakeFilteredRuleQuery($filters);
		
		$result = $this->db->query( $query );
		if ($result === false) return $this->reportError( "Error: Failed to load filtered rules from database!" );
		
		while ( $row = $result->fetch_assoc() )
		{
			$this->rulesDatas[] = $row;
		}
		
		return true;
	}
	
	
	public function LoadFilteredStats($filters = [])
	{
		$this->computedStatsDatas = [];
		$this->LoadTotalStatsCount();
		
		$query = $this->MakeFilteredStatsQuery($filters);
		
		$result = $this->db->query( $query );
		if ($result === false) return $this->reportError( "Error: Failed to load filtered computed stats from database!" );
		
		while ( $row = $result->fetch_assoc() )
		{
			$this->computedStatsDatas[] = $row;
		}
		
		return true;
	}
	
	
	public function OutputShowRulesFilterForm()
	{
		$req = $this->getRequest();
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		
		$version = $req->getVal('version');
		$ruleType = $req->getVal('ruletype');
		$searchText = $req->getVal('searchtext');
		
		$safeRuleType = $this->escapeHtml($ruleType);
		$safeSearchType = $this->escapeHtml($searchText);
		
		$output->addHTML( "<form id='filterRuleForm' action='$baselink/showrules'>" );
		
		if ($version == null && $version !== "") $version = "" . GetEsoUpdateVersion();
		
		$this->OutputVersionListHtml( 'version', $version, true );
		
		$output->addHTML( "<label for='ruletype'>Rule Type</label>" );
		$this->OutputListHtml( $ruleType, $this->RULE_TYPE_OPTIONS_ANY, 'ruletype' );
		
		$output->addHTML( "<label for='filterRuleSearch'></label><input type='text' id='filterRuleSearch' name='searchtext' value='$safeSearchType'/>" );
		
		$output->addHTML( " <input type='submit' value='Filter'/>" );
		$output->addHTML( " <input type='button' value='Clear' onclick='OnClearRuleFilterForm();'/>" );
		
		$output->addHTML( "</form>" );
		
		$ruleCount = count($this->rulesDatas);
		$totalRuleCount = $this->totalRuleCount;
		
		if ($this->hasFilteredRules)
			$output->addHTML( "Showing matching $ruleCount rules out of $totalRuleCount total." );
		else
			$output->addHTML( "Showing all $ruleCount rules." );
	}
	
	
	public function OutputShowStatsFilterForm()
	{
		$req = $this->getRequest();
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		
		$version = $req->getVal('version');
		$searchText = $req->getVal('searchtext');
		
		$safeSearchType = $this->escapeHtml($searchText);
		
		$output->addHTML( "<form id='filterStatsForm' action='$baselink/showcomputedstats'>" );
		
		$this->OutputVersionListHtml( 'version', $version, true );
		
		$output->addHTML( " <input type='text' id='filterStatSearch' name='searchtext' value='$safeSearchType'/>" );
		
		$output->addHTML( " <input type='submit' value='Filter'/>" );
		$output->addHTML( " <input type='button' value='Clear' onclick='OnClearStatFilterForm();'/>" );
		
		$output->addHTML( "</form>" );
		
		$statCount = count($this->computedStatsDatas);
		$totalStatCount = $this->totalStatsCount;
		
		if ($this->hasFilteredStats)
			$output->addHTML( "Showing matching $statCount computed stats out of $totalStatCount total." );
		else
			$output->addHTML( "Showing all $statCount computed stats." );
	}
	
	
	public function MakeNiceShortRuleType($ruleType)
	{
		$RULETYPES = [
				'' => '',
				'abilitydesc' => 'Ability Desc',
				'active' => 'Active',
				'buff' => 'Buff',
				'cp' => 'CP',
				'armorenchant' => 'Enchant (Armor)',
				'offhandenchant' => 'Enchant (Off-Hand)',
				'offhandweaponenchant ' => 'Enchant (Off-Hand)',
				'weaponenchant' => 'Enchant (Weapon)',
				'mundus' => 'Mundus',
				'passive' => 'Passive',
				'set' => 'Set',
		];
		
		$ruleType = strtolower($ruleType);
		if (array_key_exists($ruleType, $RULETYPES)) return $RULETYPES[$ruleType];
		
		return ucwords($ruleType);
	}
	
	
	public function OutputShowRulesTable()
	{
		if (!$this->LoadFilteredRules()) return false;
		
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		
		$output->addHTML( "<a href='$baselink'>Home</a> : <a href='$baselink/addrule'>Add New Rule</a>" );
		
		if ($this->hasFilteredRules)
			$output->addHTML( "<h3>Showing Matching Rules</h3>" );
		else
			$output->addHTML( "<h3>Showing All Rules</h3>" );
		
		$this->OutputShowRulesFilterForm();
		
		$output->addHTML( "<table class='wikitable sortable jquery-tablesorter' id='rules'><thead>" );
		
		$output->addHTML( "<tr>" );
		$output->addHTML( "<th>Edit</th>" );
		//$output->addHTML( "<th>ID</th>" );
		$output->addHTML( "<th>Version</th>" );
		$output->addHTML( "<th>Rule Type</th>" );
		$output->addHTML( "<th>Name ID</th>" );
		$output->addHTML( "<th>Display Name</th>" );
		$output->addHTML( "<th>Match Regex</th>" );
		$output->addHTML( "<th>Required Stat</th>" );
		$output->addHTML( "<th>Stat Require Value</th>" );
		$output->addHTML( "<th>Factor Stat</th>" );
		$output->addHTML( "<th>Original Id</th>" );
		$output->addHTML( "<th>Group Name</th>" );
		$output->addHTML( "<th>Description</th>" );
		
		$output->addHTML( "<th>Enabled</th>" );
		$output->addHTML( "<th>Toggle</th>" );
		$output->addHTML( "<th>Max Times</th>" );
		$output->addHTML( "<th>Visible</th>" );
		$output->addHTML( "<th>Enable Off Bar</th>" );
		$output->addHTML( "<th>Custom Data</th>" );
		$output->addHTML( "<th>Delete</th>" );
		$output->addHTML( "</tr></thead><tbody>" );
		
		foreach ( $this->rulesDatas as $rulesData ) 
		{
			$id = $this->escapeHtml( $rulesData['id'] );
			$ruleType = $this->escapeHtml( $this->MakeNiceShortRuleType($rulesData['ruleType']) );
			$nameId = $this->escapeHtml( $rulesData['nameId'] );
			$displayName = $this->escapeHtml( $rulesData['displayName'] );
			$matchRegex = $this->escapeHtml( $rulesData['matchRegex'] );
			$statRequireId = $this->escapeHtml( $rulesData['statRequireId'] );
			$factorStatId = $this->escapeHtml( $rulesData['factorStatId'] );
			$originalId = $this->escapeHtml( $rulesData['originalId'] );
			$groupName = $this->escapeHtml( $rulesData['groupName'] );
			$description = $this->escapeHtml( $rulesData['description'] );
			$version = $this->escapeHtml( $rulesData['version'] );
			$isEnabled = $this->escapeHtml( $rulesData['isEnabled'] );
			$toggle = $this->escapeHtml( $rulesData['isToggle'] );
			$isVisible = $this->escapeHtml( $rulesData['isVisible'] );
			$enableOffBar = $this->escapeHtml( $rulesData['enableOffBar'] );
			$statRequireValue = $this->escapeHtml( $rulesData['statRequireValue'] );
			$maxTimes = $this->escapeHtml( $rulesData['maxTimes'] );
			if ($maxTimes == 0) $maxTimes = '';
			
			$isEnabledDisplay = $this->GetBooleanDispaly ( $isEnabled );
			$toggleDisplay = $this->GetBooleanDispaly ( $toggle );
			$isVisibleDisplay = $this->GetBooleanDispaly ( $isVisible );
			$enableOffBarDisplay = $this->GetBooleanDispaly ( $enableOffBar );
			
			if ($rulesData['customData'] == '')
			{
				$data = [ ];
			} else {
				$data = json_decode( $rulesData['customData'], true );
				if ($data == null) $data = []; // TODO: Error handling?
				if (!is_array($data)) $data = ['Error: Not Array!', $rulesData['customData']];
				
				foreach ($data as $i => $d)
				{
					if (is_string($d)) $data[$i] = trim($d);
				}
				
				array_filter($data, 'strlen');
			}
			
			$rulesData['customData'] = $data;
			
			$output->addHTML( "<tr>" );
			$output->addHTML( "<td><a href='$baselink/editrule?ruleid=$id'>Edit</a></td>" );
			//$output->addHTML( "<td>$id</td>" );
			$output->addHTML( "<td>$version</td>" );
			$output->addHTML( "<td>$ruleType</td>" );
			$output->addHTML( "<td>$nameId</td>" );
			$output->addHTML( "<td>$displayName</td>" );
			$output->addHTML( "<td>$matchRegex</td>" );
			$output->addHTML( "<td>$statRequireId</td>" );
			$output->addHTML( "<td>$statRequireValue</td>" );
			$output->addHTML( "<td>$factorStatId</td>" );
			$output->addHTML( "<td>$originalId</td>" );
			$output->addHTML( "<td>$groupName</td>" );
			$output->addHTML( "<td>$description</td>" );
			$output->addHTML( "<td>$isEnabledDisplay</td>" );
			$output->addHTML( "<td>$toggleDisplay</td>" );
			$output->addHTML( "<td>$maxTimes</td>" );
			$output->addHTML( "<td>$isVisibleDisplay</td>" );
			$output->addHTML( "<td>$enableOffBarDisplay</td>" );
			
			
			$output->addHTML( "<td>" );
			
			foreach ( $rulesData['customData'] as $key => $val )
			{
				if ($val === true) $val = "true";
				if ($val === false) $val = "false";
				$output->addHTML( "$key = $val<br>" );
			}
			
			$output->addHTML( "</td>" );
			
			$output->addHTML( "<td><a href='$baselink/deleterule?ruleid=$id'>Delete</a></td>" );
			$output->addHTML( "</tr>" );
		}
		
		$output->addHTML( "</table>" );
	}
	
	
	public function OutputDeleteRule()
	{
		$permission = $this->canUserEdit();
		if ($permission === false) return $this->reportError( "Error: you have no permission to delete rules" );
		
		$output = $this->getOutput ();
		$baselink = $this->GetBaseLink ();
		
		$id = $this->GetRuleId();
		if ($id <= 0) return $this->reportError( "Error: invalid rule ID" );
		
		if (!$this->LoadRule( $id )) return $this->reportError( "Error: cannot load Rule" );
		
		$id = $this->escapeHtml( $id );
		$ruleType = $this->escapeHtml( $this->rule['ruleType'] );
		$nameId = $this->escapeHtml( $this->rule['nameId'] );
		$displayName = $this->escapeHtml( $this->rule['displayName'] );
		$matchRegex = $this->escapeHtml( $this->rule['matchRegex'] );
		$displayRegex = $this->escapeHtml( $this->rule['displayRegex'] );
		$statRequireId = $this->escapeHtml( $this->rule['statRequireId'] );
		$factorStatId = $this->escapeHtml( $this->rule['factorStatId'] );
		$originalId = $this->escapeHtml( $this->rule['originalId'] );
		$version = $this->escapeHtml( $this->rule['version'] );
		$icon = $this->escapeHtml( $this->rule['icon'] );
		$groupName = $this->escapeHtml( $this->rule['groupName'] );
		$maxTimes = $this->escapeHtml( $this->rule['maxTimes'] );
		$comment = $this->escapeHtml( $this->rule['comment'] );
		$description = $this->escapeHtml( $this->rule['description'] );
		$isEnabled = $this->escapeHtml( $this->rule['isEnabled'] );
		$isVisible = $this->escapeHtml( $this->rule['isVisible'] );
		$enableOffBar = $this->escapeHtml( $this->rule['enableOffBar'] );
		$isToggle = $this->escapeHtml( $this->rule['isToggle'] );
		$statRequireValue = $this->escapeHtml( $this->rule['statRequireValue'] );
		
		if ($this->rule['customData'] == '')
		{
			$data = [ ];
		} else
		{
			$data = json_decode ( $this->rule['customData'], true );
			if ($data == null) $data = [];	//TODO: Error handling?
			if (!is_array($data)) $data = ['Error: Not Array!', $this->rule['customData']];
			
			foreach ($data as $i => $d)
			{
				$data[$i] = trim($d);
			}
			
			array_filter($data, 'strlen');
		}
		
		$this->rule['customData'] = $data;
		
		$output->addHTML( "<h3>Are you sure you want to delete this rule: </h3>" );
		$output->addHTML( "<label><b>id:</b> $id</label><br>" );
		$output->addHTML( "<label><b>Rule Type:</b> $ruleType</label><br>" );
		$output->addHTML( "<label><b>Name Id:</b> $nameId</label><br>" );
		$output->addHTML( "<label><b>Display Name:</b> $displayName</label><br>" );
		$output->addHTML( "<label><b>Match Regex:</b> $matchRegex</label><br>" );
		$output->addHTML( "<label><b>Stat Require Id:</b> $statRequireId</label><br>" );
		$output->addHTML( "<label><b>Stat Require Value:</b> $statRequireValue</label><br>" );
		$output->addHTML( "<label><b>Factor Stat Id:</b> $factorStatId</label><br>" );
		$output->addHTML( "<label><b>Original Id:</b> $originalId</label><br>" );
		$output->addHTML( "<label><b>Version:</b> $version</label><br>" );
		$output->addHTML( "<label><b>Icon:</b> $icon</label><br>" );
		$output->addHTML( "<label><b>Group Name:</b> $groupName</label><br>" );
		$output->addHTML( "<label><b>Max Times:</b> $maxTimes</label><br>" );
		$output->addHTML( "<label><b>Comment:</b> $comment</label><br>" );
		$output->addHTML( "<label><b>Description:</b> $description</label><br>" );
		$output->addHTML( "<label><b>Enabled:</b> $isEnabled</label><br>" );
		$output->addHTML( "<label><b>Visible:</b> $isVisible</label><br>" );
		$output->addHTML( "<label><b>Enable Off Bar:</b> $enableOffBar</label><br>" );
		$output->addHTML( "<label><b>Toggle:</b> $isToggle</label><br>" );
		
		$output->addHTML( "<b>custom Data:</b><br/>" );
		
		foreach ( $this->rule['customData'] as $key => $val ) {
			$output->addHTML( "<li class='customData'>$key = $val</li>" );
		}
		
		$output->addHTML( "<br><a href='$baselink/ruledeleteconfirm?ruleid=$id&confirm=True'>Delete </a>" );
		$output->addHTML( "<a href='$baselink/ruledeleteconfirm?ruleid=$id&confirm=false'> Cancel</a>" );
	}
	
	
	public function ConfirmDeleteRule()
	{
		$permission = $this->canUserEdit();
		if ($permission === false)return $this->reportError( "Error: you have no permission to delete rules" );
		
		$output = $this->getOutput ();
		$baselink = $this->GetBaseLink ();
		$req = $this->getRequest ();
		
		$confirm = $req->getVal( 'confirm' );
		
		$id = $this->GetRuleId();
		if ($id <= 0) return $this->reportError( "Error: invalid rule ID" );
		
		if ($confirm !== 'True')
		{
			$output->addHTML( "<p>Delete cancelled</p><br>" );
			$output->addHTML( "<a href='$baselink'>Home</a>" );
		}
		else 
		{
			if (!$this->LoadRule( $id )) return $this->reportError( "Error: Failed to load rule #$id!" );
			
			$ruleType = $this->rule['ruleType'];
			$nameId = $this->rule['nameId'];
			$displayName = $this->rule['displayName'];
			$matchRegex = $this->rule['matchRegex'];
			$displayRegex = $this->rule['displayRegex'];
			$statRequireId = $this->rule['statRequireId'];
			$statRequireValue = $this->rule['statRequireValue'];
			$factorStatId = $this->rule['factorStatId'];
			$originalId = $this->rule['originalId'];
			$version = $this->rule['version'];
			$icon = $this->rule['icon'];
			$groupName = $this->rule['groupName'];
			$maxTimes = $this->rule['maxTimes'];
			$comment = $this->rule['comment'];
			$description = $this->rule['description'];
			$isEnabled = intval($this->rule['isEnabled']);
			$isVisible = intval($this->rule['isVisible']);
			$enableOffBar = intval($this->rule['enableOffBar']);
			$isToggle = intval($this->rule['isToggle']);
			
			$customData = $this->rule['customData'];
			
			$cols = [ ];
			$values = [ ];
			
			$cols[] = 'id';
			$cols[] = 'ruleType';
			$cols[] = 'nameId';
			$cols[] = 'displayName';
			$cols[] = 'matchRegex';
			$cols[] = 'displayRegex';
			$cols[] = 'statRequireId';
			$cols[] = 'statRequireValue';
			$cols[] = 'factorStatId';
			$cols[] = 'originalId';
			$cols[] = 'version';
			$cols[] = 'icon';
			$cols[] = 'groupName';
			$cols[] = 'maxTimes';
			$cols[] = 'comment';
			$cols[] = 'description';
			$cols[] = 'isEnabled';
			$cols[] = 'isVisible';
			$cols[] = 'enableOffBar';
			$cols[] = 'isToggle';
			$cols[] = 'customData';
			
			$values[] = "'" . $this->db->real_escape_string( $id ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $ruleType ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $nameId ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $displayName ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $matchRegex ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $displayRegex ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $statRequireId ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $statRequireValue ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $factorStatId ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $originalId ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $version ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $icon ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $groupName ) . "'";
			
			if ($input_maxTimes == '')
				$values[] = "NULL";
			else
				$values[] = "'" . $this->db->real_escape_string( $maxTimes ) . "'";
			
			$values[] = "'" . $this->db->real_escape_string( $comment ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $description ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $isEnabled ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $isVisible ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $enableOffBar ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $isToggle ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $customData ) . "'";
			
			$insertResult = $this->InsertQueries ( 'rulesArchive', $cols, $values );
			if (!$insertResult) return $this->reportError("Error: Failed to insert record into rulesArchive!");
			
			if (!$this->LoadEffects($id)) return $this->reportError("Error: Failed to load effects for rule #$id!");
			
			foreach ( $this->effectsDatas as $effectsData ) {
				$effectId = $effectsData['id'];
				$version = $effectsData['version'];
				$statId = $effectsData['statId'];
				$value = $effectsData['value'];
				$display = $effectsData['display'];
				$category = $effectsData['category'];
				$combineAs = $effectsData['combineAs'];
				$roundNum = $effectsData['roundNum'];
				$factorValue = $effectsData['factorValue'];
				$statDesc = $effectsData['statDesc'];
				$buffId = $effectsData['buffId'];
				$regexVar = $effectsData['regexVar'];
				
				$cols = [ ];
				$values = [ ];
				
				$cols[] = 'id';
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
				
				$values[] = "'" . $this->db->real_escape_string( $effectId ) . "'";
				$values[] = "'" . $this->db->real_escape_string( $id ) . "'";
				$values[] = "'" . $this->db->real_escape_string( $version ) . "'";
				$values[] = "'" . $this->db->real_escape_string( $statId ) . "'";
				$values[] = "'" . $this->db->real_escape_string( $value ) . "'";
				$values[] = "'" . $this->db->real_escape_string( $display ) . "'";
				$values[] = "'" . $this->db->real_escape_string( $category ) . "'";
				$values[] = "'" . $this->db->real_escape_string( $combineAs ) . "'";
				$values[] = "'" . $this->db->real_escape_string( $roundNum ) . "'";
				
				if ($input_maxTimes == '')
					$values[] = "NULL";
				else
					$values[] = "'" . $this->db->real_escape_string( $factorValue ) . "'";
				
				$values[] = "'" . $this->db->real_escape_string( $statDesc ) . "'";
				$values[] = "'" . $this->db->real_escape_string( $buffId ) . "'";
				$values[] = "'" . $this->db->real_escape_string( $regexVar ) . "'";
				
				$insertResult = $this->InsertQueries ( 'effectsArchive', $cols, $values );
				if (!$insertResult) return $this->reportError("Error: Failed to insert record into effectsArchive!");
			}
			
			$deleteResult = $this->DeleteQueries ( 'rules', 'id', $id );
			if (!$deleteResult) return $this->reportError("Error: Failed to delete record from rules!");
			
			$deleteResult = $this->DeleteQueries ( 'effects', 'ruleId', $id );
			if (!$deleteResult) return $this->reportError("Error: Failed to delete record from effects!");
			
			$output->addHTML( "<p>Rule deleted</p><br>" );
			$output->addHTML( "<a href='$baselink'>Home</a>" );
		}
	}
	
	
	public function LoadRule($primaryKey)
	{
		$primaryKey = $this->db->real_escape_string( $primaryKey );
		$query = "SELECT * FROM rules WHERE id='$primaryKey';";
		$result = $this->db->query( $query );
		
		if ($result === false) {
			return $this->reportError( "Error: failed to load rule from database" );
		}
		
		$row = [ ];
		$row[] = $result->fetch_assoc ();
		$this->rule = $row[0];
		
		if ($this->rule == null) return $this->reportError( "Error: failed to load rule from database" );
		
		return true;
	}
	
	
	public function OutputEditRuleForm()
	{
		//$permission = $this->canUserEdit();
		//if ($permission === false) return $this->reportError( "Error: you have no permission to edit rules" );
		
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		
		$id = $this->GetRuleId();
		
		if (!$this->LoadRule($id)) return $this->reportError("Error: Failed to load rule #id!");
		
		$ruleType = $this->escapeHtml( $this->rule['ruleType'] );
		$nameId = $this->escapeHtml( $this->rule['nameId'] );
		$displayName = $this->escapeHtml( $this->rule['displayName'] );
		$matchRegex = $this->escapeHtml( $this->rule['matchRegex'] );
		$displayRegex = $this->escapeHtml( $this->rule['displayRegex'] );
		$statRequireId = $this->escapeHtml( $this->rule['statRequireId'] );
		$factorStatId = $this->escapeHtml( $this->rule['factorStatId'] );
		$originalId = $this->escapeHtml( $this->rule['originalId'] );
		$version = $this->escapeHtml( $this->rule['version'] );
		$icon = $this->escapeHtml( $this->rule['icon'] );
		$groupName = $this->escapeHtml( $this->rule['groupName'] );
		$maxTimes = $this->escapeHtml( $this->rule['maxTimes'] );
		$comment = $this->escapeHtml( $this->rule['comment'] );
		$description = $this->escapeHtml( $this->rule['description'] );
		$isEnabled = $this->escapeHtml( $this->rule['isEnabled'] );
		$isVisible = $this->escapeHtml( $this->rule['isVisible'] );
		$enableOffBar = $this->escapeHtml( $this->rule['enableOffBar'] );
		$toggle = $this->escapeHtml( $this->rule['isToggle'] );
		$statRequireValue = $this->escapeHtml( $this->rule['statRequireValue'] );
		
		if ($this->rule['customData'] == '')
		{
			$data = [ ];
		} else
		{
			$data = json_decode( $this->rule['customData'], true );
			if ($data == null) $data = [];	//TODO: Error handling?
			if (!is_array($data)) $data = ['Error: Not Array!', $this->rule['customData']];
			
			foreach ($data as $i => $d)
			{
				if (is_string($d)) $data[$i] = trim($d);
			}
			
			array_filter($data, 'strlen');
		}
		
		$this->rule['customData'] = $data;
		
		$output->addHTML( "<a href='$baselink'>Home</a> : <a href='$baselink/showrules'>Show Rules</a> : <a href='$baselink/addrule'>Add Rule</a> : <a href='$baselink/testrule?ruleid=$id'>Test Rule</a> : <a href='$baselink/copyrule?ruleid=$id'>Copy Rule</a> : <a href='$baselink/deleterule?ruleid=$id'>Delete Rule</a> <br/>" );
		$output->addHTML( "<h3>Editing Rule #$id</h3>" );
		$output->addHTML( "<form action='$baselink/saveeditruleform?ruleid=$id' method='POST'>" );
		
		$this->OutputVersionListHtml( 'edit_version', $version );
		$output->addHTML( "<br/>" );
		
		$output->addHTML( "<label for='ruleType'>Rule Type</label>" );
		$this->OutputListHtml( $ruleType, $this->RULE_TYPE_OPTIONS, 'ruleType' );
		$output->addHTML( "<br/>" );
		
		$output->addHTML( "<label for='edit_nameId'>Name ID</label>" );
		$output->addHTML( "<input type='text' id='edit_nameId' name='edit_nameId' value='$nameId' size='60'>" );
		$output->addHTML( "<p class='errorMsg'></p>" );
		
		$output->addHTML( "<label for='edit_displayName'>Display Name</label>" );
		$output->addHTML( "<input type='text' id='edit_displayName' name='edit_displayName' value='$displayName' size='60'><br>" );
		
		$output->addHTML( "<label for='edit_matchRegex'>Match Regex</label>" );
		//$output->addHTML( "<input type='text' id='edit_matchRegex' name='edit_matchRegex' value='$matchRegex' size='60'>" );
		$output->addHTML( "<textarea id='edit_matchRegex' name='edit_matchRegex' rows='5' cols='10' wrap='soft' maxlength='1000' class='ruleTextArea'>$matchRegex</textarea>" );
		$output->addHTML( "<p class='errorMsg'></p>" );
		$output->addHTML( "<p class='warningErr'></p>" );
		
		$output->addHTML( "<label for='edit_displayRegex'>Display Regex</label>" );
		//$output->addHTML( "<input type='text' id='edit_displayRegex' name='edit_displayRegex' value='$displayRegex' size='60'>" );
		$output->addHTML( "<textarea id='edit_displayRegex' name='edit_displayRegex' rows='5' cols='10' wrap='soft' maxlength='1000' class='ruleTextArea'>$displayRegex</textarea>" ); 
		$output->addHTML( "<p class='errorMsg'></p>" );
		
		$output->addHTML( "<label for='edit_statRequireId'>Stat Require Id</label>" );
		$output->addHTML( "<input type='text' id='edit_statRequireId' name='edit_statRequireId' value='$statRequireId'><br>" );
		$output->addHTML( "<label for='edit_statRequireValue'>Stat Require Value</label>" );
		$output->addHTML( "<input type='text' id='edit_statRequireValue' name='edit_statRequireValue' value='$statRequireValue'><br>" );
		$output->addHTML( "<label for='edit_factorStatId'>Factor Stat Id</label>" );
		$output->addHTML( "<input type='text' id='edit_factorStatId' name='edit_factorStatId' value='$factorStatId'><br>" );
		$output->addHTML( "<label for='edit_originalId'>Original Id</label>" );
		$output->addHTML( "<input type='text' id='edit_originalId' name='edit_originalId' value='$originalId'><br>" );
		
		$output->addHTML( "<label for='edit_icon'>Icon</label>" );
		$output->addHTML( "<input type='text' id='edit_icon' size='60' name='edit_icon' value='$icon'><br>" );
		$output->addHTML( "<label for='edit_groupName'>Group Name</label>" );
		$output->addHTML( "<input type='text' id='edit_groupName' name='edit_groupName' value='$groupName'><br>" );
		$output->addHTML( "<label for='edit_maxTimes'>Maximum Times</label>" );
		$output->addHTML( "<input type='text' id='edit_maxTimes' name='edit_maxTimes' value='$maxTimes'><br>" );
		$output->addHTML( "<label for='edit_comment'>Comment</label>" );
		$output->addHTML( "<input type='text' id='edit_comment' name='edit_comment' value='$comment' size='60'><br>" );
		
		$output->addHTML( "<label for='edit_description'>Description</label>" );
		$output->addHTML( "<textarea id='edit_description' name='edit_description' class='txtArea' rows='4' cols='50'>$description</textarea><br>" );
		
		$output->addHTML( "<label for='edit_customData'>Custom Data</label>" );
		$output->addHTML( "<input type='text' class='custReadOnly' value='name' readonly></input> " );
		$output->addHTML( "<input type='text' class='custReadOnly custDataValue' value='value' readonly></input><br/>" );
		
		foreach ( $this->rule['customData'] as $key => $val )
		{
			if ($val === true) $val = "true";
			if ($val === false) $val = "false";
			$output->addHTML( "<input type='text' id='edit_customName' name='edit_customName[]' class='custCol' value='$key'>   </input>" );
			$output->addHTML( "<input type='text' id='edit_customValue' name='edit_customValue[]' value='$val' class='custDataValue'></input><br>" );
		}
		$output->addHTML( "<input type='text' id='edit_customName' name='edit_customName[]' class='custCol'>   </input>" );
		$output->addHTML( "<input type='text' id='edit_customValue' name='edit_customValue[]' class='custDataValue'></input><br>" );
		$output->addHTML( "<input type='text' id='edit_customName' name='edit_customName[]' class='custCol'>   </input>" );
		$output->addHTML( "<input type='text' id='edit_customValue' name='edit_customValue[]'  class='custDataValue'></input><br>" );
		
		$isEnabledBoxCheck = $this->GetCheckboxState ( $isEnabled );
		$isVisibleBoxCheck = $this->GetCheckboxState ( $isVisible );
		$enableOffBarBoxCheck = $this->GetCheckboxState ( $enableOffBar );
		$isEnabledBoxCheck = $this->GetCheckboxState ( $isEnabled );
		$toggleBoxCheck = $this->GetCheckboxState ( $toggle );
		
		$output->addHTML( "<br><label for='edit_isEnabled'>Enabled</label>" );
		$output->addHTML( "<input $isEnabledBoxCheck type='checkbox' id='edit_isEnabled' name='edit_isEnabled' value='1'><br> " );
		$output->addHTML( "<label for='edit_isVisible'>Visible</label>" );
		$output->addHTML( "<input $isVisibleBoxCheck type='checkbox' id='edit_isVisible' name='edit_isVisible' value='1'><br>" );
		$output->addHTML( "<label for='edit_enableOffBar'>Enable Off Bar</label>" );
		$output->addHTML( "<input $enableOffBarBoxCheck type='checkbox' id='edit_enableOffBar' name='edit_enableOffBar' value='1'><br>" );
		$output->addHTML( "<label for='edit_toggle'>Toggle</label>" );
		$output->addHTML( "<input $toggleBoxCheck type='checkbox' id='edit_toggle' name='edit_toggle' value='1'><br>" );
		
		$output->addHTML( "<br><input type='submit' value='Save Rule' class='submit_btn'>" );
		$output->addHTML( "</form><br>" );
		
		$this->OutputShowEffectsTable ();
	}
	
	
	public function OutputAddRuleForm()
	{
		$permission = $this->canUserEdit();
		if ($permission === false) return $this->reportError( "Error: you have no permission to add rules" );
		
		$output = $this->getOutput();
		
		$baselink = $this->GetBaseLink();
		
		$output->addHTML( "<h3>Adding New Rule</h3>" );
		$output->addHTML( "<form action='$baselink/saverule' method='POST'>" );
		
		$this->OutputVersionListHtml( 'version', strval(GetEsoUpdateVersion()) );
		$output->addHTML( "<br/>" );
		
		$output->addHTML( "<label for='ruleType'>Rule Type</label>" );
		$this->OutputListHtml( '', $this->RULE_TYPE_OPTIONS, 'ruleType' );
		$output->addHTML( "<br/>" );
		
		$output->addHTML( "<label for='nameId'>Name Id</label>" );
		$output->addHTML( "<input type='text' id='nameId' name='nameId' size='60'>" );
		$output->addHTML( "<p class='errorMsg'></p>" );
		
		$output->addHTML( "<label for='displayName'>Display Name</label>" );
		$output->addHTML( "<input type='text' id='displayName' name='displayName' size='60'><br>" );
		
		$output->addHTML( "<label for='matchRegex'>Match Regex</label>" );
		//$output->addHTML( "<input type='text' id='matchRegex' name='matchRegex' size='60'>" );
		$output->addHTML( "<textarea id='matchRegex' name='matchRegex' rows='5' cols='10' wrap='soft' maxlength='1000' class='ruleTextArea'></textarea>" );
		$output->addHTML( "<p class='errorMsg'></p>" );
		$output->addHTML( "<p class='warningErr'></p>" );
		
		$output->addHTML( "<label for='displayRegex'>Display Regex</label>" );
		//$output->addHTML( "<input type='text' id='displayRegex' name='displayRegex' size='60'>" );
		$output->addHTML( "<textarea id='displayRegex' name='displayRegex' rows='5' cols='10' wrap='soft' maxlength='1000' class='ruleTextArea'></textarea>" );
		$output->addHTML( "<p class='errorMsg'></p>" );
		
		$output->addHTML( "<label for='statRequireId'>Stat Require Id</label>" );
		$output->addHTML( "<input type='text' id='statRequireId' name='statRequireId'><br>" );
		$output->addHTML( "<label for='statRequireValue'>Stat Require Value</label>" );
		$output->addHTML( "<input type='text' id='statRequireValue' name='statRequireValue'><br>" );
		$output->addHTML( "<label for='factorStatId'>Factor Stat Id</label>" );
		$output->addHTML( "<input type='text' id='factorStatId' name='factorStatId'><br>" );
		$output->addHTML( "<label for='originalId'>Original Id</label>" );
		$output->addHTML( "<input type='text' id='originalId' name='originalId'><br>" );
		
		$output->addHTML( "<label for='icon'>Icon</label>" );
		$output->addHTML( "<input type='text' id='icon' name='icon' size='60'><br>" );
		$output->addHTML( "<label for='groupName'>Group Name</label>" );
		$output->addHTML( "<input type='text' id='groupName' name='groupName'><br>" );
		$output->addHTML( "<label for='maxTimes'>Maximum Times</label>" );
		$output->addHTML( "<input type='text' id='maxTimes' name='maxTimes'><br>" );
		$output->addHTML( "<label for='comment'>Comment</label>" );
		$output->addHTML( "<input type='text' id='comment' name='comment' size='60'><br>" );
		$output->addHTML( "<label for='description'>Description</label>" );
		$output->addHTML( "<textarea id='description' name='description' class='txtArea' rows='4' cols='50'></textarea><br>" );
		
		$output->addHTML( "<label for='customData'>Custom Data</label>" );
		$output->addHTML( "<input type='text' class='custReadOnly' value='name' readonly></input> " );
		$output->addHTML( "<input type='text' class='custReadOnly' value='value' readonly></input><br/>" );
		
		$output->addHTML( "<input type='text' id='customNames' name='customNames[]' class='custCol'></input>  " );
		$output->addHTML( "<input type='text' id='customValues' name='customValues[]'></input><br>" );
		$output->addHTML( "<input type='text' id='customNames' name='customNames[]'class='custCol'></input>  " );
		$output->addHTML( "<input type='text' id='customValues' name='customValues[]'></input><br>" );
		$output->addHTML( "<input type='text' id='customNames' name='customNames[]'class='custCol'></input>  " );
		$output->addHTML( "<input type='text' id='customValues' name='customValues[]'></input><br>" );
		
		// could only be true or false (1 or 0)
		$output->addHTML( "<br><label for='isEnabled'>Enabled</label>" );
		$output->addHTML( "<input type='checkbox' id='isEnabled' name='isEnabled' value='1'><br>" );
		$output->addHTML( "<label for='isVisible'>Visible</label>" );
		$output->addHTML( "<input type='checkbox' id='isVisible' name='isVisible' value='1' checked><br>" );
		$output->addHTML( "<label for='enableOffBar'>Enable Off Bar</label>" );
		$output->addHTML( "<input type='checkbox' id='enableOffBar' name='enableOffBar' value='1'><br>" );
		$output->addHTML( "<label for='toggle'>Toggle</label>" );
		$output->addHTML( "<input type='checkbox' id='toggle' name='toggle' value='1'><br>" );
		
		$output->addHTML( "<br><input type='submit' value='Save Rule' class='submit_btn'>" );
		$output->addHTML( "</form>" );
	}
	
	
	public function GetCheckboxState($boolValue)
	{
		if ($boolValue === '1') return "checked";
		return "";
	}
	
	
	public function OutputListHtml($option, $array, $listName)
	{
		$output = $this->getOutput ();
		
		$output->addHTML( "<select id='$listName' name='$listName'>" );
		
		foreach ( $array as $key => $value )
		{
			$selected = "";
			if ($key === $option) $selected = "selected";
			
			$output->addHTML( "<option value='$key' $selected >$value</option>" );
		}
		$output->addHTML( "</select>" );
	}
	
	
	public function GetBooleanDispaly($boolValue)
	{
		if ($boolValue === '1') return "Yes";
		return "";
	}
	
	
	public function ReportError($msg)
	{
		$output = $this->getOutput();
		
		$output->addHTML( $msg . "<br/>" );
		$output->addHTML( $this->db->error );	//TODO: Only output if present?
		
		error_log ( $msg );
		
		return false;
	}
	
	
	public function TransformCustomValue($value)
	{
		$lcValue = strtolower($value);
		
		if ($lcValue == 'true') return true;
		if ($lcValue == 'false') return false;
		if ($lcValue == 'null') return null;
		
		if (is_numeric($value)) return floatval($value);
		
		return $value;
	}
	
	
	public function SaveNewRule()
	{
		$permission = $this->canUserEdit();
		if ($permission === false) return $this->reportError( "Error: you have no permission to add rules" );
		
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$req = $this->getRequest();
		
		$input_ruleType = $req->getVal( 'ruleType' );
		$input_nameId = $req->getVal( 'nameId' );
		$input_displayName = $req->getVal( 'displayName' );
		$input_matchRegex = $req->getVal( 'matchRegex' );
		$input_statRequireId = $req->getVal( 'statRequireId' );
		$input_factorStatId = $req->getVal( 'factorStatId' );
		$input_originalId = $req->getVal( 'originalId' );
		$input_version = $req->getVal( 'version' );
		$input_icon = $req->getVal( 'icon' );
		$input_groupName = $req->getVal( 'groupName' );
		$input_maxTimes = trim($req->getVal( 'maxTimes' ));
		$input_comment = $req->getVal( 'comment' );
		$input_description = $req->getVal( 'description' );
		$input_isEnabled = intval($req->getVal( 'isEnabled' ));
		$input_isVisible = intval($req->getVal( 'isVisible' ));
		$input_enableOffBar = intval($req->getVal( 'enableOffBar' ));
		$input_toggle = intval($req->getVal( 'toggle' ));
		$input_statRequireValue = $req->getVal( 'statRequireValue' );
		
		$customNames = $req->getArray( 'customNames' );
		$customValues = $req->getArray( 'customValues' );
		$input_customData = [ ];
		
		foreach ( $customNames as $i => $name )
		{
			$name = trim( $name );
			$value = $customValues[$i];
			
			if ($name == '') continue;
			if ($value === null) continue;
			
			$value = $this->TransformCustomValue($value);
			
			$input_customData[$name] = $value;
		}
		
		$input_customData = json_encode( $input_customData );
		
		$cols = [ ];
		$values = [ ];
		$cols[] = 'ruleType';
		$cols[] = 'nameId';
		$cols[] = 'displayName';
		$cols[] = 'matchRegex';
		$cols[] = 'statRequireId';
		$cols[] = 'factorStatId';
		$cols[] = 'originalId';
		$cols[] = 'version';
		$cols[] = 'icon';
		$cols[] = 'groupName';
		$cols[] = 'maxTimes';
		$cols[] = 'comment';
		$cols[] = 'description';
		$cols[] = 'isEnabled';
		$cols[] = 'isVisible';
		$cols[] = 'enableOffBar';
		$cols[] = 'isToggle';
		$cols[] = 'statRequireValue';
		$cols[] = 'customData';
		
		$values[] = "'" . $this->db->real_escape_string( $input_ruleType ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_nameId ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_displayName ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_matchRegex ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_statRequireId ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_factorStatId ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_originalId ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_version ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_icon ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_groupName ) . "'";
		
		if ($input_maxTimes == '')
			$values[] = "NULL";
		else
			$values[] = "'" . $this->db->real_escape_string( $input_maxTimes ) . "'";
		
		$values[] = "'" . $this->db->real_escape_string( $input_comment ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_description ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_isEnabled ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_isVisible ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_enableOffBar ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_toggle ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_statRequireValue ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_customData ) . "'";
		
		$insertResult = $this->InsertQueries ( 'rules', $cols, $values );
		$lastId = $this->db->insert_id;
		
		if ($insertResult)
			header ( "Location: $baselink/editrule?ruleid=$lastId" );
		else
			$this->reportError("Error: Failed to save new rule record!");
		
		return $insertResult;
	}
	
	
	public function SaveEditRuleForm()
	{
		$permission = $this->canUserEdit();
		if ($permission === false) return $this->reportError( "Error: you have no permission to edit rules" );
		
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$req = $this->getRequest();
		
		$id = $this->GetRuleId();
		if ($id <= 0) return $this->reportError( "Error: invalid rule ID" );
		$id = $this->db->real_escape_string($id);
		
		$new_ruleType = $req->getVal( 'ruleType' );
		$new_nameId = $req->getVal( 'edit_nameId' );
		$new_displayName = $req->getVal( 'edit_displayName' );
		$new_matchRegex = $req->getVal( 'edit_matchRegex' );
		$new_displayRegex = $req->getVal( 'edit_displayRegex' );
		$new_statRequireId = $req->getVal( 'edit_statRequireId' );
		$new_factorStatId = $req->getVal( 'edit_factorStatId' );
		$new_originalId = $req->getVal( 'edit_originalId' );
		$new_version = $req->getVal( 'edit_version' );
		$new_icon = $req->getVal( 'edit_icon' );
		$new_groupName = $req->getVal( 'edit_groupName' );
		$new_maxTimes = $req->getVal( 'edit_maxTimes' );
		$new_comment = $req->getVal( 'edit_comment' );
		$new_description = $req->getVal( 'edit_description' );
		$new_isEnabled = intval($req->getVal( 'edit_isEnabled' ));
		$new_isVisible = intval($req->getVal( 'edit_isVisible' ));
		$new_enableOffBar = intval($req->getVal( 'edit_enableOffBar' ));
		$new_toggle = intval($req->getVal( 'edit_toggle' ));
		$new_statRequireValue = $req->getVal( 'edit_statRequireValue' );
		
		$customNames = $req->getArray( 'edit_customName' );
		$customValues = $req->getArray( 'edit_customValue' );
		$new_customData = [ ];
		
		foreach ( $customNames as $i => $name )
		{
			$name = trim( $name );
			$value = $customValues[$i];
			
			if ($name == '') continue;
			if ($value === null) continue;
			
			$value = $this->TransformCustomValue($value);
			
			$new_customData[$name] = $value;
		}
		$new_customData = json_encode( $new_customData );
		
		$values = [ ];
		
		$values[] = "ruleType='" . $this->db->real_escape_string( $new_ruleType ) . "'";
		$values[] = "nameId='" . $this->db->real_escape_string( $new_nameId ) . "'";
		$values[] = "displayName='" . $this->db->real_escape_string( $new_displayName ) . "'";
		$values[] = "matchRegex='" . $this->db->real_escape_string( $new_matchRegex ) . "'";
		$values[] = "displayRegex='" . $this->db->real_escape_string( $new_displayRegex ) . "'";
		$values[] = "statRequireId='" . $this->db->real_escape_string( $new_statRequireId ) . "'";
		$values[] = "factorStatId='" . $this->db->real_escape_string( $new_factorStatId ) . "'";
		$values[] = "originalId='" . $this->db->real_escape_string( $new_originalId ) . "'";
		$values[] = "version='" . $this->db->real_escape_string( $new_version ) . "'";
		$values[] = "icon='" . $this->db->real_escape_string( $new_icon ) . "'";
		$values[] = "groupName='" . $this->db->real_escape_string( $new_groupName ) . "'";
		
		if ($new_maxTimes == '')
			$values[] = "maxTimes=NULL";
		else
			$values[] = "maxTimes='" . $this->db->real_escape_string( $new_maxTimes ) . "'";
		
		$values[] = "comment='" . $this->db->real_escape_string( $new_comment ) . "'";
		$values[] = "description='" . $this->db->real_escape_string( $new_description ) . "'";
		$values[] = "isEnabled='" . $this->db->real_escape_string( $new_isEnabled ) . "'";
		$values[] = "isVisible='" . $this->db->real_escape_string( $new_isVisible ) . "'";
		$values[] = "enableOffBar='" . $this->db->real_escape_string( $new_enableOffBar ) . "'";
		$values[] = "isToggle='" . $this->db->real_escape_string( $new_toggle ) . "'";
		$values[] = "statRequireValue='" . $this->db->real_escape_string( $new_statRequireValue ) . "'";
		$values[] = "customData='" . $this->db->real_escape_string( $new_customData ) . "'";
		
		$updateResult = $this->UpdateQueries ( 'rules', $values, 'id', $id );
		if (!$updateResult) return $this->reportError("Error: Failed to save rule record!");
		
		$output->addHTML( "<p>Successfully saved rule #$id!</p><br>" );
		$output->addHTML( "<a href='$baselink'>Home</a> : <a href='$baselink/showrules'>Show Rules</a> : <a href='$baselink/addrule'>Add Rule</a> : <a href='$baselink/editrule?ruleid=$id'>Edit Rule</a> : <a href='$baselink/testrule?ruleid=$id'>Test Rule</a> : <a href='$baselink/copyrule?ruleid=$id'>Copy Rule</a><br/>" );
	}
	
	
	public function GetRuleId()
	{
		$req = $this->getRequest ();
		$ruleId = $req->getVal( 'ruleid' );
		
		return $ruleId;
	}
	
	
	public static function GetBaseLink()
	{
		$link = "https://en.uesp.net/wiki/Special:EsoBuildRuleEditor";
		
		return ($link);
	}
	
	
	public function LoadEffects($ruleId)
	{
		$id = $this->db->real_escape_string( $ruleId );
		$query = "SELECT * FROM effects where ruleId='$id';";
		$effects_result = $this->db->query( $query );
		
		if ($effects_result === false) {
			return $this->reportError( "Error: failed to load effects for rule #$ruleId!" );
		}
		
		$this->effectsDatas = [];
		
		while ( $row = $effects_result->fetch_assoc() ) {
			$this->effectsDatas[] = $row;
		}
		
		return true;
	}
	
	
	public function LoadAllEffects($version = '')
	{
		if ($version == '')
		{
			$query = "SELECT * FROM effects;";
		}
		else
		{
			$safeVersion = $this->db->real_escape_string( $version );
			$query = "SELECT * FROM effects WHERE version='$safeVersion';";
		}
		$effects_result = $this->db->query( $query );
		
		if ($effects_result === false) {
			return $this->reportError( "Error: failed to load effects for rule #$ruleId!" );
		}
		
		$this->effectsDatas = [];
		
		while ( $row = $effects_result->fetch_assoc( ) ) {
			$this->effectsDatas[] = $row;
		}
		
		return true;
	}
	
	
	public function OutputShowEffectsTable()
	{
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$req = $this->getRequest();
		
		if (!$this->LoadEffects($this->GetRuleId())) return $this->reportError("Error: Failed to load effects for rule!");
		
		$id = $this->GetRuleId();
		$effectId = $req->getVal( 'effectid' );
		
		$output->addHTML( "<hr><h3>All Rule Effects</h3>" );
		$output->addHTML( "<a href='$baselink/addneweffect?ruleid=$id'>Add new effect</a>" );
		
		$output->addHTML( "<table class='wikitable sortable jquery-tablesorter' id='effects'><thead>" );
		$output->addHTML( "<tr>" );
		$output->addHTML( "<th>Edit</th>" );
		//$output->addHTML( "<th>ID</th>" );
		$output->addHTML( "<th>version</th>" );
		$output->addHTML( "<th>statId</th>" );
		$output->addHTML( "<th>value</th>" );
		$output->addHTML( "<th>display</th>" );
		$output->addHTML( "<th>category</th>" );
		$output->addHTML( "<th>combineAs</th>" );
		$output->addHTML( "<th>round</th>" );
		$output->addHTML( "<th>factorValue</th>" );
		$output->addHTML( "<th>statDesc</th>" );
		$output->addHTML( "<th>buffId</th>" );
		$output->addHTML( "<th>regexVar</th>" );
		$output->addHTML( "<th>Delete</th>" );
		$output->addHTML( "</tr></thead><tbody>" );
		
		foreach ( $this->effectsDatas as $effectsData )
		{
			$effectId = $this->escapeHtml( $effectsData['id'] );
			$version = $this->escapeHtml( $effectsData['version'] );
			$statId = $this->escapeHtml( $effectsData['statId'] );
			$value = $this->escapeHtml( $effectsData['value'] );
			$display = $this->escapeHtml( $effectsData['display'] );
			$category = $this->escapeHtml( $effectsData['category'] );
			$combineAs = $this->escapeHtml( $effectsData['combineAs'] );
			$round = $this->escapeHtml( $effectsData['roundNum'] );
			$factorValue = $this->escapeHtml( $effectsData['factorValue'] );
			$statDesc = $this->escapeHtml( $effectsData['statDesc'] );
			$buffId = $this->escapeHtml( $effectsData['buffId'] );
			$regexVar = $this->escapeHtml( $effectsData['regexVar'] );
			
			$output->addHTML( "<tr>" );
			$output->addHTML( "<td><a href='$baselink/editeffect?effectid=$effectId&ruleid=$id'>Edit</a></td>" );
			//$output->addHTML( "<td>$effectId</td>" );
			$output->addHTML( "<td>$version</td>" );
			$output->addHTML( "<td>$statId</td>" );
			$output->addHTML( "<td>$value</td>" );
			$output->addHTML( "<td>$display</td>" );
			$output->addHTML( "<td>$category</td>" );
			$output->addHTML( "<td>$combineAs</td>" );
			$output->addHTML( "<td>$round</td>" );
			$output->addHTML( "<td>$factorValue</td>" );
			$output->addHTML( "<td>$statDesc</td>" );
			$output->addHTML( "<td>$buffId</td>" );
			$output->addHTML( "<td>$regexVar</td>" );
			$output->addHTML( "<td><a href='$baselink/deleteeffect?effectid=$effectId&ruleid=$id' >Delete</a></td>" );
		}
		
		$output->addHTML( "</table>" );
		$jsonEffects = json_encode ( $this->effectsDatas );
		$output->addHTML( "<script>window.g_RuleEffectData = $jsonEffects;</script>" );
	}
	
	
	public function OutputDeleteEffect()
	{
		$permission = $this->canUserEdit();
		if ($permission === false) return $this->reportError( "Error: you have no permission to delete effects" );
		
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$req = $this->getRequest();
		
		$id = $this->GetRuleId();
		$effectId = $req->getVal( 'effectid' );
		
		//if (!$this->LoadEffects($id)) return $this->reportError("Error: Failed to load effects for rule #$id!");
		if (!$this->LoadEffect( $effectId ))return $this->reportError( "Error: cannot load effect" );
		
		$version = $this->escapeHtml( $this->effect['version'] );
		$statId = $this->escapeHtml( $this->effect['statId'] );
		$value = $this->escapeHtml( $this->effect['value'] );
		$display = $this->escapeHtml( $this->effect['display'] );
		$category = $this->escapeHtml( $this->effect['category'] );
		$combineAs = $this->escapeHtml( $this->effect['combineAs'] );
		$round = $this->escapeHtml( $this->effect['roundNum'] );
		$factorValue = $this->escapeHtml( $this->effect['factorValue'] );
		$statDesc = $this->escapeHtml( $this->effect['statDesc'] );
		$buffId = $this->escapeHtml( $this->effect['buffId'] );
		$regexVar = $this->escapeHtml( $this->effect['regexVar'] );
		
		$output->addHTML( "<h3>Are you sure you want to delete this effect: </h3>" );
		$output->addHTML( "<label><b>Id</b> $effectId</label><br>" );
		$output->addHTML( "<label><b>Version</b> $version</label><br>" );
		$output->addHTML( "<label><b>Stat Id</b> $statId</label><br>" );
		$output->addHTML( "<label><b>Value</b> $value</label><br>" );
		$output->addHTML( "<label><b>Display</b> $display</label><br>" );
		$output->addHTML( "<label><b>Category</b> $category</label><br>" );
		$output->addHTML( "<label><b>Combine As</b> $combineAs</label><br>" );
		$output->addHTML( "<label><b>Round</b> $round</label><br>" );
		$output->addHTML( "<label><b>Factor Value</b> $factorValue</label><br>" );
		$output->addHTML( "<label><b>Stat Desc</b> $statDesc</label><br>" );
		$output->addHTML( "<label><b>Buff ID</b> $buffId</label><br>" );
		$output->addHTML( "<label><b>Regex Variable</b> $regexVar</label><br>" );
		
		$output->addHTML( "<br><a href='$baselink/effectdeleteconfirm?ruleid=$id&effectid=$effectId&confirm=True'>Delete </a>" );
		$output->addHTML( "<a href='$baselink/effectdeleteconfirm?effectid=$effectId&confirm=false'> Cancel</a>" );
	}
	
	
	public function ConfirmDeleteEffect()
	{
		$permission = $this->canUserEdit();
		if ($permission === false) return $this->reportError( "Error: you have no permission to delete effects" );
		
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$req = $this->getRequest();
		
		$confirm = $req->getVal( 'confirm' );
		$effectId = $req->getVal( 'effectid' );
		
		$effectId = $this->db->real_escape_string( $effectId );
		$id = $this->GetRuleId();
		
		if ($effectId <= 0) {
			return $this->reportError( "Error: invalid stat ID" );
		}
		
		if ($confirm !== 'True')
		{
			$output->addHTML( "<p>Delete cancelled</p><br>" );
			$output->addHTML( "<a href='$baselink'>Home</a>" );
		} else
		{
			if (!$this->LoadEffect( $effectId )) return $this->reportError( "Error: cannot load effect" );
			
			$version = $this->escapeHtml( $this->effect['version'] );
			$statId = $this->escapeHtml( $this->effect['statId'] );
			$value = $this->escapeHtml( $this->effect['value'] );
			$display = $this->escapeHtml( $this->effect['display'] );
			$category = $this->escapeHtml( $this->effect['category'] );
			$combineAs = $this->escapeHtml( $this->effect['combineAs'] );
			$round = $this->escapeHtml( $this->effect['roundNum'] );
			$statDesc = $this->escapeHtml( $this->effect['statDesc'] );
			$buffId = $this->escapeHtml( $this->effect['buffId'] );
			$regexVar = $this->escapeHtml( $this->effect['regexVar'] );
			$factorValue = $this->escapeHtml( $this->effect['factorValue'] );
			
			$cols = [ ];
			$values = [ ];
			$cols[] = 'id';
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
			
			$values[] = "'" . $this->db->real_escape_string( $effectId ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $id ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $version ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $statId ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $value ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $display ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $category ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $combineAs ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $round ) . "'";
			
			if ($factorValue == '')
					$values[] = "NULL";
				else
					$values[] = "'" . $this->db->real_escape_string( $factorValue ) . "'";
				
			$values[] = "'" . $this->db->real_escape_string( $statDesc ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $buffId ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $regexVar ) . "'";
			
			$insertResult = $this->InsertQueries ( 'effectsArchive', $cols, $values );
			if (!$insertResult) return $this->reportError("Error: Failed to insert record into effectsArchive!");
			
			$deleteResult = $this->DeleteQueries ( 'effects', 'id', $effectId );
			if (!$deleteResult) return $this->reportError("Error: Failed to delete record from effects!");
			
			$output->addHTML( "<p>Effect deleted</p><br>" );
			$output->addHTML( "<a href='$baselink'>Home</a> : " );
			$output->addHTML( "<a href='$baselink/showrules'>Home</a> : " );
			$output->addHTML( "<a href='$baselink/editrule?ruleid=$id'>Edit Rule #$id</a>" );
		}
	}
	
	
	public function SaveNewEffect()
	{
		$permission = $this->canUserEdit();
		if ($permission === false) return $this->reportError( "Error: you have no permission to add effects" );
		
		$output = $this->getOutput ();
		$baselink = $this->GetBaseLink ();
		$req = $this->getRequest ();
		
		$id = $this->GetRuleId();
		$input_version = $req->getVal( 'version' );
		$input_statId = $req->getVal( 'statId' );
		$input_value = $req->getVal( 'value' );
		$input_display = $req->getVal( 'display' );
		$input_category = $req->getVal( 'category' );
		$input_combineAs = $req->getVal( 'combineAs' );
		$input_round = $req->getVal( 'round' );
		$input_factorValue = trim($req->getVal( 'factorValue' ));
		$input_statDesc = $req->getVal( 'statDesc' );
		$input_buffId = $req->getVal( 'buffId' );
		$input_regexVar = $req->getVal( 'regexVar' );
		
		$cols = [ ];
		$values = [ ];
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
		
		$values[] = "'" . $this->db->real_escape_string( $id ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_version ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_statId ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_value ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_display ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_category ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_combineAs ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_round ) . "'";
		
		if ($input_factorValue == '')
			$values[] = "NULL";
		else
			$values[] = "'" . $this->db->real_escape_string( $input_factorValue ) . "'";
		
		$values[] = "'" . $this->db->real_escape_string( $input_statDesc ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_buffId ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_regexVar ) . "'";
		
		$insertResult = $this->InsertQueries ( 'effects', $cols, $values );
		if (!$insertResult) return $this->reportError("Error: Failed to insert record into effects!");
		
		$output->addHTML( "<p>New effect added</p><br>" );
		$output->addHTML( "<a href='$baselink'>Home</a> : " );
		$output->addHTML( "<a href='$baselink/showrules'>Show Rule</a> : " );
		$output->addHTML( "<a href='$baselink/addrule'>Add Rule</a> : " );
		$output->addHTML( "<a href='$baselink/editrule?ruleid=$id'>Edit Rule #$id</a> : " );
		$output->addHTML( "<a href='$baselink/addneweffect?ruleid=$id'>Add Effect to Rule #$id</a>" );
	}
	
	
	public function OutputAddEffectForm()
	{
		$permission = $this->canUserEdit();
		if ($permission === false) return $this->reportError( "Error: you have no permission to add effects" );
		
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$id = $this->GetRuleId();
		
		if (!$this->LoadRule( $id )) return $this->reportError("Error: Failed to load rule #id!");
		
		$output->addHTML( "<a href='$baselink/editrule?ruleid=$id'>Rule #$id</a>" );
		$output->addHTML( "<h3>Adding New Effect For Rule #$id</h3>" );
		$output->addHTML( "<form action='$baselink/saveneweffect?ruleid=$id' method='POST'>" );
		
		$version = $this->rule['version'];
		$this->OutputVersionListHtml( 'version', $version );
		$output->addHTML( "<br/>" );
		
		if (!$this->LoadStatIds()) return $this->reportError("Error: Failed to load statIds!");
		
		$output->addHTML( "<label for='statId'>Stat Id</label>" );
		$output->addHTML( "<input list='statIds' id='statId' name='statId'>" );
		$output->addHTML( "<datalist id='statIds' name='statId'>" );
		
		foreach ( $this->statIds as $id )
		{
			$statIdValue = $this->escapeHtml( $id ['statId'] );
			$output->addHTML( "<option value='$statIdValue'>$statIdValue</option>" );
		}
		
		$output->addHTML( "</datalist><br />" );
		
		$output->addHTML( "<label for='value'>Value</label>" );
		$output->addHTML( "<input type='text' id='value' name='value'><br>" );
		$output->addHTML( "<label for='display'>Display</label>" );
		$output->addHTML( "<input type='text' id='display' name='display'><br>" );
		$output->addHTML( "<label for='category'>Category</label>" );
		$output->addHTML( "<input type='text' id='category' name='category'><br>" );
		$output->addHTML( "<label for='combineAs'>Combine As</label>" );
		$output->addHTML( "<input type='text' id='combineAs' name='combineAs'><br>" );
		
		$this->OutputRoundsListHtml( 'round', '' );
		
		$output->addHTML( "<label for='factorValue'>Factor Value</label>" );
		$output->addHTML( "<input type='text' id='factorValue' name='factorValue'><br>" );
		$output->addHTML( "<label for='statDesc'>Stat Desc</label>" );
		$output->addHTML( "<input type='text' id='statDesc' name='statDesc'><br>" );
		
		$this->OutputBuffListHtml('buffId', '');
		
		$output->addHTML( "<br/><label for='regexVar'>Regex Variable</label>" );
		$output->addHTML( "<input type='text' id='regexVar' name='regexVar'>" );
		$output->addHTML( "<p class='errorMsg'></p>" );
		
		$output->addHTML( "<br><input type='submit' value='Save Effect' class='submit_btn'>" );
		
		$output->addHTML( "</form>" );
	}
	
	
	public function LoadEffect($effectId)
	{
		$effectId = $this->db->real_escape_string( $effectId );
		$query = "SELECT * FROM effects WHERE id = '$effectId';";
		$effects_result = $this->db->query( $query );
		
		if ($effects_result === false) {
			return $this->reportError( "Error: failed to load effect from database" );
		}
		
		$row = [ ];
		$row[] = $effects_result->fetch_assoc ();
		$this->effect = $row[0];
		
		return true;
	}
	
	
	public function LoadBuffIds()
	{
		if ($this->hasLoadedBuffIds) return true;
		
		$query = "SELECT DISTINCT nameId FROM rules where ruleType='buff';";
		
		$result = $this->db->query( $query );
		if ($result === false) return $this->reportError( "Error: failed to load buffs from database" );
		
		$this->buffIds = [];
		
		while ( $data = $result->fetch_assoc() )
		{
			$this->buffIds[] = $data;
		}
		
		sort($this->buffIds);
		
		$this->hasLoadedBuffIds = false;
		return true;
	}
	
	
	public function OutputEditEffectForm()
	{
		//$permission = $this->canUserEdit();
		//if ($permission === false) return $this->reportError( "Error: you have no permission to edit effects" );
		
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$req = $this->getRequest();
		
		$effectId = $req->getVal( 'effectid' );
		$ruleId = $this->GetRuleId();
		
		if (!$this->LoadEffect( $effectId )) return $this->reportError("Error: Failed to load effect #$effectId!");
		
		$version = $this->escapeHtml( $this->effect['version'] );
		$statId = $this->escapeHtml( $this->effect['statId'] );
		$value = $this->escapeHtml( $this->effect['value'] );
		$display = $this->escapeHtml( $this->effect['display'] );
		$category = $this->escapeHtml( $this->effect['category'] );
		$combineAs = $this->escapeHtml( $this->effect['combineAs'] );
		$round = $this->escapeHtml( $this->effect['roundNum'] );
		$factorValue = $this->escapeHtml( $this->effect['factorValue'] );
		$statDesc = $this->escapeHtml( $this->effect['statDesc'] );
		$buffId = $this->escapeHtml( $this->effect['buffId'] );
		$regexVar = $this->escapeHtml( $this->effect['regexVar'] );
		
		$output->addHTML( "<a href='$baselink'>Home : </a>" );
		$output->addHTML( "<a href='$baselink/showrules'>Show Rules : </a>" );
		$output->addHTML( "<a href='$baselink/addrule'>Add Rule : </a>" );
		$output->addHTML( "<a href='$baselink/editrule?ruleid=$ruleId'>Rule #$ruleId</a><br>" );
		$output->addHTML( "<h3>Editing Effect #$effectId for Rule #$ruleId</h3>" );
		$output->addHTML( "<form action='$baselink/saveediteffectform?effectid=$effectId&ruleid=$ruleId' method='POST'>" );
		
		$this->OutputVersionListHtml( 'edit_version', $version );
		$output->addHTML( "<br/>" );
		
		if (!$this->LoadStatIds()) return $this->reportError("Error: Failed to load statIds!");
		
		$output->addHTML( "<label for='edit_statId'>Stat Id</label>" );
		$output->addHTML( "<input list='edit_statIds' id='edit_statId' name='edit_statId' value='$statId'>" );
		$output->addHTML( "<datalist id='edit_statIds' name='edit_statId'>" );
		
		foreach ( $this->statIds as $id )
		{
			$statIdValue = $this->escapeHtml( $id['statId'] );
			$output->addHTML( "<option value='$statIdValue'>$statIdValue</option>" );
		}
		$output->addHTML( "</datalist><br />" );
		
		$output->addHTML( "<label for='edit_value'>Value</label>" );
		$output->addHTML( "<input type='text' id='edit_value' name='edit_value' value='$value'><br>" );
		$output->addHTML( "<label for='edit_display'>Display</label>" );
		$output->addHTML( "<input type='text' id='edit_display' name='edit_display' value='$display'><br>" );
		$output->addHTML( "<label for='edit_category'>Category</label>" );
		$output->addHTML( "<input type='text' id='edit_category' name='edit_category' value='$category'><br>" );
		$output->addHTML( "<label for='edit_combineAs'>CombineAs</label>" );
		$output->addHTML( "<input type='text' id='edit_combineAs' name='edit_combineAs' value='$combineAs'><br>" );
		
		$this->OutputRoundsListHtml( 'edit_round', $round );
		
		$output->addHTML( "<label for='edit_factorValue'>Factor Value</label>" );
		$output->addHTML( "<input type='text' id='edit_factorValue' name='edit_factorValue' value='$factorValue'><br>" );
		$output->addHTML( "<label for='edit_statDesc'>Stat Desc</label>" );
		$output->addHTML( "<input type='text' id='edit_statDesc' name='edit_statDesc' value='$statDesc'><br>" );
		
		$this->OutputBuffListHtml('edit_buffId', $this->effect['buffId']);
		
		$output->addHTML( "<br/><label for='edit_regexVar'>Regex Variable</label>" );
		$output->addHTML( "<input type='text' id='edit_regexVar' name='edit_regexVar' value='$regexVar'>" );
		$output->addHTML( "<p class='errorMsg'></p>" );
		
		$output->addHTML( "<br><input type='submit' value='Save Effect' class='submit_btn'>" );
		$output->addHTML( "</form><br>" );
	}
	
	
	public function SaveEditEffectForm()
	{
		$permission = $this->canUserEdit();
		if ($permission === false) return $this->reportError( "Error: you have no permission to edit effects" );
		
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$req = $this->getRequest();
		
		$ruleId = $this->GetRuleId();
		$effectId = $req->getVal( 'effectid' );
		if ($effectId <= 0) return $this->reportError( "Error: invalid effect ID" );
		
		$new_version = $req->getVal( 'edit_version' );
		$new_statId = $req->getVal( 'edit_statId' );
		$new_value = $req->getVal( 'edit_value' );
		$new_display = $req->getVal( 'edit_display' );
		$new_category = $req->getVal( 'edit_category' );
		$new_combineAs = $req->getVal( 'edit_combineAs' );
		$new_round = $req->getVal( 'edit_round' );
		$new_factorValue = trim($req->getVal( 'edit_factorValue' ));
		$new_statDesc = $req->getVal( 'edit_statDesc' );
		$new_buffId = $req->getVal( 'edit_buffId' );
		$new_regexVar = $req->getVal( 'edit_regexVar' );
		
		$values = [ ];
		
		$values[] = "version='" . $this->db->real_escape_string( $new_version ) . "'";
		$values[] = "statId='" . $this->db->real_escape_string( $new_statId ) . "'";
		$values[] = "value='" . $this->db->real_escape_string( $new_value ) . "'";
		$values[] = "display='" . $this->db->real_escape_string( $new_display ) . "'";
		$values[] = "category='" . $this->db->real_escape_string( $new_category ) . "'";
		$values[] = "combineAs='" . $this->db->real_escape_string( $new_combineAs ) . "'";
		$values[] = "roundNum='" . $this->db->real_escape_string( $new_round ) . "'";
		
		if ($new_factorValue == '')
			$values[] = "factorValue=NULL";
		else
			$values[] = "factorValue='" . $this->db->real_escape_string( $new_factorValue ) . "'";
		
		$values[] = "statDesc='" . $this->db->real_escape_string( $new_statDesc ) . "'";
		$values[] = "buffId='" . $this->db->real_escape_string( $new_buffId ) . "'";
		$values[] = "regexVar='" . $this->db->real_escape_string( $new_regexVar ) . "'";
		
		$effectId = $this->db->real_escape_string( $effectId );
		
		$updateResult = $this->UpdateQueries ( 'effects', $values, 'id', $effectId );
		if (!$updateResult) return $this->reportError("Error: Failed to save effect record!");
		
		$output->addHTML( "<p>Successfully saved effect #$effectId!</p><br>" );
		$output->addHTML( "<a href='$baselink'>Home</a> : <a href='$baselink/showrules'>Show Rules</a> : <a href='$baselink/addrule'>Add Rule</a> : <a href='$baselink/editrule?ruleid=$ruleId'>Edit Rule #$ruleId</a> <br>" );
	}
	
	
	public function GetEffectId()
	{
		$req = $this->getRequest();
		$effectId = $req->getVal( 'effectid' );
		return $effectId;
	}
	
	
	public function LoadComputedStats($version = '')
	{
		if ($version == '')
		{
			$query = "SELECT * FROM computedStats;";
		}
		else
		{
			$safeVersion = $this->db->real_escape_string($version);
			$query = "SELECT * FROM computedStats WHERE version='$safeVersion';";
		}
		
		$computedStats_result = $this->db->query( $query );
		
		if ($computedStats_result === false) {
			return $this->reportError( "Error: failed to load computed Stats from database" );
		}
		
		$this->computedStatsDatas = [];
		
		while ( $row = $computedStats_result->fetch_assoc() )
		{
			$this->computedStatsDatas[] = $row;
		}
		
		return true;
	}
	
	
	public function OutputShowComputedStatsTable()
	{
		if (!$this->LoadFilteredStats()) return $this->reportError("Error: Failed to load computed stats!");
		
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		
		$output->addHTML( "<a href='$baselink'>Home</a> : <a href='$baselink/addcomputedstat'>Add New Stat</a>" );
		
		if ($this->hasFilteredStats)
			$output->addHTML( "<h3>Showing Matching Computed Stats</h3>" );
		else
			$output->addHTML( "<h3>Showing All Computed Stats</h3>" );
		
		$this->OutputShowStatsFilterForm();
		
		$output->addHTML( "<table class='wikitable sortable jquery-tablesorter' id='computedStats'><thead>" );
		
		$output->addHTML( "<tr>" );
		$output->addHTML( "<th>Edit</th>" );
		$output->addHTML( "<th>Stat Id</th>" );
		$output->addHTML( "<th>Version</th>" );
		$output->addHTML( "<th>Round</th>" );
		$output->addHTML( "<th>Title</th>" );
		$output->addHTML( "<th>Class</th>" );
		$output->addHTML( "<th>Comment</th>" );
		$output->addHTML( "<th>Min Value</th>" );
		$output->addHTML( "<th>Max Value</th>" );
		$output->addHTML( "<th>Defer Level</th>" );
		$output->addHTML( "<th>Display</th>" );
		$output->addHTML( "<th>Compute</th>" );
		$output->addHTML( "<th>Index</th>" );
		$output->addHTML( "<th>Category</th>" );
		$output->addHTML( "<th>Suffix</th>" );
		$output->addHTML( "<th>Depends On</th>" );
		$output->addHTML( "<th>Delete</th>" );
		$output->addHTML( "</tr></thead><tbody>" );
		
		foreach ( $this->computedStatsDatas as $computedStatsData )
		{
			$id = $this->escapeHtml( $computedStatsData['id'] );
			$statId = $this->escapeHtml( $computedStatsData['statId'] );
			$version = $this->escapeHtml( $computedStatsData['version'] );
			$roundNum = $this->escapeHtml( $computedStatsData['roundNum'] );
			$title = $this->escapeHtml( $computedStatsData['title'] );
			$addClass = $this->escapeHtml( $computedStatsData['addClass'] );
			$comment = $this->escapeHtml( $computedStatsData['comment'] );
			$minimumValue = $this->escapeHtml( $computedStatsData['minimumValue'] );
			$maximumValue = $this->escapeHtml( $computedStatsData['maximumValue'] );
			$deferLevel = $this->escapeHtml( $computedStatsData['deferLevel'] );
			$display = $this->escapeHtml( $computedStatsData['display'] );
			$idx = $this->escapeHtml( $computedStatsData['idx'] );
			$category = $this->escapeHtml( $computedStatsData['category'] );
			$suffix = $this->escapeHtml( $computedStatsData['suffix'] );
			
			if ($computedStatsData['compute'] == '')
			{
				$data = [ ];
			}
			else
			{
				$data = json_decode( $computedStatsData['compute'], true );
				if ($data == null) $data = [];	//TODO: Error handling?
				if (!is_array($data)) $data = ['Error: Not Array!', $computedStatsData['compute']];
				
				foreach ($data as $i => $d)
				{
					$data[$i] = trim($d);
				}
			
			array_filter($data, 'strlen');
			}
			
			$computedStatsData['compute'] = $data;
			
			if ($computedStatsData['dependsOn'] == '')
			{
				$datas = [ ];
			} else
			{
				$datas = json_decode ( $computedStatsData['dependsOn'], true );
				if ($datas == null) $datas = [];	//TODO: Error handling?
				if (!is_array($datas)) $datas = ['Error: Not Array!', $computedStatsData['dependsOn']];
				
				foreach ($datas as $i => $d)
				{
					$datas[$i] = trim($d);
				}
			
				array_filter($datas, 'strlen');
			}
			
			$computedStatsData['dependsOn'] = $datas;
			
			$output->addHTML( "<tr>" );
			$output->addHTML( "<td><a href='$baselink/editcomputedstat?statid=$id'>Edit</a></td>" );
			$output->addHTML( "<td>$statId</td>" );
			$output->addHTML( "<td>$version</td>" );
			$output->addHTML( "<td>$roundNum</td>" );
			$output->addHTML( "<td>$title</td>" );
			$output->addHTML( "<td>$addClass</td>" );
			$output->addHTML( "<td>$comment</td>" );
			$output->addHTML( "<td>$minimumValue</td>" );
			$output->addHTML( "<td>$maximumValue</td>" );
			$output->addHTML( "<td>$deferLevel</td>" );
			$output->addHTML( "<td>$display</td>" );
			
			$output->addHTML( "<td>" );
			
			foreach ( $computedStatsData['compute'] as $key => $val )
			{
				$output->addHTML( "$val <br />" );
			}
			
			$output->addHTML( "</td>" );
			
			$output->addHTML( "<td>$idx</td>" );
			$output->addHTML( "<td>$category</td>" );
			$output->addHTML( "<td>$suffix</td>" );
			
			$output->addHTML( "<td>" );
			
			foreach ( $computedStatsData['dependsOn'] as $key => $val )
			{
				$output->addHTML( "$val <br />" );
			}
			
			$output->addHTML( "</td>" );
			
			$output->addHTML( "<td><a href='$baselink/deletcomputedstat?statid=$id'>Delete</a></td>" );
		}
		
		$output->addHTML( "</table>" );
	}
	
	
	public function OutputAddComputedStatsForm()
	{
		$permission = $this->canUserEdit();
		if ($permission === false) return $this->reportError( "Error: you have no permission to add computed stats" );
		
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		
		$output->addHTML( "<h3>Adding New Computed Stat</h3>" );
		$output->addHTML( "<form action='$baselink/savenewcomputedstat' method='POST'>" );
		
		$output->addHTML( "<label title='Should be a unique name composed of letters.' for='statId'>Stat Id</label>" );
		$output->addHTML( "<input type='text' id='statId' name='statId'><br>" );
		
		$this->OutputVersionListHtml( 'version', strval(GetEsoUpdateVersion()) );
		$output->addHTML( "<br/>" );
		$this->OutputRoundsListHtml( 'round', '' );
		
		$output->addHTML( "<label title='Optional CSS class name added to the stat block.' for='addClass'>Class</label>" );
		$output->addHTML( "<input type='text' id='addClass' name='addClass'><br>" );
		$output->addHTML( "<label title='Note added to the detail stat popup.' for='comment'>Comment</label>" );
		$output->addHTML( "<input type='text' id='comment' name='comment'><br>" );
		$output->addHTML( "<label title='Displayed stat name.' for='edit_title'>Title</label>" );
		$output->addHTML( "<input type='text' id='edit_title' name='edit_title' value='$title' size='60'><br>" );
		$output->addHTML( "<label title='Optional minimum value for the stat.' for='minimumValue'>Min Value</label>" );
		$output->addHTML( "<input type='number' id='minimumValue' name='minimumValue'><br>" );
		$output->addHTML( "<label title='Optional maximum value for the stat.' for='maximumValue'>Max Value</label>" );
		$output->addHTML( "<input type='number' id='maximumValue' name='maximumValue'><br>" );
		$output->addHTML( "<label title='Optional integer used for deferring calculation of the stat (most stats have a value of 0, higher values defer for longer).' for='deferLevel'>Defer Level</label>" );
		$output->addHTML( "<input type='text' id='deferLevel' name='deferLevel'><br>" );
		$output->addHTML( "<label title='Change how the stat value is displayed (%, %2, round2, resist, critresist, elementresist, ...).' for='display'>Display</label>" );
		$output->addHTML( "<input type='text' id='display' name='display'><br>" );
		
		$output->addHTML( "<label title='Calculation formula for the stat in postfix form (one entry per line, each line can be a normal infix calculation as well).' for='compute'>Compute</label>" );
		$output->addHTML( "<textarea id='compute' name='compute' class='txtArea' rows='15' cols='50'></textarea><br>" );
		
		$output->addHTML( "<label title='Integer value that orders the stats in the list when output (starting at 1).' for='idx'>Index</label>" );
		$output->addHTML( "<input type='text' id='idx' name='idx'><br>" );
		
		$output->addHTML( "<label title='Which category to display the stat in.' for='category'>Category</label>" );
		$this->OutputListHtml( '', $this->COMPUTED_STAT_CATEGORIES, 'category' );
		$output->addHTML( "<br/>" );
		
		$output->addHTML( "<label title='Optional suffix to append to the stat value.' for='suffix'>Suffix</label>" );
		$output->addHTML( "<input type='text' id='suffix' name='suffix'><br>" );
		$output->addHTML( "<label title='Optional list of stats that this stat depends on (one stat per line).' for='dependsOn'>Depends On</label>" );
		$output->addHTML( "<textarea id='dependsOn' name='dependsOn' class='txtArea' rows='4' cols='50'></textarea><br>" );
		
		$output->addHTML( "<br><input type='submit' value='Save Stat'>" );
		$output->addHTML( "</form>" );
	}
	
	
	public function LoadStatIds()
	{
		$query = "SELECT DISTINCT statId FROM computedStats;";
		$result = $this->db->query( $query );
		
		if ($result === false) {
			return $this->reportError( "Error: failed to load stat IDs from database" );
		}
		
		$this->statIds = [ ];
		
		while ( $row = $result->fetch_assoc() )
		{
			$this->statIds[] = $row;
		}
		
		return true;
	}
	
	
	public function SaveNewComputedStat()
	{
		$permission = $this->canUserEdit();
		if ($permission === false) return $this->reportError( "Error: you have no permission to add computed stats" );
		
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$req = $this->getRequest();
		
		$input_statId = $req->getVal( 'statId' );
		
		if (!$this->LoadStatIds()) return $this->reportError("Error: Failed to load statIds!");
		
		foreach ( $this->statIds as $id )
		{
			$usedId = $this->escapeHtml( $id ['statId'] );
			if ($input_statId === $usedId) return $this->reportError( "Error: statId '$input_statId' is already used" );
		}
		
		$input_version = $req->getVal( 'version' );
		$input_roundNum = $req->getVal( 'round' );
		$input_addClass = $req->getVal( 'addClass' );
		$input_comment = $req->getVal( 'comment' );
		$input_title = $req->getVal( 'title' );
		$input_minimumValue = trim($req->getVal( 'minimumValue' ));
		$input_maximumValue = trim($req->getVal( 'maximumValue' ));
		$input_deferLevel = trim($req->getVal( 'deferLevel' ));
		$input_display = $req->getVal( 'display' );
		
		$compute = $req->getVal( 'compute' );
		$compute = trim($compute);
		$compute_strings = explode ( "\r\n", $compute );
		$trimedStrings = array_map ( 'trim', $compute_strings );
		$input_compute = json_encode ( $trimedStrings );
		
		$input_idx = $req->getVal( 'idx' );
		$input_category = $req->getVal( 'category' );
		$input_suffix = $req->getVal( 'suffix' );
		
			// TODO: Put into function?
		$dependsOn = $req->getVal( 'dependsOn' );
		$dependsOn = trim($dependsOn);
		$dependsOn_strings = explode ( "\r\n", $dependsOn );
		$dependsOn_strings = array_map( 'trim', $dependsOn_strings );
		array_filter($dependsOn_strings);
		$input_dependsOn = json_encode ( $dependsOn_strings );
		
		$cols = [ ];
		$values = [ ];
		$cols[] = 'statId';
		$cols[] = 'title';
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
		
		$values[] = "'" . $this->db->real_escape_string( $input_statId ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_title ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_version ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_roundNum ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_addClass ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_comment ) . "'";
		
		if ($input_minimumValue == '')
			$values[] = "NULL";
		else
			$values[] = "'" . $this->db->real_escape_string( $input_minimumValue ) . "'";
		
		if ($input_maximumValue == '')
			$values[] = "NULL";
		else
			$values[] = "'" . $this->db->real_escape_string( $input_maximumValue ) . "'";
		
		if ($input_deferLevel == '')
			$values[] = "NULL";
		else
			$values[] = "'" . $this->db->real_escape_string( $input_deferLevel ) . "'";
		
		$values[] = "'" . $this->db->real_escape_string( $input_display ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_compute ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_idx ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_category ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_suffix ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $input_dependsOn ) . "'";
		
		$insertResult = $this->InsertQueries ( 'computedStats', $cols, $values );
		if (!$insertResult) return $this->reportError("Error: Failed to insert record into computedStats!");
		
		$output->addHTML( "<a href='$baselink'>Home</a><br/>" );
		$output->addHTML( "<p>New computed Stat added</p><br/>" );
	}
	
	
	public function LoadComputedStat($primaryKey)
	{
		$primaryKey = $this->db->real_escape_string( $primaryKey );
		$query = "SELECT * FROM computedStats WHERE id='$primaryKey';";
		$computedStats_result = $this->db->query( $query );
		
		if ($computedStats_result === false) {
			return $this->reportError( "Error: failed to load computed Stat from database" );
		}
		
		$row = [ ];
		$row[] = $computedStats_result->fetch_assoc();
		$this->computedStat = $row[0];
		
		return true;
	}
	
	
	public function OutputEditComputedStatForm()
	{
		//$permission = $this->canUserEdit();
		//if ($permission === false) return $this->reportError( "Error: you have no permission to edit computed stats" );
		
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$req = $this->getRequest();
		
		$id = $req->getVal( 'statid' );
		
		if (!$this->LoadComputedStat( $id )) return $this->reportError("Error: Failed to load computed stat!");
		
		$statId = $this->escapeHtml( $this->computedStat['statId'] );
		$version = $this->escapeHtml( $this->computedStat['version'] );
		$round = $this->escapeHtml( $this->computedStat['roundNum'] );
		$addClass = $this->escapeHtml( $this->computedStat['addClass'] );
		$comment = $this->escapeHtml( $this->computedStat['comment'] );
		$minimumValue = $this->escapeHtml( $this->computedStat['minimumValue'] );
		$maximumValue = $this->escapeHtml( $this->computedStat['maximumValue'] );
		$deferLevel = $this->escapeHtml( $this->computedStat['deferLevel'] );
		$display = $this->escapeHtml( $this->computedStat['display'] );
		$idx = $this->escapeHtml( $this->computedStat['idx'] );
		$category = $this->escapeHtml( $this->computedStat['category'] );
		$suffix = $this->escapeHtml( $this->computedStat['suffix'] );
		$title = $this->escapeHtml( $this->computedStat['title'] );
		
		if ($this->computedStat['compute'] == '')
		{
			$data = [ ];
		} else
		{
			$data = json_decode( $this->computedStat['compute'], true );
			if ($data == null) $data = [];	//TODO: Error handling?
			if (!is_array($data)) $data = ['Error: Not Array!', $this->computedStat['compute']];
			
			foreach ($data as $i => $d)
			{
				$data[$i] = trim($d);
				if ($data[$i] == '') unset($data[$i]);
			}
		}
		
		$this->computedStat['compute'] = $data;
		
		if ($this->computedStat['dependsOn'] == '')
		{
			$data = [ ];
		} else
		{
			$data = json_decode( $this->computedStat['dependsOn'], true );
			if ($data == null) $data = [];	//TODO: Error handling?
			if (!is_array($data)) $data = ['Error: Not Array!', $computedStatsData['dependsOn']];
			
			foreach ($data as $i => $d)
			{
				$data[$i] = trim($d);
			}
			
			array_filter($data, 'strlen');
		}
		
		$this->computedStat['dependsOn'] = $data;
		
		$output->addHTML( "<a href='$baselink/showcomputedstats'>Show Computed Stats</a><br>" );
		$output->addHTML( "<h3>Editing Computed Stat: $statId (#$id)</h3>" );
		$output->addHTML( "<form action='$baselink/saveeditcomputedstatsform?statid=$id' method='POST'>" );
		
		$output->addHTML( "<label for='edit_title'>Title</label>" );
		$output->addHTML( "<input type='text' id='edit_title' name='edit_title' value='$title'><br>" );
		
		$this->OutputVersionListHtml( 'edit_version', $version );
		$output->addHTML( "<br/>" );
		$this->OutputRoundsListHtml( 'edit_round', $round );
		
		$output->addHTML( "<label for='edit_addClass'>Class</label>" );
		$output->addHTML( "<input type='text' id='edit_addClass' name='edit_addClass' value='$addClass'><br>" );
		$output->addHTML( "<label for='edit_comment'>Comment</label>" );
		$output->addHTML( "<input type='text' id='edit_comment' name='edit_comment' value='$comment'><br>" );
		$output->addHTML( "<label for='edit_minimumValue'>Min Value</label>" );
		$output->addHTML( "<input type='text' id='edit_minimumValue' name='edit_minimumValue' value='$minimumValue'><br>" );
		$output->addHTML( "<label for='edit_maximumValue'>Max Value</label>" );
		$output->addHTML( "<input type='text' id='edit_maximumValue' name='edit_maximumValue' value='$maximumValue'><br>" );
		$output->addHTML( "<label for='edit_deferLevel'>Defer Level</label>" );
		$output->addHTML( "<input type='text' id='edit_deferLevel' name='edit_deferLevel' value='$deferLevel'><br>" );
		$output->addHTML( "<label for='edit_display'>Display</label>" );
		$output->addHTML( "<input type='text' id='edit_display' name='edit_display' value='$display'><br>" );
		
		$output->addHTML( "<label for='edit_compute'>Compute</label>" );
		$output->addHTML( "<textarea id='edit_compute' name='edit_compute' class='txtArea' rows='15' cols='50'>" );
		
		foreach ( $this->computedStat['compute'] as $key => $val )
		{
			$output->addHTML( "$val \n" );
		}
		$output->addHTML( "</textarea><br>" );
		
		$output->addHTML( "<label for='edit_idx'>Index</label>" );
		$output->addHTML( "<input type='text' id='edit_idx' name='edit_idx' value='$idx'><br>" );
		
		$output->addHTML( "<label for='edit_category'>Category</label>" );
		$this->OutputListHtml( $category, $this->COMPUTED_STAT_CATEGORIES, 'edit_category' );
		$output->addHTML( "<br/>" );
		
		$output->addHTML( "<label for='edit_suffix'>Suffix</label>" );
		$output->addHTML( "<input type='text' id='edit_suffix' name='edit_suffix' value='$suffix'><br>" );
		
		$output->addHTML( "<label for='edit_dependsOn'>Depends On</label>" );
		$output->addHTML( "<textarea id='edit_dependsOn' name='edit_dependsOn' class='txtArea' rows='4' cols='50'>" );
		
		foreach ( $this->computedStat['dependsOn'] as $key => $val ) {
			$output->addHTML( "$val \n" );
		}
		$output->addHTML( "</textarea><br>" );
		
		$output->addHTML( "<br><input class='btn' type='submit' value='Save Stat'>" );
		$output->addHTML( "</form><br>" );
	}
	
	
	public function SaveEditComputedStatsForm()
	{
		$permission = $this->canUserEdit();
		if ($permission === false) return $this->reportError( "Error: you have no permission to edit computed stats" );
		
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$req = $this->getRequest();
		
		$statId = $req->getVal( 'statid' );
		$statId = $this->db->real_escape_string( $statId );
		
		$new_version = $req->getVal( 'edit_version' );
		$new_roundNum = $req->getVal( 'edit_round' );
		$new_addClass = $req->getVal( 'edit_addClass' );
		$new_comment = $req->getVal( 'edit_comment' );
		$new_title = $req->getVal( 'edit_title' );
		$new_minimumValue = trim($req->getVal( 'edit_minimumValue' ));
		$new_maximumValue = trim($req->getVal( 'edit_maximumValue' ));
		$new_deferLevel = trim($req->getVal( 'edit_deferLevel' ));
		$new_display = $req->getVal( 'edit_display' );
		
		$compute = $req->getVal( 'edit_compute' );
		$compute = trim($compute);
		$compute_strings = explode( "\r\n", $compute );
		$trimedStrings = array_map( 'trim', $compute_strings );
		$new_compute = json_encode( $trimedStrings );
		
		$new_idx = $req->getVal( 'edit_idx' );
		$new_category = $req->getVal( 'edit_category' );
		$new_suffix = $req->getVal( 'edit_suffix' );
		
		$dependsOn = $req->getVal( 'edit_dependsOn' );
		$dependsOn = trim($dependsOn);
		$dependsOn_strings = explode( "\r\n", $dependsOn );
		$dependsOn_strings = array_map( 'trim', $dependsOn_strings );
		array_filter($dependsOn_strings);
		$new_dependsOn = json_encode( $dependsOn_strings );
		
		$values = [ ];
		
		$values[] = "version='" . $this->db->real_escape_string( $new_version ) . "'";
		$values[] = "roundNum='" . $this->db->real_escape_string( $new_roundNum ) . "'";
		$values[] = "addClass='" . $this->db->real_escape_string( $new_addClass ) . "'";
		$values[] = "comment='" . $this->db->real_escape_string( $new_comment ) . "'";
		
		if ($new_minimumValue == '')
			$values[] = "minimumValue=NULL";
		else
			$values[] = "minimumValue='" . $this->db->real_escape_string( $new_minimumValue ) . "'";
		
		if ($new_maximumValue == '')
			$values[] = "maximumValue=NULL";
		else
			$values[] = "maximumValue='" . $this->db->real_escape_string( $new_maximumValue ) . "'";
		
		if ($new_deferLevel == '')
			$values[] = "deferLevel=NULL";
		else
			$values[] = "deferLevel='" . $this->db->real_escape_string( $new_deferLevel ) . "'";
		
		$values[] = "display='" . $this->db->real_escape_string( $new_display ) . "'";
		$values[] = "compute='" . $this->db->real_escape_string( $new_compute ) . "'";
		$values[] = "idx='" . $this->db->real_escape_string( $new_idx ) . "'";
		$values[] = "category='" . $this->db->real_escape_string( $new_category ) . "'";
		$values[] = "suffix='" . $this->db->real_escape_string( $new_suffix ) . "'";
		$values[] = "dependsOn='" . $this->db->real_escape_string( $new_dependsOn ) . "'";
		$values[] = "title='" . $this->db->real_escape_string( $new_title ) . "'";
		
		$updateResult = $this->UpdateQueries( 'computedStats', $values, 'id', $statId );
		if (!$updateResult) return $this->reportError("Error: Failed to saved computed stat record!");
		
		$output->addHTML( "<p>Successfully saved computed stat #$statId!</p><br>" );
		$output->addHTML( "<a href='$baselink'>Home</a> : <a href='$baselink/showcomputedstats'>Show Stats</a> <br/>" );
	}
	
	
	public function OutputDeleteComputedStat()
	{
		$permission = $this->canUserEdit();
		if ($permission === false) return $this->reportError( "Error: you have no permission to delete computed stats" );
		
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$req = $this->getRequest();
		
		$id = $req->getVal( 'statid' );
		
		if (!$this->LoadComputedStat( $id )) return $this->reportError( "Error: Failed to load computed stat!" );
		
		$statId = $this->escapeHtml( $statId );
		$version = $this->escapeHtml( $this->computedStat['version'] );
		$roundNum = $this->escapeHtml( $this->computedStat['roundNum'] );
		$addClass = $this->escapeHtml( $this->computedStat['addClass'] );
		$comment = $this->escapeHtml( $this->computedStat['comment'] );
		$minimumValue = $this->escapeHtml( $this->computedStat['minimumValue'] );
		$maximumValue = $this->escapeHtml( $this->computedStat['maximumValue'] );
		$deferLevel = $this->escapeHtml( $this->computedStat['deferLevel'] );
		$display = $this->escapeHtml( $this->computedStat['display'] );
		$compute = $this->escapeHtml( $this->computedStat['compute'] );
		$idx = $this->escapeHtml( $this->computedStat['idx'] );
		$category = $this->escapeHtml( $this->computedStat['category'] );
		$suffix = $this->escapeHtml( $this->computedStat['suffix'] );
		$dependsOn = $this->escapeHtml( $this->computedStat['dependsOn'] );
		
		$output->addHTML( "<h3>Are you sure you want to delete this computed Stat: </h3>" );
		$output->addHTML( "<label><b>Id:</b> $id</label><br>" );
		$output->addHTML( "<label><b>Stat Id:</b> $statId</label><br>" );
		$output->addHTML( "<label><b>Version:</b> $version</label><br>" );
		$output->addHTML( "<label><b>Round:</b> $roundNum</label><br>" );
		$output->addHTML( "<label><b>Class:</b> $addClass</label><br>" );
		$output->addHTML( "<label><b>Comment:</b> $comment</label><br>" );
		$output->addHTML( "<label><b>Min Value:</b> $minimumValue</label><br>" );
		$output->addHTML( "<label><b>Max Value:</b> $maximumValue</label><br>" );
		$output->addHTML( "<label><b>Defer Level:</b> $deferLevel</label><br>" );
		$output->addHTML( "<label><b>Display:</b> $display</label><br>" );
		$output->addHTML( "<label><b>Compute:</b> $compute</label><br>" );
		$output->addHTML( "<label><b>Idx:</b> $idx</label><br>" );
		$output->addHTML( "<label><b>Category:</b> $category</label><br>" );
		$output->addHTML( "<label><b>Suffix:</b> $suffix</label><br>" );
		$output->addHTML( "<label><b>Depends On:</b> $dependsOn</label><br>" );
		
		$output->addHTML( "<br><a href='$baselink/statdeleteconfirm?statid=$id&confirm=True'>Delete </a>" );
		$output->addHTML( "<a href='$baselink/statdeleteconfirm?statid=$id&confirm=false'> Cancel</a>" );
	}
	
	
	public function ConfirmDeleteStat()
	{
		$permission = $this->canUserEdit();
		if ($permission === false) return $this->reportError( "Error: you have no permission to delete computed stats" );
		
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		$req = $this->getRequest();
		
		$confirm = $req->getVal( 'confirm' );
		$id = $req->getVal( 'statid' );
		
		if ($confirm !== 'True')
		{
			$output->addHTML( "<p>Delete cancelled</p><br>" );
			$output->addHTML( "<a href='$baselink'>Home</a>" );
		} else
		{
			if (!$this->LoadComputedStat( $id )) return $this->reportError( "Error: cannot load stat" );
			
			$statId = $this->escapeHtml( $this->computedStat['statId'] );
			$version = $this->escapeHtml( $this->computedStat['version'] );
			$round = $this->escapeHtml( $this->computedStat['roundNum'] );
			$addClass = $this->escapeHtml( $this->computedStat['addClass'] );
			$comment = $this->escapeHtml( $this->computedStat['comment'] );
			$title = $this->escapeHtml( $this->computedStat['title'] );
			$minimumValue = $this->escapeHtml( $this->computedStat['minimumValue'] );
			$maximumValue = $this->escapeHtml( $this->computedStat['maximumValue'] );
			$deferLevel = $this->escapeHtml( $this->computedStat['deferLevel'] );
			$display = $this->escapeHtml( $this->computedStat['display'] );
			$compute = $this->escapeHtml( $this->computedStat['compute'] );
			$idx = $this->escapeHtml( $this->computedStat['idx'] );
			$category = $this->escapeHtml( $this->computedStat['category'] );
			$suffix = $this->escapeHtml( $this->computedStat['suffix'] );
			$dependsOn = $this->escapeHtml( $this->computedStat['dependsOn'] );
			
			$cols = [ ];
			$values = [ ];
			$cols[] = 'id';
			$cols[] = 'statId';
			$cols[] = 'version';
			$cols[] = 'roundNum';
			$cols[] = 'addClass';
			$cols[] = 'comment';
			$cols[] = 'title';
			$cols[] = 'minimumValue';
			$cols[] = 'maximumValue';
			$cols[] = 'deferLevel';
			$cols[] = 'display';
			$cols[] = 'compute';
			$cols[] = 'idx';
			$cols[] = 'category';
			$cols[] = 'suffix';
			$cols[] = 'dependsOn';
			
			$values[] = "'" . $this->db->real_escape_string( $id ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $statId ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $version ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $round ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $addClass ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $comment ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $title ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $minimumValue ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $maximumValue ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $deferLevel ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $display ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $compute ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $idx ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $category ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $suffix ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $dependsOn ) . "'";
			
			$insertResult = $this->InsertQueries ( 'computedStatsArchive', $cols, $values );
			if (!$insertResult) return $this->reportError("Error: Failed to insert record into computedStatsArchive!");
			
			$deleteResult = $this->DeleteQueries ( 'computedStats', 'id', $id );
			if (!$deleteResult) return $this->reportError("Error: Failed to delete record from computedStats!");
			
			$output->addHTML( "<p>computed Stat deleted</p><br>" );
			$output->addHTML( "<a href='$baselink'>Home</a>" );
		}
	}
	
	
	public function CreateDuplicateRuleGroups()
	{
		$groups = [];
		
		foreach ($this->rulesDatas as $ruleId => $rule)
		{
			$version = $rule['version'];
			$ruleType = $rule['ruleType'];
			
			$matchRegex = $rule['matchRegex'];
			if ($matchRegex == '') continue;
			
			$nameId = $rule['nameId'];
			
			$matchRegex = str_replace('(', '', $matchRegex);
			$matchRegex = str_replace(')', '', $matchRegex);
			
			$groups[$version][$ruleType][$matchRegex][] = $rule;
		}
		
		
		return $groups;
	}
	
	
	public function OutputShowTestForm($title = "Test Rules")
	{
		$permission = $this->canUserEdit();
		if ($permission === false) return $this->reportError( "Error: you have no permission to test rules" );
		
		$req = $this->getRequest();
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		
		$version = $req->getVal("version");
		$ruleType = $req->getVal("ruletype");
		
		$output->addHTML( "<a href='$baselink'>Home</a> : <a href='$baselink/showrules'>Show Rules</a> : <a href='$baselink/addrule'>Add Rule</a> <br/>" );
		
		$output->addHTML( "<h3>$title</h3>" );
		$output->addHTML( "<form action='$baselink/testrules' method='GET'>" );
		
		$this->OutputVersionListHtml( 'version', $version, false );
		
		$output->addHTML( "<label for='ruleType'>Rule Type</label>" );
		$this->OutputListHtml( $ruleType, $this->RULE_TYPE_OPTIONS_ANY, 'ruletype' );
		
		$output->addHTML( "<br><input type='submit' value='Test Rules' class='submit_btn'>" );
		$output->addHTML( "</form>" );
	}
	
	
	public function LoadTestSkillData($version, $isPassive)
	{
		$version = preg_replace('/[^0-9a-zA-Z_]/', '', $version);
		if ($this->testSkillData) return $this->testSkillData;
		
		$this->InitLogDatabase();
		
		if (GetEsoItemTableSuffix($version) == '')
			$table = "minedSkills";
		else
			$table = "minedSkills$version";
		
		if ($isPassive)
			$query = "SELECT * FROM `$table` WHERE (isPlayer=1 OR setName!='') AND isPassive='1' AND (rank='1' OR rank='2');";
		else
			$query = "SELECT * FROM `$table` WHERE (isPlayer=1 OR setName!='') AND isPassive='0' AND (rank='1' OR rank='5' OR rank='9');";
		
		$result = $this->logdb->query($query);
		if ($result === false) return null;
		
		$skillData = [];
		
		while ($row = $result->fetch_assoc())
		{
			$abilityId = intval($row['id']);
			
			$skillData[$abilityId] = $row;
		}
		
		$this->InitTestSkillMatchData($skillData);
		
		$count = count($skillData);
		$this->testSkillData = $skillData;
		return $this->testSkillData;
	}
	
	
	public function LoadTestCpData($version)
	{
		$version = preg_replace('/[^0-9a-zA-Z_]/', '', $version);
		if ($this->testCpData) return $this->testCpData;
		
		$this->InitLogDatabase();
		
		if (GetEsoItemTableSuffix($version) == '')
			$table = "cp2Skills";
		else
			$table = "cp2Skills$version";
		
		$query = "SELECT * FROM `$table`;";
		$result = $this->logdb->query($query);
		if ($result === false) return null;
		
		$cpData = [];
		
		while ($row = $result->fetch_assoc())
		{
			$id = intval($row['skillId']);
			
			$cpData[$id] = $row;
		}
		
		$this->InitTestCpMatchData($cpData);
		
		//$count = count($cpData);
		//error_log("Loaded $count CP Data");
		
		$this->testCpData = $cpData;
		return $this->testCpData;
	}
	
	
	public function LoadTestSetData($version)
	{
		if ($this->testSetData) return $this->testSetData;
		
		$version = preg_replace('/[^0-9a-zA-Z_]/', '', $version);
		//if ($version == 'test') $version = strval(GetEsoUpdateVersion());
		
		$this->InitLogDatabase();
		
		if (GetEsoItemTableSuffix($version) == '')
			$table = "setSummary";
		else
			$table = "setSummary$version";
		
		#error_log("Loading test set data from $table!");
		
		$query = "SELECT * FROM `$table`;";
		$result = $this->logdb->query($query);
		if ($result === false) return null;
		
		$setData = [];
		
		while ($row = $result->fetch_assoc())
		{
			$setName = strtolower($row['setName']);
			$row['rawSetBonusDesc'] = preg_replace('/([0-9]+)-([0-9]+)/', '\2', $row['setBonusDesc']);
			
			$setData[$setName] = $row;
		}
		
		$this->InitTestSetMatchData($setData);
		
		$this->testSetData = $setData;
		return $this->testSetData;
	}
	
	
	public function LoadTestItemData($version)
	{
		$version = preg_replace('/[^0-9a-zA-Z_]/', '', $version);
		if ($this->testItemData) return $this->testItemData;
		
		$this->InitLogDatabase();
		
		if (GetEsoItemTableSuffix($version) == '')
			$table = "minedItemSummary";
		else
			$table = "minedItemSummary$version";
		
		$query = "SELECT * FROM `$table`;";
		$result = $this->logdb->query($query);
		if ($result === false) return null;
		
		$itemData = [];
		
		while ($row = $result->fetch_assoc())
		{
			$itemId = intval($row['itemId']);
			
			$itemData[$itemId] = $row;
		}
		
		$this->InitTestItemMatchData($itemData);
		
		$this->testItemData = $itemData;
		return $this->testItemData;
	}
	
	
	public function InitTestCpMatchData($cpDatas)
	{
		
		foreach ($cpDatas as $cpData)
		{
			$desc = $cpData["minDescription"];
			if ($desc == "") continue;
			
			$id = intval($cpData['skillId']);
			$this->testMatchData['cp'][$id] = [];
		}
	}
	
	
	public function InitTestSkillMatchData($skillDatas)
	{
		
		foreach ($skillDatas as $skillData)
		{
			$desc = $skillData["description"];
			if ($desc == "") continue;
			
			$abilityId = intval($skillData['id']);
			$this->testMatchData['skill'][$abilityId] = [];
		}
	}
	
	
	public function InitTestSetMatchData($setDatas)
	{
		
		foreach ($setDatas as $setData)
		{
			for ($i = 1; $i <= 12; ++$i)
			{
				$desc = $setData["setBonusDesc$i"];
				if ($desc == "") continue;
				
				$this->testMatchData['set'][$setData['setName']][$i] = [];
			}
		}
	}
	
	
	public function InitTestItemMatchData($itemDatas)
	{
		
		foreach ($itemDatas as $itemId => $itemData)
		{
			//$traitDesc = $setData["description"];
			$abilityDesc = $itemData["abilityDesc"];
			if ($abilityDesc == "") continue;
			
			$this->testMatchData['item'][$itemId] = [];
		}
	}
	
	
	public function TestCpRule($rule)
	{
		$errors = [];
		$matchedCps = [];
		$output = $this->getOutput();
		
		$cpDatas = $this->LoadTestCpData($rule['version']);
		if ($cpDatas == null) return [ 'errorsMsg' => "Failed to load CP data!" ];
		
		if ($rule['matchRegex'] == null || $rule['matchRegex'] == "") $errors[] = "Missing match regex!";
		
		foreach ($cpDatas as $id => $cpData)
		{
			$desc = trim($cpData['minDescription']);
			if ($desc == "") continue;
			
			$desc = str_replace("\n", " ", $desc);
			$desc = str_replace("\r", " ", $desc);
			$desc = FormatRemoveEsoItemDescriptionText($desc);
			
			//error_log("$desc");
			
			$result = preg_match($rule['matchRegex'], $desc, $matches);
			if (!$result) continue;
			
			$newData = [];
			$newData['rule'] = $rule;
			$newData['cp'] = $cpData;
			
			$this->testMatchData['cp'][$id][] = $newData;
			$matchedCps[] = $cpData;
		}
		
		if (count($matchedCps) == 0)
		{
			$safeRegex = $this->escapeHtml($rule['matchRegex']);
			$errors[] = "Regex doesn't match any CPs!<br/>$safeRegex";
		}
		
		return [ 'errors' => $errors, 'matchedCps' => $matchedCps ];
	}
	
	
	public function TestSetRule($rule)
	{
		$errors = [];
		$output = $this->getOutput();
		
		$setDatas = $this->LoadTestSetData($rule['version']);
		if ($setDatas == null) return [ 'errorsMsg' => "Failed to load set data!" ];
		
		if ($rule['matchRegex'] == null || $rule['matchRegex'] == "") $errors[] = "Missing match regex!";
		
		$matchedSets = [];
		$nameId = strtolower($rule['nameId']);
		$originalId = strtolower($rule['originalId']);
		if ($originalId != '') $nameId = $originalId;
		
		$customData = json_decode( $rule['customData'], true );
		if ($customData == null) $customData = []; // TODO: Error handling?
		if (!is_array($customData)) $customData = [];
		
		foreach ($setDatas as $setName => $setData)
		{
			$isMatched = false;
			
			if ($nameId != '' && $nameId != strtolower($setName)) 
			{
				//error_log("$setName: " . $customData['addPerfected']);
				
				if ($customData['addPerfected'] && substr($setName, 0, 10) == "perfected " && substr($setName, 10) == $nameId)
				{
					// Continue if set is perfected and matches the non-perfected rule set name that adds a perfected match
				}
				else
				{
					continue;
				}
			}
			
			for ($i = 1; $i <= 12; ++$i)
			{
				$desc = preg_replace('/([0-9]+)-([0-9]+)/', '\2', $setData["setBonusDesc$i"]);
				$desc = preg_replace('/\([0-9]+ item[s]*\) /', '', $desc);
				if ($desc == "") continue;
				
				$result = preg_match($rule['matchRegex'], $desc, $matches);
				if (!$result) continue;
				
				$isMatched = true;
				
				$newData = [];
				$newData['rule'] = $rule;
				$newData['set'] = $setData;
				
				$this->testMatchData['set'][$setData['setName']][$i][] = $newData;
			}
			
			if ($isMatched) $matchedSets[] = $setData;
		}
		
		if (count($matchedSets) == 0)
		{
			$safeRegex = $this->escapeHtml($rule['matchRegex']);
			$errors[] = "Regex doesn't match any sets!<br/>$safeRegex";
		}
		else
		{
			//$count = count($matchedSets);
			//$safeRegex = $this->escapeHtml($rule['matchRegex']);
			//$errors[] = "Regex matches $count sets!<br/>$safeRegex";
		}
		
		return [ 'errors' => $errors, 'matchedSets' => $matchedSets ];
	}
	
	
	public function TestSkillRule($rule, $isPassive)
	{
		$errors = [];
		$output = $this->getOutput();
		
		$skillDatas = $this->LoadTestSkillData($rule['version'], $isPassive);
		if ($skillDatas == null) return [ 'errorsMsg' => "Failed to load skill data!" ];
		
		if ($rule['matchRegex'] == null || $rule['matchRegex'] == "") $errors[] = "Missing match regex!";
		
		$matchedSkills = [];
		
		foreach ($skillDatas as $abilityId => $skillData)
		{
			$skillPassive = intval($skillData['isPassive']) != 0;
			if ($isPassive != $skillPassive) continue;
			
			$name = trim($skillData['name']);
			
			if ($rule['isToggle'] && $rule['nameId'] && $rule['matchSkillName'])
			{
				if ($name != $rule['nameId']) continue;
			}
			
			$desc = trim($skillData['description']);
			if ($desc == "") continue;
			
			$abilityDesc = trim($skillData['abilityDesc']);
			if ($abilityDesc) $desc = "$abilityDesc\n$desc";
			
			$header = trim($skillData["descHeader"]);
			if ($header) $desc = "$header\r\n$desc";
			
			$desc = FormatRemoveEsoItemDescriptionText($desc);
			
			//error_log("{$rule['matchRegex']}, $desc");
			//error_log("$desc");
			
			$result = preg_match($rule['matchRegex'], $desc, $matches);
			if (!$result) continue;
			
			//error_log("Matched $abilityId");
			
			$newData = [];
			$newData['rule'] = $rule;
			$newData['isPassive'] = $isPassive;
			$newData['skill'] = $skillData;
			
			$this->testMatchData['skill'][$abilityId][] = $newData;
			$matchedSkills[] = $skillData;
		}
		
		if (count($matchedSkills) == 0)
		{
			$safeRegex = $this->escapeHtml($rule['matchRegex']);
			$errors[] = "Regex doesn't match any skills!<br/>$safeRegex";
		}
		
		return [ 'errors' => $errors, 'matchedSkills' => $matchedSkills ];
	}
	
	
	public function TestAbilityDescRule($rule)
	{
		$errors = [];
		$output = $this->getOutput();
		
		$itemDatas = $this->LoadTestItemData($rule['version']);
		if ($itemDatas == null) return [ 'errorsMsg' => "Failed to load item data!" ];
		
		if ($rule['matchRegex'] == null || $rule['matchRegex'] == "") $errors[] = "Missing match regex!";
		
		$matchedItems = [];
		
		foreach ($itemDatas as $itemId => $itemData)
		{
			$desc = trim($itemData['abilityDesc']);
			if ($desc == "") continue;
			
			$desc = FormatRemoveEsoItemDescriptionText($desc);
			
			$result = preg_match($rule['matchRegex'], $desc, $matches);
			if (!$result) continue;
			
			$newData = [];
			$newData['rule'] = $rule;
			$newData['item'] = $itemData;
			
			$this->testMatchData['item'][$itemId][] = $newData;
			$matchedItems[] = $itemData;
		}
		
		if (count($matchedItems) == 0)
		{
			$safeRegex = $this->escapeHtml($rule['matchRegex']);
			$errors[] = "Regex doesn't match any items!<br/>$safeRegex";
		}
		
		return [ 'errors' => $errors, 'matchedItems' => $matchedItems  ];
	}
	
	
	public function TestBuffRule($rule)
	{
		$errors = [];
		
		if ($rule['nameId'] == null || $rule['nameId'] == "") $errors[] = "Missing nameID!";
		if ($rule['icon'] == null || $rule['icon'] == "") $errors[] = "Missing icon!";
		
		$effectErrors = $this->TestRuleEffects(null, null, $rule);
		if ($effectErrors) $errors = array_merge($errors, $effectErrors);
		
		return [ 'errors' => $errors ];
	}
	
	
	public function TestMundusRule($rule)
	{
		$errors = [];
		
		if ($rule['matchRegex'] == null || $rule['matchRegex'] == "") $errors[] = "Missing match regex!";
		if ($rule['description'] == null || $rule['description'] == "") $errors[] = "Missing description!";
		
		if ($rule['matchRegex'] && $rule['description'])
		{
			$regexErrors = $this->TestRuleRegex($rule['matchRegex'], $rule['description'], $rule);
			if ($regexErrors) $errors = array_merge($errors, $regexErrors);
			
			$effectErrors = $this->TestRuleEffects($rule['matchRegex'], $rule['description'], $rule);
			if ($effectErrors) $errors = array_merge($errors, $effectErrors);
		}
		
		return [ 'errors' => $errors ];
	}
	
	
	public function TestEnchantRule($rule)
	{
		$testResult = [];
		return $testResult;
	}
	
	
	public function TestRuleRegex($regex, $text, $rule)
	{
		if ($regex == null || $regex == '') return [ "Missing regex!" ];
		if ($text == null || $text == '') return [ "Missing text for regex!" ];
		
		$safeText = $this->escapeHtml($text);
		$safeRegex = $this->escapeHtml($regex);
		
		$result = preg_match($regex, $text, $matches);
		if ($result === false) return [ "Invalid regex!<br/>'$safeRegex'" ];
		if ($result === 0) return [ "Regex does not match text!<br/>'$safeRegex' != '$safeText'" ];
		
		return null;
	}
	
	
	public function TestRuleEffects($regex, $text, $rule)
	{
		if ($rule['effects'] === null) return [ "Rule has no effects (NULL)!" ];
		if (count($rule['effects']) == 0) return [ "Rule has no effects!" ];
		
		if ($regex == null || $regex == '')
		{
			$regex = null;
			$matches = null;
		}
		elseif  ($text == null || $text == '')
		{
			return null;
		}
		else
		{
			$result = preg_match($regex, $text, $matches);
			if (!$result) return null;
		}
		
		$errors = [];
		
		foreach ($rule['effects'] as $effect)
		{
			$regexVar = $effect['regexVar'];
			$ruleId = $effect['ruleId'];
			$effectId = $effect['id'];
			$statId = $effect['statId'];
			$buffId = $effect['buffId'];
			$value = $effect['value'];
			
			$effectLink = $this->GetBaseLink() . "/editeffect?effectid=$effectId&ruleid=$ruleId";
			$effectTag = "<a href='$effectLink'>Effect #$effectId</a>";
			
			if ($statId == null || $statId == '') 
			{
				if ($buffId == null || $buffId == '') 
				{
					if ($value == null || $value == '')
					{
						$errors[] = "$effectTag: Missing statId, value, and buffId!";
					}
				}
			}
			
			if ($regexVar == null || $regexVar == '') 
				$regexVar = 1;
			else if (preg_match('/[0-9]+/', $regexVar))
				$regexVar = intval($regexVar);
			
			if ($matches == null)
			{
				if ($value == null || $value == '')
				{
					if ($buffId == null || $buffId == '') $errors[] = "$effectTag: Missing value or buffId for rule with no regex!";
				}
			}
			elseif ($matches[$regexVar] == null)
			{
				$regexVar = $this->escapeHtml($regexVar);
				$errors[] = "$effectTag: Variable '$regexVar' not found in match regex!";
			}
		}
		
		return $errors;
	}
	
	
	public function TestRule($rule)
	{
		switch ($rule['ruleType'])
		{
			case 'cp': return $this->TestCpRule($rule);
			case 'set': return $this->TestSetRule($rule);
			case 'active': return $this->TestSkillRule($rule, false);
			case 'passive': return $this->TestSkillRule($rule, true);
			case 'abilitydesc': return $this->TestAbilityDescRule($rule);
			case 'buff': return $this->TestBuffRule($rule);
			case 'mundus': return $this->TestMundusRule($rule);
			case 'weaponenchant':
			case 'armorenchant':
			case 'offhandenchant':
			case 'offhandweaponenchant':
				return $this->TestEnchantRule($rule, true);
		}
		
		return [ "errorMsg" => "Error: Unknown rule type '{$rule['ruleType']}' found!" ];
	}
	
	
	public function OutputTestRuleResult($result, $rule, $showExtra = false)
	{
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		
		$id = $rule['id'];
		$ruleType = $rule['ruleType'];
		if ($this->RULE_TYPE_OPTIONS[$ruleType]) $ruleType = $this->RULE_TYPE_OPTIONS[$ruleType];
		
		$link = "$baselink/editrule?ruleid=$id";
		$rowHeader = "<tr><td><a href='$link'>$ruleType Rule #$id</a></td><td><ul>\n";
		
		$numErrors = 0;
		
		if ($result['errorMsg'])
		{
			$output->addHTML($rowHeader);
			
			$text = $result['errorMsg'];
			$text = str_replace("\n", "<br/>", $text);
			$output->addHTML("<li>$text</li>\n");
			
			$output->addHTML("</ul></td></tr>\n");
			++$numErrors;
		}
		
		if ($result['errors'])
		{
			$output->addHTML($rowHeader);
			
			foreach ($result['errors'] as $errorMsg)
			{
				$text = $errorMsg;
				$text = str_replace("\n", "<br/>", $text);
				$output->addHTML("<li>$text</li>\n");
			}
			
			$output->addHTML("</ul></td></tr>\n");
			
			$numErrors += count($result['errors']);
		}
		
		if ($result['matchedSets'] && $showExtra) $this->OutputTestRuleSetResult($result, $rule);
		if ($result['matchedSkills'] && $showExtra) $this->OutputTestRuleSkillResult($result, $rule);
		if ($result['matchedCps'] && $showExtra) $this->OutputTestRuleCpResult($result, $rule);
		if ($result['matchedItems'] && $showExtra) $this->OutputTestRuleItemResult($result, $rule);
		
		return $numErrors;
	}
	
	
	public function OutputTestRuleCpResult($result, $rule)
	{
		$matchedCps = $result['matchedCps'];
		$output = $this->getOutput();
		$count = count($matchedCps);
		
		$output->addHTML("<tr><td></td><td>Found $count matching CPs:<ul>\n");
		
		foreach ($matchedCps as $cpData)
		{
			$name = $this->escapeHtml($cpData['name']);
			$id = $cpData['skillId'];
			$desc = $cpData["minDescription"];
			$desc = FormatRemoveEsoItemDescriptionText($desc);
			$desc = $this->escapeHtml($desc);
			
			$output->addHTML("<li>$name ($id) : $desc</li>");
		}
		
		$output->addHTML("</ul></td></tr>");
	}
	
	
	public function OutputTestRuleItemResult($result, $rule)
	{
		$matchedItems = $result['matchedItems'];
		$output = $this->getOutput();
		$count = count($matchedItems);
		
		$output->addHTML("<tr><td></td><td>Found $count matching items!<ul>\n");
		
		foreach ($matchedItems as $itemData)
		{
			$name = $this->escapeHtml($itemData['name']);
			$id = $itemData['itemId'];
			$desc = $itemData["abilityDesc"];
			$desc = FormatRemoveEsoItemDescriptionText($desc);
			$desc = $this->escapeHtml($desc);
			
			$output->addHTML("<li>$name ($id) : $desc</li>");
		}
		
		$output->addHTML("</ul></td></tr>");
	}
	
	
	public function OutputTestRuleSkillResult($result, $rule)
	{
		$matchedSkills = $result['matchedSkills'];
		$output = $this->getOutput();
		$count = count($matchedSkills);
		
		$output->addHTML("<tr><td></td><td>Found $count matching skills:<ul>\n");
		
		foreach ($matchedSkills as $skillData)
		{
			$name = $this->escapeHtml($skillData['name']);
			$abilityId = $skillData['id'];
			$header = trim($skillData['descHeader']);
			$abilityDesc = trim($skillData['abilityDesc']);
			$desc = $skillData["description"];
			if ($abilityDesc) $desc = "$abilityDesc\r\n$desc";
			if ($header) $desc = "$header\r\n$desc";
			$desc = FormatRemoveEsoItemDescriptionText($desc);
			$desc = $this->escapeHtml($desc);
			
			$output->addHTML("<li>$name ($abilityId) : $desc</li>");
		}
		
		$output->addHTML("</ul></td></tr>");
	}
	
	
	public function OutputTestRuleSetResult($result, $rule)
	{
		$matchedSets = $result['matchedSets'];
		$output = $this->getOutput();
		$count = count($matchedSets);
		
		$output->addHTML("<tr><td></td><td>Found $count matching sets:<ul>\n");
		
		foreach ($matchedSets as $setData)
		{
			$setName = $this->escapeHtml($setData['setName']);
			$output->addHTML("<li>$setName");
			
			for ($i = 1; $i <= 12; ++$i)
			{
				$desc = preg_replace('/([0-9]+)-([0-9]+)/', '\2', $setData["setBonusDesc$i"]);
				$desc = preg_replace('/\([0-9]+ item[s]*\) /', '', $desc);
				if ($desc == "") continue;
				
				$result = preg_match($rule['matchRegex'], $desc, $matches);
				if (!$result) continue;
				
				$desc = $this->escapeHtml($setData["setBonusDesc$i"]);
				$output->addHTML("<pre>$desc</pre>");
			}
			
			$output->addHTML("</li>");
		}
		
		$output->addHTML("</ul></td></tr>");
	}
	
	
	public function OutputDoSaveCopyRule()
	{
		$req = $this->getRequest();
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		
		$permission = $this->canUserEdit();
		if ($permission === false) return $this->reportError( "Error: you have no permission to copy rules!" );
		
		$ruleId = $req->getVal("ruleid");
		$oldVersion = $req->getVal("oldversion");
		$version = $req->getVal("version");
		
		if (!$this->LoadRule($ruleId)) return $this->reportError("Error: Failed to load rule $ruleId for copying!");
		if ($version == null || $version == '') return $this->reportError("Error: Missing new version for rule $ruleId copy!");
		
		$ruleType = $this->rule['ruleType'];
		$nameId = $this->rule['nameId'];
		$displayName = $this->rule['displayName'];
		$matchRegex = $this->rule['matchRegex'];
		$displayRegex = $this->rule['displayRegex'];
		$statRequireId = $this->rule['statRequireId'];
		$statRequireValue = $this->rule['statRequireValue'];
		$factorStatId = $this->rule['factorStatId'];
		$isEnabled = intval($this->rule['isEnabled']);
		$isVisible = intval($this->rule['isVisible']);
		$isToggle = intval($this->rule['isToggle']);
		$enableOffBar = intval($this->rule['enableOffBar']);
		$originalId = $this->rule['originalId'];
		$icon = $this->rule['icon'];
		$groupName = $this->rule['groupName'];
		$maxTimes = $this->rule['maxTimes'];
		$comment = $this->rule['comment'];
		$description = $this->rule['description'];
		$customData = $this->rule['customData'];
		
		$cols = [ ];
		$values = [ ];
		
		$cols[] = 'version';
		$cols[] = 'ruleType';
		$cols[] = 'nameId';
		$cols[] = 'displayName';
		$cols[] = 'matchRegex';
		$cols[] = 'displayRegex';
		$cols[] = 'statRequireId';
		$cols[] = 'statRequireValue';
		$cols[] = 'factorStatId';
		$cols[] = 'isEnabled';
		$cols[] = 'isVisible';
		$cols[] = 'isToggle';
		$cols[] = 'enableOffBar';
		$cols[] = 'originalId';
		$cols[] = 'icon';
		$cols[] = 'groupName';
		$cols[] = 'maxTimes';
		$cols[] = 'comment';
		$cols[] = 'description';
		$cols[] = 'customData';
		
		$values[] = "'" . $this->db->real_escape_string( $version ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $ruleType ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $nameId ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $displayName ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $matchRegex ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $displayRegex ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $statRequireId ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $statRequireValue ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $factorStatId ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $isEnabled ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $isVisible ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $isToggle ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $enableOffBar ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $originalId ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $icon ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $groupName ) . "'";
		
		if ($maxTimes == null || $maxTimes == '')
			$values[] = "NULL";
		else
			$values[] = "'" . $this->db->real_escape_string( $maxTimes ) . "'";
		
		$values[] = "'" . $this->db->real_escape_string( $comment ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $description ) . "'";
		$values[] = "'" . $this->db->real_escape_string( $customData ) . "'";
		
		$insertResult = $this->InsertQueries( 'rules', $cols, $values );
		if (!$insertResult) return $this->reportError("Error: Failed to insert record into rules!");
		
		$newRuleId = $this->db->insert_id;
		
		if (!$this->LoadEffects($ruleId)) return $this->reportError("Error: Failed to load effects for rule #$ruleId!");
		
		foreach ( $this->effectsDatas as $effectsData ) {
			$effectId = $effectsData['id'];
			$statId = $effectsData['statId'];
			$value = $effectsData['value'];
			$display = $effectsData['display'];
			$category = $effectsData['category'];
			$combineAs = $effectsData['combineAs'];
			$roundNum = $effectsData['roundNum'];
			$factorValue = $effectsData['factorValue'];
			$statDesc = $effectsData['statDesc'];
			$buffId = $effectsData['buffId'];
			$regexVar = $effectsData['regexVar'];
			
			$cols = [ ];
			$values = [ ];
			
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
			
			$values[] = "'" . $this->db->real_escape_string( $newRuleId ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $version ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $statId ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $value ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $display ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $category ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $combineAs ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $roundNum ) . "'";
			
			if ($factorValue == null || $factorValue == '')
				$values[] = "NULL";
			else
				$values[] = "'" . $this->db->real_escape_string( $factorValue ) . "'";
			
			$values[] = "'" . $this->db->real_escape_string( $statDesc ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $buffId ) . "'";
			$values[] = "'" . $this->db->real_escape_string( $regexVar ) . "'";
			
			$insertResult = $this->InsertQueries ( 'effects', $cols, $values );
			if (!$insertResult) return $this->reportError("Error: Failed to insert record into effects!");
		}
		
		$output->addHTML("Copied <a href='$baselink/editrule?ruleid=$ruleId'>Rule #$ruleId</a> into new rule <a href='$baselink/editrule?ruleid=$newRuleId'>Rule #$newRuleId</a> for version $version!");
	}
	
	
	public function OutputDoShowCopyRule()
	{
		$req = $this->getRequest();
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		
		$permission = $this->canUserEdit();
		if ($permission === false) return $this->reportError( "Error: you have no permission to copy rules!" );
		
		$ruleId = $req->getVal("ruleid");
		
		if (!$this->LoadRule($ruleId)) return $this->reportError("Error: Failed to load rule $ruleId for copying!");
		
		$output->addHTML( "<h2>Copy Rule #$ruleId</h2>" );
		
		$version = $this->escapeHtml($this->rule['version']);
		$output->addHTML("Copy the given rule and its effects from version $version to a new version:");
		
		$output->addHTML( "<form action='$baselink/savecopyrule' method='POST'>" );
		$output->addHTML("<input type='hidden' name='ruleid' value='$ruleId' />");
		$output->addHTML("<input type='hidden' name='oldversion' value='$version' />");
		
			//TODO: Add option for copying to all versions?
		$this->OutputVersionListHtml('version', '', false, $this->rule['version']);
		
		$output->addHTML("<input type='submit' value='Copy' />");
		
		$output->addHTML("</form>");
	}
	
	
	public function OutputDoTestRule()
	{
		$permission = $this->canUserEdit();
		if ($permission === false) return $this->reportError( "Error: you have no permission to test rules" );
		
		$req = $this->getRequest();
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		
		$ruleId = $req->getVal("ruleid");
		
		if (!$this->LoadRule($ruleId)) return $this->reportError("Error: Failed to load rule $ruleId for testing!");
		
		$output->addHTML( "<a href='$baselink'>Home</a> : <a href='$baselink/showrules'>Show Rules</a> : <a href='$baselink/addrule'>Add Rule</a> : <a href='$baselink/editrule?ruleid=$ruleId'>Edit Rule</a><br/>" );
		
		$output->addHTML( "<h2>Testing Rule #$ruleId</h2>" );
		
		$output->addHTML("<table class='wikitable'>");
		$output->addHTML("<tr><th></th><th>Errors / Notes</th></tr>");
		
		$result = $this->TestRule($this->rule);
		$numErrors = $this->OutputTestRuleResult($result, $this->rule, true);
		
		$output->addHTML("</table>");
		$output->addHTML("Found $numErrors errors in rules!");
	}
	
	
	public function OutputDoRuleTests()
	{
		$permission = $this->canUserEdit();
		if ($permission === false) return $this->reportError( "Error: you have no permission to test rules" );
		
		$req = $this->getRequest();
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		
		$version = $req->getVal("version");
		$ruleType = $req->getVal("ruletype");
		
		if (!$this->LoadFilteredRules()) return $this->reportError("Error: Failed to load rules for tests!");
		$this->LoadEffectsForRules($this->rulesDatas);
		
		$this->OutputShowTestForm("Test Rules Results");
		
		$ruleCount = count($this->rulesDatas);
		$totalRuleCount = $this->totalRuleCount;
		
		if ($this->hasFilteredRules)
			$output->addHTML( "Testing matching $ruleCount rules out of $totalRuleCount total." );
		else
			$output->addHTML( "Testing all $ruleCount rules." );
		
		$output->addHTML("<table class='wikitable'>");
		$output->addHTML("<tr><th>Rule</th><th>Errors</th></tr>");
		$numErrors = 0;
		
		foreach ($this->rulesDatas as $rule)
		{
			$result = $this->TestRule($rule);
			$numErrors += $this->OutputTestRuleResult($result, $rule);
		}
		
		$numErrors += $this->OutputTestMatchData();
		
		$output->addHTML("</table>");
		$output->addHTML("Found $numErrors errors in rules!");
	}
	
	
	public function OutputTestMatchData()
	{
		$errors = $this->OutputTestMatchSetData();
		$itemErrors = $this->OutputTestMatchItemData();
		$skillErrors = $this->OutputTestMatchSkillData();
		$cpErrors = $this->OutputTestMatchCpData();
		
		if (count($skillErrors) > 0) array_push($errors, ...$skillErrors);
		if (count($cpErrors) > 0) array_push($errors, ...$cpErrors);
		if (count($itemErrors) > 0) array_push($errors, ...$itemErrors);
		
		$output = $this->getOutput();
		
		foreach ($errors as $errorMsg)
		{
			$output->addHTML("<tr><td></td><td>");
			$output->addHTML("$errorMsg");
			$output->addHTML("</td></tr>\n");
		}
		
		return count($errors);
	}
	
	
	public function GetOutputTestRulesHtml($matchData)
	{
		$ruleTexts = [];
		$baselink = $this->GetBaseLink();
		
		foreach ($matchData as $data)
		{
			$rule = $data['rule'];
			$ruleId = $rule['id'];
			$nameId = $rule['nameId'];
			$isToggle = $rule['isToggle'];
			$statRequireId = $this->escapeHtml($rule['statRequireId']);
			$statRequireValue = $this->escapeHtml($rule['statRequireValue']);
			$matchRegex = $this->escapeHtml($rule['matchRegex']);
			
			$options = [];
			if ($isToggle) $options[] = "Toggle";
			if ($statRequireId) $options[] = "$statRequireId=$statRequireValue";
			$options = implode(", ", $options);
			
			if ($nameId && $options)
				$ruleTexts[] = "<a href='$baselink/editrule?ruleid=$ruleId'>Rule #$ruleId</a> -- $nameId ($options): $matchRegex";
			elseif ($nameId)
				$ruleTexts[] = "<a href='$baselink/editrule?ruleid=$ruleId'>Rule #$ruleId</a> -- $nameId: $matchRegex";
			elseif ($options)
				$ruleTexts[] = "<a href='$baselink/editrule?ruleid=$ruleId'>Rule #$ruleId</a> -- ($options) $matchRegex";
			else
				$ruleTexts[] = "<a href='$baselink/editrule?ruleid=$ruleId'>Rule #$ruleId</a> -- $matchRegex";
		}
		
		$ruleTexts = "<li>" . implode("</li><li>", $ruleTexts) . "</li>";
		return $ruleTexts;
	}
	
	
	public function OutputTestMatchCpData()
	{
		$errors = [];
		if ($this->testMatchData['cp'] == null) return $errors;
		
		//$output = $this->getOutput();
		//$output->addHTML("<pre>" . print_r($this->testMatchData['skill'], true) . "</pre>");
		
		$baselink = $this->GetBaseLink();
		
		foreach ($this->testMatchData['cp'] as $abilityId => $matchData)
		{
			$count = count($matchData);
			
			if ($count == 1)
			{
				continue;
			}
			elseif ($count == 0)
			{
				$cpData = $this->testCpData[$abilityId];
				$desc = FormatRemoveEsoItemDescriptionText($cpData["minDescription"]);
				$desc = $this->escapeHtml($desc);
				$desc = "<pre>$desc</pre>";
				$errors[] = "CP {$cpData['name']} (#$abilityId) has no rule match!<br/>$desc";
			}
			else
			{
				$cpData = $matchData[0]['cp'];
				$desc = FormatRemoveEsoItemDescriptionText($cpData["minDescription"]);
				$desc = $this->escapeHtml($desc);
				$desc = "<pre>$desc</pre>";
				
				$ruleTexts = $this->GetOutputTestRulesHtml($matchData);
				$errors[] = "CP {$cpData['name']} (#$abilityId) has $count rule matches!<br/>$desc</br><ul>$ruleTexts</ul>";
			}
		}
		
		return $errors;
	}
	
	
	public function OutputTestMatchSkillData()
	{
		$errors = [];
		if ($this->testMatchData['skill'] == null) return $errors;
		
		//$output = $this->getOutput();
		//$output->addHTML("<pre>" . print_r($this->testMatchData['skill'], true) . "</pre>");
		
		$baselink = $this->GetBaseLink();
		
		foreach ($this->testMatchData['skill'] as $abilityId => $matchData)
		{
			$count = count($matchData);
			
			if ($count == 1)
			{
				continue;
			}
			elseif ($count == 0)
			{
				$skillData = $this->testSkillData[$abilityId];
				
				$desc = $skillData["description"];
				$header = trim($skillData["descHeader"]);
				if ($header) $desc = "$header\r\n$desc";
				
				$desc = FormatRemoveEsoItemDescriptionText($desc);
				$desc = $this->escapeHtml($desc);
				$desc = "<pre>$desc</pre>";
				$errors[] = "Skill {$skillData['name']} ($abilityId) has no rule match!<br/>$desc";
			}
			else
			{
				$skillData = $matchData[0]['skill'];
				
				$desc = $skillData["description"];
				$header = trim($skillData["descHeader"]);
				if ($header) $desc = "$header\r\n$desc";
				
				$desc = FormatRemoveEsoItemDescriptionText($desc);
				$desc = $this->escapeHtml($desc);
				$desc = "<pre>$desc</pre>";
				$ruleTexts = $this->GetOutputTestRulesHtml($matchData);
				$errors[] = "Skill {$skillData['name']} ($abilityId) has $count rule matches!<br/>$desc</br><ul>$ruleTexts</ul>";
			}
		}
		
		return $errors;
	}
	
	
	public function OutputTestMatchSetData()
	{
		$errors = [];
		if ($this->testMatchData['set'] == null) return $errors;
		
		$baselink = $this->GetBaseLink();
		
		foreach ($this->testMatchData['set'] as $setName => $setBonusData)
		{
			foreach ($setBonusData as $i => $matchData)
			{
				$count = count($matchData);
				
				if ($count == 1)
				{
					continue;
				}
				elseif ($count == 0)
				{
					$setData = $this->testSetData[strtolower($setName)];
					$setBonusDesc = "<pre>" . $this->escapeHtml($setData["setBonusDesc$i"]) . "</pre>";
					$errors[] = "Set $setName Bonus #$i has no rule match!<br/>$setBonusDesc";
				}
				else
				{
					$setData = $matchData[0]['set'];
					$setBonusDesc = "<pre>" . $this->escapeHtml($setData["setBonusDesc$i"]) . "</pre>";
					$ruleTexts = $this->GetOutputTestRulesHtml($matchData);
					$errors[] = "Set $setName Bonus #$i has $count rule matches!<br/>$setBonusDesc</br><ul>$ruleTexts</ul>";
				}
			}
		}
		
		return $errors;
	}
	
	
	public function OutputTestMatchItemData()
	{
		$errors = [];
		if ($this->testMatchData['item'] == null) return $errors;
		
		$baselink = $this->GetBaseLink();
		
		foreach ($this->testMatchData['item'] as $itemId => $matchData)
		{
			$count = count($matchData);
			
			if ($count == 0)
			{
				$itemData = $this->testItemData[$itemId];
				
				$desc = $itemData["abilityDesc"];
				
				$desc = FormatRemoveEsoItemDescriptionText($desc);
				$desc = $this->escapeHtml($desc);
				$desc = "<pre>$desc</pre>";
				$errors[] = "Item {$itemData['name']} ($itemId) has no rule match!<br/>$desc";
			}
			
		}
		
		return $errors;
	}
	
	
	public function CheckDuplicateRules()
	{
		$permission = $this->canUserEdit();
		if ($permission === false) return $this->reportError( "Error: you have no permission to test rules" );
		
		$req = $this->getRequest();
		$output = $this->getOutput();
		$baselink = $this->GetBaseLink();
		
		$version = $req->getVal('version');
		
		if (!$this->LoadRules($version)) return $this->reportError("Error: Failed to load rules!");
		
		$groups = $this->CreateDuplicateRuleGroups();
		
		$output->addHTML( "<a href='$baselink'>Home</a><br/>" );
		
		if ($version == null) 
			$version = 'All';
		else
			$version = $this->escapeHtml($version);
		
		$output->addHTML("<br/>Showing duplicate rules for version $version:<p/><br/>");
		$output->addHTML("<ol>");
		
		foreach ($groups as $version => $groups1)
		{
			foreach ($groups1 as $ruleType => $groups2)
			{
				foreach ($groups2 as $matchRegex => $groups3)
				{
					$count = count($groups3);
					if ($count <= 1) continue;
					
					$matchRegex = $this->escapeHtml($matchRegex);
					
					$output->addHTML("<li>");
					$output->addHTML("<b>$count Duplicates</b> -- ($ruleType) $matchRegex");
					
					$output->addHTML("<ol>");
					
					foreach ($groups3 as $i => $rule)
					{
						$ruleId = $rule['id'];
						$url = $this->GetBaseLink() . "/editrule?ruleid=$ruleId";
						$nameId = $this->escapeHtml($rule['nameId']);
						
						$output->addHTML("<li>");
						$output->addHTML("<a href='$url'>$nameId -- Rule #$ruleId</a>");
						$output->addHTML("</li>");
					}
					
					$output->addHTML("</ol>");
					$output->addHTML("</li>");
				}
			}
		}
		
		$output->addHTML("</ol>");
		
		return true;
	}
	
	
	public function OutputTableOfContents()
	{
		$output = $this->getOutput();
		
		$baselink = $this->GetBaseLink();
		
		$output->addHTML( "Edit rules, effects, and computed stats used for the <a href='/wiki/Special:EsoBuildEditor'>ESO Build Editor</a>.<p/>" );
		
		$output->addHTML( "<ul>" );
		$output->addHTML( "<li><a href='$baselink/showrules'>Show Rules</a></li>" );
		$output->addHTML( "<li><a href='$baselink/addrule'>Add Rule</a></li>" );
		$output->addHTML( "<br>" );
		$output->addHTML( "<li><a href='$baselink/showcomputedstats'>Show Computed Stats</a></li>" );
		$output->addHTML( "<li><a href='$baselink/addcomputedstat'>Add Computed Stat</a></li>" );
		$output->addHTML( "<br>" );
		$output->addHTML( "<li><a href='$baselink/addversion'>Add Version</a></li>" );
		$output->addHTML( "<li><a href='$baselink/checkduprules'>Find Duplicate Rules</a></li>" );
		$output->addHTML( "<li><a href='$baselink/showtest'>Test Rules</a></li>" );
		
		$output->addHTML( "</ul>" );
	}
	
	
	function execute($parameter)
	{
		$request = $this->getRequest();
		$output = $this->getOutput();
		$this->setHeaders();
		
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
			$this->OutputAddEffectForm();
		elseif ($parameter == "saveneweffect")
			$this->SaveNewEffect();
		elseif ($parameter == "saveediteffectform")
			$this->SaveEditEffectForm();
		elseif ($parameter == "editeffect")
			$this->OutputEditEffectForm();
		elseif ($parameter == "showcomputedstats" || $parameter == "showstats")
			$this->OutputShowComputedStatsTable();
		elseif ($parameter == "addcomputedstat" || $parameter == "addstat")
			$this->OutputAddComputedStatsForm();
		elseif ($parameter == "savenewcomputedstat")
			$this->SaveNewComputedStat();
		elseif ($parameter == "editcomputedstat")
			$this->OutputEditComputedStatForm();
		elseif ($parameter == "saveeditcomputedstatsform")
			$this->SaveEditComputedStatsForm();
		elseif ($parameter == "deleterule")
			$this->OutputDeleteRule();
		elseif ($parameter == "ruledeleteconfirm")
			$this->ConfirmDeleteRule();
		elseif ($parameter == "deletcomputedstat")
			$this->OutputDeleteComputedStat();
		elseif ($parameter == "statdeleteconfirm")
			$this->ConfirmDeleteStat();
		elseif ($parameter == "deleteeffect")
			$this->OutputDeleteEffect();
		elseif ($parameter == "effectdeleteconfirm")
			$this->ConfirmDeleteEffect();
		elseif ($parameter == "addversion")
			$this->OutputAddVersionForm();
		elseif ($parameter == "saveversion")
			$this->SaveNewVersion();
		elseif ($parameter == "checkduprules")
			$this->CheckDuplicateRules();
		elseif ($parameter == "showtest")
			$this->OutputShowTestForm();
		elseif ($parameter == "testrules")
			$this->OutputDoRuleTests();
		elseif ($parameter == "testrule")
			$this->OutputDoTestRule();
		elseif ($parameter == "copyrule")
			$this->OutputDoShowCopyRule();
		elseif ($parameter == "savecopyrule")
			$this->OutputDoSaveCopyRule();
		else
			$this->OutputTableOfContents();
	}
	
	
	function getgroupName()
	{
		return 'wiki';
	}
	
};

