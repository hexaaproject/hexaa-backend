<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\VirtualProperty;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use Ramsey\Uuid\Uuid;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * LinkerToken
 *
 * @author solazs@sztaki.hu
 *
 * @ORM\Table(
 *   name="linker_token",
 *   indexes={
 *     @ORM\Index(name="token_idx", columns={"token"})
 *   },
 *   uniqueConstraints={
 *     @ORM\UniqueConstraint(name="token", columns={"token"})
 *   }
 * )
 * @ORM\Entity
 * @UniqueEntity("token")
 * @ORM\HasLifecycleCallbacks
 */
class LinkerToken {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Exclude
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=255)
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $token;
    /**
     * @var EntitlementPack
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\EntitlementPack")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="entitlement_pack_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Groups({"expanded"})
     *
     */
    private $entitlementPack;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expiresAt", type="datetime")
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $expiresAt;
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

    public function __construct(EntitlementPack $ep) {
        try {
            $uuid = Uuid::uuid4();
        } catch (UnsatisfiedDependencyException $e) {
            // Some dependency was not met. Either the method cannot be called on a
            // 32-bit system, or it can, but it relies on Moontoast\Math to be present.
            $uuid = uniqid();
        }
        $date = new \DateTime('now');
        date_timezone_set($date, new \DateTimeZone("UTC"));
        $date->modify('+7 days');
        $this->token = $uuid;
        $this->expiresAt = $date;
        $this->entitlementPack = $ep;
    }

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
     * @return EntitlementPack
     */
    public function setCreatedAt($createdAt) {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("entitlement_pack_id")
     * @Type("integer")
     * @Groups({"minimal", "normal"})
     */
    public function getOrganizationId() {
        return $this->entitlementPack->getId();
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
     * @return EntitlementPack
     */
    public function setUpdatedAt($updatedAt) {
        $this->updatedAt = $updatedAt;

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
     * Get token
     *
     * @return string
     */
    public function getToken() {
        return $this->token;
    }

    /**
     * Set token
     *
     * @param string $token
     * @return LinkerToken
     */
    public function setToken($token) {
        $this->token = $token;

        return $this;
    }

    /**
     * Get expiresAt
     *
     * @return \DateTime
     */
    public function getExpiresAt() {
        return $this->expiresAt;
    }

    /**
     * Set expiresAt
     *
     * @param \DateTime $expiresAt
     * @return LinkerToken
     */
    public function setExpiresAt($expiresAt) {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    /**
     * @return EntitlementPack
     */
    public function getEntitlementPack() {
        return $this->entitlementPack;
    }

    /**
     * @param EntitlementPack $entitlementPack
     */
    public function setEntitlementPack($entitlementPack) {
        $this->entitlementPack = $entitlementPack;
    }
}
