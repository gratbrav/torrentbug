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
 * Handle alle message events
 *
 * @package  Torrentbug
 * @author   Gratbrav
 */
class Service
{
    /**
     * DB reference
     * @var Database
     */
    protected $db = null;

    /**
     * Username
     * @var string
     */
    protected $user = null;

    /**
     * Messages
     * @var array
     */
    protected $messages = null;

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
        if (is_null($this->messages)) {
            $this->loadMessages();
        }

        return (array)$this->messages;
    }

    /**
     * Return single message by id
     * 
     * @param numeric $msgId
     * @return Message
     */
    public function getMessageById($msgId)
    {
        if (is_null($this->messages) || !isset($this->messges[$msgId])) {
            $this->loadMessages();
        }

        $message = isset($this->messages[$msgId]) ? $this->messages[$msgId] : new Message();

        return $message;
    }

    /**
     * Load Messages from database
     * @return Service
     */
    protected function loadMessages()
    {
        $query = "SELECT "
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
                . "to_user = :user "
            . " ORDER BY time";

        $statement = $this->db->prepare($query);
        $statement->execute([
            ':user' => $this->user,
        ]);

        while ($data = $statement->fetch()) {
            $this->messages[$data['mid']] = new Message($data);
        }

        return $this;
    }

    /**
     * Delete message by id
     * 
     * @param numeric $msgId
     * @return array
     */
    public function delete($msgId)
    {
        $query = "DELETE " 
            . " FROM "
                . " tf_messages "
            . " WHERE "
                . " mid = :mid "
                . " AND to_user = :user ";

        $statement = $this->db->prepare($query);
        $statement->execute([
            ':mid' => $msgId,
            ':user' => $this->user,
        ]);

        return $statement->fetch();
    }

    /**
     * Mark message as read by id
     * 
     * @param numeric $msgId
     * @return unknown
     */
    public function markAsRead($msgId)
    {
        $query = "UPDATE "
                . " tf_messages "
            . " SET "
                . " IsNew = 0, "
                . " force_read = 0"
            . " WHERE "
                . " mid = :mid "
                . " AND to_user = :user ";

        $statement = $this->db->prepare($query);
        $statement->execute([
            ':mid' => $msgId,
            ':user' => $this->user,
        ]);

        return $statement->fetch();
    }

}