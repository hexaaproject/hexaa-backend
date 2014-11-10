<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use Symfony\Component\Validator\Constraints as Assert;
use Hexaa\ApiBundle\Validator\Constraints as HexaaAssert;

/**
 * AttributeValuePrincipal
 *
 * @ORM\Table(name="attribute_value_principal", indexes={@ORM\Index(name="principal_id_idx", columns={"principal_id"}), @ORM\Index(name="attribute_spec_id_idx", columns={"attribute_spec_id"})})
 * @ORM\Entity
 * @HexaaAssert\ServiceExistsAndWantsAttribute()
 * @ORM\HasLifecycleCallbacks
 */
class AttributeValuePrincipal
{
    public function __construct() {
        $this->services = new \Doctrine\Common\Collections\ArrayCollection();
        
    }

    /**
     *
     * @var string
     *
     * @ORM\Column(name="value", type="text", nullable=true)
     * 
     * @Assert\NotBlank()
     * 
     */
    private $value;

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
     * @Assert\NotBlank()
     * 
     * @Exclude
     */
    private $principal;

    /**
     * @var \Hexaa\StorageBundle\Entity\AttributeSpec
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\AttributeSpec")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="attribute_spec_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * 
     * @Exclude
     * @HexaaAssert\AttributeSpec4User()
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
     * @ORM\ManyToMany(targetEntity="Service")
     * @ORM\JoinTable(name="service_attribute_value_principal")
     * @Exclude
     * @Assert\Valid(traverse=true)
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
    */
    public function getPrincipalId()
    {
        return $this->principal->getId();
    }
    
    /**
     * @VirtualProperty
     * @SerializedName("attribute_spec_id")
     * @Type("integer")
    */
    public function getAttributeSpecId()
    {
        return $this->attributeSpec->getId();
    }
    
    /**
     * @VirtualProperty
     * @SerializedName("service_ids")
     * @Type("array<integer>")
    */
    public function getServiceIds()
    {
        $retarr = array();
        foreach ($this->services as $s){
            $retarr[]=$s->getId();
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
     * Set isDefault
     *
     * @param boolean $isDefault
     * @return AttributeValuePrincipal
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    /**
     * Get isDefault
     *
     * @return boolean 
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    /**
     * Set consentStatus
     *
     * @param string $consentStatus
     * @return AttributeValuePrincipal
     */
    public function setConsentStatus($consentStatus)
    {
        $this->consentStatus = $consentStatus;

        return $this;
    }

    /**
     * Get consentStatus
     *
     * @return string 
     */
    public function getConsentStatus()
    {
        return $this->consentStatus;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return AttributeValuePrincipal
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue()
    {
        return $this->value;
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
     * Set principal
     *
     * @param \Hexaa\StorageBundle\Entity\Principal $principal
     * @return AttributeValuePrincipal
     */
    public function setPrincipal(\Hexaa\StorageBundle\Entity\Principal $principal = null)
    {
        $this->principal = $principal;

        return $this;
    }

    /**
     * Get principal
     *
     * @return \Hexaa\StorageBundle\Entity\Principal 
     */
    public function getPrincipal()
    {
        return $this->principal;
    }

    /**
     * Set attributeSpec
     *
     * @param \Hexaa\StorageBundle\Entity\AttributeSpec $attributeSpec
     * @return AttributeValuePrincipal
     */
    public function setAttributeSpec(\Hexaa\StorageBundle\Entity\AttributeSpec $attributeSpec = null)
    {
        $this->attributeSpec = $attributeSpec;

        return $this;
    }

    /**
     * Get attributeSpec
     *
     * @return \Hexaa\StorageBundle\Entity\AttributeSpec 
     */
    public function getAttributeSpec()
    {
        return $this->attributeSpec;
    }


    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return AttributeValuePrincipal
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
     * @return AttributeValuePrincipal
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
     * Add services
     *
     * @param \Hexaa\StorageBundle\Entity\Service $services
     * @return AttributeValuePrincipal
     */
    public function addService(\Hexaa\StorageBundle\Entity\Service $services)
    {
        $this->services[] = $services;

        return $this;
    }

    /**
     * Remove services
     *
     * @param \Hexaa\StorageBundle\Entity\Service $services
     */
    public function removeService(\Hexaa\StorageBundle\Entity\Service $services)
    {
        $this->services->removeElement($services);
    }

    /**
     * Get services
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * Has services
     *
     * @return boolean
     */
    public function hasService(\Hexaa\StorageBundle\Entity\Service $service)
    {
        return $this->services->contains($service);
    }
}
