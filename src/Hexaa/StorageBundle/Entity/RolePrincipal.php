<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

/**
 * RolePrincipal
 *
 * @ORM\Table(name="role_principal", indexes={@ORM\Index(name="role_id_idx", columns={"role_id"}), @ORM\Index(name="principal_id_idx", columns={"principal_id"})})
 * @ORM\Entity
 */
class RolePrincipal
{
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expiration", type="datetime", nullable=true)
     */
    private $expiration;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Hexaa\StorageBundle\Entity\Role
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Role")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Exclude
     */
    private $role;

    /**
     * @var \Hexaa\StorageBundle\Entity\Principal
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Principal")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="principal_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Exclude
     */
    private $principal;



    /**
     * Set expiration
     *
     * @param \DateTime $expiration
     * @return RolePrincipal
     */
    public function setExpiration($expiration)
    {
        $this->expiration = $expiration;

        return $this;
    }

    /**
     * Get expiration
     *
     * @return \DateTime 
     */
    public function getExpiration()
    {
        return $this->expiration;
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
     * Set role
     *
     * @param \Hexaa\StorageBundle\Entity\Role $role
     * @return RolePrincipal
     */
    public function setRole(\Hexaa\StorageBundle\Entity\Role $role = null)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role
     *
     * @return \Hexaa\StorageBundle\Entity\Role 
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set principal
     *
     * @param \Hexaa\StorageBundle\Entity\Principal $principal
     * @return RolePrincipal
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
}
