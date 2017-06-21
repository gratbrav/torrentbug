<?php
/**
 * TorrentBug
 *
 * @link      https://github.com/gratbrav/torrentbug
 * @license   https://github.com/gratbrav/torrentbug/blob/master/LICENSE
 */
namespace Gratbrav\Torrentbug\User;

/**
 * User Class
 *
 * User
 *
 * @package Torrentbug
 * @author Gratbrav
 */
class User
{

    protected $uid = 0;

    protected $userId;

    protected $password;

    protected $hits;

    protected $lastVisit = '';

    protected $timeCreated;

    protected $userLevel;

    protected $hideOffline;

    protected $theme;

    protected $languageFile;

    function __construct($data = [])
    {
        $this->setUid($data['uid']);
        $this->setUserId($data['user_id']);
        $this->setPassword($data['password']);
        $this->setHits($data['hits']);
        $this->setLastVisit($data['last_visit']);
        $this->setTimeCreated($data['time_created']);
        $this->setUserLevel($data['user_level']);
        $this->setHideOffline($data['hide_offline']);
        $this->setTheme($data['theme']);
        $this->setLanguageFile($data['language_file']);
    }

    /**
     *
     * @return the $uid
     */
    public function getUid()
    {
        return (int)$this->uid;
    }

    /**
     *
     * @return the $userId
     */
    public function getUserId()
    {
        return (string)$this->userId;
    }

    /**
     *
     * @return the $password
     */
    public function getPassword()
    {
        return (string)$this->password;
    }

    /**
     *
     * @return the $hits
     */
    public function getHits()
    {
        return (int)$this->hits;
    }

    /**
     *
     * @return the $lastVisit
     */
    public function getLastVisit()
    {
        return (string)$this->lastVisit;
    }

    /**
     *
     * @return the $timeCreated
     */
    public function getTimeCreated()
    {
        return (int)$this->timeCreated;
    }

    /**
     *
     * @return the $userLevel
     */
    public function getUserLevel()
    {
        return (int)$this->userLevel;
    }

    /**
     *
     * @return the $hideOffline
     */
    public function getHideOffline()
    {
        return (int)$this->hideOffline;
    }

    /**
     *
     * @return the $theme
     */
    public function getTheme()
    {
        return (string)$this->theme;
    }

    /**
     *
     * @return the $languageFile
     */
    public function getLanguageFile()
    {
        return (string)$this->languageFile;
    }

    /**
     *
     * @param field_type $uid            
     */
    public function setUid($uid)
    {
        if ($uid != '' && is_numeric($uid)) {
            $this->uid = $uid;
        }
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
     * @param field_type $password            
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     *
     * @param field_type $hits            
     */
    public function setHits($hits)
    {
        $this->hits = $hits;
    }

    /**
     *
     * @param field_type $lastVisit            
     */
    public function setLastVisit($lastVisit)
    {
        $this->lastVisit = $lastVisit;
    }

    /**
     *
     * @param field_type $timeCreated            
     */
    public function setTimeCreated($timeCreated)
    {
        $this->timeCreated = $timeCreated;
    }

    /**
     *
     * @param field_type $userLevel            
     */
    public function setUserLevel($userLevel)
    {
        $this->userLevel = $userLevel;
    }

    /**
     *
     * @param field_type $hideOffline            
     */
    public function setHideOffline($hideOffline)
    {
        $this->hideOffline = $hideOffline;
    }

    /**
     *
     * @param field_type $theme            
     */
    public function setTheme($theme)
    {
        $this->theme = $theme;
    }

    /**
     *
     * @param field_type $languageFile            
     */
    public function setLanguageFile($languageFile)
    {
        $this->languageFile = $languageFile;
    }
}