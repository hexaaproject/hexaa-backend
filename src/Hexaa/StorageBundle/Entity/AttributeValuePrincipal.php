<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Type;
use Symfony\Component\Validator\Constraints as Assert;
use Hexaa\ApiBundle\Validator\Constraints as HexaaAssert;

/**
 * AttributeValuePrincipal
 *
 * @ORM\Table(
 *   name="attribute_value_principal",
 *   indexes={
 *     @ORM\Index(name="principal_id_idx", columns={"principal_id"}),
 *     @ORM\Index(name="attribute_spec_id_idx", columns={"attribute_spec_id"})
 *   }
 * )
 * @ORM\Entity
 * @HexaaAssert\ServiceExistsAndWantsAttribute()
 * @ORM\HasLifecycleCallbacks
 *
 */
class AttributeValuePrincipal {

    public function __construct() {
        $this->services = new ArrayCollection();
    }

    /**
     *
     * @var string
     *
     * @ORM\Column(name="value", type="blob", nullable=true)
     * @Accessor(getter="getValue", setter="setValue")
     * @Assert\NotBlank()
     *
     * @Groups({"minimal", "normal", "expanded"})
     *
     */
    private $value;

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
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $id;

    /**
     * @var Principal
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Principal")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="principal_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     *
     * @Assert\NotBlank()
     *
     * @Groups({"expanded"})
     */
    private $principal;

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
     * @HexaaAssert\AttributeSpec4User()
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

    /**
     * @ORM\ManyToMany(targetEntity="Service")
     * @ORM\JoinTable(name="service_attribute_value_principal")
     * @Assert\Valid(traverse=true)
     *
     * @Groups({"expanded"})
     */
    private $services;

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
     * @SerializedName("principal_id")
     * @Type("integer")
     * @Groups({"minimal", "normal"})
     */
    public function getPrincipalId() {
        return $this->principal->getId();
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
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set principal
     *
     * @param Principal $principal
     * @return AttributeValuePrincipal
     */
    public function setPrincipal(Principal $principal = null) {
        $this->principal = $principal;

        return $this;
    }

    /**
     * Get principal
     *
     * @return Principal
     */
    public function getPrincipal() {
        return $this->principal;
    }

    /**
     * Set attributeSpec
     *
     * @param AttributeSpec $attributeSpec
     * @return AttributeValuePrincipal
     */
    public function setAttributeSpec(AttributeSpec $attributeSpec = null) {
        $this->attributeSpec = $attributeSpec;

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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return AttributeValuePrincipal
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
     * @return AttributeValuePrincipal
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
     * Add services
     *
     * @param Service $services
     * @return AttributeValuePrincipal
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
     * Has services
     *
     * @param Service $service
     * @return bool
     */
    public function hasService(Service $service) {
        return $this->services->contains($service);
    }

    /**
     * Set value
     *
     * @param string $value
     * @return AttributeValuePrincipal
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
        if ($this->value == null){
            return null;
        } else {
            return stream_get_contents($this->value);
        }
    }

    /**
     * Set loaDate
     *
     * @param \DateTime $loaDate
     * @return AttributeValuePrincipal
     */
    public function setLoaDate($loaDate) {
        $this->loaDate = $loaDate;

        return $this;
    }

    public function __toString() {
        return $this->getValue();
    }

}
