<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AttributeValuePrincipal
 *
 * @ORM\Table(name="attribute_value_principal", indexes={@ORM\Index(name="principal_id_idx", columns={"principal_id"}), @ORM\Index(name="attribute_spec_id_idx", columns={"attribute_spec_id"})})
 * @ORM\Entity
 */
class AttributeValuePrincipal
{
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_default", type="boolean", nullable=true)
     */
    private $isDefault;
    
    /**
     * @var string
     *
     * @ORM\Column(name="consent_status", type="string", columnDefinition="ENUM('accepted', 'not_accepted')", nullable=false)
     */
    private $consentStatus;

    /**
     *
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=255, nullable=true)
     */
    private $value;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Hexaa\StorageBundle\Entity\Principal
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Principal")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="principal_id", referencedColumnName="id")
     * })
     */
    private $principal;

    /**
     * @var \Hexaa\StorageBundle\Entity\AttributeSpec
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\AttributeSpec")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="attribute_spec_id", referencedColumnName="id")
     * })
     */
    private $attributeSpec;





    /**
     * Set isDefault
     *
     * @param boolean $isDefault
     * @return AttributeValuePrincipal
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    /**
     * Get isDefault
     *
     * @return boolean 
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    /**
     * Set consentStatus
     *
     * @param string $consentStatus
     * @return AttributeValuePrincipal
     */
    public function setConsentStatus($consentStatus)
    {
        $this->consentStatus = $consentStatus;

        return $this;
    }

    /**
     * Get consentStatus
     *
     * @return string 
     */
    public function getConsentStatus()
    {
        return $this->consentStatus;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return AttributeValuePrincipal
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue()
    {
        return $this->value;
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
     * Set principal
     *
     * @param \Hexaa\StorageBundle\Entity\Principal $principal
     * @return AttributeValuePrincipal
     */
    public function setPrincipal(\Hexaa\StorageBundle\Entity\Principal $principal = null)
    {
        $this->principal = $principal;

        return $this;
    }

    /**
     * Get principal
     *
     * @return \Hexaa\StorageBundle\Entity\Principal 
     */
    public function getPrincipal()
    {
        return $this->principal;
    }

    /**
     * Set attributeSpec
     *
     * @param \Hexaa\StorageBundle\Entity\AttributeSpec $attributeSpec
     * @return AttributeValuePrincipal
     */
    public function setAttributeSpec(\Hexaa\StorageBundle\Entity\AttributeSpec $attributeSpec = null)
    {
        $this->attributeSpec = $attributeSpec;

        return $this;
    }

    /**
     * Get attributeSpec
     *
     * @return \Hexaa\StorageBundle\Entity\AttributeSpec 
     */
    public function getAttributeSpec()
    {
        return $this->attributeSpec;
    }

}
