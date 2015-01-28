<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Hexaa\ApiBundle\Validator\Constraints as HexaaAssert;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Type;
use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

/**
 * Invitation
 *
 * @ORM\Table(name="invitation", indexes={@ORM\Index(name="inviter_id_idx", columns={"inviter_id"}), @ORM\Index(name="organization_id_idx", columns={"organization_id"}), @ORM\Index(name="service_id_idx", columns={"service_id"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @HexaaAssert\InvitationHasValidTarget()
 */
class Invitation {

    public function __construct() {
        //$this->emails = new \Doctrine\Common\Collections\ArrayCollection();
        $this->emails = array();
        $this->statuses = array();
        $this->displayNames = array();
    }

    /**
     * @var array
     *
     * @ORM\Column(name="emails", type="array", length=16777215, nullable=false)
     * @Assert\Type(type="array")
     * @Assert\All({
     *     @Assert\Email(
     *          strict=true,
     *          message="The given address: {{ value }} is not a valid e-mail address.")
     * })
     */
    private $emails;

    /**
     * @var array
     *
     * @ORM\Column(name="statuses", type="array", length=16777215, nullable=false)
     * })
     */
    private $statuses;

    /**
     * @var array
     *
     * @ORM\Column(name="display_names", type="array", length=16777215, nullable=false)
     * })
     */
    private $displayNames;

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
    private $landingUrl = null;

    /**
     * @var string
     *
     * @ORM\Column(name="locale", type="string", length=255, nullable=true)
     */
    private $locale = "en_EN";

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
     * @ORM\Column(name="principal_limit", type="bigint", nullable=true)
     */
    private $limit;

    /**
     * @var integer
     *
     * @ORM\Column(name="reinvite_count", type="bigint", nullable=true)
     */
    private $reinviteCount;

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
     *   @ORM\JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE")
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
     * @Type("integer")
     */
    public function getServiceId() {
        if (isset($this->service))
            return $this->service->getId();
    }

    /**
     * @VirtualProperty
     * @SerializedName("organization_id")
     * @Type("integer")
     */
    public function getOrganizationId() {
        if (isset($this->organization))
            return $this->organization->getId();
    }

    /**
     * @VirtualProperty
     * @SerializedName("role_id")
     * @Type("integer")
     */
    public function getRoleId() {
        if (isset($this->role))
            return $this->role->getId();
    }

    /**
     * Set emails
     *
     * @param array $emails
     * @return Invitation
     */
    public function setEmails($emails) {
        $this->emails = $emails;
        foreach ($emails as $email) {
            $this->statuses[$email] = "pending";
        }
        foreach (array_keys($this->statuses) as $statusMail){
            if (!in_array($statusMail, $emails)){
                unset($this->statuses[$statusMail]);
                unset($this->displayNames[$statusMail]);
            }
        }

        return $this;
    }

    /**
     * Add email / set status
     *
     * @param string $email
     * @param string $status
     * @return Invitation
     */
    public function setEmail($email, $status = "pending") {
        if (!in_array($email, $this->emails)) {
            $this->emails[] = $email;
        }
        $this->statuses[$email] = $status;


        return $this;
    }

    /**
     * Get emails
     *
     * @return string 
     */
    public function getEmails() {
        return $this->emails;
    }

    /**
     * Get statuses
     *
     * @return string 
     */
    public function getStatuses() {
        return $this->statuses;
    }

    /**
     * Get display names
     *
     * @return string 
     */
    public function getDisplayNames() {
        return $this->displayNames;
    }

    /**
     * Remove email
     *
     * @param string $email
     * @return Invitation
     */
    public function removeEmail($email) {
        //unset($this->emails[$email]);

        if (($key = array_search($email, $this->emails)) !== false) {
            unset($this->emails[$key]);

            unset($this->displayNames[$email]);

            unset($this->statuses[$email]);
        }

        return $this;
    }

