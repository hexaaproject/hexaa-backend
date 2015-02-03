<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\Type;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

/**
 * EntitlementPack
 *
 * @ORM\Table(
 *   name="entitlement_pack",
 *   indexes={
 *     @ORM\Index(name="service_id_idx", columns={"service_id"})
 *   },
 *   uniqueConstraints={
 *     @ORM\UniqueConstraint(name="name_service", columns={"name", "service_id"})
 *   }
 * )
 * @ORM\Entity(repositoryClass="Hexaa\StorageBundle\Entity\EntitlementPackRepository")
 * @UniqueEntity({"service","name"})
 * @ORM\HasLifecycleCallbacks
 *
 */
class EntitlementPack {

    public function __construct() {
        $this->entitlements = new ArrayCollection();
        $this->tokens = new ArrayCollection();
    }

    /**
     * @var Entitlement
     * @ORM\ManyToMany(targetEntity="Entitlement")
     * @ORM\JoinTable(name="entitlement_pack_entitlement")
     * @Groups({"expanded"})
     */
    private $entitlements;

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
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255, columnDefinition="ENUM('private', 'public')", nullable=false)
     * 
     * @Assert\NotBlank()
     * @Assert\Choice(choices={"private","public"})
     * @Groups({"normal", "expanded"})
     */
    private $type;

    /**
     * @var LinkerToken
     * @ORM\ManyToMany(targetEntity="Hexaa\StorageBundle\Entity\LinkerToken",cascade={"persist","remove"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="token_id", referencedColumnName="id")
     * })
     * @Exclude
     */
    private $tokens;

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
     * @var Service
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Service")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="service_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Groups({"expanded"})
     */
    private $service;

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
     * @SerializedName("entitlement_ids")
     * @Type("array<integer>")
     * @Groups({"normal"})
     */
    public function getEntitlementIds() {
        $ids = array();
        foreach ($this->entitlements as $e) {
            $ids[] = $e->getId();
        }
        return $ids;
    }

    /**
     * @VirtualProperty
     * @SerializedName("scoped_name")
     * @Type("string")
     * @Groups({"minimal", "normal", "expanded"})
     */
    public function getScopedName() {
        return $this->service->getName() . "::" . $this->name;
    }

    /**
     * @VirtualProperty
     * @SerializedName("service_id")
     * @Type("integer")
     * @Groups({"minimal", "normal"})
     */
    public function getServiceId() {
        return $this->service->getId();
    }

    /**
     * Set name
     *
     * @param string $name
     * @return EntitlementPack
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
     * Set description
     *
     * @param string $description
     * @return EntitlementPack
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
     * Set type
     *
     * @param string $type
     * @return EntitlementPack
     */
    public function setType($type) {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType() {
        return $this->type;
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
     * Set service
     *
     * @param Service $service
     * @return EntitlementPack
     */
    public function setService(Service $service = null) {
        $this->service = $service;

        return $this;
    }

    /**
     * Get service
     *
     * @return Service
     */
    public function getService() {
        return $this->service;
    }

    /**
     * Add entitlements
     *
     * @param Entitlement $entitlements
     * @return EntitlementPack
     */
    public function addEntitlement(Entitlement $entitlements) {
        $this->entitlements[] = $entitlements;

        return $this;
    }

    /**
     * Remove entitlements
     *
     * @param Entitlement $entitlements
     */
    public function removeEntitlement(Entitlement $entitlements) {
        $this->entitlements->removeElement($entitlements);
    }

    /**
     * Get entitlements
     *
     * @return ArrayCollection
     */
    public function getEntitlements() {
        return $this->entitlements;
    }

    /**
     * Has entitlement
     *
     * @param Entitlement $entitlement
     *
     * @return boolean
     */
    public function hasEntitlement(Entitlement $entitlement) {
        return $this->entitlements->contains($entitlement);
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return EntitlementPack
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
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return EntitlementPack
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
     * Add tokens
     *
     * @param LinkerToken $tokens
     * @return EntitlementPack
     */
    public function addToken(LinkerToken $tokens)
    {
        $this->tokens[] = $tokens;

        return $this;
    }

    /**
     * Remove tokens
     *
     * @param LinkerToken $tokens
     */
    public function removeToken(LinkerToken $tokens)
    {
        $this->tokens->removeElement($tokens);
    }

    /**
     * Get tokens
     *
     * @return ArrayCollection
     */
    public function getTokens()
    {
        return $this->tokens;
    }
}
