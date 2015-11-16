<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\VirtualProperty;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * OrganizationEntitlementPack
 *
 * @ORM\Table(
 *   name="organization_entitlement_pack",
 *   indexes={
 *     @ORM\Index(name="organization_id_idx", columns={"organization_id"}),
 *     @ORM\Index(name="entitlement_pack_id_idx", columns={"entitlement_pack_id"})
 *   },
 *   uniqueConstraints={
 *     @ORM\UniqueConstraint(name="organization_entitlement_pack", columns={"organization_id", "entitlement_pack_id"})
 *   }
 * )
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @UniqueEntity({"organization", "entitlementPack"})
 *
 */
class OrganizationEntitlementPack {
    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255, columnDefinition="ENUM('accepted', 'pending')", nullable=false)
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $status = "pending";

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="accept_at", type="datetime", nullable=true)
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $acceptAt;

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
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Organization", inversedBy="entitlementPacks")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     *
     * @Groups({"expanded"})
     */
    private $organization;

    /**
     * @var EntitlementPack
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\EntitlementPack")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="entitlement_pack_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     *
     * @Groups({"expanded"})
     */
    private $entitlementPack;

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
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt() {
        return $this->createdAt;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return OrganizationEntitlementPack
     */
    public function setCreatedAt($createdAt) {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return OrganizationEntitlementPack
     */
    public function setStatus($status = "pending") {
        $this->status = $status;

        return $this;
    }

    /**
     * Get acceptAt
     *
     * @return \DateTime
     */
    public function getAcceptAt() {
        return $this->acceptAt;
    }

    /**
     * Set acceptAt
     *
     * @param \DateTime $acceptAt
     * @return OrganizationEntitlementPack
     */
    public function setAcceptAt($acceptAt) {
        $this->acceptAt = $acceptAt;

        return $this;
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
     * Get organization
     *
     * @return Organization
     */
    public function getOrganization() {
        return $this->organization;
    }

    /**
     * Set organization
     *
     * @param Organization $organization
     * @return OrganizationEntitlementPack
     */
    public function setOrganization(Organization $organization = null) {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get entitlementPack
     *
     * @return EntitlementPack
     */
    public function getEntitlementPack() {
        return $this->entitlementPack;
    }

    /**
     * Set entitlementPack
     *
     * @param EntitlementPack $entitlementPack
     * @return OrganizationEntitlementPack
     */
    public function setEntitlementPack(EntitlementPack $entitlementPack = null) {
        $this->entitlementPack = $entitlementPack;

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
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return OrganizationEntitlementPack
     */
    public function setUpdatedAt($updatedAt) {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function __toString() {
        return "OEPo" . $this->getOrganizationId() . "ep" . $this->getEntitlementPackId();
    }

    /**
     * @VirtualProperty
     * @SerializedName("organization_id")
     * @Type("integer")
     * @Groups({"minimal", "normal"})
     */
    public function getOrganizationId() {
        return $this->organization->getId();
    }

    /**
     * @VirtualProperty
     * @SerializedName("entitlement_pack_id")
     * @Type("integer")
     * @Groups({"minimal", "normal"})
     */
    public function getEntitlementPackId() {
        return $this->entitlementPack->getId();
    }
}
