<?php
namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Exclude;

/**
 * @ORM\Entity
 */
class OrganizationPage {
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
     * @var \Hexaa\StorageBundle\Entity\Organization
     *
     * @ORM\ManyToOne(targetEntity="Organization")
     * @SerializedName("properties")
     */
    protected $organization;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="Principal", mappedBy="organization_page")
     */
    protected $managers;
    
    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="Principal", mappedBy="organization_page")
     */
    protected $members;
    
    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="Entitlement", mappedBy="organization_page")
     */
    protected $entitlements;
    
    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="OrganizationEntitlementPack", mappedBy="organization_page")
     */
    protected $connectedEntitlementPacks;
    
    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="Role", mappedBy="organization_page")
     */
    protected $roles;
    
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->managers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->members = new \Doctrine\Common\Collections\ArrayCollection();
        $this->entitlements = new \Doctrine\Common\Collections\ArrayCollection();
        $this->connectedEntitlementPacks = new \Doctrine\Common\Collections\ArrayCollection();
        $this->roles = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set organization
     *
     * @param \Hexaa\StorageBundle\Entity\Organization $organization
     * @return OrganizationPage
     */
    public function setOrganization(\Hexaa\StorageBundle\Entity\Organization $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization
     *
     * @return \Hexaa\StorageBundle\Entity\Organization 
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Add managers
     *
     * @param \Hexaa\StorageBundle\Entity\Principal $managers
     * @return OrganizationPage
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
     * Get managers
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getManagers()
    {
        return $this->managers;
    }

    /**
     * Add members
     *
     * @param \Hexaa\StorageBundle\Entity\Principal $members
     * @return OrganizationPage
     */
    public function addMember(\Hexaa\StorageBundle\Entity\Principal $members)
    {
        $this->members[] = $members;

        return $this;
    }

    /**
     * Remove members
     *
     * @param \Hexaa\StorageBundle\Entity\Principal $members
     */
    public function removeMember(\Hexaa\StorageBundle\Entity\Principal $members)
    {
        $this->members->removeElement($members);
    }

    /**
     * Get members
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getMembers()
    {
        return $this->members;
    }

    /**
     * Add entitlements
     *
     * @param \Hexaa\StorageBundle\Entity\Entitlement $entitlements
     * @return OrganizationPage
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
     * Add connectedEntitlementPacks
     *
     * @param \Hexaa\StorageBundle\Entity\OrganizationEntitlementPack $connectedEntitlementPacks
     * @return OrganizationPage
     */
    public function addConnectedEntitlementPack(\Hexaa\StorageBundle\Entity\OrganizationEntitlementPack $connectedEntitlementPacks)
    {
        $this->connectedEntitlementPacks[] = $connectedEntitlementPacks;

        return $this;
    }

    /**
     * Remove connectedEntitlementPacks
     *
     * @param \Hexaa\StorageBundle\Entity\OrganizationEntitlementPack $connectedEntitlementPacks
     */
    public function removeConnectedEntitlementPack(\Hexaa\StorageBundle\Entity\OrganizationEntitlementPack $connectedEntitlementPacks)
    {
        $this->connectedEntitlementPacks->removeElement($connectedEntitlementPacks);
    }

    /**
     * Get connectedEntitlementPacks
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getConnectedEntitlementPacks()
    {
        return $this->connectedEntitlementPacks;
    }

    /**
     * Add roles
     *
     * @param \Hexaa\StorageBundle\Entity\Role $roles
     * @return OrganizationPage
     */
    public function addRole(\Hexaa\StorageBundle\Entity\Role $roles)
    {
        $this->roles[] = $roles;

        return $this;
    }

    /**
     * Remove roles
     *
     * @param \Hexaa\StorageBundle\Entity\Role $roles
     */
    public function removeRole(\Hexaa\StorageBundle\Entity\Role $roles)
    {
        $this->roles->removeElement($roles);
    }

    /**
     * Get roles
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRoles()
    {
        return $this->roles;
    }
}
