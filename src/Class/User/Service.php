<?php
/**
 * TorrentBug
 *
 * @link      https://github.com/gratbrav/torrentbug
 * @license   https://github.com/gratbrav/torrentbug/blob/master/LICENSE
 */
namespace Gratbrav\Torrentbug\User;

use Gratbrav\Torrentbug\Database;

/**
 * User Service
 *
 * Handle alle user events
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
     * User
     * 
     * @var array
     */
    protected $users = null;

    /**
     * Constructor
     */
    function __construct()
    {
        $db = Database::getInstance();
        $this->db = $db->getDatabase();
    }

    /**
     * Return all user
     *
     * @return array
     */
    public function getUsers()
    {
        if (is_null($this->users)) {
            $this->loadUsers();
        }
        
        return (array) $this->users;
    }

    /**
     * Return single user by id
     *
     * @param integer $userId
     * @return User
     */
    public function getUserById($userId)
    {
        if (is_null($this->users) || !isset($this->users[$userId])) {
            $this->loadUsers();
        }

        $user = isset($this->users[$userId]) ? $this->users[$userId] : new User();

        return $user;
    }

    /**
     * Is login in use
     * 
     * @param string $login
     * @return boolean
     */
    public function isLoginInUse($login)
    {
        if (is_null($this->users) || !isset($this->users[$userId])) {
            $this->loadUsers();
        }

        foreach ($this->users as $user) {
            if ($user->getUserId() == $login) {
                return true;
            }
        }

        return false;
    }

    /**
     * Load user from database
     * 
     * @return Service
     */
    protected function loadUsers()
    {
        $query = "SELECT " . " * " . " FROM " . " tf_users " . " ORDER BY user_id";
        
        $statement = $this->db->prepare($query);
        $statement->execute();
        
        while ($data = $statement->fetch()) {
            $this->users[$data['uid']] = new User($data);
        }
        
        return $this;
    }

    /**
     * Delete user by id
     *
     * @param integer $uId
     * @return array
     */
    public function delete($uId)
    {
        $query = "DELETE " . " FROM " . " tf_users " . " WHERE " . " uid = :userId ";
        
        $statement = $this->db->prepare($query);
        $statement->execute([
            ':userId' => $uId
        ]);
        
        return $statement->fetch();
    }

    /**
     * Save user
     *
     * @param User $user            
     */
    public function save(User $user)
    {
        if ($user->getUid()) {
            $query = "UPDATE tf_users SET user_id = :userId, password = :password, hits = :hits, last_visit = :lastVisit, time_created = :timeCreated, user_level = :userLevel, hide_offline = :hideOffline, theme = :theme, language_file = :languageFile WHERE uid = :uid";
        } else {
            $query = "INSERT INTO tf_users VALUES (:uid, :userId, :password, :hits, :lastVisit, :timeCreated, :userLevel, :hideOffline, :theme, :languageFile)";
        }
        error_log($query);
        $statement = $this->db->prepare($query);
        $statement->execute([
            ':uid' => $user->getUid(),
            ':userId' => $user->getUserId(),
            ':password' => $user->getPassword(),
            ':hits' => $user->getHits(),
            ':lastVisit' => $user->getLastVisit(),
            ':timeCreated' => ($user->getUid()) ? $user->getTimeCreated() : time(),
            ':userLevel' => $user->getUserLevel(),
            ':hideOffline' => $user->getHideOffline(),
            ':theme' => $user->getTheme(),
            ':languageFile' => $user->getLanguageFile()
        ]);
        error_log(print_r($user, true));
        return $statement->fetch();
    }
}