<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use JMS\Serializer\Annotation\Exclude;

/**
 * Principal
 *
 * @ORM\Table(name="principal", uniqueConstraints={@ORM\UniqueConstraint(name="fedid", columns={"fedid"})})
 * @ORM\Entity
 * @UniqueEntity("fedid")
 * @ORM\HasLifecycleCallbacks
 */
class Principal {

    /**
     * @var string
     *
     * @ORM\Column(name="fedid", type="string", length=255, nullable=false)
     * 
     * @Assert\NotBlank()
     */
    private $fedid;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     * 
     * @Assert\Email(strict=true)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=255, nullable=true)
     * @Exclude
     */
    private $token;

    /**
     * @var string
     *
     * @ORM\Column(name="display_name", type="string", length=255, nullable=true)
     */
    private $displayName;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="token_expire", type="datetime", nullable=true)
     * @Exclude
     */
    private $tokenExpire;

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
     * Set fedid
     *
     * @param string $fedid
     * @return Principal
     */
    public function setFedid($fedid) {
        $this->fedid = $fedid;

        return $this;
    }

    /**
     * Get fedid
     *
     * @return string 
     */
    public function getFedid() {
        return $this->fedid;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set token
     *
     * @param string $token
     * @return Principal
     */
    public function setToken($token) {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string 
     */
    public function getToken() {
        return $this->token;
    }

    /**
     * Set display name
     *
     * @param string $displayName
     * @return Principal
     */
    public function setDisplayName($displayName) {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * Get display name
     *
     * @return string 
     */
    public function getDisplayName() {
        return $this->displayName;
    }

    /**
     * Set tokenExpire
     *
     * @param \DateTime $tokenExpire
     * @return Principal
     */
    public function setTokenExpire($tokenExpire) {
        $this->tokenExpire = $tokenExpire;

        return $this;
    }

    /**
     * Get tokenExpire
     *
     * @return \DateTime 
     */
    public function getTokenExpire() {
        return $this->tokenExpire;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Principal
     */
    public function setCreatedAt($createdAt) {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt() {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return Principal
     */
    public function setUpdatedAt($updatedAt) {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime 
     */
    public function getUpdatedAt() {
        return $this->updatedAt;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Principal
     */
    public function setEmail($email) {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail() {
        return $this->email;
    }

}
