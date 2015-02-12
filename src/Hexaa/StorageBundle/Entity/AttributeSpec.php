<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AttributeSpec
 *
 * @ORM\Table(
 *   name="attribute_spec",
 *   uniqueConstraints={
 *     @ORM\UniqueConstraint(name="uri", columns={"uri"}),
 *     @ORM\UniqueConstraint(name="name", columns={"name"})
 *   }
 * )
 * @ORM\Entity(repositoryClass="Hexaa\StorageBundle\Entity\AttributeSpecRepository")
 * @UniqueEntity("uri")
 * @UniqueEntity("name")
 * @ORM\HasLifecycleCallbacks
 */
class AttributeSpec {


    /**
     * @var string
     *
     * @ORM\Column(name="uri", type="string", length=255, nullable=false)
     *
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = "2",
     *      max = "255"
     * )
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $uri;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     *
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = "2",
     *      max = "255"
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
     * @Groups({"normal", "expanded"})
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="maintainer", type="string", length=10, columnDefinition="ENUM('user', 'manager', 'admin')", nullable=false)
     *
     * @Assert\Choice(choices={"user", "manager", "admin"})
     * @Assert\NotBlank()
     *
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $maintainer;

    /**
     * @var string
     *
     * @ORM\Column(name="syntax", type="string", columnDefinition="ENUM('string', 'base64')", length=10, nullable=false)
     *
     * @Assert\Choice(choices={"string", "base64"})
     * @Assert\NotBlank()
     *
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $syntax;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_multivalue", type="boolean", nullable=true)
     *
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $isMultivalue;

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
     * Set uri
     *
     * @param string $uri
     * @return AttributeSpec
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
     * Set name
     *
     * @param string $name
     * @return AttributeSpec
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
     * @return AttributeSpec
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
     * Set maintainer
     *
     * @param string $maintainer
     * @return AttributeSpec
     */
    public function setMaintainer($maintainer) {
        $this->maintainer = $maintainer;

        return $this;
    }

    /**
     * Get maintainer
     *
     * @return string
     */
    public function getMaintainer() {
        return $this->maintainer;
    }

    /**
     * Set datatype
     *
     * @param string $syntax
     * @return AttributeSpec
     */
    public function setSyntax($syntax) {
        $this->syntax = $syntax;

        return $this;
    }

    /**
     * Get datatype
     *
     * @return string
     */
    public function getSyntax() {
        return $this->syntax;
    }

    /**
     * Set isMultivalue
     *
     * @param boolean $isMultivalue
     * @return AttributeSpec
     */
    public function setIsMultivalue($isMultivalue) {
        $this->isMultivalue = $isMultivalue;

        return $this;
    }

    /**
     * Get isMultivalue
     *
     * @return boolean
     */
    public function getIsMultivalue() {
        return $this->isMultivalue;
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
}
