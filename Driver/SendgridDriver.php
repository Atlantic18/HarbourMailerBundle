<?php

namespace Harbour\MailerBundle\Driver;

class SendgridDriver extends AbstractDriver implements DriverInterface
{
    /**
     * Mailer instance
     *
     * @var \Swift_Mailer
     */
    private $mailer = null;

    /**
     * Set email driver credentials
     *
     * @param string $username Username
     * @param string $password Password
     */
    public function setCredentials($username, $password)
    {
        $this->mailer = null;

        parent::setCredentials($username, $password);
    }

    /**
     * Swift mailer instance
     *
     * @return \Swift_Mailer Mailer
     */
    private function getMailer()
    {
        if(null === $this->mailer)
        {
            $transport = \Swift_SmtpTransport::newInstance('smtp.sendgrid.net', 587);
            $transport->setUsername($this->username);
            $transport->setPassword($this->password);

            $this->mailer = \Swift_Mailer::newInstance($transport);
        }

        return $this->mailer;
    }

    /**
     * Send email to a single recipient
     *
     * @param  string $recipient Email address
     * @return boolean           True in case successfully sent
     * @throws \Harbour\MailerBundle\Exception\MailerException
     */
    public function send($recipient, $params = null)
    {
        $mailer = $this->getMailer();

        $message = \Swift_Message::newInstance()
            ->setSubject($this->subject)
            ->setFrom($this->sender)
            ->setTo($recipient)
            ->setBody($this->message, 'text/html');

        if($this->alternativeMessage)
        {
            $message->addPart($this->alternativeMessage, 'text/plain');
        }

        return $mailer->send($message) ? true : false;
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
        $failedRecipients = array();

        $mailer = $this->getMailer();

        $message = \Swift_Message::newInstance()
            ->setSubject($this->subject)
            ->setFrom($this->sender)
            ->setBody($this->message, 'text/html');

        if($this->alternativeMessage)
        {
            $message->addPart($this->alternativeMessage, 'text/plain');
        }

        foreach ($recipients as $recipient)
        {
            $message->setTo($recipient);

            if(!$mailer->send($message))
            {
                $failedRecipients[] = $recipient;
            }
        }

        return $failedRecipients;
    }
}