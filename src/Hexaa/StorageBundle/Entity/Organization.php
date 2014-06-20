<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Organization
 *
 * @ORM\Table(name="organization", uniqueConstraints={@ORM\UniqueConstraint(name="name", columns={"name"})})
 * @ORM\Entity
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
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var integer
     *
     * @ORM\Column(name="default_role_id", type="bigint", nullable=true)
     */
    private $defaultRoleId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



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
     * @param integer $defaultRoleId
     * @return Organization
     */
    public function setDefaultRoleId($defaultRoleId)
    {
        $this->defaultRoleId = $defaultRoleId;

        return $this;
    }

    /**
     * Get defaultRoleId
     *
     * @return integer 
     */
    public function getDefaultRoleId()
    {
        return $this->defaultRoleId;
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
        $this->principals[] = $managers;

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
}
