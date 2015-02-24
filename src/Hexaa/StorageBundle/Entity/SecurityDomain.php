<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Hexaa\ApiBundle\Validator\Constraints as HexaaAssert;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\VirtualProperty;
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
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="scoped_key", type="string", length=255)
     * @Groups({"minimal", "normal", "expanded"})
     * @HexaaAssert\ValidScopedKey
     */
    private $scopedKey;

    /**
     * @var array
     *
     * @ManyToMany(targetEntity="Organization", mappedBy="securityDomains", cascade={"all"})
     * @JoinTable(name="organization_security_domain")
     * @Groups({"expanded"})
     **/
    private $organizations;

    /**
     * @var array
     *
     * @ManyToMany(targetEntity="Hexaa\StorageBundle\Entity\Service", mappedBy="securityDomains")
     * @JoinTable(name="service_security_domain")
     * @Groups({"expanded"})
     **/
    private $services;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdAt", type="datetime")
     * @Groups({"normal", "expanded"})
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updatedAt", type="datetime")
     * @Groups({"normal", "expanded"})
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
     * @VirtualProperty
     * @SerializedName("service_ids")
     * @Type("array")
     * @Groups({"normal"})
     */
    public function getServiceIds() {
        $ids = array();
        foreach($this->services as $service){
            $ids[] = $service->getId();
        }
        return $ids;
    }

    /**
     * @VirtualProperty
     * @SerializedName("organization_ids")
     * @Type("array")
     * @Groups({"normal"})
     */
    public function getOrganizationIds() {
        $ids = array();
        foreach($this->organizations as $organization){
            $ids[] = $organization->getId();
        }
        return $ids;
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
     * Set scopedKey
     *
     * @param string $scopedKey
     * @return SecurityDomain
     */
    public function setScopedKey($scopedKey) {
        $this->scopedKey = $scopedKey;

        return $this;
    }

    /**
     * Get scopedKey
     *
     * @return string
     */
    public function getScopedKey() {
        return $this->scopedKey;
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
     * @param \Hexaa\StorageBundle\Entity\Organization $organization
     * @return SecurityDomain
     */
    public function addOrganization(Organization $organization) {
        $this->organizations->add($organization);
            $organization->addSecurityDomain($this);
        return $this;
    }

    /**
     * Remove organizations
     *
     * @param \Hexaa\StorageBundle\Entity\Organization $organization
     */
    public function removeOrganization(Organization $organization) {
        $this->organizations->removeElement($organization);
        $organization->removeSecurityDomain($this);
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
     * @param Service $service
     * @return SecurityDomain
     */
    public function addService(Service $service) {
        $this->services->add($service);
        $service->addSecurityDomain($this);

        return $this;
    }

    /**
     * Remove services
     *
     * @param Service $service
     */
    public function removeService(Service $service) {
        $this->services->removeElement($service);
        $service->removeSecurityDomain($this);
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

    public function __toString(){
        return $this->name;
    }
}
