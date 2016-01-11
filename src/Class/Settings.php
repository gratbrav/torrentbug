<?php 

class Class_Settings
{
	protected $db = null;
	protected $config = array();
	
	function __construct()
	{
		$db = Class_Database::getInstance();
		$this->db = $db->getDatabase();
		
		$this->load();
	}
	
	public function get($key = '')
	{
		if ($key == '') {
			return $this->config;
		} else {
			if (isset($this->config[$key])) {
				return $this->config[$key];	
			} else {
				return null;
			}
		}
	}
	
	public function set($key, $value)
	{
	    if ($key == '') {
	        return false;
	    } 
	    
        if (isset($this->config[$key])) {
            $this->config[$key] = $value;
            return true;
        }
        
	    return false;
	}
	
	protected function load()
	{
	    $query = "SELECT tf_key, tf_value FROM tf_settings";
	    $recordset = $this->db->Execute($query);
	    // showError($db, $sql);
	
	    while (list($key, $value) = $recordset->FetchRow()) {
	    	
	        $tmpValue = '';
	        if (strpos($key,"Filter") > 0) {
	            $tmpValue = unserialize($value);
	        } else if ($key == 'searchEngineLinks') {
	            $tmpValue = unserialize($value);
	        }
	        
	        if (is_array($tmpValue)) {
	            $value = $tmpValue;
	        }
	        
	        $this->config[$key] = $value;
	    }
	    
	    $this->config['torrent_file_path'] = $this->config['path'] . '.torrents/';
	}
	

	public function save($data = array())
	{
		$config = $this->config;
		
	    if (count($data)) {
	    	$this->config = array_map($this->config, $data);
	    }
        
    	foreach ($this->config as $key => $value) {
        	if (array_key_exists($key, $config)) {
            	if ($config[$key] != $value) {
                	$this->updateValue($key, $value);
            	}
        	} else {
            	$this->insertValue($key, $value);
        	}
    	}
	}
	
	protected function updateValue($key, $value)
	{
	    $query = "UPDATE tf_settings SET tf_value = '" . $update . "' WHERE tf_key = '" . $key . "'";

        $result = $this->db->Execute($query);
        // showError($db,$query);
	}
	
	protected function insertValue($key, $value)
	{
	    $query = "INSERT INTO tf_settings VALUES ('" . $key . "', '" . $update_value . "')";
	
	    $result = $this->db->Execute($query);
        // showError($db,$query);
	}

}