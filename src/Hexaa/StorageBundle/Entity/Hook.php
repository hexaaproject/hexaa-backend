<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\VirtualProperty;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Hexaa\ApiBundle\Validator\Constraints as HexaaAssert;

/**
 * Hook
 *
 * @ORM\Table(
 *   name="hook",
 *   uniqueConstraints={
 *     @ORM\UniqueConstraint(name="name_service", columns={"name","service_id"}),
 *     @ORM\UniqueConstraint(name="name_organization", columns={"name","organization_id"})
 *   }
 * )
 * @ORM\Entity()
 * @UniqueEntity({"name","service"})
 * @UniqueEntity({"name","organization"})
 * @ORM\HasLifecycleCallbacks
 * @HexaaAssert\HookHasValidTarget()
 */
class Hook
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank()
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255)
     * @Assert\Url()
     * @Assert\NotBlank()
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(
     *   name="type",
     *   type="string",
     *   length=255,
     *   columnDefinition="ENUM('user_added', 'user_removed', 'attribute_change')",
     *   nullable=false
     * )
     * @Assert\NotBlank()
     * @Assert\Choice(
     *   choices = {"user_added", "user_removed", "attribute_change"},
     *   message="valid types are: user_added, user_removed, attribute_change")
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $type;

    /**
     * @var Service
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Service", inversedBy="hooks")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="service_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Groups({"expanded"})
     */
    private $service;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Organization", inversedBy="hooks")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Groups({"expanded"})
     */
    private $organization;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdAt", type="datetime")
     * @Groups({"normal", "expanded"})
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updatedAt", type="datetime")
     * @Groups({"normal", "expanded"})
     */
    private $updatedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="lastCallMessage", type="text", nullable=true)
     * @Groups({"normal", "expanded"})
     */
    private $lastCallMessage;


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
     * @return Hook
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Hook
     */
    public function setName($name)
    {
        $this->name = $name;

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
     * Set description
     *
     * @param string $description
     * @return Hook
     */
    public function setDescription($description)
    {
        $this->description = $description;

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
     * Set url
     *
     * @param string $url
     * @return Hook
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return Hook
     */
    public function setType($type)
    {
        $this->type = $type;

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
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return Hook
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get lastCallMessage
     *
     * @return string
     */
    public function getLastCallMessage()
    {
        return $this->lastCallMessage;
    }

    /**
     * Set lastCallMessage
     *
     * @param string $lastCallMessage
     * @return Hook
     */
    public function setLastCallMessage($lastCallMessage)
    {
        $this->lastCallMessage = $lastCallMessage;

        return $this;
    }

    /**
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param Organization $organization
     */
    public function setOrganization(Organization $organization = null)
    {
        $this->organization = $organization;
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
     * @return Hook
     */
    public function setService(Service $service = null)
    {
        $this->service = $service;

        return $this;
    }
}
