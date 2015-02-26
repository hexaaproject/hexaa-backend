<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Hexaa\ApiBundle\Validator\Constraints as HexaaAssert;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\VirtualProperty;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Organization
 *
 * @ORM\Table(
 *   name="organization",
 *   uniqueConstraints={
 *     @ORM\UniqueConstraint(name="name", columns={"name"})
 *   }
 * )
 * @ORM\Entity(repositoryClass="Hexaa\StorageBundle\Entity\OrganizationRepository")
 * @UniqueEntity("name")
 * @ORM\HasLifecycleCallbacks
 * @HexaaAssert\ManagerIsOrganizationMember(groups={"setmanager"})
 *
 */
class Organization {
    /**
     * @ORM\ManyToMany(targetEntity="Principal")
     * @ORM\JoinTable(name="organization_manager")
     * @Groups({"expanded"})
     */
    private $managers;

    /**
     * @ORM\ManyToMany(targetEntity="Principal")
     * @ORM\JoinTable(name="organization_principal")
     * @Groups({"expanded"})
     * @Accessor(getter="getPrincipalsForSerialization")
     */
    private $principals;


    public function __construct() {
        $this->principals = new ArrayCollection();
        $this->managers = new ArrayCollection();
        $this->entitlementPacks = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->securityDomains = new ArrayCollection();
    }

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     *
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = "3",
     *      max = "125"
     * )
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $name;

    /**
     * @var boolean
     *
     * @ORM\Column(name="isolate_members", type="boolean", nullable=true)
     *
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $isolateMembers = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="isolate_role_members", type="boolean", nullable=true)
     *
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $isolateRoleMembers = false;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     * @Assert\Url()
     *
     * @Groups({"normal", "expanded"})
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @Groups({"normal", "expanded"})
     */
    private $description;

    /**
     * @var \Hexaa\StorageBundle\Entity\Role
     *
     * @ORM\OneToOne(targetEntity="Hexaa\StorageBundle\Entity\Role")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="default_role_id", referencedColumnName="id")
     * })
     * @Groups({"expanded"})
     */
    private $defaultRole;

    /**
     * @ORM\OneToMany(targetEntity="OrganizationEntitlementPack", mappedBy="organization", cascade={"persist"})
     * @Assert\Valid(traverse=true)
     * @HexaaAssert\NewEntitlementPackIsEnabledAndNotPrivate()
     * @Groups({"expanded"})
     */
    private $entitlementPacks;

    /**
     * @var array
     *
     * @ManyToMany(targetEntity="Hexaa\StorageBundle\Entity\Tag", inversedBy="organizations", cascade={"all"})
     * @ORM\JoinTable(
     *   name="organization_tag",
     *   joinColumns={@ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="CASCADE")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="tag_id", referencedColumnName="name", onDelete="CASCADE")}
     * )
     * @Groups({"minimal", "normal", "extended"})
     **/
    private $tags;

    /**
     * @var array
     *
     * @ManyToMany(targetEntity="SecurityDomain", inversedBy="organizations")
     * @JoinTable(name="organization_security_domain")
     * @Exclude
     **/
    private $securityDomains;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     * @Groups({"normal", "expanded"})
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     * @Groups({"normal", "expanded"})
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
     * @SerializedName("default_role_id")
     * @Type("integer")
     * @Groups({"minimal", "normal"})
     */
    public function getRoleId() {
        if (isset($this->defaultRole)) {
            return $this->defaultRole->getId();
        } else return null;

    }


    /**
     * Set name
     *
     * @param string $name
     * @return Organization
     */
    public function setName($name) {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return Organization
     */
    public function setUrl($url) {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Organization
     */
    public function setDescription($description) {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Set defaultRoleId
     *
     * @param Role $defaultRole
     * @return Organization
     */
    public function setDefaultRole($defaultRole) {
        $this->defaultRole = $defaultRole;

        return $this;
    }

    /**
     * Get defaultRoleId
     *
     * @return Role
     */
    public function getDefaultRole() {
        return $this->defaultRole;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Organization
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
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Add managers
     *
     * @param Principal $managers
     * @return Organization
     */
    public function addManager(Principal $managers) {
        $this->managers[] = $managers;
        if (!$this->principals->contains($managers)) {
            $this->principals[] = $managers;
        }

        return $this;
    }

    /**
     * Remove managers
     *
     * @param Principal $managers
     */
    public function removeManager(Principal $managers) {
        $this->managers->removeElement($managers);
    }

    /**
     * Get managers
     *
     * @return ArrayCollection
     */
    public function getManagers() {
        return $this->managers;
    }

    /**
     * Has manager
     *
     * @param Principal $manager
     *
     * @return boolean
     */
    public function hasManager(Principal $manager) {
        return $this->managers->contains($manager);
    }

    /**
     * Has principal
     *
     * @param Principal $principal
     *
     * @return boolean
     */
    public function hasPrincipal(Principal $principal) {
        return $this->principals->contains($principal);
    }

    /**
     * Add principals
     *
     * @param Principal $principals
     * @return Organization
     */
    public function addPrincipal(Principal $principals) {
        $this->principals[] = $principals;

        return $this;
    }

    /**
     * Remove principals
     *
     * @param Principal $principals
     */
    public function removePrincipal(Principal $principals) {
        $this->principals->removeElement($principals);
        $this->managers->removeElement($principals);
    }

    /**
     * Get principals
     *
     * @return ArrayCollection
     */
    public function getPrincipals() {
        return $this->principals;
    }

    /**
     * Get principals for serialization
     *
     * @return ArrayCollection
     */
    public function getPrincipalsForSerialization() {
        if ($this->isolateMembers) {
            return null;
        } else {
            return $this->principals;
        }
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return Organization
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
     * Add entitlementPacks
     *
     * @param OrganizationEntitlementPack $entitlementPacks
     * @return Role
     */
    public function addEntitlementPack(OrganizationEntitlementPack $entitlementPacks) {
        $this->entitlementPacks[] = $entitlementPacks;

        if ($entitlementPacks->getOrganization() !== $this) {
            $entitlementPacks->setOrganization($this);
        }

        return $this;
    }

    /**
     * Remove entitlementPacks
     *
     * @param OrganizationEntitlementPack $entitlementPacks
     */
    public function removeEntitlementPack(OrganizationEntitlementPack $entitlementPacks) {

        $entitlementPacks->setOrganization(null);
        $this->entitlementPacks->removeElement($entitlementPacks);
    }

    /**
     * Get entitlementPacks
     *
     * @return ArrayCollection
     */
    public function getEntitlementPacks() {
        return $this->entitlementPacks;
    }

    /**
     * Has EntitlementPack
     *
     * @param OrganizationEntitlementPack $entitlementPack
     *
     * @return boolean
     */
    public function hasEntitlementPack(OrganizationEntitlementPack $entitlementPack) {
        return $this->entitlementPacks->contains($entitlementPack);
    }


    /**
     * Clear entitlementPacks
     *
     */
    public function clearEntitlementPacks() {
        $this->entitlementPacks->clear();
    }

    /**
     * @return boolean
     */
    public function isIsolateMembers() {
        return $this->isolateMembers;
    }

    /**
     * @param boolean $isolateMembers
     */
    public function setIsolateMembers($isolateMembers) {
        $this->isolateMembers = $isolateMembers;
    }

    /**
     * @return boolean
     */
    public function isIsolateRoleMembers() {
        return $this->isolateRoleMembers;
    }

    /**
     * @param boolean $isolateRoleMembers
     */
    public function setIsolateRoleMembers($isolateRoleMembers) {
        $this->isolateRoleMembers = $isolateRoleMembers;
    }

    /**
     * Add tags
     *
     * @param Tag $tag
     * @return Organization
     */
    public function addTag(Tag $tag) {
        if(!$tag->hasOrganization($this)) {
            $tag->addOrganization($this);
        }
        $this->tags->add($tag);
    }

    /**
     * Remove tags
     *
     * @param Tag $tag
     */
    public function removeTag(Tag $tag) {
        if($tag->hasOrganization($this)) {
            $tag->removeOrganization($this);
        }
        $this->tags->removeElement($tag);
    }

    /**
     * Get tags
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTags() {
        return $this->tags;
    }

    /**
     * Has tag
     *
     * @param Tag $tag
     * @return boolean
     */
    public function hasTag(Tag $tag) {
        return $this->tags->contains($tag);
    }

    /**
     * Add securityDomains
     *
     * @param \Hexaa\StorageBundle\Entity\SecurityDomain $securityDomain
     * @return Service
     */
    public function addSecurityDomain(SecurityDomain $securityDomain) {
        if (!$securityDomain->hasOrganization($this)){
            $securityDomain->addOrganization($this);
        }
        $this->securityDomains->add($securityDomain);

        return $this;
    }

    /**
     * Remove securityDomains
     *
     * @param \Hexaa\StorageBundle\Entity\SecurityDomain $securityDomain
     */
    public function removeSecurityDomain(SecurityDomain $securityDomain) {
        if($securityDomain->hasOrganization($this)) {
            $securityDomain->removeOrganization($this);
        }
        $this->securityDomains->removeElement($securityDomain);
    }

    /**
     * Get securityDomains
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSecurityDomains() {
        return $this->securityDomains;
    }

    /**
     * Has SecurityDomain
     *
     * @param SecurityDomain $securityDomain
     * @return boolean
     */
    public function hasSecurityDomain(SecurityDomain $securityDomain) {
        return $this->tags->contains($securityDomain);
    }

    public function __toString() {
        return $this->name;
    }
}
