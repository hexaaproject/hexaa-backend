<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Principal
 *
 * @ORM\Table(
 *   name="principal",
 *   uniqueConstraints={
 *     @ORM\UniqueConstraint(name="fedid", columns={"fedid"})
 *   },
 *   indexes={
 *     @ORM\Index(name="fedid_idx", columns={"fedid"}),
 *     @ORM\Index(name="token_idx", columns={"token_id"})
 *   }
 * )
 * @ORM\Entity(repositoryClass="Hexaa\StorageBundle\Entity\PrincipalRepository")
 * @UniqueEntity("fedid")
 * @ORM\HasLifecycleCallbacks
 *
 */
class Principal
{

    /**
     * @var string
     *
     * @ORM\Column(name="fedid", type="string", length=255, nullable=false)
     *
     * @Assert\NotBlank()
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $fedid;
    /**
     * @var \Hexaa\StorageBundle\Entity\PersonalToken
     *
     * @ORM\OneToOne(targetEntity="Hexaa\StorageBundle\Entity\PersonalToken",cascade={"persist"}, inversedBy="principal")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="token_id", referencedColumnName="id")
     * })
     * @Exclude
     */
    private $token = null;
    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     *
     * @Assert\Email(strict=true)
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $email;
    /**
     * @var string
     *
     * @ORM\Column(name="display_name", type="string", length=255, nullable=true)
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $displayName;
    /**
     * var Collection
     *
     * @ORM\ManyToMany(targetEntity="Service", mappedBy="managers")
     * @Groups({"expanded"})
     */
    private $services;
    /**
     * @ORM\OneToMany(targetEntity="Hexaa\StorageBundle\Entity\Invitation", mappedBy="inviter")
     * @Assert\Valid()
     * @Groups({"expanded"})
     */
    private $invitations;
    /**
     * @ORM\OneToMany(targetEntity="Hexaa\StorageBundle\Entity\AttributeValuePrincipal", mappedBy="principal")
     * @Assert\Valid()
     * @Groups({"expanded"})
     */
    private $attributeValuePrincipals;
    /**
     * var Collection
     *
     * @ORM\ManyToMany(targetEntity="Organization", mappedBy="managers")
     * @Groups({"expanded"})
     */
    private $managedOrganizations;
    /**
     * var Collection
     *
     * @ORM\ManyToMany(targetEntity="Organization", mappedBy="principals")
     * @Groups({"expanded"})
     */
    private $memberedOrganizations;
    /**
     * @ORM\OneToMany(targetEntity="RolePrincipal", mappedBy="principal", cascade={"persist"}, orphanRemoval=true)
     * @Assert\Valid(traverse=true)
     * @Groups({"expanded"})
     */
    private $roles;
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"minimal", "normal", "expanded"})
     *
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
        $this->services = new ArrayCollection();
        $this->roles = new ArrayCollection();
        $this->invitations = new ArrayCollection();
        $this->attributeValuePrincipals = new ArrayCollection();
        $this->managedOrganizations = new ArrayCollection();
        $this->memberedOrganizations = new ArrayCollection();
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
     * @return Principal
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get fedid
     *
     * @return string
     */
    public function getFedid()
    {
        return $this->fedid;
    }

    /**
     * Set fedid
     *
     * @param string $fedid
     * @return Principal
     */
    public function setFedid($fedid)
    {
        $this->fedid = $fedid;

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
     * Get token
     *
     * @return PersonalToken
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set token
     *
     * @param PersonalToken $token
     * @return Principal
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get display name
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * Set display name
     *
     * @param string $displayName
     * @return Principal
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;

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
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return Principal
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Principal
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    public function __toString()
    {
        return $this->fedid;
    }

    /**
     * @return Collection
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * @param Collection $services
     */
    public function setServices($services)
    {
        $this->services = $services;
    }

    /**
     * @param Service $service
     * @return bool
     */
    public function hasService(Service $service)
    {
        return $this->services->contains($service);
    }


    /**
     * Add services
     *
     * @param \Hexaa\StorageBundle\Entity\Service $services
     * @return Principal
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
     * Add invitations
     *
     * @param \Hexaa\StorageBundle\Entity\Invitation $invitations
     * @return Principal
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
     * Add attributeValuePrincipals
     *
     * @param \Hexaa\StorageBundle\Entity\AttributeValuePrincipal $attributeValuePrincipals
     * @return Principal
     */
    public function addAttributeValuePrincipal(\Hexaa\StorageBundle\Entity\AttributeValuePrincipal $attributeValuePrincipals)
    {
        $this->attributeValuePrincipals[] = $attributeValuePrincipals;

        return $this;
    }

    /**
     * Remove attributeValuePrincipals
     *
     * @param \Hexaa\StorageBundle\Entity\AttributeValuePrincipal $attributeValuePrincipals
     */
    public function removeAttributeValuePrincipal(\Hexaa\StorageBundle\Entity\AttributeValuePrincipal $attributeValuePrincipals)
    {
        $this->attributeValuePrincipals->removeElement($attributeValuePrincipals);
    }

    /**
     * Get attributeValuePrincipals
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAttributeValuePrincipals()
    {
        return $this->attributeValuePrincipals;
    }

    /**
     * Add managedOrganizations
     *
     * @param \Hexaa\StorageBundle\Entity\Organization $managedOrganizations
     * @return Principal
     */
    public function addManagedOrganization(\Hexaa\StorageBundle\Entity\Organization $managedOrganizations)
    {
        $this->managedOrganizations[] = $managedOrganizations;

        return $this;
    }

    /**
     * Remove managedOrganizations
     *
     * @param \Hexaa\StorageBundle\Entity\Organization $managedOrganizations
     */
    public function removeManagedOrganization(\Hexaa\StorageBundle\Entity\Organization $managedOrganizations)
    {
        $this->managedOrganizations->removeElement($managedOrganizations);
    }

    /**
     * Get managedOrganizations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getManagedOrganizations()
    {
        return $this->managedOrganizations;
    }

    /**
     * Add memberedOrganizations
     *
     * @param \Hexaa\StorageBundle\Entity\Organization $memberedOrganizations
     * @return Principal
     */
    public function addMemberedOrganization(\Hexaa\StorageBundle\Entity\Organization $memberedOrganizations)
    {
        $this->memberedOrganizations[] = $memberedOrganizations;

        return $this;
    }

    /**
     * Remove memberedOrganizations
     *
     * @param \Hexaa\StorageBundle\Entity\Organization $memberedOrganizations
     */
    public function removeMemberedOrganization(\Hexaa\StorageBundle\Entity\Organization $memberedOrganizations)
    {
        $this->memberedOrganizations->removeElement($memberedOrganizations);
    }

    /**
     * Get memberedOrganizations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMemberedOrganizations()
    {
        return $this->memberedOrganizations;
    }

    /**
     * Add roles
     *
     * @param \Hexaa\StorageBundle\Entity\RolePrincipal $roles
     * @return Principal
     */
    public function addRole(\Hexaa\StorageBundle\Entity\RolePrincipal $roles)
    {
        $this->roles[] = $roles;

        return $this;
    }

    /**
     * Remove roles
     *
     * @param \Hexaa\StorageBundle\Entity\RolePrincipal $roles
     */
    public function removeRole(\Hexaa\StorageBundle\Entity\RolePrincipal $roles)
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
