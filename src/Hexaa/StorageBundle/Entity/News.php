<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;
use Hexaa\ApiBundle\Validator\Constraints as HexaaAssert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Consent
 *
 * @ORM\Table(name="news", indexes={@ORM\Index(name="principal", columns={"principal_id"}), @ORM\Index(name="service_id_idx", columns={"service_id"}), @ORM\Index(name="organization_id_idx", columns={"organization_id"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class News {

    //public function __construct() {}
 
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Hexaa\StorageBundle\Entity\Principal
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Principal")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="principal_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * 
     * @Exclude
     */
    private $principal;

    /**
     * @var \Hexaa\StorageBundle\Entity\Service
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Service")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="service_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * 
     * @Exclude
     */
    private $service;

    /**
     * @var \Hexaa\StorageBundle\Entity\Organization
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Organization")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * 
     * @Exclude
     */
    private $organization;

    /**
     * @var string
     *
     * @ORM\Column(name="tag", type="string", length=255, nullable=false)
     * })
     */
    private $tag;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = "3",
     *      max = "125"
     * )
     */
    private $title;

    /**
     * @var text
     *
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    private $message;

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
        $time = new \DateTime('now');
        $this->setUpdatedAt($time);
        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt($time);
        }
    }

    /**
     * @VirtualProperty
     * @SerializedName("service_id")
     */
    public function getServiceId() {
        if (isset($this->service))
            return $this->service->getId();
    }

    /**
     * @VirtualProperty
     * @SerializedName("organization_id")
     */
    public function getOrganizationId() {
        if (isset($this->organization))
            return $this->organization->getId();
    }

    /**
     * @VirtualProperty
     * @SerializedName("principal_id")
     */
    public function getPrincipalId() {
        if (isset($this->principal))
            return $this->principal->getId();
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
     * Set tag
     *
     * @param array $tag
     * @return News
     */
    public function setTag($tag) {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Get tag
     *
     * @return array 
     */
    public function getTag() {
        return $this->tag;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return News
     */
    public function setTitle($title) {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return News
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return News
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
     * @return News
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
     * Set principal
     *
     * @param \Hexaa\StorageBundle\Entity\Principal $principal
     * @return News
     */
    public function setPrincipal(\Hexaa\StorageBundle\Entity\Principal $principal = null) {
        $this->principal = $principal;

        return $this;
    }

    /**
     * Get principal
     *
     * @return \Hexaa\StorageBundle\Entity\Principal 
     */
    public function getPrincipal() {
        return $this->principal;
    }

    /**
     * Set service
     *
     * @param \Hexaa\StorageBundle\Entity\Service $service
     * @return News
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
     * Set organization
     *
     * @param \Hexaa\StorageBundle\Entity\Organization $organization
     * @return News
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

}
