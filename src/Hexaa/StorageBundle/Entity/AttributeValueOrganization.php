<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;
use Hexaa\ApiBundle\Validator\Constraints as HexaaAssert;

/**
 * AttributeValueOrganization
 *
 * @ORM\Table(name="attribute_value_organization", indexes={@ORM\Index(name="organization_id_idx", columns={"organization_id"}), @ORM\Index(name="attribute_spec_id_idx", columns={"attribute_spec_id"})})
 * @ORM\Entity
 * @HexaaAssert\ServiceExistsAndWantsAttribute()
 * @ORM\HasLifecycleCallbacks
 */
class AttributeValueOrganization {

    /**
     * @ORM\ManyToMany(targetEntity="Service")
     * 
     * @Exclude
     */
    private $services;

    public function __construct() {
        $this->services = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     *
     * @var string
     *
     * @ORM\Column(name="value", type="blob", nullable=true)
     * @Accessor(getter="getValue", setter="setValue")
     * 
     * @Assert\NotBlank()
     */
    private $value;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_default", type="boolean", nullable=true)
     */
    private $isDefault;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * 
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="loa", type="bigint", nullable=true)
     */
    private $loa = 0;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="loa_date", type="datetime", nullable=true)
     */
    private $loaDate;

    /**
     * @var \Hexaa\StorageBundle\Entity\Organization
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Organization")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * 
     * @Exclude
     * @Assert\NotBlank()
     */
    private $organization;

    /**
     * @var \Hexaa\StorageBundle\Entity\AttributeSpec
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\AttributeSpec")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="attribute_spec_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * 
     * @Exclude
     * @Assert\NotBlank()
     */
    private $attributeSpec;

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
        $now = new \DateTime('now');
        $this->setUpdatedAt($now);

        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt($now);
            $this->loaDate = $now;
        }
    }

    /**
     * @VirtualProperty
     * @SerializedName("organization_id")
     * @Type("integer")
     */
    public function getOrganizationId() {
        return $this->organization->getId();
    }

    /**
     * @VirtualProperty
     * @SerializedName("attribute_spec_id")
     * @Type("integer")
     */
    public function getAttributeSpecId() {
        return $this->attributeSpec->getId();
    }

    /**
     * @VirtualProperty
     * @SerializedName("service_ids")
     * @Type("array<integer>")
     */
    public function getServiceIds() {
        $retarr = array();
        foreach ($this->services as $s) {
            $retarr[] = $s->getId();
        }
        return $retarr;
    }

    /**
     * Set loa
     *
     * @param integer $loa
     * @return Service
     */
    public function setLoa($loa) {
        $this->loa = $loa;
        $this->loaDate = new \DateTime();

        return $this;
    }

    /**
     * Get loa
     *
     * @return integer 
     */
    public function getLoa() {
        return $this->loa;
    }

    /**
     * Get loaDate
     *
     * @return \DateTime 
     */
    public function getLoaDate() {
        return $this->loaDate;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return AttributeValueOrganization
     */
    public function setValue($value) {
        $this->value = (binary) $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue() {
        return stream_get_contents($this->value);
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
     * Set organization
     *
     * @param \Hexaa\StorageBundle\Entity\Organization $organization
     * @return AttributeValueOrganization
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
     * Set attributeSpec
     *
     * @param \Hexaa\StorageBundle\Entity\AttributeSpec $attributeSpec
     * @return AttributeValueOrganization
     */
    public function setAttributeSpec(\Hexaa\StorageBundle\Entity\AttributeSpec $attributeSpec = null) {
        $this->attributeSpec = $attributeSpec;

        return $this;
    }

    /**
     * Get attributeSpec
     *
     * @return \Hexaa\StorageBundle\Entity\AttributeSpec 
     */
    public function getAttributeSpec() {
        return $this->attributeSpec;
    }

    /**
     * Add services
     *
     * @param \Hexaa\StorageBundle\Entity\Service $services
     * @return AttributeValueOrganization
     */
    public function addService(\Hexaa\StorageBundle\Entity\Service $services) {
        $this->services[] = $services;

        return $this;
    }

    /**
     * Remove services
     *
     * @param \Hexaa\StorageBundle\Entity\Service $services
     */
    public function removeService(\Hexaa\StorageBundle\Entity\Service $services) {
        $this->services->removeElement($services);
    }

    /**
     * Get services
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getServices() {
        return $this->services;
    }

    /**
     * Has service
     *
     * @param \Hexaa\StorageBundle\Entity\Service $service
     *
     * @return boolean
     */
    public function hasService(\Hexaa\StorageBundle\Entity\Service $service) {
        return $this->services->contains($service);
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return AttributeValueOrganization
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
     * @return AttributeValueOrganization
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
     * Set isDefault
     *
     * @param boolean $isDefault
     * @return AttributeValueOrganization
     */
    public function setIsDefault($isDefault) {
        $this->isDefault = $isDefault;

        return $this;
    }

    /**
     * Get isDefault
     *
     * @return boolean 
     */
    public function getIsDefault() {
        return $this->isDefault;
    }

}
