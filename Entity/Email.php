<?php
namespace Harbour\MailerBundle\Entity;

use Doctrine\ORM\Mapping AS ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="harbour_email", 
 *     indexes={
 *         @ORM\Index(name="ChannelCreatedAtIndex", columns={"channel","created_at"}),
 *         @ORM\Index(name="ChannelIndex", columns={"channel"}),
 *         @ORM\Index(name="CreatedAtIndex", columns={"created_at"})
 *     }
 * )
 */
class Email
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=64, nullable=false)
     */
    private $subject;

    /**
     * @ORM\Column(type="string", length=32, nullable=false)
     */
    private $email_group;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=32, nullable=false)
     */
    private $channel;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $created_at;

    /**
     * @ORM\ManyToOne(targetEntity="Coral\CoreBundle\Entity\Account")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", nullable=false)
     */
    private $account;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set subject
     *
     * @param string $subject
     * @return Email
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set channel
     *
     * @param string $channel
     * @return Email
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * Get channel
     *
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return Email
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get created_at
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set Account
     *
     * @param \Coral\CoreBundle\Entity\Account $account
     * @return Email
     */
    public function setAccount(\Coral\CoreBundle\Entity\Account $account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * Get Account
     *
     * @return \Coral\CoreBundle\Entity\Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * Set email_group
     *
     * @param string $emailGroup
     * @return Email
     */
    public function setEmailGroup($emailGroup)
    {
        $this->email_group = $emailGroup;

        return $this;
    }

    /**
     * Get email_group
     *
     * @return string
     */
    public function getEmailGroup()
    {
        return $this->email_group;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Email
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }
}