    /**
     * Set token
     *
     * @param string $token
     * @return Invitation
     */
    public function setToken($token) {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string 
     */
    public function getToken() {
        return $this->token;
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return Invitation
     */
    public function setLocale($locale) {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale
     *
     * @return string 
     */
    public function getLocale() {
        return $this->locale;
    }

    /**
     * Set landingUrl
     *
     * @param string $landingUrl
     * @return Invitation
     */
    public function setLandingUrl($landingUrl) {
        $this->landingUrl = $landingUrl;

        return $this;
    }

    /**
     * Get landingUrl
     *
     * @return string 
     */
    public function getLandingUrl() {
        return $this->landingUrl;
    }

    /**
     * Set doRedirect
     *
     * @param boolean $doRedirect
     * @return Invitation
     */
    public function setDoRedirect($doRedirect) {
        $this->doRedirect = $doRedirect;

        return $this;
    }

    /**
     * Get doRedirect
     *
     * @return boolean 
     */
    public function getDoRedirect() {
        return $this->doRedirect;
    }

    /**
     * Set asManager
     *
     * @param boolean $asManager
     * @return Invitation
     */
    public function setAsManager($asManager) {
        $this->asManager = $asManager;

        return $this;
    }

    /**
     * Get asManager
     *
     * @return boolean 
     */
    public function getAsManager() {
        return $this->asManager;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return Invitation
     */
    public function setMessage($message) {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * Set counter
     *
     * @param integer $counter
     * @return Invitation
     */
    public function setCounter($counter) {
        $this->counter = $counter;

        return $this;
    }

    /**
     * Get counter
     *
     * @return integer 
     */
    public function getCounter() {
        return $this->counter;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return Invitation
     */
    public function setStartDate($startDate) {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime 
     */
    public function getStartDate() {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     * @return Invitation
     */
    public function setEndDate($endDate) {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime 
     */
    public function getEndDate() {
        return $this->endDate;
    }

    /**
     * Set limit
     *
     * @param integer $limit
     * @return Invitation
     */
    public function setLimit($limit) {
        if ($limit == 0) {
            if (count($this->emails) < 1) {
                $limit = 1;
            } else {
                $limit = count($this->emails);
            }
        }
        $this->limit = $limit;

        return $this;
    }

    /**
     * Get limit
     *
     * @return integer 
     */
    public function getLimit() {
        return $this->limit;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Invitation
     */
    public function setCreatedAt($createdAt) {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt() {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return Invitation
     */
    public function setUpdatedAt($updatedAt) {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime 
     */
    public function getUpdatedAt() {
        return $this->updatedAt;
    }

    /**
     * Set role
     *
     * @param \Hexaa\StorageBundle\Entity\Role $role
     * @return Invitation
     */
    public function setRole(\Hexaa\StorageBundle\Entity\Role $role = null) {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role
     *
     * @return \Hexaa\StorageBundle\Entity\Role 
     */
    public function getRole() {
        return $this->role;
    }

    /**
     * Set organization
     *
     * @param \Hexaa\StorageBundle\Entity\Organization $organization
     * @return Invitation
     */
    public function setOrganization(\Hexaa\StorageBundle\Entity\Organization $organization = null) {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization
     *
     * @return \Hexaa\StorageBundle\Entity\Organization 
     */
    public function getOrganization() {
        return $this->organization;
    }

    /**
     * Set service
     *
     * @param \Hexaa\StorageBundle\Entity\Service $service
     * @return Invitation
     */
    public function setService(\Hexaa\StorageBundle\Entity\Service $service = null) {
        $this->service = $service;

        return $this;
    }

    /**
     * Get service
     *
     * @return \Hexaa\StorageBundle\Entity\Service 
     */
    public function getService() {
        return $this->service;
    }

    /**
     * Set inviter
     *
     * @param \Hexaa\StorageBundle\Entity\Principal $inviter
     * @return Invitation
     */
    public function setInviter(\Hexaa\StorageBundle\Entity\Principal $inviter = null) {
        $this->inviter = $inviter;

        return $this;
    }

    /**
     * Get inviter
     *
     * @return \Hexaa\StorageBundle\Entity\Principal 
     */
    public function getInviter() {
        return $this->inviter;
    }

    /**
     * Set reinviteCount
     *
     * @param integer $reinviteCount
     * @return Invitation
     */
    public function setReinviteCount($reinviteCount) {
        $this->reinviteCount = $reinviteCount;

        return $this;
    }

    /**
     * Get reinviteCount
     *
     * @return integer 
     */
    public function getReinviteCount() {
        return $this->reinviteCount;
    }

    /**
     * Set lastReinviteAt
     *
     * @param \DateTime $lastReinviteAt
     * @return Invitation
     */
    public function setLastReinviteAt($lastReinviteAt) {
        $this->lastReinviteAt = $lastReinviteAt;

        return $this;
    }

    /**
     * Get lastReinviteAt
     *
     * @return \DateTime 
     */
    public function getLastReinviteAt() {
        return $this->lastReinviteAt;
    }

    /**
     * Set statuses
     *
     * @param array $statuses
     * @return Invitation
     */
    public function setStatuses($statuses) {
        $this->statuses = $statuses;

        return $this;
    }

    /**
     * Set displayNames
     *
     * @param array $displayNames
     * @return Invitation
     */
    public function setDisplayNames($displayNames) {
        $this->displayNames = $displayNames;

        return $this;
    }

    /**
     * Generate token
     * 
     * @return string
     */
    public function generateToken() {
        try {
            $token = Uuid::uuid4()->toString();
        } catch (UnsatisfiedDependencyException $e) {

            // Some dependency was not met. Either the method cannot be called on a
            // 32-bit system, or it can, but it relies on Moontoast\Math to be present.
            $token = uniqid();
        }
        $this->token = $token;
        return $token;
    }

    public function __toString(){
        return 'INVITATION' . $this->id;
    }

}
