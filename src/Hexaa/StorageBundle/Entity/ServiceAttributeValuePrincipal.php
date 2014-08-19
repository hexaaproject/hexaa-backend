<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ServiceAttributeValuePrincipal
 *
 * @ORM\Table(name="service_attribute_value_principal2", indexes={@ORM\Index(name="attribute_value_principal_id_idx", columns={"attribute_value_principal_id"}), @ORM\Index(name="service_id_idx", columns={"service_id"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ServiceAttributeValuePrincipal
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Hexaa\StorageBundle\Entity\AttributeValuePrincipal
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\AttributeValuePrincipal")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="attribute_value_principal_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * 
     * @Exclude
     */
    private $attributeValuePrincipal;

    /**
     * @var \Hexaa\StorageBundle\Entity\Service
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Service")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="service_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * 
     * @Exclude
     */
    private $service;
      
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_allowed", type="boolean", nullable=true)
     * 
     * @Assert\NotBlank()
     */
    private $isAllowed;

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
     * @VirtualProperty
     * @SerializedName("service_id")
    */
    public function getServiceId()
    {
        return $this->service->getId();       
    }
    
    /**
     * @VirtualProperty
     * @SerializedName("attributeValuePrincipalId")
    */
    public function getAttributeValuePrincipalId()
    {
        return $this->attributeValuePrincipal->getId();
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
     * Set attributeValuePrincipal
     *
     * @param \Hexaa\StorageBundle\Entity\AttributeValuePrincipal $attributeValuePrincipal
     * @return ServiceAttributeValuePrincipal
     */
    public function setAttributeValuePrincipal(\Hexaa\StorageBundle\Entity\AttributeValuePrincipal $attributeValuePrincipal = null)
    {
        $this->attributeValuePrincipal = $attributeValuePrincipal;

        return $this;
    }

    /**
     * Get attributeValuePrincipal
     *
     * @return \Hexaa\StorageBundle\Entity\AttributeValuePrincipal 
     */
    public function getAttributeValuePrincipal()
    {
        return $this->attributeValuePrincipal;
    }

    /**
     * Set service
     *
     * @param \Hexaa\StorageBundle\Entity\Service $service
     * @return ServiceAttributeValuePrincipal
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
     * Set isAllowed
     *
     * @param boolean $isAllowed
     * @return ServiceAttributeValuePrincipal
     */
    public function setIsAllowed($isAllowed)
    {
        $this->isAllowed = $isAllowed;

        return $this;
    }

    /**
     * Get isAllowed
     *
     * @return boolean 
     */
    public function getIsAllowed()
    {
        return $this->isAllowed;
    }


    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return ServiceAttributeValuePrincipal
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
     * @return ServiceAttributeValuePrincipal
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
}
