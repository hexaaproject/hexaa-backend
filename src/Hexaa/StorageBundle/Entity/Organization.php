<?php

/**
 * Copyright 2014-2018 MTA SZTAKI, ugyeletes@sztaki.hu
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Hexaa\StorageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Hexaa\ApiBundle\Validator\Constraints as HexaaAssert;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\MaxDepth;
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
 *
 */
class Organization
{
    /**
     * @ORM\ManyToMany(targetEntity="Principal", inversedBy="managedOrganizations")
     * @ORM\JoinTable(name="organization_manager")
     * @Groups({"expanded"})
     * @MaxDepth(2)
     */
    private $managers;

    /**
     * @ORM\ManyToMany(targetEntity="Principal", inversedBy="memberedOrganizations")
     * @ORM\JoinTable(name="organization_principal")
     * @Groups({"expanded"})
     * @Accessor(getter="getPrincipalsForSerialization")
     * @MaxDepth(2)
     */
    private $principals;

    /**
     * @ORM\OneToMany(targetEntity="Hexaa\StorageBundle\Entity\Invitation", mappedBy="organization")
     * @Groups({"expanded"})
     * @MaxDepth(2)
     */
    private $invitations;
    /**
     * @ORM\OneToMany(targetEntity="Hexaa\StorageBundle\Entity\AttributeValueOrganization", mappedBy="organization")
     * @Groups({"expanded"})
     * @MaxDepth(2)
     */
    private $attributeValueOrganizations;

