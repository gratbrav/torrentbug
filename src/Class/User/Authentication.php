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
 * User Authentication Class
 *
 * Class for authentication a user
 *
 * @package Torrentbug
 * @author Gratbrav
 */
class Authentication
{

    /**
     * Database reference
     * 
     * @var Database
     */
    protected $db = null;

    /**
     * User login
     * 
     * @var string
     */
    protected $user = '';

    /**
     * User password
     * 
     * @var string
     */
    protected $password = '';

    /**
     * Constructor
     *
     * @param string $user
     *            User login
     * @param string $password
     *            User password
     */
    function __construct($user = '', $password = '')
    {
        $this->setUser($user);
        $this->setPassword($password);
        
        $db = Database::getInstance();
        $this->db = $db->getDatabase();
    }

    /**
     * Check if login exists
     *
     * @param string $user
     *            User login
     * @param string $password
     *            User password
     * @return integer
     */
    public function checkLogin($user = '', $password = '')
    {
        $this->setUser($user);
        $this->setPassword($password);
        
        if (! empty($this->user) && ! empty($this->password)) {
            $pwd = md5($this->password);
            
            $query = "SELECT uid FROM tf_users WHERE user_id= :user AND password= :password";

            $statement = $this->db->prepare($query);
            $statement->execute([
                ':user' => $this->user,
                ':password' => $pwd
            ]);
            $row = $statement->fetch();
        }
        
        return (int)(isset($row['uid']) ? $row['uid'] : 0);
    }

    /**
     * Set login
     *
     * @param string $user
     *            Login
     * @return \User\Authentication
     */
    protected function setUser($user)
    {
        if (! empty($user)) {
            $this->user = (string) $user;
        }
        
        return $this;
    }

    /**
     * Set password
     *
     * @param string $password
     *            Password
     * @return Authentication
     */
    protected function setPassword($password)
    {
        if (! empty($password)) {
            $this->password = (string) $password;
        }
        
        return $this;
    }
}