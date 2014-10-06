<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Validator\Constraints as Assert;
use Hexaa\ApiBundle\Validator\Constraints as HexaaAssert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\Type;

/**
 * Role
 *
 * @ORM\Table(name="role", indexes={@ORM\Index(name="organization_id_idx", columns={"organization_id"})})
 * @ORM\Entity
 * @UniqueEntity({"organization", "name"})
 * @HexaaAssert\EntitlementCanBeAddedToRole()
 * @ORM\HasLifecycleCallbacks
 */
class Role {

    /**
     * @ORM\ManyToMany(targetEntity="Entitlement")
     * @Exclude
     */
    private $entitlements;

    public function __construct() {
        $this->entitlements = new \Doctrine\Common\Collections\ArrayCollection();
        $this->principals = new \Doctrine\Common\Collections\ArrayCollection();
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
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_date", type="datetime", nullable=true)
     * @Assert\DateTime()
     * 
     */
    private $startDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_date", type="datetime", nullable=true)
     * @Assert\DateTime()
     */
    private $endDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Hexaa\StorageBundle\Entity\Organization
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Organization")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Exclude
     */
    private $organization;

    /**
     * @ORM\OneToMany(targetEntity="RolePrincipal", mappedBy="role", cascade={"persist"}, orphanRemoval=true)
     * @Assert\Valid(traverse=true)
     * @HexaaAssert\PrincipalCanBeAddedToRole()
     * @Exclude
     */
    private $principals;

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
     * @SerializedName("scoped_name")
     * @Type("string")
     */
    public function getScopedName() {
        return $this->organization->getName() . "::" . $this->name;
    }

    /**
     * @VirtualProperty
     * @SerializedName("organization_id")
     * @Type("integer")
     */
    public function getOrganizationId() {
        return $this->organization->getId();
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Role
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
     * Set showorder
     *
     * @param integer $showorder
     * @return Role
     */
    public function setShoworder($showorder) {
        $this->showorder = $showorder;

        return $this;
    }

    /**
     * Get showorder
     *
     * @return integer 
     */
    public function getShoworder() {
        return $this->showorder;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Role
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Role
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
     * Set organization
     *
     * @param \Hexaa\StorageBundle\Entity\Organization $organization
     * @return Role
     */
    public function setOrganization(\Hexaa\StorageBundle\Entity\Organization $organization = null) {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization
     *
     * @return \Hexaa\StorageBundle\Entity\Organization 
     */
    public function getOrganization() {
        return $this->organization;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return Role
     */
    public function setStartDate($startDate) {/*
      if (!$startDate){ */
        $this->startDate = $startDate; /*
          } else {
          $this->startDate = new \DateTime($startDate);
          } */

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime 
     */
    public function getStartDate() {/*
      if ($startDate instanceof \DateTime){
      return $this->startDate->format("Y-m-d H:i:s");
      } else { */
        return $this->startDate;
        //}
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     * @return Role
     */
    public function setEndDate($endDate) {
        /* if (!$endDate){ */
        $this->endDate = $endDate; /*
          } else {
          $this->endDate = new \DateTime($endDate);
          } */

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime 
     */
    public function getEndDate() {
        /*
          if ($endDate instanceof \DateTime){
          return $this->endDate->format("Y-m-d H:i:s");
          } else { */
        return $this->endDate;
        //}
    }

    /**
     * Add entitlements
     *
     * @param \Hexaa\StorageBundle\Entity\Entitlement $entitlements
     * @return Role
     */
    public function addEntitlement(\Hexaa\StorageBundle\Entity\Entitlement $entitlements) {
        $this->entitlements[] = $entitlements;

        return $this;
    }

    /**
     * Remove entitlements
     *
     * @param \Hexaa\StorageBundle\Entity\Entitlement $entitlements
     */
    public function removeEntitlement(\Hexaa\StorageBundle\Entity\Entitlement $entitlements) {
        $this->entitlements->removeElement($entitlements);
    }

    /**
     * Get entitlements
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getEntitlements() {
        return $this->entitlements;
    }

    /**
     * Has entitlement
     *
     * @param \Hexaa\StorageBundle\Entity\Entitlement $entitlement
     *
     * @return boolean
     */
    public function hasEntitlement(\Hexaa\StorageBundle\Entity\Entitlement $entitlement) {
        return $this->entitlements->contains($entitlement);
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return Role
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
     * Add principals
     *
     * @param \Hexaa\StorageBundle\Entity\RolePrincipal $principals
     * @return Role
     */
    public function addPrincipal(\Hexaa\StorageBundle\Entity\RolePrincipal $principals) {
        $this->principals[] = $principals;

        if ($principals->getRole() !== $this) {
            $principals->setRole($this);
        }

        return $this;
    }

    /**
     * Remove principals
     *
     * @param \Hexaa\StorageBundle\Entity\RolePrincipal $principals
     */
    public function removePrincipal(\Hexaa\StorageBundle\Entity\RolePrincipal $principals) {

        //$principals->setRole(null);
        $this->principals->removeElement($principals);
    }

    /**
     * Get principals
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPrincipals() {
        return $this->principals;
    }

    /**
     * Has principal
     *
     * @param \Hexaa\StorageBundle\Entity\RolePrincipal $principal
     *
     * @return boolean
     */
    public function hasPrincipal(\Hexaa\StorageBundle\Entity\RolePrincipal $principal) {
        return $this->principals->contains($principal);
    }

}
