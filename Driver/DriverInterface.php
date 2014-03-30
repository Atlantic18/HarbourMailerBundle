<?php

namespace Harbour\MailerBundle\Driver;

interface DriverInterface
{
    /**
     * Set email driver credentials
     *
     * @param string $username Username
     * @param string $password Password
     */
    public function setCredentials($username, $password);

    /**
     * Set email from field
     *
     * @param string $sender Email from
     */
    public function setSender($sender);

    /**
     * Set email subject to be sent
     *
     * @param string $subject Email subject
     */
    public function setSubject($subject);

    /**
     * Set email message to be sent
     *
     * @param string $message Email message can be either html or text
     */
    public function setMessage($message);

    /**
     * In case primary message is html content, here can be set alternative plain text message
     *
     * @param string $message Alternative plain text message
     */
    public function setAlternativeMessage($message);

    /**
     * Send email to a single recipient
     *
     * @param  string $recipient Email address
     * @return boolean           True in case successfully sent
     * @throws \Harbour\MailerBundle\Exception\MailerException
     */
    public function send($recipient, $params = null);

    /**
     * Send email to multiple recipients
     *
     * @param  array $recipients Email addresses
     * @return array failed recipients
     * @throws \Harbour\MailerBundle\Exception\MailerException
     */
    public function sendMultiple($recipients, $params = null);
}