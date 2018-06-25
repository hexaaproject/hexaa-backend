<?php

/**
 * Copyright 2014-2018 MTA SZTAKI, ugyeletes@sztaki.hu
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hexaa\ApiBundle\Validator\Constraints as HexaaAssert;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\MaxDepth;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\VirtualProperty;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Invitation
 *
 * @ORM\Table(
 *   name="invitation",
 *   indexes={
 *     @ORM\Index(name="inviter_id_idx", columns={"inviter_id"}),
 *     @ORM\Index(name="organization_id_idx", columns={"organization_id"}),
 *     @ORM\Index(name="service_id_idx", columns={"service_id"})
 *   }
 * )
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @HexaaAssert\InvitationHasValidTarget()
 *
 */
class Invitation
{

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
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $emails;
    /**
     * @var array
     *
     * @ORM\Column(name="statuses", type="array", length=16777215, nullable=false)
     * })
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $statuses;
    /**
     * @var array
     *
     * @ORM\Column(name="display_names", type="array", length=16777215, nullable=false)
     * })
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $displayNames;
    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=255, nullable=false)
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $token;
    /**
     * @var string
     *
     * @ORM\Column(name="landing_url", type="string", length=255, nullable=true)
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $landingUrl = null;
    /**
     * @var string
     *
     * @ORM\Column(name="locale", type="string", length=255, nullable=true)
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $locale = "en_EN";
    /**
     * @var boolean
     *
     * @ORM\Column(name="do_redirect", type="boolean", nullable=true)
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $doRedirect;
    /**
     * @var boolean
     *
     * @ORM\Column(name="as_manager", type="boolean", nullable=true)
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $asManager;
    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text", nullable=true)
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $message;
    /**
     * @var integer
     *
     * @ORM\Column(name="counter", type="bigint", nullable=true)
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $counter;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_date", type="datetime", nullable=true)
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $startDate;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_date", type="datetime", nullable=true)
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $endDate;
    /**
     * @var integer
     *
     * @ORM\Column(name="principal_limit", type="bigint", nullable=true)
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $limit;
    /**
     * @var integer
     *
     * @ORM\Column(name="reinvite_count", type="bigint", nullable=true)
     * @Groups({"normal", "expanded"})
     */
    private $reinviteCount;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_reinvite_at", type="datetime", nullable=true)
     * @Groups({"normal", "expanded"})
     */
    private $lastReinviteAt;
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $id;
    /**
     * @var Role
     *
     * @ORM\ManyToOne(targetEntity="Role", inversedBy="invitations")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Groups({"expanded"})
     * @MaxDepth(1)
     */
    private $role;
    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Organization", inversedBy="invitations")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Groups({"expanded"})
     * @MaxDepth(1)
     */
    private $organization;
    /**
     * @var Service
     *
     * @ORM\ManyToOne(targetEntity="Service", inversedBy="invitations")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="service_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Groups({"expanded"})
     * @MaxDepth(1)
     */
    private $service;
    /**
     * @var Principal
     *
     * @ORM\ManyToOne(targetEntity="Principal", inversedBy="invitations")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="inviter_id", referencedColumnName="id")
     * })
     * @Groups({"expanded"})
     * @MaxDepth(1)
     */
    private $inviter;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     * @Groups({"normal", "expanded"})
     */
    private $createdAt;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     * @Groups({"normal", "expanded"})
     */
    private $updatedAt;

    public function __construct()
    {
        $this->emails = array();
        $this->statuses = array();
        $this->displayNames = array();
        $this->generateToken();
    }

    /**
     * Generate token
     *
     * @return string
     */
    public function generateToken()
    {
        if ($this->token === null) {
            try {
                $token = Uuid::uuid4()->toString();
            } catch (UnsatisfiedDependencyException $e) {

                // Some dependency was not met. Either the method cannot be called on a
                // 32-bit system, or it can, but it relies on Moontoast\Math to be present.
                $token = uniqid();
            }
            $this->token = $token;
        }

        return $this->token;
    }

    /**
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps()
    {
        $this->setUpdatedAt(new \DateTime('now'));

        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new \DateTime('now'));
        }
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
     * @VirtualProperty
     * @SerializedName("service_id")
     * @Type("integer")
     * @Groups({"minimal", "normal"})
     */
    public function getServiceId()
    {
        if (isset($this->service)) {
            return $this->service->getId();
        } else {
            return null;
        }
    }

