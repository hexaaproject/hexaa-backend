<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AttributeSpec
 *
 * @ORM\Table(name="attribute_spec")
 * @ORM\Entity
 */
class AttributeSpec
{
    /**
     * @var string
     *
     * @ORM\Column(name="oid", type="string", length=255, nullable=false)
     */
    private $oid;
    
    /**
     * @var string
     *
     * @ORM\Column(name="friendly_name", type="string", length=255, nullable=false)
     */
    private $friendlyName;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var integer
     *
     * @ORM\Column(name="maintainer", type="bigint", nullable=true)
     * TODO
     */
    private $maintainer;

    /**
     * @var string
     *
     * @ORM\Column(name="syntax", type="string", length=255, nullable=false)
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
     * @param integer $maintainer
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
     * @return integer 
     */
    public function getMaintainer()
    {
        return $this->maintainer;
    }

    /**
     * Set datatype
     *
     * @param string $datatype
     * @return AttributeSpec
     */
    public function setDatatype($datatype)
    {
        $this->datatype = $datatype;

        return $this;
    }

    /**
     * Get datatype
     *
     * @return string 
     */
    public function getDatatype()
    {
        return $this->datatype;
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
    
}
