<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hexaa\ApiBundle\Validator\Constraints as HexaaAssert;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\VirtualProperty;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entitlement
 *
 * @ORM\Table(
 *   name="entitlement",
 *   indexes={
 *     @ORM\Index(name="service_id_idx", columns={"service_id"})
 *   },
 *   uniqueConstraints={
 *     @ORM\UniqueConstraint(name="uri", columns={"uri"}),
 *     @ORM\UniqueConstraint(name="name_service", columns={"name", "service_id"})
 *   }
 * )
 * @ORM\Entity(repositoryClass="Hexaa\StorageBundle\Entity\EntitlementRepository")
 * @UniqueEntity({"name", "service"})
 * @UniqueEntity("uri")
 * @ORM\HasLifecycleCallbacks
 * @HexaaAssert\EntitlementURI()
 *
 */
class Entitlement {
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
     *
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     *
     *
     * @Groups({"normal", "expanded"})
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="uri", type="string", length=255, nullable=false)
     *
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = "3",
     *      max = "125"
     * )
     *
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $uri;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
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
     *
     * @Groups({"expanded"})
     */
    private $service;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     *
     * @Groups({"normal", "expanded"})
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     *
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
     * @return Entitlement
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
     * @return Entitlement
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
     * Set uri
     *
     * @param string $uri
     * @return Entitlement
     */
    public function setUri($uri) {
        $this->uri = $uri;

        return $this;
    }

    /**
     * Get uri
     *
     * @return string
     */
    public function getUri() {
        return $this->uri;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Entitlement
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
     * @return Entitlement
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
     * @return Entitlement
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

    public function __toString() {
        return $this->getScopedName();
    }
}
