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
 * @ORM\Table(name="invitation")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @HexaaAssert\InvitationHasValidTarget()
 */
class Invitation
{
    
    /**
     * @ORM\ManyToMany(targetEntity="Principal")
     * @ORM\JoinTable(name="invitation_principal")
     * @Exclude
     */
    private $principals;
    
    
    public function __construct() {
        $this->emails = new \Doctrine\Common\Collections\ArrayCollection();
        $this->principals = new \Doctrine\Common\Collections\ArrayCollection();
        
    }
    
    
    /**
     * @var string
     *
     * @ORM\Column(name="emails", type="string", length=255, nullable=false)
     * @Assert\NotNull()
     * })
     */
    private $emails;
    
    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=255, nullable=false)
     */
    private $token;
    
    /**
     * @var string
     *
     * @ORM\Column(name="landing_url", type="string", length=255, nullable=true)
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
     * @ORM\Column(name="start_date", type="datetime", nullable=true)
     */
    private $startDate;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_date", type="datetime", nullable=true)
     */
    private $endDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="limit", type="bigint", nullable=true)
     */
    private $limit;


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
     */
    private $role;

    /**
     * @var \Hexaa\StorageBundle\Entity\Organization
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Organization")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="CASCADE")
     * })
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
        return $this->service->getId();       
    }
    
    
    /**
     * @VirtualProperty
     * @SerializedName("organization_id")
    */
    public function getOrganizationId()
    {
        return $this->service->getId();       
    }
    
    
    /**
     * @VirtualProperty
     * @SerializedName("role_id")
    */
    public function getRoleId()
    {
        return $this->service->getId();       
    }

    /**
     * Set emails
     *
     * @param string $emails
     * @return Invitation
     */
    public function setEmails($emails)
    {
        $this->emails = $emails;

        return $this;
    }
    
    

    /**
     * Add email / set status
     *
     * @param string $emails
     * @return Invitation
     */
    public function setEmail($email, $status = "pending")
    {
        $this->emails[$email] = $status;

        return $this;
    }

    /**
     * Get emails
     *
     * @return string 
     */
    public function getEmails()
    {
        return $this->emails;
    }
    
    /**
     * Remove email
     *
     * @param string $email
     * @return Invitation
     */
    public function removeEmail($email)
    {
        $this->emails->removeElement($email);

        return $this;
    }
    

    /**
     * Set token
     *
     * @param string $token
     * @return Invitation
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string 
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set landingUrl
     *
     * @param string $landingUrl
     * @return Invitation
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
     * @return Invitation
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
     * @return Invitation
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
     * @return Invitation
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
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return Invitation
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime 
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     * @return Invitation
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime 
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set limit
     *
     * @param integer $limit
     * @return Invitation
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Get limit
     *
     * @return integer 
     */
    public function getLimit()
    {
        return $this->limit;
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
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return Invitation
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime 
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Add principals
     *
     * @param \Hexaa\StorageBundle\Entity\Principal $principals
     * @return Invitation
     */
    public function addPrincipal(\Hexaa\StorageBundle\Entity\Principal $principals)
    {
        $this->principals[] = $principals;

        return $this;
    }

    /**
     * Remove principals
     *
     * @param \Hexaa\StorageBundle\Entity\Principal $principals
     */
    public function removePrincipal(\Hexaa\StorageBundle\Entity\Principal $principals)
    {
        $this->principals->removeElement($principals);
    }

    /**
     * Get principals
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPrincipals()
    {
        return $this->principals;
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
     * Set service
     *
     * @param \Hexaa\StorageBundle\Entity\Service $service
     * @return Invitation
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
}