    /**
     * @VirtualProperty
     * @SerializedName("organization_id")
     * @Type("integer")
     * @Groups({"minimal", "normal"})
     */
    public function getOrganizationId()
    {
        if (isset($this->organization)) {
            return $this->organization->getId();
        } else {
            return null;
        }
    }

    /**
     * @VirtualProperty
     * @SerializedName("role_id")
     * @Type("integer")
     * @Groups({"minimal", "normal"})
     */
    public function getRoleId()
    {
        if (isset($this->role)) {
            return $this->role->getId();
        } else {
            return null;
        }
    }

    /**
     * @VirtualProperty
     * @SerializedName("inviter_id")
     * @Type("integer")
     * @Groups({"minimal", "normal"})
     */
    public function getInviterId()
    {
        return $this->inviter->getId();
    }

    /**
     * Add email / set status
     *
     * @param string $email
     * @param string $status
     * @return Invitation
     */
    public function setEmail($email, $status = "pending")
    {
        if (!in_array($email, $this->emails)) {
            $this->emails[] = $email;
        }
        $this->statuses[$email] = $status;


        return $this;
    }

    /**
     * Get emails
     *
     * @return array
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * Set emails
     *
     * @param array $emails
     * @return Invitation
     */
    public function setEmails($emails)
    {
        $this->emails = $emails;
        foreach ($emails as $email) {
            $this->statuses[$email] = "pending";
        }
        foreach (array_keys($this->statuses) as $statusMail) {
            if (!in_array($statusMail, $emails)) {
                unset($this->statuses[$statusMail]);
                unset($this->displayNames[$statusMail]);
            }
        }

        return $this;
    }

    /**
     * Get statuses
     *
     * @return array
     */
    public function getStatuses()
    {
        return $this->statuses;
    }

    /**
     * Set statuses
     *
     * @param array $statuses
     * @return Invitation
     */
    public function setStatuses($statuses)
    {
        $this->statuses = $statuses;

        return $this;
    }

    /**
     * Get display names
     *
     * @return array
     */
    public function getDisplayNames()
    {
        return $this->displayNames;
    }

    /**
     * Set displayNames
     *
     * @param array $displayNames
     * @return Invitation
     */
    public function setDisplayNames($displayNames)
    {
        $this->displayNames = $displayNames;

        return $this;
    }

    /**
     * Remove email
     *
     * @param string $email
     * @return Invitation
     */
    public function removeEmail($email)
    {
        //unset($this->emails[$email]);

        if (($key = array_search($email, $this->emails)) !== false) {
            unset($this->emails[$key]);

            unset($this->displayNames[$email]);

            unset($this->statuses[$email]);
        }

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
     * Get locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return Invitation
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

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
     * Get doRedirect
     *
     * @return boolean
     */
    public function getDoRedirect()
    {
        return $this->doRedirect;
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
     * Get asManager
     *
     * @return boolean
     */
    public function getAsManager()
    {
        return $this->asManager;
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
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
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
     * Get counter
     *
     * @return integer
     */
    public function getCounter()
    {
        return $this->counter;
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
     * Get startDate
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
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
     * Get endDate
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
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
     * Get limit
     *
     * @return integer
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Set limit
     *
     * @param integer $limit
     * @return Invitation
     */
    public function setLimit($limit)
    {
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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
     * Get role
     *
     * @return Role
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set role
     *
     * @param Role $role
     * @return Invitation
     */
    public function setRole(Role $role = null)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get organization
     *
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Set organization
     *
     * @param Organization $organization
     * @return Invitation
     */
    public function setOrganization(Organization $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get service
     *
     * @return Service
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Set service
     *
     * @param Service $service
     * @return Invitation
     */
    public function setService(Service $service = null)
    {
        $this->service = $service;

        return $this;
    }

    /**
     * Get inviter
     *
     * @return Principal
     */
    public function getInviter()
    {
        return $this->inviter;
    }

    /**
     * Set inviter
     *
     * @param Principal $inviter
     * @return Invitation
     */
    public function setInviter(Principal $inviter = null)
    {
        $this->inviter = $inviter;

        return $this;
    }

    /**
     * Get reinviteCount
     *
     * @return integer
     */
    public function getReinviteCount()
    {
        return $this->reinviteCount;
    }

    /**
     * Set reinviteCount
     *
     * @param integer $reinviteCount
     * @return Invitation
     */
    public function setReinviteCount($reinviteCount)
    {
        $this->reinviteCount = $reinviteCount;

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

    public function __toString()
    {
        return 'INVITATION'.$this->id;
    }

}
