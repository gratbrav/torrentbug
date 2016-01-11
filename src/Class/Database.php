<?php

include_once dirname(__FILE__) . '/../db.php';
	
/**
* Database Class
*
* Class for database connectiony
*
* @package  gratbrav
* @author   Gratbrav
* @version  $Revision: 1.0 $
* @access   public
*/
class Class_Database
{
	/**
	 * Database
	 */
	protected $db = '';

	/**
	 * Instance
	 * 
	 * @var self
	 */
	static private $instance = null;

	static public function getInstance()
	{
		if (null === self::$instance) {
			self::$instance = new self;
		}
		return self::$instance;
	}


	/**
	 * Get Database
	 */
	public function getDatabase()
	{
		return $this->db;
	}


	/**
	 * Constructor from Database Class
	 */
	private function __construct()
	{
		include('./config.php');
		include('./adodb/adodb.inc.php');
		
		// 2004-12-09 PFM: connect to database.
		$db = NewADOConnection($cfg['db_type']);
		$db->Connect($cfg['db_host'], $cfg['db_user'], $cfg['db_pass'], $cfg['db_name']);
		
		if (!$db) {
        	die ('Could not connect to database: '.$db->ErrorMsg().'<br>Check your database settings in the config.php file.');
    	}
    
    	$this->db = $db;
	}


	/**
	 * disable clone of class
	 */
	private function __clone(){}
}