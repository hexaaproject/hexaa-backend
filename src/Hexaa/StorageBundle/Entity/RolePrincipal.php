<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\MaxDepth;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\VirtualProperty;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * RolePrincipal
 *
 * @ORM\Table(
 *   name="role_principal",
 *   indexes={
 *     @ORM\Index(name="role_id_idx", columns={"role_id"}),
 *     @ORM\Index(name="principal_id_idx", columns={"principal_id"})
 *   },
 *   uniqueConstraints={
 *     @ORM\UniqueConstraint(name="role_principal", columns={"role_id", "principal_id"})
 *   }
 * )
 * @ORM\Entity(repositoryClass="Hexaa\StorageBundle\Entity\RolePrincipalRepository")
 * @ORM\HasLifecycleCallbacks
 * @UniqueEntity({"role", "principal"})
 *
 *
 */
class RolePrincipal
{
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expiration", type="datetime", nullable=true)
     * @Assert\DateTime()
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $expiration;

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
     * @var Role
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Role", inversedBy="principals")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Groups({"expanded"})
     * @Assert\Valid()
     * @MaxDepth(2)
     */
    private $role;

    /**
     * @var Principal
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\Principal", inversedBy="roles")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="principal_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     *
     * @Groups({"expanded"})
     * @MaxDepth(2)
     */
    private $principal;

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
     * @return RolePrincipal
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("principal_id")
     * @Type("integer")
     * @Groups({"minimal", "normal"})
     */
    public function getPrincipalId()
    {
        return $this->principal->getId();
    }

    /**
     * @VirtualProperty
     * @SerializedName("role_id")
     * @Type("integer")
     * @Groups({"minimal", "normal"})
     */
    public function getRoleId()
    {
        return $this->role->getId();
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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
     * @return RolePrincipal
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function __toString()
    {
        return "RPr".$this->getRole()->getId()."p".$this->getPrincipal()->getId();
    }

    /**
     * Get role
     *
     * @return Role
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set role
     *
     * @param Role $role
     * @return RolePrincipal
     */
    public function setRole(Role $role = null)
    {
        $this->role = $role;

        if ($this->role !== null && !$role->hasPrincipal($this)) {
            $this->role->addPrincipal($this);
        }

        return $this;
    }

    /**
     * Get principal
     *
     * @return Principal
     */
    public function getPrincipal()
    {
        return $this->principal;
    }

    /**
     * Set principal
     *
     * @param Principal $principal
     * @return RolePrincipal
     */
    public function setPrincipal(Principal $principal = null)
    {
        $this->principal = $principal;
        if (!$principal->getRoles()->contains($this)) {
            $principal->addRole($this);
        }

        return $this;
    }
}
