<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use Symfony\Component\Validator\Constraints as Assert;
use Hexaa\ApiBundle\Validator\Constraints as HexaaAssert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Consent
 *
 * @ORM\Table(name="consent", indexes={@ORM\Index(name="principal", columns={"principal_id"}), @ORM\Index(name="service_id_idx", columns={"service_id"})})
 * @ORM\Entity
 * @UniqueEntity({"service", "principal"})
 * @ORM\HasLifecycleCallbacks
 */
class Consent
{
    
    public function __construct() {
        $this->enabledAttributeSpecs = new ArrayCollection();
    }

    /**
     * @var boolean
     *
     * @ORM\Column(name="enable_entitlements", type="boolean", nullable=true)
     */
    private $enableEntitlements = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    
    /**
     * @ORM\ManyToMany(targetEntity="AttributeSpec")
     * @ORM\JoinTable(name="consent_attribute_spec")
     * @Exclude
     * @Assert\Valid(traverse=true)
     * @Assert\All({
     *      @HexaaAssert\AttributeSpecByUserAndId()
     * })
     */
    private $enabledAttributeSpecs;

    /**
     * @var Principal
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
     * @var Service
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
     * @var \DateTime
     *
     * @ORM\Column(name="expiration", type="datetime", nullable=false)
     */
    private $expiration;

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
        $exp = new \DateTime('now');
        $exp->add(new \DateInterval("P6M"));
        $this->setExpiration($exp);

        
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
     * @SerializedName("service_id")
     * @Type("integer")
    */
    public function getServiceId()
    {
        return $this->service->getId();
    }
    
    /**
     * @VirtualProperty
     * @SerializedName("enabled_attribute_spec_ids")
     * @Type("array<integer>")
    */
    public function getEnabledAttributeSpecIds()
    {
        $retarr = array();
        foreach ($this->enabledAttributeSpecs as $as){
            $retarr[] = $as->getId();
        }
        return $retarr;
    }
    

    /**
     * Set enableEntitlements
     *
     * @param boolean $enableEntitlements
     * @return Consent
     */
    public function setEnableEntitlements($enableEntitlements)
    {
        $this->enableEntitlements = $enableEntitlements;

        return $this;
    }

    /**
     * Get enableEntitlements
     *
     * @return boolean 
     */
    public function getEnableEntitlements()
    {
        return $this->enableEntitlements;
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
     * Set expiration
     *
     * @param \DateTime $expiration
     * @return Consent
     */
    public function setExpiration($expiration)
    {
        $this->expiration = $expiration;

        return $this;
    }

    /**
     * Get expiration
     *
     * @return \DateTime 
     */
    public function getExpiration()
    {
        return $this->expiration;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Consent
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
     * @return Consent
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
     * Add enabledAttributeSpecs
     *
     * @param AttributeSpec $enabledAttributeSpecs
     * @return Consent
     */
    public function addEnabledAttributeSpec(AttributeSpec $enabledAttributeSpecs)
    {
        $this->enabledAttributeSpecs[] = $enabledAttributeSpecs;

        return $this;
    }

    /**
     * Remove enabledAttributeSpecs
     *
     * @param AttributeSpec $enabledAttributeSpecs
     */
    public function removeEnabledAttributeSpec(AttributeSpec $enabledAttributeSpecs)
    {
        $this->enabledAttributeSpecs->removeElement($enabledAttributeSpecs);
    }

    /**
     * Get enabledAttributeSpecs
     *
     * @return ArrayCollection
     */
    public function getEnabledAttributeSpecs()
    {
        return $this->enabledAttributeSpecs;
    }

    /**
     * Has enabledAttributeSpecs
     *
     * @param AttributeSpec $as
     * @return bool
     */
    public function hasEnabledAttributeSpecs(AttributeSpec $as = null)
    {
        return $this->enabledAttributeSpecs->contains($as);
    }

    /**
     * Set principal
     *
     * @param Principal $principal
     * @return Consent
     */
    public function setPrincipal(Principal $principal = null)
    {
        $this->principal = $principal;

        return $this;
    }

    /**
     * Get principal
     *
     * @return Principal
     */
    public function getPrincipal()
    {
        return $this->principal;
    }

    /**
     * Set service
     *
     * @param Service $service
     * @return Consent
     */
    public function setService(Service $service = null)
    {
        $this->service = $service;

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

    public function __toString(){
        return 'CONSENT' . $this->id;
    }
}
