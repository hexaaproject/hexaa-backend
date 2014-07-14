<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Hexaa\ApiBundle\Validator\Constraints as HexaaAssert;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Exclude;

/**
 * Invitation
 *
 * @ORM\Table(name="email_invitation")
 * @ORM\Entity
 * @HexaaAssert\InvitationHasValidTarget()
 * @ORM\HasLifecycleCallbacks
 */
class EmailInvitation
{
    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=false)
     * 
     * @Assert\Email()
     * @Assert\NotBlank()
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255, columnDefinition="ENUM('accepted', 'pending', 'rejected')", nullable=false)
     */
    private $status;
    
    /**
     * @var string
     *
     * @ORM\Column(name="landing_url", type="string", length=255, nullable=true)
     * @Assert\Url()
     */
    private $landingUrl;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="do_redirect", type="boolean", nullable=true)
     */
    private $doRedirect;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="as_manager", type="boolean", nullable=true)
     */
    private $asManager;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text", nullable=true)
     * @Assert\NotNull()
     */
    private $message;

    /**
     * @var integer
     *
     * @ORM\Column(name="counter", type="bigint", nullable=true)
     */
    private $counter;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="accept_at", type="datetime", nullable=true)
     */
    private $acceptAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_reinvite_at", type="datetime", nullable=true)
     */
    private $lastReinviteAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Hexaa\StorageBundle\Entity\Role
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Role")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="role_id", referencedColumnName="id")
     * })
     * @Exclude()
     */
    private $role;

    /**
     * @var \Hexaa\StorageBundle\Entity\Organization
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Organization")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Exclude()
     */
    private $organization;    

    /**
     * @var \Hexaa\StorageBundle\Entity\Service
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Service")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="service_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Exclude()
     */
    private $service;

    /**
     * @var \Hexaa\StorageBundle\Entity\Principal
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Principal")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="inviter_id", referencedColumnName="id")
     * })
     */
    private $inviter;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     */
    private $updatedAt;

    /**
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps() {
        $this->setUpdatedAt(new \DateTime('now'));

        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new \DateTime('now'));
        }
    }
    
    
    /**
     * @VirtualProperty
     * @SerializedName("service_id")
    */
    public function getServiceId()
    {
        if (isset($this->service)) return $this->service->getId();       
    }
    
    
    /**
     * @VirtualProperty
     * @SerializedName("organization_id")
    */
    public function getOrganizationId()
    {
        if (isset($this->organization)) return $this->organization->getId();       
    }
    
    
    /**
     * @VirtualProperty
     * @SerializedName("role_id")
    */
    public function getRoleId()
    {
        if (isset($this->role)) return $this->role->getId();       
    }


    /**
     * Set email
     *
     * @param string $email
     * @return Invitation
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

    /**
     * Set uuid
     *
     * @param string $uuid
     * @return Invitation
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * Get uuid
     *
     * @return string 
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return Invitation
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set counter
     *
     * @param integer $counter
     * @return Invitation
     */
    public function setCounter($counter)
    {
        $this->counter = $counter;

        return $this;
    }

    /**
     * Get counter
     *
     * @return integer 
     */
    public function getCounter()
    {
        return $this->counter;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Invitation
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set acceptAt
     *
     * @param \DateTime $acceptAt
     * @return Invitation
     */
    public function setAcceptAt($acceptAt)
    {
        $this->acceptAt = $acceptAt;

        return $this;
    }

    /**
     * Get acceptAt
     *
     * @return \DateTime 
     */
    public function getAcceptAt()
    {
        return $this->acceptAt;
    }

    /**
     * Set lastReinviteAt
     *
     * @param \DateTime $lastReinviteAt
     * @return Invitation
     */
    public function setLastReinviteAt($lastReinviteAt)
    {
        $this->lastReinviteAt = $lastReinviteAt;

        return $this;
    }

    /**
     * Get lastReinviteAt
     *
     * @return \DateTime 
     */
    public function getLastReinviteAt()
    {
        return $this->lastReinviteAt;
    }

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
     * Set role
     *
     * @param \Hexaa\StorageBundle\Entity\Role $role
     * @return Invitation
     */
    public function setRole(\Hexaa\StorageBundle\Entity\Role $role = null)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role
     *
     * @return \Hexaa\StorageBundle\Entity\Role 
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set organization
     *
     * @param \Hexaa\StorageBundle\Entity\Organization $organization
     * @return Invitation
     */
    public function setOrganization(\Hexaa\StorageBundle\Entity\Organization $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization
     *
     * @return \Hexaa\StorageBundle\Entity\Organization 
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Set inviter
     *
     * @param \Hexaa\StorageBundle\Entity\Principal $inviter
     * @return Invitation
     */
    public function setInviter(\Hexaa\StorageBundle\Entity\Principal $inviter = null)
    {
        $this->inviter = $inviter;

        return $this;
    }

    /**
     * Get inviter
     *
     * @return \Hexaa\StorageBundle\Entity\Principal 
     */
    public function getInviter()
    {
        return $this->inviter;
    }

    /**
     * Set service
     *
     * @param \Hexaa\StorageBundle\Entity\Service $service
     * @return EmailInvitation
     */
    public function setService(\Hexaa\StorageBundle\Entity\Service $service = null)
    {
        $this->service = $service;

        return $this;
    }

    /**
     * Get service
     *
     * @return \Hexaa\StorageBundle\Entity\Service 
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Set landingUrl
     *
     * @param string $landingUrl
     * @return EmailInvitation
     */
    public function setLandingUrl($landingUrl)
    {
        $this->landingUrl = $landingUrl;

        return $this;
    }

    /**
     * Get landingUrl
     *
     * @return string 
     */
    public function getLandingUrl()
    {
        return $this->landingUrl;
    }

    /**
     * Set doRedirect
     *
     * @param boolean $doRedirect
     * @return EmailInvitation
     */
    public function setDoRedirect($doRedirect)
    {
        $this->doRedirect = $doRedirect;

        return $this;
    }

    /**
     * Get doRedirect
     *
     * @return boolean 
     */
    public function getDoRedirect()
    {
        return $this->doRedirect;
    }

    /**
     * Set asManager
     *
     * @param boolean $asManager
     * @return EmailInvitation
     */
    public function setAsManager($asManager)
    {
        $this->asManager = $asManager;

        return $this;
    }

    /**
     * Get asManager
     *
     * @return boolean 
     */
    public function getAsManager()
    {
        return $this->asManager;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return EmailInvitation
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage()
    {
        return $this->message;
    }
}
