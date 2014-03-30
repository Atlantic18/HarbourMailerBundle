<?php

namespace Harbour\MailerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;

use Coral\ContentBundle\Controller\ConfigurableJsonController;
use Coral\CoreBundle\Utility\JsonParser;
use Coral\CoreBundle\Exception\JsonException;
use Coral\CoreBundle\Exception\AuthenticationException;

use Harbour\MailerBundle\Entity\Email;
use Harbour\MailerBundle\Driver\TemplateDriverInterface;

/**
 * @Route("/v1/mailer")
 */
class DefaultController extends ConfigurableJsonController
{
    /**
     * @Route("/send")
     * @Method("POST")
     */
    public function sendAction()
    {
        $request       = new JsonParser($this->get("request")->getContent(), true);
        $configuration = $this->getConfiguration('config-mailer');

        $type = $request->getMandatoryParam('type');

        $this->throwNotFoundExceptionIf(!$configuration->hasParam($type), "Unknown message type: [$type].");

        //filter out unsubscribed recipients
        $recipients = $request->getMandatoryParam("recipients");

        $unsubscribeRecipients = array();
        $successRecipients = array();
        $failedRecipients = array();

        $this->filterOutUnsubscribes($recipients, $request->getMandatoryParam('group'), $unsubscribeRecipients);

        //get mailer for x emails
        $numberOfRecipients = count($recipients);
        if($numberOfRecipients)
        {
            //check number of recipients
            $status = $this->getStatus();
            if($numberOfRecipients > $status[$type]['remains'])
            {
                throw new \Exception('Number of recipients exceeds limit ' . $numberOfRecipients . '/' . $status[$type]['remains']);
            }

            $em = $this->getDoctrine()->getManager();

            //prepare mailer instances
            $mailers = $this->getMailerInstances($request, $numberOfRecipients);
            $mailerIndex = 0;
            $counter = 0;

            $params = $request->hasParam('params') ? $request->getMandatoryParam('params') : array();
            foreach ($recipients as $recipient)
            {
                if($mailers[$mailerIndex]['remains'] == 0)
                {
                    $mailerIndex++;
                }

                if($mailers[$mailerIndex]['instance']->send($recipient, $params))
                {
                    $successRecipients[] = $recipient;
                }
                else
                {
                    $failedRecipients[] = $recipient;
                }

                $email = new Email;
                $email->setSubject($request->getMandatoryParam('subject'));
                $email->setEmail($recipient);
                $email->setEmailGroup($request->getMandatoryParam('group'));
                $email->setChannel($mailers[$mailerIndex]['name']);
                $email->setAccount($this->getAccount());

                $em->persist($email);

                if($counter++ > 30)
                {
                    $em->flush();
                    $counter = 0;
                }

                $mailers[$mailerIndex]['remains'] = $mailers[$mailerIndex]['remains'] - 1;
            }

            $em->flush();
        }

        return new JsonResponse(array(
            'status'  => 'ok',
            'sent' => $successRecipients,
            'failed' => $failedRecipients,
            'unsubscribed' => $unsubscribeRecipients
        ), 200);
    }

    /**
     * Prepare mailer instances for needed number of recipients
     * @param  JsonParser $request        Json request
     * @param  int $numberOfRecipients    How many emails to be sent
     * @return array                      mailer_name => driver instance, remains
     */
    private function getMailerInstances($request, $numberOfRecipients)
    {
        $mailers     = array();
        $mailerLimit = 0;
        $type        = $request->getMandatoryParam('type');
        $allMailers  = $this->getMailersWithLimits();
        while($numberOfRecipients > $mailerLimit)
        {
            foreach ($allMailers[$type] as $name => $config)
            {
                $instanceName = '\\Harbour\\MailerBundle\\Driver\\' . ucfirst($config['driver']) . 'Driver';

                $instance = new $instanceName($config['username'], $config['password']);
                $instance->setSender($request->getMandatoryParam('sender'));
                $instance->setSubject($request->getMandatoryParam('subject'));
                if($request->hasParam('content.template'))
                {
                    if($instance instanceof TemplateDriverInterface)
                    {
                        $instance->setTemplate($request->getMandatoryParam('content.template'));
                    }
                    else
                    {
                        throw new \Exception('Selected driver doesn\'t support templates: ' . $config['driver']);
                    }
                }
                else
                {
                    $instance->setMessage(base64_decode($request->getMandatoryParam('content.html')));
                    $instance->setAlternativeMessage($request->getOptionalParam('content.text'));
                }

                $mailerLimit += $config['remains'];
                $mailers[] = array(
                    'instance' => $instance,
                    'remains'  => $config['remains'],
                    'name'     => $name
                );
            }
        }

        return $mailers;
    }

