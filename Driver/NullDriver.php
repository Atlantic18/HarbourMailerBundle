<?php

namespace Harbour\MailerBundle\Driver;

/**
 * Used for testing, doesn't send anything anywhere
 */

class NullDriver extends AbstractDriver implements DriverInterface
{
    /**
     * Send email to a single recipient
     *
     * @param  string $recipient Email address
     * @return boolean           True in case successfully sent
     * @throws \Harbour\MailerBundle\Exception\MailerException
     */
    public function send($recipient, $params = null)
    {
        return (false === strpos($recipient, 'invalid')) ? true : false;
    }

    /**
     * Send email to multiple recipients
     *
     * @param  array $recipients Email addresses
     * @return array failed recipients
     * @throws \Harbour\MailerBundle\Exception\MailerException
     */
    public function sendMultiple($recipients, $params = null)
    {
        return array();
    }
}