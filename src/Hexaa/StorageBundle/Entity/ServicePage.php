<?php
namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Exclude;

/**
 * @ORM\Table(name="service_page")
 * @ORM\Entity
 */
class ServicePage {
//Dummy class formType-hoz
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Exclude
     */
    private $id;
    
    /**
     * @var \Hexaa\StorageBundle\Entity\Service
     *
     * @ORM\ManyToOne(targetEntity="Service")
     * @SerializedName("properties")
     */
    protected $service;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="Principal", mappedBy="service_page")
     */
    protected $managers;
    
    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="ServiceAttributeSpec", mappedBy="service_page")
     */
    protected $attributeSpecifications;
    
    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="Entitlement", mappedBy="service_page")
     */
    protected $entitlements;
    
    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="EntitlementPack", mappedBy="service_page")
     */
    protected $entitlementPacks;
    
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->managers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->attributeSpecifications = new \Doctrine\Common\Collections\ArrayCollection();
        $this->entitlements = new \Doctrine\Common\Collections\ArrayCollection();
        $this->entitlementPacks = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Add managers
     *
     * @param \Hexaa\StorageBundle\Entity\Principal $managers
     * @return ServicePage
     */
    public function addManager(\Hexaa\StorageBundle\Entity\Principal $managers)
    {
        $this->managers[] = $managers;

        return $this;
    }

    /**
     * Remove managers
     *
     * @param \Hexaa\StorageBundle\Entity\Principal $managers
     */
    public function removeManager(\Hexaa\StorageBundle\Entity\Principal $managers)
    {
        $this->managers->removeElement($managers);
    }

    /**
     * Add attributeSpecifications
     *
     * @param \Hexaa\StorageBundle\Entity\ServiceAttributeSpec $attributeSpecifications
     * @return ServicePage
     */
    public function addAttributeSpecification(\Hexaa\StorageBundle\Entity\ServiceAttributeSpec $attributeSpecifications)
    {
        $this->attributeSpecifications[] = $attributeSpecifications;

        return $this;
    }

    /**
     * Remove attributeSpecifications
     *
     * @param \Hexaa\StorageBundle\Entity\ServiceAttributeSpec $attributeSpecifications
     */
    public function removeAttributeSpecification(\Hexaa\StorageBundle\Entity\ServiceAttributeSpec $attributeSpecifications)
    {
        $this->attributeSpecifications->removeElement($attributeSpecifications);
    }

    /**
     * Add entitlements
     *
     * @param \Hexaa\StorageBundle\Entity\Entitlement $entitlements
     * @return ServicePage
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
     * Add entitlementPacks
     *
     * @param \Hexaa\StorageBundle\Entity\EntitlementPack $entitlementPacks
     * @return ServicePage
     */
    public function addEntitlementPack(\Hexaa\StorageBundle\Entity\EntitlementPack $entitlementPacks)
    {
        $this->entitlementPacks[] = $entitlementPacks;

        return $this;
    }

    /**
     * Remove entitlementPacks
     *
     * @param \Hexaa\StorageBundle\Entity\EntitlementPack $entitlementPacks
     */
    public function removeEntitlementPack(\Hexaa\StorageBundle\Entity\EntitlementPack $entitlementPacks)
    {
        $this->entitlementPacks->removeElement($entitlementPacks);
    }
    

    /**
     * Get managers
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getManagers()
    {
        return $this->managers;
    }

    /**
     * Get attributeSpecifications
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getAttributeSpecifications()
    {
        return $this->attributeSpecifications;
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
     * Get entitlementPacks
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getEntitlementPacks()
    {
        return $this->entitlementPacks;
    }


    /**
     * Set service
     *
     * @param \Hexaa\StorageBundle\Entity\Service $service
     * @return ServicePage
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
}
