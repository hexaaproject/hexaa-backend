<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Exclude;

/**
 * Invitation
 *
 * @ORM\Table(name="url_invitation")
 * @ORM\Entity
 */
class UrlInvitation
{
    /**
     * @var string
     *
     * @ORM\Column(name="emails", type="string", length=255, nullable=false)
     * @Assert\NotNull()
     */
    private $emails;
    
    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=false)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255, columnDefinition="ENUM('accepted', 'pending', 'rejected')", nullable=false)
     * @Assert\NotNull()
     * @Assert\Choice(choices = {"accepted", "pending", "rejected"}, message = "Choose a valid value.")
     */
    private $status;
    
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
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

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
     *   @ORM\JoinColumn(name="organization_id", referencedColumnName="id")
     * })
     */
    private $organization; 

    /**
     * @var \Hexaa\StorageBundle\Entity\Service
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Service")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="service_id", referencedColumnName="id")
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
     * Set url
     *
     * @param string $url
     * @return MassInvitation
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return MassInvitation
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set counter
     *
     * @param integer $counter
     * @return MassInvitation
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
     * @return MassInvitation
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
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return MassInvitation
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
     * @return MassInvitation
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
     * @return MassInvitation
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
     * Set role
     *
     * @param \Hexaa\StorageBundle\Entity\Role $role
     * @return MassInvitation
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
     * @return MassInvitation
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
     * @return MassInvitation
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
     * Constructor
     */
    public function __construct()
    {
        $this->principals = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add principals
     *
     * @param \Hexaa\StorageBundle\Entity\Principal $principals
     * @return UrlInvitation
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
     * Set status
     *
     * @param string $status
     * @return UrlInvitation
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
     * Set landingUrl
     *
     * @param string $landingUrl
     * @return UrlInvitation
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
     * @return UrlInvitation
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
     * Set emails
     *
     * @param string $emails
     * @return UrlInvitation
     */
    public function setEmails($emails)
    {
        $this->emails = $emails;

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
     * Set asManager
     *
     * @param boolean $asManager
     * @return UrlInvitation
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
     * @return UrlInvitation
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
     * Set service
     *
     * @param \Hexaa\StorageBundle\Entity\Service $service
     * @return UrlInvitation
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
}
