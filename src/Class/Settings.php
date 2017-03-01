<?php 
/**
 * TorrentBug
 *
 * @link      https://github.com/gratbrav/torrentbug
 * @license   https://github.com/gratbrav/torrentbug/blob/master/LICENSE
 */

namespace Gratbrav\Torrentbug;

use Gratbrav\Torrentbug\Database;

/**
 * Setings Class
 *
 * Class for torrentbug settings
 *
 * @package  Torrentbug
 * @author   Gratbrav
 */
class Settings
{
	protected $db = null;
	protected $config = array();
	
	function __construct()
	{
		$db = Database::getInstance();
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
	    $statement = $this->db->prepare($query);
	    $statement->execute();
	
	    while (list($key, $value) = $statement->fetch()) {
	    	
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
	    
	    // @TODO save and load from db
	    $this->config['torrent_file_path'] = $this->config['path'] . '.torrents/';
	    // $this->config['document_root'] = realpath($this->config['path'] . '..');
	}
	

	public function save($data = array())
	{
		$config = $this->config;
		
	    if (count($data)) {
	    	$this->config = array_merge($this->config, $data);
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
	    $query = "UPDATE tf_settings SET tf_value = :value WHERE tf_key = :key";
	    $statement = $this->db->prepare($query);
	    $statement->execute([':value' => $value, ':key' => $key]);
	}
	
	protected function insertValue($key, $value)
	{
	    $query = "INSERT INTO tf_settings VALUES (:key, :value)";
	    $statement = $this->db->prepare($query);
	    $statement->execute([':value' => $value, ':key' => $key]);
	}

}