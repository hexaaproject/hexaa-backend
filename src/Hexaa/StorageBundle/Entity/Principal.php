<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Principal
 *
 * @ORM\Table(
 *   name="principal",
 *   uniqueConstraints={
 *     @ORM\UniqueConstraint(name="fedid", columns={"fedid"})
 *   },
 *   indexes={
 *     @ORM\Index(name="fedid_idx", columns={"fedid"}),
 *     @ORM\Index(name="token_idx", columns={"token_id"})
 *   }
 * )
 * @ORM\Entity(repositoryClass="Hexaa\StorageBundle\Entity\PrincipalRepository")
 * @UniqueEntity("fedid")
 * @ORM\HasLifecycleCallbacks
 *
 */
class Principal
{

    /**
     * @var string
     *
     * @ORM\Column(name="fedid", type="string", length=255, nullable=false)
     *
     * @Assert\NotBlank()
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $fedid;

    /**
     * @var \Hexaa\StorageBundle\Entity\PersonalToken
     *
     * @ORM\ManyToOne(targetEntity="Hexaa\StorageBundle\Entity\PersonalToken",cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="token_id", referencedColumnName="id")
     * })
     * @Exclude
     */
    private $token = null;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     *
     * @Assert\Email(strict=true)
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="display_name", type="string", length=255, nullable=true)
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $displayName;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"minimal", "normal", "expanded"})
     *
     */
    private $id;

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
     * @return Principal
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get fedid
     *
     * @return string
     */
    public function getFedid()
    {
        return $this->fedid;
    }

    /**
     * Set fedid
     *
     * @param string $fedid
     * @return Principal
     */
    public function setFedid($fedid)
    {
        $this->fedid = $fedid;

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
     * Get token
     *
     * @return PersonalToken
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set token
     *
     * @param PersonalToken $token
     * @return Principal
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get display name
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * Set display name
     *
     * @param string $displayName
     * @return Principal
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;

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
     * @return Principal
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Principal
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    public function __toString()
    {
        return $this->fedid;
    }
}
