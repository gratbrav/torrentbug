<?php
/**
 * TorrentBug
 *
 * @link      https://github.com/gratbrav/torrentbug
 * @license   https://github.com/gratbrav/torrentbug/blob/master/LICENSE
 */
namespace Gratbrav\Torrentbug\Log;

use Gratbrav\Torrentbug\Database;

/**
 * Log Service
 *
 * Handle all log events
 *
 * @package Torrentbug
 * @author Gratbrav
 */
class Service
{

    /**
     * DB reference
     * 
     * @var Database
     */
    protected $db = null;

    /**
     * Logs
     * 
     * @var array
     */
    protected $logs = null;

    /**
     * Filter
     * 
     * @var array
     */
    protected $filter = [];

    /**
     * Constructor
     */
    function __construct()
    {
        $db = Database::getInstance();
        $this->db = $db->getDatabase();
    }

    /**
     * Return all logs
     *
     * @return array
     */
    public function getLogs()
    {
        if (is_null($this->logs)) {
            $this->loadLogs();
        }

        return (array) $this->logs;
    }

    /**
     * Return single log by id
     *
     * @param numeric $logId
     * @return Log
     */
    public function getLogById($logId)
    {
        if (is_null($this->logs) || ! isset($this->logs[$logId])) {
            $this->loadLogs();
        }

        $log = isset($this->logs[$logId]) ? $this->logs[$logId] : new Log();

        return $log;
    }

    /**
     * Load Logs from database
     * 
     * @return Service
     */
    protected function loadLogs()
    {
        $whereExpr = "";
        if (isset($this->filter['action']) && !is_array($this->filter['action'])) {
            $whereExpr .= " AND `action` = " . $this->filter['action'];
        } else if (isset($this->filter['action']) && is_array($this->filter['action'])) {
            $whereExpr .= " ( ";
            foreach ($this->filter['action'] as $index => $action) {
                $whereExpr .= ($index) ? " OR " : "";
                $whereExpr .= " action = '$action' ";
            }
            $whereExpr .= " ) ";
        }

        $whereExpr = ($whereExpr != '') ? $whereExpr : ' 1 ';

        $query = "SELECT "
                . " * "
            . " FROM "
                . " tf_log "
            . " WHERE "
                . $whereExpr
            . " ORDER BY "
                . " time ";

        $statement = $this->db->prepare($query);
        $statement->execute();

        while ($data = $statement->fetch()) {
            $this->logs[$data['cid']] = new Log($data);
        }

        return $this;
    }

    /**
     * Delete log by id
     *
     * @param numeric $logId
     * @return array
     */
    public function delete($logId)
    {
        $query = "DELETE " . " FROM " . " tf_log " . " WHERE " . " cid = :logId ";

        $statement = $this->db->prepare($query);
        $statement->execute([
            ':logId' => $logId
        ]);

        return $statement->fetch();
    }

    /**
     * Save log
     *
     * data:
     * - user_id: user
     * - file: file
     * - action: action
     *
     * @param array $data
     */
    public function save($data)
    {
        $ip = htmlentities($_SERVER['REMOTE_ADDR'], ENT_QUOTES);
        $userAgent = htmlentities($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES);

        $query = "INSERT INTO tf_log VALUES (0, :userId, :file, :action, :ip, :ipResolved, :userAgent, :time)";

        $statement = $this->db->prepare($query);
        $statement->execute([
            ':userId' => $data['user_id'],
            ':file' => $data['file'],
            ':action' => $data['action'],
            ':ip' => $ip,
            ':ipResolved' => $ip,
            ':userAgent' => $userAgent,
            ':time' => time()
        ]);

        return $statement->fetch();
    }

    /**
     * Set Filter
     * 
     * @param array $filter
     * @return \Gratbrav\Torrentbug\Log\Service
     */
    public function setFilter($filter = [])
    {
        $this->filter = (array)$filter;
        return $this;
    }

}