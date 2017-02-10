<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Hexaa\ApiBundle\Validator\Constraints as HexaaAssert;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\VirtualProperty;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Role
 *
 * @ORM\Table(
 *   name="role",
 *   indexes={
 *     @ORM\Index(name="organization_id_idx", columns={"organization_id"})
 *   },
 *   uniqueConstraints={
 *     @ORM\UniqueConstraint(name="name_organization", columns={"name", "organization_id"})
 *   }
 * )
 * @ORM\Entity
 * @UniqueEntity({"organization", "name"})
 * @HexaaAssert\EntitlementCanBeAddedToRole()
 * @ORM\HasLifecycleCallbacks
 *
 */
class Role
{

    /**
     * @ORM\ManyToMany(targetEntity="Entitlement", inversedBy="roles")
     * @Groups({"expanded"})
     */
    private $entitlements;
    /**
     * @ORM\OneToMany(targetEntity="Hexaa\StorageBundle\Entity\Invitation", mappedBy="role")
     * @Assert\Valid()
     * @Groups({"expanded"})
     */
    private $invitations;
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
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @Groups({"normal", "expanded"})
     */
    private $description;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_date", type="datetime", nullable=true)
     * @Assert\DateTime()
     * @Groups({"minimal", "normal", "expanded"})
     *
     */
    private $startDate;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_date", type="datetime", nullable=true)
     * @Assert\DateTime()
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $endDate;
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
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Organization", inversedBy="roles")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Groups({"expanded"})
     */
    private $organization;
    /**
     * @ORM\OneToMany(targetEntity="RolePrincipal", mappedBy="role", cascade={"persist"}, orphanRemoval=true)
     * @Assert\Valid(traverse=true)
     * @HexaaAssert\PrincipalCanBeAddedToRole()
     * @Groups({"expanded"})
     * @Accessor(getter="getPrincipalsForSerialization")
     */
    private $principals;
    /**
     * @var \Hexaa\StorageBundle\Entity\Organization
     *
     * @ORM\OneToOne(targetEntity="Hexaa\StorageBundle\Entity\Organization", mappedBy="defaultRole")
     * @Groups({"expanded"})
     */
    private $defaultAt;

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
        $this->entitlements = new ArrayCollection();
        $this->principals = new ArrayCollection();
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
     * @return Role
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("scoped_name")
     * @Type("string")
     * @Groups({"minimal", "normal", "expanded"})
     */
    public function getScopedName()
    {
        return $this->organization->getName() . "::" . $this->name;
    }

    /**
     * @VirtualProperty
     * @SerializedName("organization_id")
     * @Type("integer")
     * @Groups({"minimal", "normal"})
     */
    public function getOrganizationId()
    {
        return $this->organization->getId();
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
     * @return Role
     */
    public function setName($name)
    {
        $this->name = $name;

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
     * @return Role
     */
    public function setDescription($description)
    {
        $this->description = $description;

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
     * Get organization
     *
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Set organization
     *
     * @param Organization $organization
     * @return Role
     */
    public function setOrganization(Organization $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime
     */
    public function getStartDate()
    {/*
      if ($startDate instanceof \DateTime){
      return $this->startDate->format("Y-m-d H:i:s");
      } else { */
        return $this->startDate;
        //}
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return Role
     */
    public function setStartDate($startDate)
    {/*
      if (!$startDate){ */
        $this->startDate = $startDate; /*
          } else {
          $this->startDate = new \DateTime($startDate);
          } */

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        /*
          if ($endDate instanceof \DateTime){
          return $this->endDate->format("Y-m-d H:i:s");
          } else { */
        return $this->endDate;
        //}
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     * @return Role
     */
    public function setEndDate($endDate)
    {
        /* if (!$endDate){ */
        $this->endDate = $endDate; /*
          } else {
          $this->endDate = new \DateTime($endDate);
          } */

        return $this;
    }

    /**
     * Add entitlements
     *
     * @param Entitlement $entitlements
     * @return Role
     */
    public function addEntitlement(Entitlement $entitlements)
    {
        $this->entitlements[] = $entitlements;

        return $this;
    }

    /**
     * Remove entitlements
     *
     * @param Entitlement $entitlements
     */
    public function removeEntitlement(Entitlement $entitlements)
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
     * @param Entitlement $entitlement
     *
     * @return boolean
     */
    public function hasEntitlement(Entitlement $entitlement)
    {
        return $this->entitlements->contains($entitlement);
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
     * @return Role
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Add principals
     *
     * @param RolePrincipal $principals
     * @return Role
     */
    public function addPrincipal(RolePrincipal $principals)
    {
        $this->principals[] = $principals;

        if ($principals->getRole() !== $this) {
            $principals->setRole($this);
        }

        return $this;
    }

    /**
     * Remove principals
     *
     * @param RolePrincipal $principals
     */
    public function removePrincipal(RolePrincipal $principals)
    {

        //$principals->setRole(null);
        $this->principals->removeElement($principals);
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
        if ($this->organization->isIsolateRoleMembers()) {
            return null;
        } else {
            return $this->principals;
        }
    }

    /**
     * Has principal
     *
     * @param RolePrincipal $principal
     *
     * @return boolean
     */
    public function hasPrincipal(RolePrincipal $principal)
    {
        return $this->principals->contains($principal);
    }

    public function __toString()
    {
        return $this->name;
    }


    /**
     * Add invitations
     *
     * @param \Hexaa\StorageBundle\Entity\Invitation $invitations
     * @return Role
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
     * @return \Hexaa\StorageBundle\Entity\Organization
     */
    public function getDefaultAt()
    {
        return $this->defaultAt;
    }

    /**
     * @param \Hexaa\StorageBundle\Entity\Organization $defaultAt
     */
    public function setDefaultAt($defaultAt)
    {
        $this->defaultAt = $defaultAt;
    }
}
