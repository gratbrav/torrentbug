<?php
namespace Message;

/**
* Message Service
*
* Handle alle message events
*
* @package  Torrentbug
* @author   Gratbrav
*/
class Service
{
    /**
     * DB reference
     * @var ressource
     */
    protected $db = null;

    /**
     * Username
     * 
     * @var string
     */
    protected $user;

    protected $messages;

    /**
     * Constructor
     * 
     * @param string $user
     */
    function __construct($user)
    {
        $db = \Database::getInstance();
        $this->db = $db->getDatabase();

        $this->user = $user;
    }

    public function getMessages()
    {
        if (is_null($this->messages)) {
            $this->loadMessages();
        }
        
        return (array)$this->messages;
    }

    protected function loadMessages()
    {
        $user = $this->db->qstr($this->user);

        $sql = "SELECT "
                . " mid, "
                . " from_user, "
                . " message, "
                . " IsNew, "
                . " ip, "
                . " time, "
                . " force_read "
            . " FROM "
                . " tf_messages "
            . " WHERE "
                . "to_user = " . $user
            . " ORDER BY time";

        $result = $this->db->Execute($sql);
        // showError($db,$sql);

        while ($data = $result->FetchRow()) {
            $this->messages[] = new Message($data);
        }
    }

    /**
     * Delete message by id
     * 
     * @param number $msgId
     * @return unknown
     */
    public function delete($msgId)
    {
        $user = $this->db->qstr($this->user);
        $msgId = $this->db->qstr($msgId);

        $sql = "DELETE " 
            . " FROM "
                . " tf_messages "
            . " WHERE "
                . " mid = " . $msgId 
                . " AND to_user = " . $user;

        $result = $this->db->Execute($sql);
        // showError($db,$sql);
        return $result;
    }
}

