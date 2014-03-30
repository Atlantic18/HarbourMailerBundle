<?php

namespace Harbour\MailerBundle\Driver;

interface TemplateDriverInterface
{
    /**
     * Email template identifier to be used. The template to be used is dependent on the driver
     *
     * @param string $template Template identifier
     */
    public function setTemplate($template);
}