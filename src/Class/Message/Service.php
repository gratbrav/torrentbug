<?php
/**
 * TorrentBug
 *
 * @link      https://github.com/gratbrav/torrentbug
 * @license   https://github.com/gratbrav/torrentbug/blob/master/LICENSE
 */
namespace Gratbrav\Torrentbug\Message;

use Gratbrav\Torrentbug\Database;

/**
 * Message Service
 *
 * Handle all message events
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
     * Username
     * 
     * @var string
     */
    protected $user = null;

    /**
     * Message List
     * 
     * @var array
     */
    protected $messageList = null;

    /**
     * Constructor
     *
     * @param string $user            
     */
    function __construct($user)
    {
        $db = Database::getInstance();
        $this->db = $db->getDatabase();
        
        $this->user = $user;
    }

    /**
     * Return all messages
     *
     * @return array
     */
    public function getMessages()
    {
        if (is_null($this->messageList)) {
            $this->loadMessages();
        }
        
        return (array) $this->messageList;
    }

    /**
     * Return single message by id
     *
     * @param integer $msgId
     * @return Message
     */
    public function getMessageById($msgId)
    {
        if (is_null($this->messageList) || ! isset($this->messgeList[$msgId])) {
            $this->loadMessages();
        }
        
        $message = isset($this->messageList[$msgId]) ? $this->messageList[$msgId] : new Message();
        
        return $message;
    }

    /**
     * Load Messages from database
     * 
     * @return Service
     */
    protected function loadMessages()
    {
        $query = "SELECT " . " mid, " . " from_user, " . " message, " . " IsNew, " . " ip, " . " time, " . " force_read " . " FROM " . " tf_messages " . " WHERE " . "to_user = :user " . " ORDER BY time";
        
        $statement = $this->db->prepare($query);
        $statement->execute([
            ':user' => $this->user
        ]);
        
        while ($data = $statement->fetch()) {
            $this->messageList[$data['mid']] = new Message($data);
        }
        
        return $this;
    }

    /**
     * Delete message by id
     *
     * @param integer $msgId
     * @return Service
     */
    public function delete($msgId)
    {
        $query = "DELETE " . " FROM " . " tf_messages " . " WHERE " . " mid = :mid " . " AND to_user = :user ";
        
        $statement = $this->db->prepare($query);
        $statement->execute([
            ':mid' => $msgId,
            ':user' => $this->user
        ]);
        
        return $this;
    }

    /**
     * Mark message as read by id
     *
     * @param integer $msgId
     * @return Service
     */
    public function markAsRead($msgId)
    {
        $query = "UPDATE " . " tf_messages " . " SET " . " IsNew = 0, " . " force_read = 0" . " WHERE " . " mid = :mid " . " AND to_user = :user ";
        
        $statement = $this->db->prepare($query);
        $statement->execute([
            ':mid' => $msgId,
            ':user' => $this->user
        ]);
        
        return $this;
    }

    /**
     * Save message
     *
     * @param Message $message
     * @return Service
     */
    public function save(Message $message)
    {
        $query = "INSERT INTO tf_messages VALUES (:mid, :toUser, :fromUser, :message, :isNew, :ip, :time, :forceRead)";

        $statement = $this->db->prepare($query);
        $statement->execute([
            ':mid' => $message->getMessageId(),
            ':fromUser' => $message->getSender(),
            ':toUser' => $message->getRecipient(),
            ':message' => $message->getMessage(),
            ':isNew' => $message->getIsNew(),
            ':ip' => $_SERVER['REMOTE_ADDR'],
            ':time' => $message->getTime(),
            ':forceRead' => $message->getForceRead(),
        ]);

        return $this;
    }

    /**
     * Check user has a force to read message
     *
     * return boolean
     */
    public function hasForceReadMessage()
    {
        if (is_null($this->messageList)) {
            $this->loadMessages();
        }

        foreach ((array)$this->messageList as $message) {
            if ($message->getForceRead()) {
                return true;
            }
        }

        return false;
    }

}