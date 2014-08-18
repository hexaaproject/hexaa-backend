<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Consent
 *
 * @ORM\Table(name="consent")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Consent
{
    
    public function __construct() {
        $this->enabledAttributeSpecs = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @var \DateTime
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
     */
    private $enabledAttributeSpecs;

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
        $this->setUpdatedAt(new \DateTime('now'));

        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new \DateTime('now'));
        }
    }
    
    
    /**
     * @VirtualProperty
     * @SerializedName("principal_id")
    */
    public function getPrincipalId()
    {
        return $this->principal->getId();
    }
    
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
     * @SerializedName("enabled_attribute_spec_ids")
    */
    public function getEnabledAttributeSpecIds()
    {
        $retarr = array();
        foreach ($this->enabledAttributeSpecs as $as){
            $retarr[] = $as->getId();
        }
        return $retarr;
    }
    
}