    /**
     * Filter out duplicates and unsubscribed emails
     * @param  array  $recipients            values as emails
     * @param  string $group                 unsubscribe group
     * @param  array  $unsubscribeRecipients return value of unsubscribed recipients
     * @return void
     */
    private function filterOutUnsubscribes(&$recipients, $group, &$unsubscribeRecipients)
    {
        $recipients   = array_unique($recipients);
        $recipients   = array_flip($recipients);
        $unsubscribes = $this->get('coral_connect')->doGetRequest('/v1/unsubscriber/list/' . $group);
        foreach ($unsubscribes->getMandatoryParam('items') as $unsubscribe)
        {
            if(isset($recipients[$unsubscribe]))
            {
                unset($recipients[$unsubscribe]);
                $unsubscribeRecipients[] = $unsubscribe;
            }
        }
        $recipients   = array_flip($recipients);
    }

    /**
     * Array with count of emails sent for a channel today
     *
     * @return array channel => sent
     */
    private function getEmailsSentTodayCount()
    {
        $currentDate = new \DateTime("now");
        $result = $this->getDoctrine()->getManager()->createQuery(
                'SELECT e.channel, COUNT(e.id) AS channel_count
                FROM HarbourMailerBundle:Email e
                WHERE e.account = :account_id
                AND e.created_at >= :created_at_start
                AND e.created_at <= :created_at_stop
                GROUP BY e.channel'
            )
            ->setParameter('account_id', $this->getAccount()->getId())
            ->setParameter('created_at_start', $currentDate->format('Y-m-d') . " 00:00")
            ->setParameter('created_at_stop', $currentDate->format('Y-m-d') . " 23:59")
            ->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        $channels = array();
        foreach($result as $row)
        {
            $channels[$row['channel']] = $row['channel_count'];
        }
        return $channels;
    }

    /**
     * Array with count of emails sent for a channel this month
     *
     * @return array channel => sent
     */
    private function getEmailsSentThisMonthCount()
    {
        $currentDate = new \DateTime("now");
        $result = $this->getDoctrine()->getManager()->createQuery(
                'SELECT e.channel, COUNT(e.id) AS channel_count
                FROM HarbourMailerBundle:Email e
                WHERE e.account = :account_id
                AND e.created_at >= :month_start
                AND e.created_at <= :month_end
                GROUP BY e.channel'
            )
            ->setParameter('account_id', $this->getAccount()->getId())
            ->setParameter('month_start', $currentDate->format('Y-m') . '-01')
            ->setParameter('month_end', $currentDate->format('Y-m') . '-32')
            ->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        $channels = array();
        foreach($result as $row)
        {
            $channels[$row['channel']] = $row['channel_count'];
        }
        return $channels;
    }

    /**
     * Mailers each with limits for the instance
     *
     * @return array nested of types and limits
     */
    private $mailersCache = null;

    private function getMailersWithLimits()
    {
        if(null === $this->mailersCache)
        {
            $emailsSentToday     = $this->getEmailsSentTodayCount();
            $emailsSentThisMonth = $this->getEmailsSentThisMonthCount();
            $configuration       = $this->getConfiguration('config-mailer');
            $mailers             = array();

            foreach($configuration->getParams() as $type => $config)
            {
                $mailers[$type] = array();
                foreach($configuration->getMandatoryParam($type . '.*') as $index => $channel)
                {
                    $name  = $configuration->getMandatoryParam($type . '[' . $index . '].name');
                    $limit = $configuration->getMandatoryParam($type . '[' . $index . '].limit');

                    $rawLimit = substr($limit, 0, -1);
                    $remains  = $rawLimit;

                    if(strpos($limit, 'd'))
                    {
                        $remains -= isset($emailsSentToday[$name]) ? $emailsSentToday[$name] : 0;
                    }
                    else
                    {
                        $remains -= isset($emailsSentThisMonth[$name]) ? $emailsSentThisMonth[$name] : 0;
                    }

                    $mailers[$type][$name] = array(
                        'limit'    => $limit,
                        'username' => $configuration->getMandatoryParam($type . '[' . $index . '].username'),
                        'password' => $configuration->getMandatoryParam($type . '[' . $index . '].password'),
                        'driver'   => $configuration->getMandatoryParam($type . '[' . $index . '].driver'),
                        'remains'  => $remains
                    );
                }
            }

            $this->mailersCache = $mailers;
        }

        return $this->mailersCache;
    }

    /**
     * Status for all channel types
     *
     * @return array channel name => daily, monthly, remains
     */
    private function getStatus()
    {
        $items = array();

        foreach ($this->getMailersWithLimits() as $type => $mailers)
        {
            $daily   = 0;
            $monthly = 0;
            $remains = 0;

            foreach ($mailers as $name => $limits)
            {
                $remains += $limits['remains'];
                $limit    = $limits['limit'];
                $rawLimit = substr($limit, 0, -1);

                if(strpos($limit, 'd'))
                {
                    $daily   += $rawLimit;
                }
                else
                {
                    $monthly += $rawLimit;
                }
            }

            $items[$type] = array(
                'daily'   => $daily,
                'monthly' => $monthly,
                'remains' => $remains
            );
        }

        return $items;
    }

    /**
     * @Route("/status")
     * @Method("GET")
     */
    public function statusAction()
    {
        return $this->createListJsonResponse($this->getStatus());
    }
}
