<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Hexaa\ApiBundle\Validator\Constraints as HexaaAssert;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\VirtualProperty;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AttributeValueOrganization
 *
 * @ORM\Table(
 *   name="attribute_value_organization",
 *   indexes={
 *     @ORM\Index(name="organization_id_idx", columns={"organization_id"}),
 *     @ORM\Index(name="attribute_spec_id_idx", columns={"attribute_spec_id"})
 *   }
 * )
 * @ORM\Entity
 * @HexaaAssert\ServiceExistsAndWantsAttribute()
 * @HexaaAssert\AttributeValueHasNoServiceIfNotMultivalue()
 * @ORM\HasLifecycleCallbacks
 *
 */
class AttributeValueOrganization {

    /**
     * @ORM\ManyToMany(targetEntity="Service")
     *
     *
     * @Groups({"expanded"})
     */
    private $services;
    /**
     *
     * @var string
     *
     * @ORM\Column(name="value", type="blob", nullable=true)
     * @Accessor(getter="getValue", setter="setValue")
     *
     * @Groups({"minimal", "normal", "expanded"})
     *
     * @Assert\NotBlank()
     */
    private $value;
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_default", type="boolean", nullable=true)
     *
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $isDefault;
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @Groups({"minimal", "normal", "expanded"})
     *
     */
    private $id;
    /**
     * @var integer
     *
     * @ORM\Column(name="loa", type="bigint", nullable=true)
     *
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $loa = 0;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="loa_date", type="datetime", nullable=true)
     *
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $loaDate;
    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Organization")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     *
     * @Assert\NotBlank()
     *
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $organization;
    /**
     * @var AttributeSpec
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\AttributeSpec")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="attribute_spec_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     *
     *
     * @Groups({"expanded"})
     * @Assert\NotBlank()
     * @HexaaAssert\AttributeSpec4Manager()
     */
    private $attributeSpec;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     *
     * @Groups({"normal", "expanded"})
     */
    private $createdAt;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     *
     * @Groups({"normal", "expanded"})
     */
    private $updatedAt;

    public function __construct() {
        $this->services = new ArrayCollection();
    }

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
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt() {
        return $this->createdAt;
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
     * @VirtualProperty
     * @SerializedName("organization_id")
     * @Type("integer")
     * @Groups({"minimal", "normal"})
     */
    public function getOrganizationId() {
        return $this->organization->getId();
    }

    /**
     * @VirtualProperty
     * @SerializedName("attribute_spec_id")
     * @Type("integer")
     * @Groups({"minimal", "normal"})
     */
    public function getAttributeSpecId() {
        return $this->attributeSpec->getId();
    }

    /**
     * @VirtualProperty
     * @SerializedName("service_ids")
     * @Type("array<integer>")
     * @Groups({"normal"})
     */
    public function getServiceIds() {
        $retarr = array();
        foreach($this->services as $s) {
            $retarr[] = $s->getId();
        }

        return $retarr;
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
     * Get loaDate
     *
     * @return \DateTime
     */
    public function getLoaDate() {
        return $this->loaDate;
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
     * Get organization
     *
     * @return Organization
     */
    public function getOrganization() {
        return $this->organization;
    }

    /**
     * Set organization
     *
     * @param Organization $organization
     * @return AttributeValueOrganization
     */
    public function setOrganization(Organization $organization = null) {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get attributeSpec
     *
     * @return AttributeSpec
     */
    public function getAttributeSpec() {
        return $this->attributeSpec;
    }

    /**
     * Set attributeSpec
     *
     * @param AttributeSpec $attributeSpec
     * @return AttributeValueOrganization
     */
    public function setAttributeSpec(AttributeSpec $attributeSpec = null) {
        $this->attributeSpec = $attributeSpec;

        return $this;
    }

    /**
     * Add services
     *
     * @param Service $services
     * @return AttributeValueOrganization
     */
    public function addService(Service $services) {
        $this->services[] = $services;

        return $this;
    }

    /**
     * Remove services
     *
     * @param Service $services
     */
    public function removeService(Service $services) {
        $this->services->removeElement($services);
    }

    /**
     * Get services
     *
     * @return ArrayCollection
     */
    public function getServices() {
        return $this->services;
    }

    /**
     * Has service
     *
     * @param Service $service
     *
     * @return boolean
     */
    public function hasService(Service $service) {
        return $this->services->contains($service);
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
     * Get isDefault
     *
     * @return boolean
     */
    public function getIsDefault() {
        return $this->isDefault;
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

    public function __toString() {
        return $this->getValue();
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue() {
        if ($this->value == null) {
            return null;
        } else {
            $content = '';
            while(!feof($this->value)) {
                $content .= fread($this->value, 1024);
            }
            rewind($this->value);

            return $content;
        }
    }

    /**
     * Set value
     *
     * @param string $value
     * @return AttributeValueOrganization
     */
    public function setValue($value) {
        $this->value = (binary)$value;

        return $this;
    }

}
