<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Validator\Constraints as Assert;
use Hexaa\ApiBundle\Validator\Constraints as HexaaAssert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Service
 *
 * @ORM\Table(name="service", uniqueConstraints={@ORM\UniqueConstraint(name="name", columns={"name"})})
 * @ORM\Entity(repositoryClass="Hexaa\StorageBundle\Entity\ServiceRepository")
 * @UniqueEntity("name")
 * @ORM\HasLifecycleCallbacks
 */
class Service {

    /**
     * @ORM\ManyToMany(targetEntity="Principal")
     * @Exclude
     */
    private $managers;

    public function __construct() {
        $this->managers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->attributeSpecs = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = "3",
     *      max = "255",
     *      minMessage = "Minimum name length: 3 characters",
     *      maxMessage = "Maximum name length: 255 characters"
     * )
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="ServiceAttributeSpec", mappedBy="service", cascade={"persist"}, orphanRemoval=true)
     * @Assert\Valid(traverse=true)
     * @Exclude
     */
    private $attributeSpecs;

    /**
     * @var string
     *
     * @ORM\Column(name="entityid", type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     * @HexaaAssert\ValidEntityid()
     */
    private $entityid;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="min_loa", type="bigint", nullable=true)
     */
    private $minLoa = 0;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
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
     * Set name
     *
     * @param string $name
     * @return Service
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
     * Set minLoa
     *
     * @param integer $minLoa
     * @return Service
     */
    public function setMinLoa($minLoa) {
        $this->minLoa = $minLoa;

        return $this;
    }

    /**
     * Get minLoa
     *
     * @return integer 
     */
    public function getMinLoa() {
        return $this->minLoa;
    }

    /**
     * Set entityid
     *
     * @param string $entityid
     * @return Service
     */
    public function setEntityid($entityid) {
        $this->entityid = $entityid;

        return $this;
    }

    /**
     * Get entityid
     *
     * @return string 
     */
    public function getEntityid() {
        return $this->entityid;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return Service
     */
    public function setUrl($url) {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Service
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Service
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
     * @return Service
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
     * Has manager
     *
     * @param \Hexaa\StorageBundle\Entity\Principal $manager
     *
     * @return boolean
     */
    public function hasManager(\Hexaa\StorageBundle\Entity\Principal $manager) {
        return $this->managers->contains($manager);
    }

    /**
     * Add managers
     *
     * @param \Hexaa\StorageBundle\Entity\Principal $managers
     * @return Service
     */
    public function addManager(\Hexaa\StorageBundle\Entity\Principal $managers) {
        $this->managers[] = $managers;

        return $this;
    }

    /**
     * Remove managers
     *
     * @param \Hexaa\StorageBundle\Entity\Principal $managers
     */
    public function removeManager(\Hexaa\StorageBundle\Entity\Principal $managers) {
        $this->managers->removeElement($managers);
    }

    /**
     * Get managers
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getManagers() {
        return $this->managers;
    }

    /**
     * Add AttributeSpecs
     *
     * @param \Hexaa\StorageBundle\Entity\ServiceAttributeSpec $attributeSpecs
     * @return Service
     */
    public function addAttributeSpec(\Hexaa\StorageBundle\Entity\ServiceAttributeSpec $attributeSpecs) {
        $this->attributeSpecs[] = $attributeSpecs;

        if ($attributeSpecs->getService() !== $this) {
            $attributeSpecs->setService($this);
        }

        return $this;
    }

    /**
     * Remove AttributeSpecs
     *
     * @param \Hexaa\StorageBundle\Entity\ServiceAttributeSpec $attributeSpecs
     */
    public function removeAttributeSpec(\Hexaa\StorageBundle\Entity\ServiceAttributeSpec $attributeSpecs) {

        $attributeSpecs->setService(null);
        $this->attributeSpecs->removeElement($attributeSpecs);
    }

    /**
     * Get attributeSpecs
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getAttributeSpecs() {
        return $this->attributeSpecs;
    }

    /**
     * Has attributeSpec
     *
     * @param \Hexaa\StorageBundle\Entity\ServiceAttributeSpec $attributeSpec
     *
     * @return boolean
     */
    public function hasAttributeSpec(\Hexaa\StorageBundle\Entity\ServiceAttributeSpec $attributeSpec) {
        return $this->attributeSpecs->contains($attributeSpec);
    }

}
