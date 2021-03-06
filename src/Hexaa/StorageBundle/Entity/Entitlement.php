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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Hexaa\ApiBundle\Validator\Constraints as HexaaAssert;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\MaxDepth;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\VirtualProperty;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entitlement
 *
 * @ORM\Table(
 *   name="entitlement",
 *   indexes={
 *     @ORM\Index(name="service_id_idx", columns={"service_id"})
 *   },
 *   uniqueConstraints={
 *     @ORM\UniqueConstraint(name="uri", columns={"uri"}),
 *     @ORM\UniqueConstraint(name="name_service", columns={"name", "service_id"})
 *   }
 * )
 * @ORM\Entity(repositoryClass="Hexaa\StorageBundle\Entity\EntitlementRepository")
 * @UniqueEntity({"name", "service"})
 * @UniqueEntity("uri")
 * @ORM\HasLifecycleCallbacks
 * @HexaaAssert\EntitlementURI()
 *
 */
class Entitlement
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     *
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = "3",
     *      max = "125"
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
     *
     * @Groups({"normal", "expanded"})
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="uri", type="string", length=255, nullable=false)
     *
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = "3",
     *      max = "125"
     * )
     *
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $uri;

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
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Hexaa\StorageBundle\Entity\Link", mappedBy="entitlements")
     */
    private $links;

    /**
     * @var Service
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Service", inversedBy="entitlements")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="service_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     *
     * @Groups({"expanded"})
     * @MaxDepth(1)
     */
    private $service;

    /**
     * @ORM\ManyToMany(targetEntity="Hexaa\StorageBundle\Entity\Role", mappedBy="entitlements")
     * @Assert\Valid()
     */
    private $roles;

    /**
     * @var Entitlement
     * @ORM\ManyToMany(targetEntity="EntitlementPack", mappedBy="entitlements")
     * @ORM\JoinTable(name="entitlement_pack_entitlement")
     * @Groups({"expanded"})
     * @MaxDepth(1)
     */
    private $entitlementPacks;

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
     * Constructor
     */
    public function __construct()
    {
        $this->links = new ArrayCollection();
        $this->roles = new ArrayCollection();
        $this->entitlementPacks = new ArrayCollection();
    }

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
     * @return Entitlement
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("scoped_name")
     * @Type("string")
     * @Groups({"minimal", "normal", "expanded"})
     */
    public function getScopedName()
    {
        return $this->service->getName()."::".$this->name;
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
     * @return Entitlement
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
     * @return Entitlement
     */
    public function setDescription($description)
    {
        $this->description = $description;

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
     * @return Entitlement
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

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
     * @return Entitlement
     */
    public function setService(Service $service = null)
    {
        $this->service = $service;

        return $this;
    }

    public function __toString()
    {
        return $this->getUri();
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
     * @return Entitlement
     */
    public function setUri($uri)
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * Add links
     *
     * @param \Hexaa\StorageBundle\Entity\Link $links
     * @return Entitlement
     */
    public function addLink(\Hexaa\StorageBundle\Entity\Link $links)
    {
        $this->links[] = $links;

        return $this;
    }

    /**
     * Remove links
     *
     * @param \Hexaa\StorageBundle\Entity\Link $links
     */
    public function removeLink(\Hexaa\StorageBundle\Entity\Link $links)
    {
        $this->links->removeElement($links);
    }

    /**
     * Get links
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * Add roles
     *
     * @param \Hexaa\StorageBundle\Entity\Entitlement $roles
     * @return Entitlement
     */
    public function addRole(\Hexaa\StorageBundle\Entity\Entitlement $roles)
    {
        $this->roles[] = $roles;

        return $this;
    }

    /**
     * Remove roles
     *
     * @param \Hexaa\StorageBundle\Entity\Entitlement $roles
     */
    public function removeRole(\Hexaa\StorageBundle\Entity\Entitlement $roles)
    {
        $this->roles->removeElement($roles);
    }

    /**
     * Get roles
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Add entitlementPacks
     *
     * @param \Hexaa\StorageBundle\Entity\EntitlementPack $entitlementPacks
     * @return Entitlement
     */
    public function addEntitlementPack(\Hexaa\StorageBundle\Entity\EntitlementPack $entitlementPacks)
    {
        $this->entitlementPacks[] = $entitlementPacks;

        return $this;
    }

    /**
     * Remove entitlementPacks
     *
     * @param \Hexaa\StorageBundle\Entity\EntitlementPack $entitlementPacks
     */
    public function removeEntitlementPack(\Hexaa\StorageBundle\Entity\EntitlementPack $entitlementPacks)
    {
        $this->entitlementPacks->removeElement($entitlementPacks);
    }

    /**
     * Get entitlementPacks
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEntitlementPacks()
    {
        return $this->entitlementPacks;
    }
}
