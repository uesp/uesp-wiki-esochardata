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
                        group TINYTEXT,
                        maxTimes INTEGER,
                        comment TINYTEXT NOT NULL,
                        description TINYTEXT NOT NULL,
                        disableIds TINYTEXT,
                        PRIMARY KEY (id),
                        INDEX index_version(version(10)),
                        INDEX index_ruleId(originalId),
                    	);

										");

		if ($result === false) return false;


		return true;
	}


	public function InitDatabase()
	{
		global $uespEsoBuildDataWriteDBHost, $uespEsoBuildDataWriteUser, $uespEsoBuildDataWritePW, $uespEsoBuildDataDatabase;

		$this->db = new mysqli($uespEsoBuildDataWriteDBHost, $uespEsoBuildDataWriteUser, $uespEsoBuildDataWritePW, $uespEsoBuildDataDatabase);
		if ($this->db->connect_error) return false;

		$this->CreateTables();

		return true;
	}


	public function LoadRules()
	{
		$query = "SELECT * FROM rules;";
		$result = $this->db->query($query);
		//....
		//$this->rulesData[]
	}


	public function OutputShowRulesTable()
	{
		$this->LoadRules();
		//....
	}


	public function OutputAddRuleForm()
	{
		$output = $this->getOutput();

		$output->addHTML("<h3>Add New Rule</h3>");
		$output->addHTML("<form action="/">");
		$output->addHTML("<label for="ruleType">Rule Type:</label><br>");
		$output->addHTML("<label for="nameId">Name ID:</label><br>");
		$output->addHTML("<label for="displayName">Display Name:</label><br>");
		$output->addHTML("<label for="matchRegex">Match Regex:</label><br>");
		$output->addHTML("<label for="displayRegex">Display Regex:</label><br>");
		$output->addHTML("<label for="requireSkillLine">requireSkillLine:</label><br>");
		$output->addHTML("<label for="statRequireId">statRequireId:</label><br>");
		$output->addHTML("<label for="factorStatId">factorStatId:</label><br>");

		//could only be true or false (1 or 0)
		$output->addHTML("<label for="isEnabled">Enabled:</label><br>");
		$output->addHTML("<label for="isVisible">Visible:</label><br>");
		$output->addHTML("<label for="enableOffBar">Enable Off Bar:</label><br>");
		$output->addHTML("<label for="matchSkillName">Match Skill Name:</label><br>");
		$output->addHTML("<label for="updateBuffValue">Update Buff Value:</label><br>");

		$output->addHTML("<label for="originalId">Original ID:</label><br>");
		$output->addHTML("<label for="icon">Icon:</label><br>");
		$output->addHTML("<label for="group">Group:</label><br>");
		$output->addHTML("<label for="maxTimes">Maximum Times:</label><br>");
		$output->addHTML("<label for="comment">Comment:</label><br>");
		$output->addHTML("<label for="description">Description:</label><br>");
		$output->addHTML("<label for="disableIds">Disable IDs:</label><br>");

	}


	public function OutputEditRuleForm()
	{
		//....
		//$this->LoadRule(ruleId);
	}


	public function OutputTableOfContents()
	{
		$output = $this->getOutput();

		$baselink = "https://dev.uesp.net/wiki/Special:EsoBuildRuleEditor";

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
		$output->addHTML("TODO: ESO Build Rule Editor");

		if ($parameter == "showrules")
			$this->OutputShowRulesTable();
		elseif ($parameter == "addrule")
			$this->OutputAddRuleForm();
		elseif ($parameter == "editrule")
			$this->OutputEditRuleForm();
		else
			$this->OutputTableOfContents();
	}


	function getGroupName()
	{
		return 'wiki';
	}

};
