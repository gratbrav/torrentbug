<?php
/**
* User Authentication Class
*
* Class for authentication a user
*
* @package  Torrentbug
* @author   Gratbrav
* @version  $Revision: 1.0 $
* @access   public
*/
namespace User;

class Authentication
{
    /**
     * Database reference
     * @var ressource
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
     * @param string $user      User login
     * @param string $password  User password
     */
    function __construct($user = '', $password = '')
    {
        $this->setUser($user);
        $this->setPassword($password);

        $db = \Database::getInstance();
        $this->db = $db->getDatabase();
    }

    /**
     * Check if login exists
     * 
     * @param string $user      User login
     * @param string $password  User password
     * @return unknown|boolean
     */
    public function checkLogin($user = '', $password = '') 
    {
        $this->setUser($user);
        $this->setPassword($password);

		if (!empty($this->user) && !empty($this->password)) {
			$user = $this->user;
			$pwd = md5($this->password);
			
			$query = "SELECT uid, hits, hide_offline, theme, language_file FROM tf_users WHERE user_id= :user AND password= :password";
			$statement = $this->db->prepare($query);
			$statement->execute([':user' => $user, ':password' => $pwd]);

			return $statement->fetch();
		}
		
		return false;
	}
	
    /**
     * Set login
     * 
     * @param string $user  Login
     * @return \User\Authentication
     */
    protected function setUser($user)
    {
        if (!empty($user)) {
            $this->user = (string)$user;
        }

        return $this;
    }

    /**
     * Set password
     * 
     * @param string $password  Password
     * @return \User\Authentication
     */
    protected function setPassword($password)
    {
        if (!empty($password)) {
            $this->password = (string)$password;
        }

        return $this;
    }

}
