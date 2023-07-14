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
				'offhandenchant' => 'Enchantment (Off-Hand Weapon)',
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
		$permission = $this->canUserEdit();
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
		$permission = $this->canUserEdit();
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
		
		if ($result === false) return $this->reportError( "Error: failed to INSERT data into database" );
		return true;
	}
	
	
	public function DeleteQueries($tableName, $conditionName, $value)
	{
		$value = $this->db->real_escape_string( $value );
		$query = "DELETE FROM $tableName WHERE $conditionName='$value';";
		$result = $this->db->query( $query );
		
		if ($result === false) return $this->reportError( "Error: failed to DELETE data from database" );
		return true;
	}
	
	
	public function UpdateQueries($tableName, $values, $conditionName, $value)
	{
		$values = implode( ',', $values );
		
		$query = "UPDATE $tableName SET $values WHERE $conditionName='$value';";
		
		error_log($query);
		
		$result = $this->db->query( $query );
		
		if ($result === false) return $this->reportError( "Error: failed to UPDATE data in database" );
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
		$output->addHTML( "<th>Original Id</th>" );
		$output->addHTML( "<th>Group Name</th>" );
		$output->addHTML( "<th>Description</th>" );
		
		$output->addHTML( "<th>Enabled</th>" );
		$output->addHTML( "<th>Toggle</th>" );
		$output->addHTML( "<th>Max Times</th>" );
		$output->addHTML( "<th>Visible</th>" );
		$output->addHTML( "<th>Enable Off Bar</th>" );
		$output->addHTML( "<th>Stat Require Value</th>" );
		$output->addHTML( "<th>Custom Data</th>" );
		$output->addHTML( "<th>Delete</th>" );
		$output->addHTML( "</tr></thead><tbody>" );
		
		foreach ( $this->rulesDatas as $rulesData ) {
			
			$id = $this->escapeHtml( $rulesData['id'] );
			$ruleType = $this->escapeHtml( $this->MakeNiceShortRuleType($rulesData['ruleType']) );
			$nameId = $this->escapeHtml( $rulesData['nameId'] );
			$displayName = $this->escapeHtml( $rulesData['displayName'] );
			$matchRegex = $this->escapeHtml( $rulesData['matchRegex'] );
			$statRequireId = $this->escapeHtml( $rulesData['statRequireId'] );
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
			$output->addHTML( "<td>$originalId</td>" );
			$output->addHTML( "<td>$groupName</td>" );
			$output->addHTML( "<td>$description</td>" );
			$output->addHTML( "<td>$isEnabledDisplay</td>" );
			$output->addHTML( "<td>$toggleDisplay</td>" );
			$output->addHTML( "<td>$maxTimes</td>" );
			$output->addHTML( "<td>$isVisibleDisplay</td>" );
			$output->addHTML( "<td>$enableOffBarDisplay</td>" );
			$output->addHTML( "<td>$statRequireValue</td>" );
			
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
			$isEnabled = $this->rule['isEnabled'];
			$isVisible = $this->rule['isVisible'];
			$enableOffBar = $this->rule['enableOffBar'];
			$isToggle = $this->rule['isToggle'];
			
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
			
			$deleteResult = $this->DeleteQueries ( 'rules', 'id', $id );
			if (!$deleteResult) return $this->reportError("Error: Failed to delete record from rules!");
			
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
				$values[] = "'" . $this->db->real_escape_string( $factorValue ) . "'";
				$values[] = "'" . $this->db->real_escape_string( $statDesc ) . "'";
				$values[] = "'" . $this->db->real_escape_string( $buffId ) . "'";
				$values[] = "'" . $this->db->real_escape_string( $regexVar ) . "'";
				
				$insertResult = $this->InsertQueries ( 'effectsArchive', $cols, $values );
				if (!$insertResult) return $this->reportError("Error: Failed to insert record into effectsArchive!");
			}
			
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
		$permission = $this->canUserEdit();
		if ($permission === false) return $this->reportError( "Error: you have no permission to edit rules" );
		
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
		
		$output->addHTML( "<a href='$baselink/showrules'>Show Rules</a> : <a href='$baselink/testrule?ruleid=$id'>Test Rule</a> : <a href='$baselink/copyrule?ruleid=$id'>Copy Rule</a> : <a href='$baselink/deleterule?ruleid=$id'>Delete Rule</a> <br/>" );
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
		$output->addHTML( "<input type='text' id='edit_matchRegex' name='edit_matchRegex' value='$matchRegex' size='60'>" );
		$output->addHTML( "<p class='errorMsg'></p>" );
		$output->addHTML( "<p class='warningErr'></p>" );
		
		$output->addHTML( "<label for='edit_displayRegex'>Display Regex</label>" );
		$output->addHTML( "<input type='text' id='edit_displayRegex' name='edit_displayRegex' value='$displayRegex' size='60'>" );
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
		$output->addHTML( "<input type='text' class='custReadOnly' value='value' readonly></input><br/>" );
		
		foreach ( $this->rule['customData'] as $key => $val )
		{
			if ($val === true) $val = "true";
			if ($val === false) $val = "false";
			$output->addHTML( "<input type='text' id='edit_customName' name='edit_customName[]' class='custCol' value='$key'>   </input>" );
			$output->addHTML( "<input type='text' id='edit_customValue' name='edit_customValue[]' value='$val'></input><br>" );
		}
		$output->addHTML( "<input type='text' id='edit_customName' name='edit_customName[]' class='custCol'>   </input>" );
		$output->addHTML( "<input type='text' id='edit_customValue' name='edit_customValue[]'></input><br>" );
		$output->addHTML( "<input type='text' id='edit_customName' name='edit_customName[]' class='custCol'>   </input>" );
		$output->addHTML( "<input type='text' id='edit_customValue' name='edit_customValue[]'></input><br>" );
		
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
		
		$output->addHTML( "<br><input type='submit' value='Save Edits' class='submit_btn'>" );
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
		
		$this->OutputVersionListHtml( 'version', '1' );
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
		$output->addHTML( "<input type='text' id='matchRegex' name='matchRegex' size='60'>" );
		$output->addHTML( "<p class='errorMsg'></p>" );
		$output->addHTML( "<p class='warningErr'></p>" );
		
		$output->addHTML( "<label for='displayRegex'>Display Regex</label>" );
		$output->addHTML( "<input type='text' id='displayRegex' name='displayRegex' size='60'>" );
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
		$input_isEnabled = $req->getVal( 'isEnabled' );
		$input_isVisible = $req->getVal( 'isVisible' );
		$input_enableOffBar = $req->getVal( 'enableOffBar' );
		$input_toggle = $req->getVal( 'toggle' );
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
		$new_statRequireId = $req->getVal( 'edit_statRequireId' );
		$new_factorStatId = $req->getVal( 'edit_factorStatId' );
		$new_originalId = $req->getVal( 'edit_originalId' );
		$new_version = $req->getVal( 'edit_version' );
		$new_icon = $req->getVal( 'edit_icon' );
		$new_groupName = $req->getVal( 'edit_groupName' );
		$new_maxTimes = $req->getVal( 'edit_maxTimes' );
		$new_comment = $req->getVal( 'edit_comment' );
		$new_description = $req->get