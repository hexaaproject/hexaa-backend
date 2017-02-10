<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Hexaa\StorageBundle\Entity\AttributeValueOrganization;
use Hexaa\StorageBundle\Entity\AttributeValuePrincipal;
use Hexaa\StorageBundle\Entity\ServiceAttributeSpec;
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
class AttributeSpec
{

    public function __construct()
    {
        $this->serviceAttributeSpecs = new ArrayCollection();
        $this->attributeValueOrganizations = new ArrayCollection();
        $this->attributeValuePrincipals = new ArrayCollection();
    }


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
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Hexaa\StorageBundle\Entity\ServiceAttributeSpec", mappedBy="attributeSpec", cascade={"persist"})
     * @Assert\Valid()
     *
     * @Groups({"expanded"})
     */
    private $serviceAttributeSpecs;
    /**
     * @ORM\OneToMany(targetEntity="Hexaa\StorageBundle\Entity\AttributeValuePrincipal", mappedBy="attributeSpec")
     * @Assert\Valid()
     * @Groups({"expanded"})
     */
    private $attributeValuePrincipals;
    /**
     * @ORM\OneToMany(targetEntity="Hexaa\StorageBundle\Entity\AttributeValueOrganization", mappedBy="attributeSpec")
     * @Assert\Valid()
     * @Groups({"expanded"})
     */
    private $attributeValueOrganizations;

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
    public function updatedTimestamps()
    {
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
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return AttributeSpec
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get uri
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Set uri
     *
     * @param string $uri
     * @return AttributeSpec
     */
    public function setUri($uri)
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return AttributeSpec
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return AttributeSpec
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get maintainer
     *
     * @return string
     */
    public function getMaintainer()
    {
        return $this->maintainer;
    }

    /**
     * Set maintainer
     *
     * @param string $maintainer
     * @return AttributeSpec
     */
    public function setMaintainer($maintainer)
    {
        $this->maintainer = $maintainer;

        return $this;
    }

    /**
     * Get datatype
     *
     * @return string
     */
    public function getSyntax()
    {
        return $this->syntax;
    }

    /**
     * Set datatype
     *
     * @param string $syntax
     * @return AttributeSpec
     */
    public function setSyntax($syntax)
    {
        $this->syntax = $syntax;

        return $this;
    }

    /**
     * Get isMultivalue
     *
     * @return boolean
     */
    public function getIsMultivalue()
    {
        return $this->isMultivalue;
    }

    /**
     * Set isMultivalue
     *
     * @param boolean $isMultivalue
     * @return AttributeSpec
     */
    public function setIsMultivalue($isMultivalue)
    {
        $this->isMultivalue = $isMultivalue;

        return $this;
    }

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
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return AttributeSpec
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * Add serviceAttributeSpecs
     *
     * @param \Hexaa\StorageBundle\Entity\ServiceAttributeSpec $serviceAttributeSpecs
     * @return AttributeSpec
     */
    public function addServiceAttributeSpec(ServiceAttributeSpec $serviceAttributeSpecs)
    {
        $this->serviceAttributeSpecs[] = $serviceAttributeSpecs;

        return $this;
    }

    /**
     * Remove serviceAttributeSpecs
     *
     * @param \Hexaa\StorageBundle\Entity\ServiceAttributeSpec $serviceAttributeSpecs
     */
    public function removeServiceAttributeSpec(ServiceAttributeSpec $serviceAttributeSpecs)
    {
        $this->serviceAttributeSpecs->removeElement($serviceAttributeSpecs);
    }

    /**
     * Get serviceAttributeSpecs
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getServiceAttributeSpecs()
    {
        return $this->serviceAttributeSpecs;
    }

    /**
     * Add attributeValuePrincipals
     *
     * @param \Hexaa\StorageBundle\Entity\AttributeValuePrincipal $attributeValuePrincipals
     * @return AttributeSpec
     */
    public function addAttributeValuePrincipal(AttributeValuePrincipal $attributeValuePrincipals)
    {
        $this->attributeValuePrincipals[] = $attributeValuePrincipals;

        return $this;
    }

    /**
     * Remove attributeValuePrincipals
     *
     * @param AttributeValuePrincipal $attributeValuePrincipals
     */
    public function removeAttributeValuePrincipal(AttributeValuePrincipal $attributeValuePrincipals)
    {
        $this->attributeValuePrincipals->removeElement($attributeValuePrincipals);
    }

    /**
     * Get attributeValuePrincipals
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAttributeValuePrincipals()
    {
        return $this->attributeValuePrincipals;
    }

    /**
     * Add attributeValueOrganizations
     *
     * @param \Hexaa\StorageBundle\Entity\AttributeValueOrganization $attributeValueOrganizations
     * @return AttributeSpec
     */
    public function addAttributeValueOrganization(AttributeValueOrganization $attributeValueOrganizations)
    {
        $this->attributeValueOrganizations[] = $attributeValueOrganizations;

        return $this;
    }

    /**
     * Remove attributeValueOrganizations
     *
     * @param \Hexaa\StorageBundle\Entity\AttributeValueOrganization $attributeValueOrganizations
     */
    public function removeAttributeValueOrganization(AttributeValueOrganization $attributeValueOrganizations)
    {
        $this->attributeValueOrganizations->removeElement($attributeValueOrganizations);
    }

    /**
     * Get attributeValueOrganizations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAttributeValueOrganizations()
    {
        return $this->attributeValueOrganizations;
    }
}
