<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ServiceAttributeSpec
 *
 * @ORM\Table(name="service_attribute_spec", indexes={@ORM\Index(name="attribute_spec_id_idx", columns={"attribute_spec_id"}), @ORM\Index(name="service_id_idx", columns={"service_id"})})
 * @ORM\Entity
 */
class ServiceAttributeSpec
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * 
     */
    private $id;

    /**
     * @var \Hexaa\StorageBundle\Entity\AttributeSpec
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\AttributeSpec")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="attribute_spec_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Exclude()
     */
    private $attributeSpec;

    /**
     * @var \Hexaa\StorageBundle\Entity\Service
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Service")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="service_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Exclude()
     */
    private $service;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_public", type="boolean", nullable=true)
     * 
     * @Assert\NotBlank()
     * 
     */
    private $isPublic;
    
    /**
     * @VirtualProperty
     * @SerializedName("service_id")
    */
    public function getServiceId()
    {
        return $this->service->getId();       
    }
    
    /**
     * @VirtualProperty
     * @SerializedName("attribute_spec_id")
    */
    public function getAttributeSpecId()
    {
        return $this->attributeSpec->getId();
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
     * Set attributeSpec
     *
     * @param \Hexaa\StorageBundle\Entity\AttributeSpec $attributeSpec
     * @return ServiceAttributeSpec
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

    /**
     * Set service
     *
     * @param \Hexaa\StorageBundle\Entity\Service $service
     * @return ServiceAttributeSpec
     */
    public function setService(\Hexaa\StorageBundle\Entity\Service $service = null)
    {
        $this->service = $service;

        return $this;
    }

    /**
     * Get service
     *
     * @return \Hexaa\StorageBundle\Entity\Service 
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Set isPublic
     *
     * @param boolean $isPublic
     * @return ServiceAttributeSpec
     */
    public function setIsPublic($isPublic)
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    /**
     * Get isPublic
     *
     * @return boolean 
     */
    public function getIsPublic()
    {
        return $this->isPublic;
    }
}
