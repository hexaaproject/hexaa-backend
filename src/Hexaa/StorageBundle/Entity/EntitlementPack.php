<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * EntitlementPack
 *
 * @ORM\Table(name="entitlement_pack", indexes={@ORM\Index(name="token_idx", columns={"token"}), @ORM\Index(name="service_id_idx", columns={"service_id"})})
 * @ORM\Entity
 * @UniqueEntity("name")
 * @UniqueEntity("token")
 * @ORM\HasLifecycleCallbacks
 */
class EntitlementPack
{
    public function __construct() {
        $this->entitlements = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @var \Hexaa\StorageBundle\Entity\Entitlement
     * @ORM\ManyToMany(targetEntity="Entitlement")
     * @ORM\JoinTable(name="entitlement_pack_entitlement")
     * @Groups({"gui"})
     * @Exclude
     */
    private $entitlements;
    
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     * @Groups({"api","gui", "oep"})
     * 
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = "3",
     *      max = "125"
     * )
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @Groups({"api","gui", "oep"})
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255, columnDefinition="ENUM('private', 'public')", nullable=false)
     * @Groups({"api","gui", "oep"})
     * 
     * @Assert\NotBlank()
     * @Assert\Choice(choices={"private","public"})
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=255, nullable=false)
     * @Groups({"api","gui", "oep"})
     */
    private $token;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"api","gui", "oep"})
     */
    private $id;

    /**
     * @var \Hexaa\StorageBundle\Entity\Service
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Service")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="service_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Exclude
     */
    private $service;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     * @Groups({"api","gui", "oep"})
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     * @Groups({"api","gui", "oep"})
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
     * @SerializedName("entitlement_ids")
     * @Groups({"gui"})
    */
    public function getEntitlementIds()
    {
        $ids = array();
        foreach($this->entitlements as $e){
	    $ids[]=$e->getId();
	}
	return $ids;
    }
    
    /**
     * @VirtualProperty
     * @SerializedName("service_id")
     * @Groups({"api","gui", "oep"})
    */
    public function getServiceId()
    {
        return $this->service->getId();
    }




    /**
     * Set name
     *
     * @param string $name
     * @return EntitlementPack
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
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
     * Set description
     *
     * @param string $description
     * @return EntitlementPack
     */
    public function setDescription($description)
    {
        $this->description = $description;

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
     * Set type
     *
     * @param string $type
     * @return EntitlementPack
     */
    public function setType($type)
    {
        $this->type = $type;

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
     * Set token
     *
     * @param string $token
     * @return EntitlementPack
     */
    public function setToken($token)
    {
        $this->token = $token;

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
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set service
     *
     * @param \Hexaa\StorageBundle\Entity\Service $service
     * @return EntitlementPack
     */
    public function setService(\Hexaa\StorageBundle\Entity\Service $service = null)
    {
        $this->service = $service;

        return $this;
    }

    /**
     * Get service
     *
     * @return \Hexaa\StorageBundle\Entity\Service 
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Add entitlements
     *
     * @param \Hexaa\StorageBundle\Entity\Entitlement $entitlements
     * @return EntitlementPack
     */
    public function addEntitlement(\Hexaa\StorageBundle\Entity\Entitlement $entitlements)
    {
        $this->entitlements[] = $entitlements;

        return $this;
    }

    /**
     * Remove entitlements
     *
     * @param \Hexaa\StorageBundle\Entity\Entitlement $entitlements
     */
    public function removeEntitlement(\Hexaa\StorageBundle\Entity\Entitlement $entitlements)
    {
        $this->entitlements->removeElement($entitlements);
    }

    /**
     * Get entitlements
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getEntitlements()
    {
        return $this->entitlements;
    }
    
    /**
     * Has entitlement
     *
     * @param \Hexaa\StorageBundle\Entity\Entitlement $entitlement
     *
     * @return boolean
     */
    public function hasEntitlement(\Hexaa\StorageBundle\Entity\Entitlement $entitlement) 
    {
	return $this->entitlements->contains($entitlement);
    }
    

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return EntitlementPack
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
     * @return EntitlementPack
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
}
