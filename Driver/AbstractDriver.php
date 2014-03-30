<?php

namespace Harbour\MailerBundle\Driver;

abstract class AbstractDriver implements DriverInterface
{
    /**
     * Username
     *
     * @var string
     */
    protected $username;

    /**
     * Password
     *
     * @var string
     */
    protected $password;

    /**
     * Email subject
     *
     * @var string
     */
    protected $subject = null;

    /**
     * Email message
     *
     * @var string
     */
    protected $message = null;

    /**
     * Email from field
     *
     * @var string
     */
    protected $sender = null;

    /**
     * Email alternative message
     *
     * @var string
     */
    protected $alternativeMessage = null;

    public function __construct($username, $password)
    {
        $this->setCredentials($username, $password);
    }

    /**
     * Set email driver credentials
     *
     * @param string $username Username
     * @param string $password Password
     */
    public function setCredentials($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Set email from field
     *
     * @param string $sender Email from
     */
    public function setSender($sender)
    {
        $this->sender = $sender;
    }

    /**
     * Set email subject to be sent
     *
     * @param string $subject Email subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * Set email message to be sent
     *
     * @param string $message Email message can be either html or text
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * In case primary message is html content, here can be set alternative plain text message
     *
     * @param string $message Alternative plain text message
     */
    public function setAlternativeMessage($message)
    {
        $this->alternativeMessage = $message;
    }
}