<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use JMS\Serializer\Annotation\Groups;
use Hexaa\ApiBundle\Validator\Constraints as HexaaAssert;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\VirtualProperty;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Link
 *
 * @ORM\Table(name="link",
 *   indexes={
 *     @ORM\Index(name="organization_id_idx", columns={"organization_id"}),
 *     @ORM\Index(name="service_id_idx", columns={"service_id"}),
 *   },
 *   uniqueConstraints={
 *     @ORM\UniqueConstraint(name="organization_service", columns={"organization_id", "service_id"})
 *   })
 * @ORM\Entity(repositoryClass="Hexaa\StorageBundle\Entity\LinkRepository")
 * @UniqueEntity({"organization", "service"})
 * @HexaaAssert\LinkServiceChecksOut
 * @HexaaAssert\LinkHasOrganizationOrService)
 * @ORM\HasLifecycleCallbacks
 */
class Link
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255, nullable=false)
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $status = 'pending';
    /**
     * @ORM\OneToMany(targetEntity="Hexaa\StorageBundle\Entity\LinkerToken", mappedBy="link", cascade={"persist"})
     * @Groups({"expanded"})
     */
    private $tokens;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Organization", inversedBy="links", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     *
     * @Groups({"expanded"})
     */
    private $organization;

    /**
     * @var Service
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Service", inversedBy="links", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="service_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     *
     * @Groups({"expanded"})
     */
    private $service;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Hexaa\StorageBundle\Entity\EntitlementPack", inversedBy="links", cascade={"persist"})
     * @JoinTable(name="link_entitlement_pack",
     *     joinColumns={@JoinColumn(name="link_id", referencedColumnName="id", onDelete="CASCADE")},
     *     inverseJoinColumns={@JoinColumn(name="entitlement_pack_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     *
     * @Groups({"expanded"})
     */
    private $entitlementPacks;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Hexaa\StorageBundle\Entity\Entitlement", inversedBy="links", cascade={"persist"})
     * @JoinTable(name="link_entitlement",
     *     joinColumns={@JoinColumn(name="link_id", referencedColumnName="id", onDelete="CASCADE")},
     *     inverseJoinColumns={@JoinColumn(name="entitlement_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     *
     * @Groups({"expanded"})
     */
    private $entitlements;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updatedAt", type="datetime")
     * @Groups({"normal", "expanded"})
     */
    private $updatedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdAt", type="datetime")
     * @Groups({"normal", "expanded"})
     */
    private $createdAt;

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
     * @VirtualProperty
     * @SerializedName("service_id")
     * @Type("integer")
     * @Groups({"minimal", "normal"})
     */
    public function getServiceId()
    {
        return $this->service == null ? null : $this->service->getId();
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
     * Set status
     *
     * @param string $status
     * @return Link
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
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return Link
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Link
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
     * Constructor
     */
    public function __construct()
    {
        $this->entitlementPacks = new ArrayCollection();
        $this->entitlements = new ArrayCollection();
        $this->tokens = new ArrayCollection();
    }

    /**
     * Set organization
     *
     * @param \Hexaa\StorageBundle\Entity\Organization $organization
     * @return Link
     */
    public function setOrganization(Organization $organization = null)
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
     * Add entitlementPacks
     *
     * @param EntitlementPack $entitlementPacks
     * @return Link
     */
    public function addEntitlementPack(EntitlementPack $entitlementPacks)
    {
        $this->entitlementPacks[] = $entitlementPacks;

        return $this;
    }

    /**
     * Remove entitlementPacks
     *
     * @param EntitlementPack $entitlementPacks
     */
    public function removeEntitlementPack(EntitlementPack $entitlementPacks)
    {
        $this->entitlementPacks->removeElement($entitlementPacks);
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
     * Add entitlements
     *
     * @param Entitlement $entitlements
     * @return Link
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
     * @return \Hexaa\StorageBundle\Entity\Service
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @param \Hexaa\StorageBundle\Entity\Service $service
     */
    public function setService($service)
    {
        $this->service = $service;
    }


    /**
     * Add tokens
     *
     * @param \Hexaa\StorageBundle\Entity\LinkerToken $tokens
     * @return Link
     */
    public function addToken(LinkerToken $tokens)
    {
        $this->tokens[] = $tokens;

        return $this;
    }

    /**
     * Remove tokens
     *
     * @param \Hexaa\StorageBundle\Entity\LinkerToken $tokens
     */
    public function removeToken(LinkerToken $tokens)
    {
        $this->tokens->removeElement($tokens);
    }

    /**
     * Get tokens
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTokens()
    {
        return $this->tokens;
    }

    public function hasEntitlement(Entitlement $entitlement, EntitlementPack $exceptEntitlementPack = null)
    {
        if ($this->entitlements->contains($entitlement)) {
            return true;
        } else {
            /** @var EntitlementPack $entitlementPack */
            foreach ($this->entitlementPacks as $entitlementPack) {
                if ($exceptEntitlementPack == null) {
                    if ($entitlementPack->hasEntitlement($entitlement)) {
                        return true;
                    }
                } else {
                    if ($entitlementPack->getId() !== $exceptEntitlementPack->getId()
                      && $entitlementPack->hasEntitlement($entitlement)
                    ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function hasEntitlementPack(EntitlementPack $entitlementPack)
    {
        return $this->entitlementPacks->contains($entitlementPack);
    }
}
