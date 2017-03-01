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
 * @package  Torrentbug
 * @author   Gratbrav
 */
class Message
{
    protected $messageId;
    protected $recipient;
    protected $sender;
    protected $message;
    protected $isNew;
    protected $ip;
    protected $time;
    protected $forceRead;

    function __construct($data)
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
     * @return the $messageId
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * @return the $recipient
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * @return the $sender
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @return the $message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return the $isNew
     */
    public function getIsNew()
    {
        return $this->isNew;
    }

    /**
     * @return the $ip
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @return the $time
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @return the $forceRead
     */
    public function getForceRead()
    {
        return $this->forceRead;
    }

    /**
     * @param field_type $messageId
     */
    public function setMessageId($messageId)
    {
        $this->messageId = $messageId;
    }

    /**
     * @param field_type $recipient
     */
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;
    }

    /**
     * @param field_type $sender
     */
    public function setSender($sender)
    {
        $this->sender = $sender;
    }

    /**
     * @param field_type $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @param field_type $isNew
     */
    public function setIsNew($isNew)
    {
        $this->isNew = $isNew;
    }

    /**
     * @param field_type $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * @param field_type $time
     */
    public function setTime($time)
    {
        $this->time = $time;
    }

    /**
     * @param field_type $forceRead
     */
    public function setForceRead($forceRead)
    {
        $this->forceRead = $forceRead;
    }

}