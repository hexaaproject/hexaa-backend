<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Hexaa\ApiBundle\Validator\Constraints as HexaaAssert;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * SecurityDomain
 *
 * @ORM\Table(
 *   name="security_domain",
 *   uniqueConstraints={
 *     @ORM\UniqueConstraint(name="name", columns={"name"})
 *   }
 * )
 * @ORM\Entity()
 * @UniqueEntity("name")
 * @ORM\HasLifecycleCallbacks
 */
class SecurityDomain {
    /**
     * Constructor
     */
    public function __construct() {
        $this->organizations = new ArrayCollection();
        $this->services = new ArrayCollection();
    }

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"minimal", "normal", "extended"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Groups({"minimal", "normal", "extended"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     * @Groups({"minimal", "normal", "extended"})
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="scopedKeyName", type="string", length=255)
     * @Groups({"minimal", "normal", "extended"})
     * @HexaaAssert\ValidScopedKey()
     */
    private $scopedKeyName;

    /**
     * @var array
     *
     * @ManyToMany(targetEntity="Hexaa\StorageBundle\Entity\Organization", mappedBy="securityDomains")
     * @JoinTable(name="organization_security_domain")
     * @Exclude
     **/
    private $organizations;

    /**
     * @var array
     *
     * @ManyToMany(targetEntity="Hexaa\StorageBundle\Entity\Service", mappedBy="securityDomains")
     * @JoinTable(name="service_security_domain")
     * @Exclude
     **/
    private $services;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdAt", type="datetime")
     * @Groups({"normal", "extended"})
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updatedAt", type="datetime")
     * @Groups({"normal", "extended"})
     */
    private $updatedAt;

    /**
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps() {
        $now = new \DateTime('now');
        $this->setUpdatedAt($now);

        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt($now);
        }
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
     * Set name
     *
     * @param string $name
     * @return SecurityDomain
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
     * @return SecurityDomain
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
     * Set scopedKeyName
     *
     * @param string $scopedKeyName
     * @return SecurityDomain
     */
    public function setScopedKeyName($scopedKeyName) {
        $this->scopedKeyName = $scopedKeyName;

        return $this;
    }

    /**
     * Get scopedKeyName
     *
     * @return string
     */
    public function getScopedKeyName() {
        return $this->scopedKeyName;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return SecurityDomain
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
     * @return SecurityDomain
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
     * Add organizations
     *
     * @param \Hexaa\StorageBundle\Entity\Organization $organizations
     * @return Tag
     */
    public function addOrganization(Organization $organizations) {
        $this->organizations[] = $organizations;

        return $this;
    }

    /**
     * Remove organizations
     *
     * @param \Hexaa\StorageBundle\Entity\Organization $organizations
     */
    public function removeOrganization(Organization $organizations) {
        $this->organizations->removeElement($organizations);
    }

    /**
     * Get organizations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrganizations() {
        return $this->organizations;
    }

    /**
     * Has Organization
     *
     * @param Organization $organization
     * @return bool
     */
    public function hasOrganization(Organization $organization) {
        return $this->organizations->contains($organization);
    }

    /**
     * Add services
     *
     * @param \Hexaa\StorageBundle\Entity\Service $services
     * @return Tag
     */
    public function addService(Service $services) {
        $this->services[] = $services;

        return $this;
    }

    /**
     * Remove services
     *
     * @param \Hexaa\StorageBundle\Entity\Service $services
     */
    public function removeService(Service $services) {
        $this->services->removeElement($services);
    }

    /**
     * Get services
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getServices() {
        return $this->services;
    }

    /**
     * Has Service
     *
     * @param Service $service
     * @return boolean
     */
    public function hasService(Service $service) {
        return $this->services->contains($service);
    }
}
