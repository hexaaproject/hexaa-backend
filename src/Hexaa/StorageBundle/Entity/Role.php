<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

/**
 * Role
 *
 * @ORM\Table(name="role", indexes={@ORM\Index(name="organization_id_idx", columns={"organization_id"})})
 * @ORM\Entity
 */
class Role
{    
    /**
     * @ORM\ManyToMany(targetEntity="Entitlement")
     */
    private $entitlements;

 
    public function __construct() {
        $this->entitlements = new \Doctrine\Common\Collections\ArrayCollection();
    }
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="showorder", type="bigint", nullable=true)
     */
    private $showorder;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_date", type="datetime", nullable=false)
     */
    private $startDate;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_date", type="datetime", nullable=true)
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
     *   @ORM\JoinColumn(name="organization_id", referencedColumnName="id")
     * })
     * @Exclude
     */
    private $organization;



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
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set showorder
     *
     * @param integer $showorder
     * @return Role
     */
    public function setShoworder($showorder)
    {
        $this->showorder = $showorder;

        return $this;
    }

    /**
     * Get showorder
     *
     * @return integer 
     */
    public function getShoworder()
    {
        return $this->showorder;
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
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
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
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
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
     * @return Role
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
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return Role
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime 
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     * @return Role
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime 
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Add entitlements
     *
     * @param \Hexaa\StorageBundle\Entity\Entitlement $entitlements
     * @return Role
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
}
