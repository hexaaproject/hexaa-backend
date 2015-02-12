<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Tag
 *
 * @ORM\Table(
 *   name="tag",
 *   indexes={
 *       @ORM\Index(name="name_idx", columns={"name"})
 *     },
 *   uniqueConstraints={
 *     @ORM\UniqueConstraint(name="name", columns={"name"})
 *   }
 * )
 * @ORM\Entity()
 * @UniqueEntity("name")
 * @ORM\HasLifecycleCallbacks
 */
class Tag {

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
     * @var array
     *
     * @ManyToMany(targetEntity="Hexaa\StorageBundle\Entity\Organization", mappedBy="tags")
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
