<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;

/**
 * Tag
 *
 * @ORM\Table(
 *   name="tag",
 * )
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 */
class Tag {

    /**
     * Constructor
     *
     * @param string $name
     */
    public function __construct($name) {
        $this->organizations = new ArrayCollection();
        $this->services = new ArrayCollection();
        $this->name = $name;
    }

    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(name="name", type="string", length=255)
     * @Groups({"minimal", "normal", "extended"})
     */
    private $name;

    /**
     * @var array
     *
     * @ManyToMany(targetEntity="Hexaa\StorageBundle\Entity\Organization", mappedBy="tags", cascade={"all"})
     * @JoinTable(name="organization_tag")
     * @Exclude
     **/
    private $organizations;

    /**
     * @var array
     *
     * @ManyToMany(targetEntity="Hexaa\StorageBundle\Entity\Service", mappedBy="tags")
     * @JoinTable(name="service_tag")
     * @Exclude
     **/
    private $services;

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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return AttributeSpec
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
     * @return AttributeSpec
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
     * @return string
     */
    public function __toString() {
        return $this->name;
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
     * @return Tag
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
     * Add organizations
     *
     * @param \Hexaa\StorageBundle\Entity\Organization $organization
     * @return Tag
     */
    public function addOrganization(Organization $organization) {
        $this->organizations->add($organization);

        return $this;
    }

    /**
     * Remove organizations
     *
     * @param \Hexaa\StorageBundle\Entity\Organization $organization
     */
    public function removeOrganization(Organization $organization) {
        $this->organizations->removeElement($organization);
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
     * @return Tag
     */
    public function addService(Service $service) {
        $this->services->add($service);
        $service->addTag($this);

        return $this;
    }

    /**
     * Remove services
     *
     * @param Service $service
     */
    public function removeService(Service $service) {
        $this->services->removeElement($service);
        $service->removeTag($this);
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
