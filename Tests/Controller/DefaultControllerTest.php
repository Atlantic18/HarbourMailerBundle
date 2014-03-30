<?php

namespace Harbour\MailerBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use Coral\CoreBundle\Utility\JsonParser;
use Coral\CoreBundle\Test\JsonTestCase;

class DefaultControllerTest extends JsonTestCase
{
    public function testStatus()
    {
        $this->loadFixtures(array(
            'Coral\CoreBundle\Tests\DataFixtures\ORM\MinimalSettingsData'
        ));

        $client = $this->doGetRequest('/v1/mailer/status');

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertCount(2, $jsonRequest->getMandatoryParam('items'));
        $this->assertEquals(3, $jsonRequest->getMandatoryParam('items.marketing.daily'));
        $this->assertEquals(1, $jsonRequest->getMandatoryParam('items.marketing.monthly'));
        $this->assertEquals(4, $jsonRequest->getMandatoryParam('items.marketing.remains'));
        $this->assertEquals(2, $jsonRequest->getMandatoryParam('items.transactional.remains'));
    }

    public function testSendInvalidType()
    {
        $this->loadFixtures(array(
            'Coral\CoreBundle\Tests\DataFixtures\ORM\MinimalSettingsData'
        ));

        $client = $this->doPostRequest(
            '/v1/mailer/send',
            '{
                "type": "notype",
                "group": "ormd2",
                "sender": "support@atlantic18.com",
                "recipients": [ "email@example.com", "anotheremail@example.com" ],
                "content": { "html": "base64encodedcontent" }
            }'
        );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 404);
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $this->assertEquals('failed', $jsonRequest->getMandatoryParam('status'));
        $this->assertRegExp('/unknown/', strtolower($jsonRequest->getMandatoryParam('message')));
    }

    public function testSendOverLimit()
    {
        $this->loadFixtures(array(
            'Coral\CoreBundle\Tests\DataFixtures\ORM\MinimalSettingsData'
        ));

        $client = $this->doPostRequest(
            '/v1/mailer/send',
            '{
                "type": "transactional",
                "group": "ormd2",
                "sender": "support@atlantic18.com",
                "recipients": [ "email@example.com", "email2@example.com", "email3@example.com", "email4@example.com" ],
                "content": { "html": "base64encodedcontent" }
            }'
        );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 500);
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $this->assertEquals('failed', $jsonRequest->getMandatoryParam('status'));
        $this->assertRegExp('/limit/', $jsonRequest->getMandatoryParam('message'));
    }

    public function testSend()
    {
        $this->loadFixtures(array(
            'Coral\CoreBundle\Tests\DataFixtures\ORM\MinimalSettingsData'
        ));

        $client = $this->doPostRequest(
            '/v1/mailer/send',
            '{
                "type": "marketing",
                "group": "ormd2",
                "sender": "support@atlantic18.com",
                "subject": "some subject",
                "recipients": [ "email@example.com", "email2@example.com", "invalid@example.com", "email3@example.com" ],
                "content": { "html": "base64encodedcontent" }
            }'
        );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertCount(2, $jsonRequest->getMandatoryParam('sent'));
        $this->assertCount(1, $jsonRequest->getMandatoryParam('failed'));
        $this->assertCount(1, $jsonRequest->getMandatoryParam('unsubscribed'));
        $this->assertEquals('email2@example.com', $jsonRequest->getMandatoryParam('sent[0]'));
        $this->assertEquals('email3@example.com', $jsonRequest->getMandatoryParam('sent[1]'));
        $this->assertEquals('email@example.com', $jsonRequest->getMandatoryParam('unsubscribed[0]'));
        $this->assertEquals('invalid@example.com', $jsonRequest->getMandatoryParam('failed[0]'));

        //check status
        $client = $this->doGetRequest('/v1/mailer/status');

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertCount(2, $jsonRequest->getMandatoryParam('items'));
        $this->assertEquals(1, $jsonRequest->getMandatoryParam('items.marketing.remains'));
    }
}
