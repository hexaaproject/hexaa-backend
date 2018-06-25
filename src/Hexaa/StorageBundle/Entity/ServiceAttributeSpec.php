<?php

/**
 * Copyright 2014-2018 MTA SZTAKI, ugyeletes@sztaki.hu
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hexaa\ApiBundle\Validator\Constraints as HexaaAssert;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\MaxDepth;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\VirtualProperty;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ServiceAttributeSpec
 *
 * @ORM\Table(
 *   name="service_attribute_spec",
 *   indexes={
 *     @ORM\Index(name="attribute_spec_id_idx", columns={"attribute_spec_id"}),
 *     @ORM\Index(name="service_id_idx", columns={"service_id"})
 *   },
 *   uniqueConstraints={
 *     @ORM\UniqueConstraint(name="service_attribute_spec", columns={"service_id", "attribute_spec_id"})
 *   }
 * )
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @UniqueEntity({"service", "attributeSpec"})
 *
 */
class ServiceAttributeSpec
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Exclude
     */
    private $id;

    /**
     * @var AttributeSpec
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\AttributeSpec", inversedBy="serviceAttributeSpecs")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="attribute_spec_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Groups({"expanded"})
     * @Assert\NotBlank()
     * @HexaaAssert\IsPublicAttributeSpecEnabled()
     * @MaxDepth(1)
     */
    private $attributeSpec;

    /**
     * @var Service
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Service", inversedBy="attributeSpecs")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="service_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Groups({"expanded"})
     * @Assert\NotBlank()
     * @MaxDepth(1)
     */
    private $service;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_public", type="boolean", nullable=true)
     * @Groups({"minimal", "normal", "expanded"})
     *
     *
     */
    private $isPublic;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     * @Groups({"normal", "expanded"})
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
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
     * @return ServiceAttributeSpec
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

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
     * Get attributeSpec
     *
     * @return AttributeSpec
     */
    public function getAttributeSpec()
    {
        return $this->attributeSpec;
    }

    /**
     * Set attributeSpec
     *
     * @param AttributeSpec $attributeSpec
     * @return ServiceAttributeSpec
     */
    public function setAttributeSpec(AttributeSpec $attributeSpec = null)
    {
        $this->attributeSpec = $attributeSpec;

        return $this;
    }

    /**
     * Get service
     *
     * @return Service
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Set service
     *
     * @param Service $service
     * @return ServiceAttributeSpec
     */
    public function setService(Service $service = null)
    {
        $this->service = $service;

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
     * @return ServiceAttributeSpec
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function __toString()
    {
        return "SASs".$this->getServiceId()."as".$this->getAttributeSpecId();
    }

    /**
     * @VirtualProperty
     * @SerializedName("service_id")
     * @Type("integer")
     * @Groups({"minimal", "normal"})
     */
    public function getServiceId()
    {
        return $this->service->getId();
    }

    /**
     * @VirtualProperty
     * @SerializedName("attribute_spec_id")
     * @Type("integer")
     * @Groups({"minimal", "normal"})
     */
    public function getAttributeSpecId()
    {
        return $this->attributeSpec->getId();
    }
}
