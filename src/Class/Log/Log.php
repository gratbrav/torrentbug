<?php
/**
 * TorrentBug
 *
 * @link      https://github.com/gratbrav/torrentbug
 * @license   https://github.com/gratbrav/torrentbug/blob/master/LICENSE
 */
namespace Gratbrav\Torrentbug\Log;

/**
 * Log Class
 *
 * Log
 *
 * @package Torrentbug
 * @author Gratbrav
 */
class Log
{

    protected $cid;

    protected $userId;

    protected $file;

    protected $action;

    protected $ip;

    protected $ipResolved;

    protected $userAgent;

    protected $time;

    function __construct($data)
    {
        $this->setCid($data['cid']);
        $this->setUserId($data['user_id']);
        $this->setFile($data['file']);
        $this->setAction($data['action']);
        $this->setIp($data['ip']);
        $this->setIpResolved($data['ip_resolved']);
        $this->setUserAgent($data['user_agent']);
        $this->setTime($data['time']);
    }

    /**
     *
     * @return the $cid
     */
    public function getCid()
    {
        return $this->cid;
    }

    /**
     *
     * @return the $userId
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     *
     * @return the $file
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     *
     * @return the $action
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     *
     * @return the $ip
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     *
     * @return the $ipResolved
     */
    public function getIpResolved()
    {
        return $this->ipResolved;
    }

    /**
     *
     * @return the $userAgent
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     *
     * @return the $time
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     *
     * @param field_type $cid            
     */
    public function setCid($cid)
    {
        $this->cid = $cid;
    }

    /**
     *
     * @param field_type $userId            
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     *
     * @param field_type $file            
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     *
     * @param field_type $action            
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     *
     * @param field_type $ip            
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     *
     * @param field_type $ipResolved            
     */
    public function setIpResolved($ipResolved)
    {
        $this->ipResolved = $ipResolved;
    }

    /**
     *
     * @param field_type $userAgent            
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;
    }

    /**
     *
     * @param field_type $time            
     */
    public function setTime($time)
    {
        $this->time = $time;
    }
}