<?php

namespace Harbour\MailerBundle\Driver;

use Harbour\MailerBundle\Exception\MailerException;
use Coral\CoreBundle\Utility\JsonParser;

class MandrillDriver extends AbstractDriver implements DriverInterface, TemplateDriverInterface
{
    /**
     * Template name
     *
     * @var string
     */
    private $template = null;

    /**
     * Email template identifier to be used. The template to be used is dependent on the driver
     *
     * @param string $template Template identifier
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    private function doCurlPostRequest($uri, $data)
    {

        $ch = curl_init($uri);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type:application/json'
        ));

        $rawResponse = curl_exec($ch);
        $httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if(false === $rawResponse)
        {
            throw new MailerException('Unable to connect to Mandrill service. Response code: ' . $httpCode);
        }

        $parser = new JsonParser($rawResponse, true);
        if($httpCode < 200 || $httpCode > 299)
        {
            $errorMessage = "Error connecting to Mandrill service.
                Response code: $httpCode.
                Error: ";
            if($parser->hasParam('message') && $parser->hasParam('name'))
            {
                $errorMessage .= '"' . $parser->getMandatoryParam('name') . ': ' . $parser->getMandatoryParam('message') . '"';
            }
            else
            {
                $errorMessage .= $rawResponse;
            }
            throw new MailerException($errorMessage);
        }

        return $parser;
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
        return $this->sendMultiple(array($recipient), $params);
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
        $to = array();
        foreach ($recipients as $recipient) {
            $to[] = array('email' => $recipient);
        }

        if(null === $params)
        {
            $params = array();
        }

        if(!is_array($params))
        {
            throw new MailerException("Passed params are not of type array. Unable to send.");
        }

        $defaults = array(
            "subject" => $this->subject,
            "from_email" => $this->sender,
            "to" => $to,
            "preserve_recipients" => false
        );

        if((null !== $this->template) && $this->template)
        {
            $templateContent = array();
            if(isset($params['template_content']))
            {
                $templateContent = $params['template_content'];
                unset($params['template_content']);
            }

            $response = $this->doCurlPostRequest(
                'https://mandrillapp.com/api/1.0/messages/send-template.json',
                array(
                    "key" => $this->password,
                    "template_name" => $this->template,
                    "template_content" => $templateContent,
                    "message" => array_merge($defaults, $params)
                )
            );
        }
        else
        {
            $defaults["html"] = $this->message;
            $defaults["text"] = $this->alternativeMessage;

            $response = $this->doCurlPostRequest(
                'https://mandrillapp.com/api/1.0/messages/send.json',
                array(
                    "key" => $this->password,
                    "template_name" => $this->template,
                    "message" => array_merge($defaults, $params)
                )
            );
        }

        $mandrillResponse = $response->getParams();

        if(
            isset($mandrillResponse[0]) &&
            isset($mandrillResponse[0]['status']) &&
            (
                $mandrillResponse[0]['status'] == 'sent' ||
                $mandrillResponse[0]['status'] == 'queued' ||
                $mandrillResponse[0]['status'] == 'scheduled'
            )
        )
        {
            return true;
        }

        return false;
    }
}