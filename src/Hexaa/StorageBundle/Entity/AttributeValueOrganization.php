<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AttributeValueOrganization
 *
 * @ORM\Table(name="attribute_value_organization", indexes={@ORM\Index(name="organization_id_idx", columns={"organization_id"}), @ORM\Index(name="attribute_spec_id_idx", columns={"attribute_spec_id"})})
 * @ORM\Entity
 */
class AttributeValueOrganization
{
    /**
     * @ORM\ManyToMany(targetEntity="Service")
     * 
     * @Exclude
     */
    private $services;

 
    public function __construct() {
        $this->services = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     *
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=255, nullable=true)
     * 
     * @Assert\NotBlank()
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
     * @var \Hexaa\StorageBundle\Entity\Organization
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Organization")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * 
     * @Exclude
     */
    private $organization;

    /**
     * @var \Hexaa\StorageBundle\Entity\AttributeSpec
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\AttributeSpec")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="attribute_spec_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * 
     * @Exclude
     */
    private $attributeSpec;


    /**
     * Set value
     *
     * @param string $value
     * @return AttributeValueOrganization
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
     * Set organization
     *
     * @param \Hexaa\StorageBundle\Entity\Organization $organization
     * @return AttributeValueOrganization
     */
    public function setOrganization(\Hexaa\StorageBundle\Entity\Organization $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization
     *
     * @return \Hexaa\StorageBundle\Entity\Organization 
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Set attributeSpec
     *
     * @param \Hexaa\StorageBundle\Entity\AttributeSpec $attributeSpec
     * @return AttributeValueOrganization
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
     * @return AttributeValueOrganization
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
     * Add services
     *
     * @param \Hexaa\StorageBundle\Entity\Service $services
     * @return AttributeValueOrganization
     */
    public function addService(\Hexaa\StorageBundle\Entity\Service $services)
    {
        $this->services[] = $services;

        return $this;
    }

    /**
     * Remove services
     *
     * @param \Hexaa\StorageBundle\Entity\Service $services
     */
    public function removeService(\Hexaa\StorageBundle\Entity\Service $services)
    {
        $this->services->removeElement($services);
    }

    /**
     * Get services
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getServices()
    {
        return $this->services;
    }
    
    /**
     * Has service
     *
     * @param \Hexaa\StorageBundle\Entity\Service $service
     *
     * @return boolean
     */
    public function hasService(\Hexaa\StorageBundle\Entity\Service $service)
    {
        return $this->services->contains($service);
    }
}
