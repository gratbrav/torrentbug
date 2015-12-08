<?php

class Class_User_Authentication
{
	protected $db = null;
	protected $user = null;
	protected $password = null;

	function __construct($user, $password)
	{
		$this->user = $user;
		$this->password = $password;
		
		$db = Class_Database::getInstance();
		$this->db = $db->getDatabase();
	}
	
	public function checkLogin() 
	{
		if ($this->user && $this->password) {
			$user = $this->db->qstr($this->user);
			$pwd = $this->db->qstr(md5($this->password));
			
			$sql = "SELECT uid, hits, hide_offline, theme, language_file FROM tf_users WHERE user_id=".$user." AND password=".$pwd;
			$result = $this->db->Execute($sql);
			//showError($db,$sql);
			return $result;
		}
		
		return false;
	}
}