    /**
     * @ORM\OneToMany(targetEntity="Hook", mappedBy="organization", cascade={"persist"}, orphanRemoval=true)
     * @Assert\Valid(traverse=true)
     */
    private $hooks;
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
     * @ORM\OneToOne(targetEntity="Hexaa\StorageBundle\Entity\Role", inversedBy="defaultAt")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="default_role_id", referencedColumnName="id")
     * })
     * @Groups({"expanded"})
     * @MaxDepth(2)
     */
    private $defaultRole;
    /**
     * @ORM\OneToMany(targetEntity="Hexaa\StorageBundle\Entity\Link", mappedBy="organization", cascade={"persist"})
     * @Assert\Valid()
     * @Groups({"expanded"})
     * @MaxDepth(2)
     */
    private $links;
    /**
     * @ORM\OneToMany(targetEntity="Hexaa\StorageBundle\Entity\Role", mappedBy="organization", cascade={"persist"})
     * @Groups({"expanded"})
     * @MaxDepth(2)
     */
    private $roles;
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

    public function __construct()
    {
        $this->principals = new ArrayCollection();
        $this->managers = new ArrayCollection();
        $this->links = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->securityDomains = new ArrayCollection();
        $this->hooks = new ArrayCollection();
        $this->roles = new ArrayCollection();
        $this->attributeValueOrganizations = new ArrayCollection();
        $this->invitations = new ArrayCollection();
    }

    /**
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps()
    {
        $this->setUpdatedAt(new \DateTime('now'));

        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new \DateTime('now'));
        }
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Organization
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("default_role_id")
     * @Type("integer")
     * @Groups({"minimal", "normal"})
     */
    public function getRoleId()
    {
        if (isset($this->defaultRole)) {
            return $this->defaultRole->getId();
        } else {
            return null;
        }

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
     * Set name
     *
     * @param string $name
     * @return Organization
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return Organization
     */
    public function setUrl($url)
    {
        $this->url = $url;

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
     * Set description
     *
     * @param string $description
     * @return Organization
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get defaultRoleId
     *
     * @return Role
     */
    public function getDefaultRole()
    {
        return $this->defaultRole;
    }

    /**
     * Set defaultRoleId
     *
     * @param Role $defaultRole
     * @return Organization
     */
    public function setDefaultRole($defaultRole)
    {
        $this->defaultRole = $defaultRole;

        return $this;
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
     * @param Principal $managers
     * @return Organization
     */
    public function addManager(Principal $managers)
    {
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
    public function removeManager(Principal $managers)
    {
        $this->managers->removeElement($managers);
    }

    /**
     * Get managers
     *
     * @return ArrayCollection
     */
    public function getManagers()
    {
        return $this->managers;
    }

    /**
     * Has manager
     *
     * @param Principal $manager
     *
     * @return boolean
     */
    public function hasManager(Principal $manager)
    {
        return $this->managers->contains($manager);
    }

    /**
     * Has principal
     *
     * @param Principal $principal
     *
     * @return boolean
     */
    public function hasPrincipal(Principal $principal)
    {
        return $this->principals->contains($principal);
    }

    /**
     * Add principals
     *
     * @param Principal $principals
     * @return Organization
     */
    public function addPrincipal(Principal $principals)
    {
        $this->principals[] = $principals;

        return $this;
    }

    /**
     * Remove principals
     *
     * @param Principal $principals
     */
    public function removePrincipal(Principal $principals)
    {
        $this->principals->removeElement($principals);
        $this->managers->removeElement($principals);
    }

    /**
     * Get principals
     *
     * @return ArrayCollection
     */
    public function getPrincipals()
    {
        return $this->principals;
    }

    /**
     * Get principals for serialization
     *
     * @return ArrayCollection
     */
    public function getPrincipalsForSerialization()
    {
        if ($this->isolateMembers) {
            return null;
        } else {
            return $this->principals;
        }
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
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return Organization
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Add links
     *
     * @param Link $link
     * @return Organization
     */
    public function addLink(Link $link)
    {
        $this->links[] = $link;

        if ($link->getOrganization() !== $this) {
            $link->setOrganization($this);
        }

        return $this;
    }

    /**
     * Remove link
     *
     * @param Link $link
     */
    public function removeLink(Link $link)
    {

        $link->setOrganization(null);
        $this->links->removeElement($link);
    }

    /**
     * Get links
     *
     * @return ArrayCollection
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * Has EntitlementPack
     *
     * @param Link $link
     *
     * @return boolean
     */
    public function hasLink(Link $link)
    {
        return $this->links->contains($link);
    }


    /**
     * Clear links
     *
     */
    public function clearLinks()
    {
        $this->links->clear();
    }

    /**
     * @return boolean
     */
    public function isIsolateMembers()
    {
        return $this->isolateMembers;
    }

    /**
     * Get isolateMembers
     *
     * @return boolean
     */
    public function getIsolateMembers()
    {
        return $this->isolateMembers;
    }

    /**
     * @param boolean $isolateMembers
     */
    public function setIsolateMembers($isolateMembers)
    {
        $this->isolateMembers = $isolateMembers;
    }

    /**
     * @return boolean
     */
    public function isIsolateRoleMembers()
    {
        return $this->isolateRoleMembers;
    }

    /**
     * Get isolateRoleMembers
     *
     * @return boolean
     */
    public function getIsolateRoleMembers()
    {
        return $this->isolateRoleMembers;
    }

    /**
     * @param boolean $isolateRoleMembers
     */
    public function setIsolateRoleMembers($isolateRoleMembers)
    {
        $this->isolateRoleMembers = $isolateRoleMembers;
    }

    /**
     * Add tags
     *
     * @param Tag $tag
     * @return Organization
     */
    public function addTag(Tag $tag)
    {
        if (!$tag->hasOrganization($this)) {
            $tag->addOrganization($this);
        }
        $this->tags->add($tag);

        return $this;
    }

    /**
     * Remove tags
     *
     * @param Tag $tag
     */
    public function removeTag(Tag $tag)
    {
        if ($tag->hasOrganization($this)) {
            $tag->removeOrganization($this);
        }
        $this->tags->removeElement($tag);
    }

    /**
     * Get tags
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Has tag
     *
     * @param Tag $tag
     * @return boolean
     */
    public function hasTag(Tag $tag)
    {
        return $this->tags->contains($tag);
    }

    /**
     * Add securityDomains
     *
     * @param \Hexaa\StorageBundle\Entity\SecurityDomain $securityDomain
     * @return Organization
     */
    public function addSecurityDomain(SecurityDomain $securityDomain)
    {
        $this->securityDomains->add($securityDomain);

        return $this;
    }

    /**
     * Remove securityDomains
     *
     * @param \Hexaa\StorageBundle\Entity\SecurityDomain $securityDomain
     */
    public function removeSecurityDomain(SecurityDomain $securityDomain)
    {
        $this->securityDomains->removeElement($securityDomain);
    }

    /**
     * Get securityDomains
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSecurityDomains()
    {
        return $this->securityDomains;
    }

    /**
     * Has SecurityDomain
     *
     * @param SecurityDomain $securityDomain
     * @return boolean
     */
    public function hasSecurityDomain(SecurityDomain $securityDomain)
    {
        return $this->tags->contains($securityDomain);
    }

    /**
     * @return mixed
     */
    public function getHooks()
    {
        return $this->hooks;
    }

    /**
     * @param mixed $hooks
     */
    public function setHooks($hooks)
    {
        $this->hooks = $hooks;
    }

    public function __toString()
    {
        return $this->name;
    }

    /**
     * Add hooks
     *
     * @param \Hexaa\StorageBundle\Entity\Hook $hooks
     * @return Organization
     */
    public function addHook(Hook $hooks)
    {
        $this->hooks[] = $hooks;

        return $this;
    }

    /**
     * Remove hooks
     *
     * @param \Hexaa\StorageBundle\Entity\Hook $hooks
     */
    public function removeHook(Hook $hooks)
    {
        $this->hooks->removeElement($hooks);
    }

    /**
     * Add roles
     *
     * @param \Hexaa\StorageBundle\Entity\Role $roles
     * @return Organization
     */
    public function addRole(Role $roles)
    {
        $this->roles[] = $roles;

        return $this;
    }

    /**
     * Remove roles
     *
     * @param \Hexaa\StorageBundle\Entity\Role $roles
     */
    public function removeRole(Role $roles)
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

    /**
     * Add invitations
     *
     * @param \Hexaa\StorageBundle\Entity\Invitation $invitations
     * @return Organization
     */
    public function addInvitation(\Hexaa\StorageBundle\Entity\Invitation $invitations)
    {
        $this->invitations[] = $invitations;

        return $this;
    }

    /**
     * Remove invitations
     *
     * @param \Hexaa\StorageBundle\Entity\Invitation $invitations
     */
    public function removeInvitation(\Hexaa\StorageBundle\Entity\Invitation $invitations)
    {
        $this->invitations->removeElement($invitations);
    }

    /**
     * Get invitations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getInvitations()
    {
        return $this->invitations;
    }

    /**
     * Add attributeValueOrganizations
     *
     * @param \Hexaa\StorageBundle\Entity\AttributeValueOrganization $attributeValueOrganizations
     * @return Organization
     */
    public function addAttributeValueOrganization(
      \Hexaa\StorageBundle\Entity\AttributeValueOrganization $attributeValueOrganizations
    ) {
        $this->attributeValueOrganizations[] = $attributeValueOrganizations;

        return $this;
    }

    /**
     * Remove attributeValueOrganizations
     *
     * @param \Hexaa\StorageBundle\Entity\AttributeValueOrganization $attributeValueOrganizations
     */
    public function removeAttributeValueOrganization(
      \Hexaa\StorageBundle\Entity\AttributeValueOrganization $attributeValueOrganizations
    ) {
        $this->attributeValueOrganizations->removeElement($attributeValueOrganizations);
    }

    /**
     * Get attributeValueOrganizations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAttributeValueOrganizations()
    {
        return $this->attributeValueOrganizations;
    }
}
