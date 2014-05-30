<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OrganizationEntitlementPack
 *
 * @ORM\Table(name="organization_entitlement_pack", indexes={@ORM\Index(name="organization_id_idx", columns={"organization_id"}), @ORM\Index(name="entitlement_pack_id_idx", columns={"entitlement_pack_id"})})
 * @ORM\Entity
 */
class OrganizationEntitlementPack
{
    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255, columnDefinition="ENUM('accepted', 'pending')", nullable=false)
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="accept_at", type="datetime", nullable=true)
     */
    private $acceptAt;

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
     */
    private $organization;

    /**
     * @var \Hexaa\StorageBundle\Entity\EntitlementPack
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\EntitlementPack")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="entitlement_pack_id", referencedColumnName="id")
     * })
     */
    private $entitlementPack;



    /**
     * Set status
     *
     * @param string $status
     * @return OrganizationEntitlementPack
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return OrganizationEntitlementPack
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
     * Set acceptAt
     *
     * @param \DateTime $acceptAt
     * @return OrganizationEntitlementPack
     */
    public function setAcceptAt($acceptAt)
    {
        $this->acceptAt = $acceptAt;

        return $this;
    }

    /**
     * Get acceptAt
     *
     * @return \DateTime 
     */
    public function getAcceptAt()
    {
        return $this->acceptAt;
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
     * @return OrganizationEntitlementPack
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
     * Set entitlementPack
     *
     * @param \Hexaa\StorageBundle\Entity\EntitlementPack $entitlementPack
     * @return OrganizationEntitlementPack
     */
    public function setEntitlementPack(\Hexaa\StorageBundle\Entity\EntitlementPack $entitlementPack = null)
    {
        $this->entitlementPack = $entitlementPack;

        return $this;
    }

    /**
     * Get entitlementPack
     *
     * @return \Hexaa\StorageBundle\Entity\EntitlementPack 
     */
    public function getEntitlementPack()
    {
        return $this->entitlementPack;
    }
}
