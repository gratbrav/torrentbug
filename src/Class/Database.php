<?php
/**
 * TorrentBug
 *
 * @link      https://github.com/gratbrav/torrentbug
 * @license   https://github.com/gratbrav/torrentbug/blob/master/LICENSE
 */
namespace Gratbrav\Torrentbug;

include_once __DIR__ . '/../config.php';

/**
 * Database Class
 *
 * Class for database connections
 *
 * @package Torrentbug
 * @author Gratbrav
 */
class Database
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
    private static $instance = null;

    static public function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
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
     * Alias for getDatabase()
     *
     * @return string|PDO
     */
    public function getDB()
    {
        return $this->getDatabase();
    }

    /**
     * Constructor from Database Class
     */
    private function __construct()
    {
        // include_once(__DIR__ . '/../config.php');
        global $cfg;
        
        $db = new \PDO("mysql:host={$cfg['db_host']};dbname={$cfg['db_name']};charset=utf8", $cfg['db_user'], $cfg['db_pass']);
        
        if (! $db) {
            die('Could not connect to database: ' . $db->ErrorMsg() . '<br>Check your database settings in the config.php file.');
        }
        
        $this->db = $db;
    }

    /**
     * disable clone of class
     */
    private function __clone()
    {}
}