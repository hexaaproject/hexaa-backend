<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * AttributeSpec
 *
 * @ORM\Table(name="attribute_spec")
 * @ORM\Entity(repositoryClass="Hexaa\StorageBundle\Entity\AttributeSpecRepository")
 * @UniqueEntity("oid")
 * @UniqueEntity("friendlyName")
 * @ORM\HasLifecycleCallbacks
 */
class AttributeSpec
{
    /**
     * @var string
     *
     * @ORM\Column(name="oid", type="string", length=255, nullable=false)
     * 
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = "2",
     *      max = "255"
     * )
     */
    private $oid;
    
    /**
     * @var string
     *
     * @ORM\Column(name="friendly_name", type="string", length=255, nullable=false)
     * 
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = "2",
     *      max = "255"
     * )
     */
    private $friendlyName;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="maintainer", type="string", length=255, columnDefinition="ENUM('user', 'manager', 'admin')", nullable=false)
     * 
     * @Assert\Choice(choices={"user", "manager", "admin"})
     * @Assert\NotBlank()
     */
    private $maintainer;

    /**
     * @var string
     *
     * @ORM\Column(name="syntax", type="string", length=255, nullable=false)
     * 
     * @Assert\NotBlank()
     */
    private $syntax;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_multivalue", type="boolean", nullable=true)
     */
    private $isMultivalue;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

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
     * Set oid
     *
     * @param string $oid
     * @return AttributeSpec
     */
    public function setOid($oid)
    {
        $this->oid = $oid;

        return $this;
    }

    /**
     * Get oid
     *
     * @return string 
     */
    public function getOid()
    {
        return $this->oid;
    }

    /**
     * Set friendlyName
     *
     * @param string $friendlyName
     * @return AttributeSpec
     */
    public function setFriendlyName($friendlyName)
    {
        $this->friendlyName = $friendlyName;

        return $this;
    }

    /**
     * Get friendlyName
     *
     * @return string 
     */
    public function getFriendlyName()
    {
        return $this->friendlyName;
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
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
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
     * Get maintainer
     *
     * @return string
     */
    public function getMaintainer()
    {
        return $this->maintainer;
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
     * Get datatype
     *
     * @return string 
     */
    public function getSyntax()
    {
        return $this->syntax;
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
     * Get isMultivalue
     *
     * @return boolean 
     */
    public function getIsMultivalue()
    {
        return $this->isMultivalue;
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
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
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
     * Get updatedAt
     *
     * @return \DateTime 
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function __toString(){
        return $this->friendlyName;
    }
}
