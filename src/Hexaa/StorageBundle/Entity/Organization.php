<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Validator\Constraints as Assert;
use Hexaa\ApiBundle\Validator\Constraints as HexaaAssert;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Organization
 *
 * @ORM\Table(name="organization", uniqueConstraints={@ORM\UniqueConstraint(name="name", columns={"name"})})
 * @ORM\Entity
 * @UniqueEntity("name")
 * @ORM\HasLifecycleCallbacks
 * @HexaaAssert\ManagerIsOrganizationMember()
 */
class Organization
{
    /**
     * @ORM\ManyToMany(targetEntity="Principal")
     * @ORM\JoinTable(name="organization_manager")
     * @Exclude
     */
    private $managers;
    
    /**
     * @ORM\ManyToMany(targetEntity="Principal")
     * @ORM\JoinTable(name="organization_principal")
     * @Exclude
     */
    private $principals;
    
    

 
    public function __construct() {
        $this->principals = new \Doctrine\Common\Collections\ArrayCollection();
        $this->managers = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
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
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var \Hexaa\StorageBundle\Entity\Role
     *
     * @ORM\OneToOne(targetEntity="Hexaa\StorageBundle\Entity\Role")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="default_role_id", referencedColumnName="id")
     * })
     * @Exclude()
     */
    private $defaultRole;

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
     * @VirtualProperty
     * @SerializedName("default_role_id")
     * @Type("integer")
     */
    public function getRoleId() {
        if (isset($this->defaultRole)){
            return $this->defaultRole->getId();
        } else return null;
            
    }



    /**
     * Set name
     *
     * @param string $name
     * @return Organization
     */
    public function setName($name)
    {
        $this->name = $name;

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
     * Set description
     *
     * @param string $description
     * @return Organization
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
     * Set defaultRoleId
     *
     * @param \Hexaa\StorageBundle\Entity\Role $defaultRole
     * @return Organization
     */
    public function setDefaultRole($defaultRole)
    {
        $this->defaultRole = $defaultRole;

        return $this;
    }

    /**
     * Get defaultRoleId
     *
     * @return \Hexaa\StorageBundle\Entity\Role
     */
    public function getDefaultRole()
    {
        return $this->defaultRole;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Organization
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
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Add managers
     *
     * @param \Hexaa\StorageBundle\Entity\Principal $managers
     * @return Organization
     */
    public function addManager(\Hexaa\StorageBundle\Entity\Principal $managers)
    {
        $this->managers[] = $managers;
        if (!$this->principals->contains($managers))
        {
            $this->principals[] = $managers;
        }

        return $this;
    }

    /**
     * Remove managers
     *
     * @param \Hexaa\StorageBundle\Entity\Principal $managers
     */
    public function removeManager(\Hexaa\StorageBundle\Entity\Principal $managers)
    {
        $this->managers->removeElement($managers);
    }

    /**
     * Get managers
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getManagers()
    {
        return $this->managers;
    }
    
    /**
     * Has manager
     *
     * @param \Hexaa\StorageBundle\Entity\Principal $manager
     *
     * @return boolean
     */
    public function hasManager(\Hexaa\StorageBundle\Entity\Principal $manager) 
    {
	return $this->managers->contains($manager);
    }
    
    /**
     * Has principal
     *
     * @param \Hexaa\StorageBundle\Entity\Principal $principal
     *
     * @return boolean
     */
    public function hasPrincipal(\Hexaa\StorageBundle\Entity\Principal $principal) 
    {
	return $this->principals->contains($principal);
    }

    /**
     * Add principals
     *
     * @param \Hexaa\StorageBundle\Entity\Principal $principals
     * @return Organization
     */
    public function addPrincipal(\Hexaa\StorageBundle\Entity\Principal $principals)
    {
        $this->principals[] = $principals;

        return $this;
    }

    /**
     * Remove principals
     *
     * @param \Hexaa\StorageBundle\Entity\Principal $principals
     */
    public function removePrincipal(\Hexaa\StorageBundle\Entity\Principal $principals)
    {
        $this->principals->removeElement($principals);
        $this->managers->removeElement($principals);
    }

    /**
     * Get principals
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPrincipals()
    {
        return $this->principals;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return Organization
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
