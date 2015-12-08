<?php
class Class_Database
{
	protected $db = '';

	static private $instance = null;

	static public function getInstance()
	{
		if (null === self::$instance) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	public function getDatabase()
	{
		return $this->db;
	}
 
	private function __construct()
	{
		include('./config.php');
		include('./adodb/adodb.inc.php');
		
		// 2004-12-09 PFM: connect to database.
		$db = NewADOConnection($cfg["db_type"]);
		$db->Connect($cfg["db_host"], $cfg["db_user"], $cfg["db_pass"], $cfg["db_name"]);
		
		if(!$db) {
        	die ('Could not connect to database: '.$db->ErrorMsg().'<br>Check your database settings in the config.php file.');
    	}
    
    	$this->db = $db;
	}

	private function __clone(){}
}