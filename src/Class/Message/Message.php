<?php
/**
 * TorrentBug
 *
 * @link      https://github.com/gratbrav/torrentbug
 * @license   https://github.com/gratbrav/torrentbug/blob/master/LICENSE
 */
namespace Gratbrav\Torrentbug\Message;

/**
 * Message Class
 *
 * Message
 *
 * @package Torrentbug
 * @author Gratbrav
 */
class Message
{

    protected $messageId = 0;

    protected $recipient;

    protected $sender;

    protected $message;

    protected $isNew = 1;

    protected $ip;

    protected $time = null;

    protected $forceRead = 0;

    function __construct($data = [])
    {
        $this->setMessageId($data['mid']);
        $this->setRecipient($data['to_user']);
        $this->setSender($data['from_user']);
        $this->setMessage($data['message']);
        $this->setIsNew($data['IsNew']);
        $this->setIp($data['ip']);
        $this->setTime($data['time']);
        $this->setForceRead($data['force_read']);
    }

    /**
     *
     * @return integer $messageId
     */
    public function getMessageId()
    {
        return (int)$this->messageId;
    }

    /**
     *
     * @return the $recipient
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     *
     * @return the $sender
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     *
     * @return the $message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     *
     * @return integer $isNew
     */
    public function getIsNew()
    {
        return (int)$this->isNew;
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
     * @return integer $time
     */
    public function getTime()
    {
        return is_null($this->time) ? time() : $this->time;
    }

    /**
     *
     * @return integer $forceRead
     */
    public function getForceRead()
    {
        return (int)$this->forceRead;
    }

    /**
     *
     * @param integer $messageId
     */
    public function setMessageId($messageId)
    {
        $this->messageId = (int)$messageId;
    }

    /**
     *
     * @param field_type $recipient
     */
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;
    }

    /**
     *
     * @param field_type $sender
     */
    public function setSender($sender)
    {
        $this->sender = $sender;
    }

    /**
     *
     * @param field_type $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     *
     * @param integer $isNew
     */
    public function setIsNew($isNew)
    {
        $this->isNew = (int)$isNew;
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
     * @param field_type $time
     */
    public function setTime($time)
    {
        $this->time = $time;
    }

    /**
     *
     * @param integer $forceRead
     */
    public function setForceRead($forceRead)
    {
        $this->forceRead = (int)$forceRead;
    }
}