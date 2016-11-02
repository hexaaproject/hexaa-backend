<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use JMS\Serializer\Annotation\Groups;
use Hexaa\ApiBundle\Validator\Constraints as HexaaAssert;

/**
 * Link
 *
 * @ORM\Table(name="link")
 * @ORM\Entity(repositoryClass="Hexaa\StorageBundle\Entity\LinkRepository")
 * @HexaaAssert\LinkServiceChecksOut
 */
class Link
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255, nullable=false)
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
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Organization", inversedBy="links")
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
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Service", inversedBy="links")
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
     * @ORM\ManyToMany(targetEntity="Hexaa\StorageBundle\Entity\EntitlementPack", inversedBy="links")
     * @JoinTable(name="link_entitlement_pack",
     *     joinColumns={@JoinColumn(name="link_id", referencedColumnName="id")},
     *     inverseJoinColumns={@JoinColumn(name="entitlement_pack_id", referencedColumnName="id")}
     * )
     *
     * @Groups({"expanded"})
     */
    private $entitlementPacks;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Hexaa\StorageBundle\Entity\Entitlement", inversedBy="links")
     * @JoinTable(name="link_entitlement",
     *     joinColumns={@JoinColumn(name="link_id", referencedColumnName="id")},
     *     inverseJoinColumns={@JoinColumn(name="entitlement_id", referencedColumnName="id")}
     * )
     *
     * @Groups({"expanded"})
     */
    private $entitlements;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updatedAt", type="datetime")
     */
    private $updatedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdAt", type="datetime")
     */
    private $createdAt;


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
}
