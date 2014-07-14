<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
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
class Principal
{
    /**
     * @var string
     *
     * @ORM\Column(name="fedid", type="string", length=255, nullable=false)
     * 
     */
    private $fedid;
    
    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=255, nullable=true)
     * @Exclude
     */
    private $token;
    
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
    public function setFedid($fedid)
    {
        $this->fedid = $fedid;

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
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set token
     *
     * @param string $token
     * @return Principal
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string 
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set tokenExpire
     *
     * @param \DateTime $tokenExpire
     * @return Principal
     */
    public function setTokenExpire($tokenExpire)
    {
        $this->tokenExpire = $tokenExpire;

        return $this;
    }

    /**
     * Get tokenExpire
     *
     * @return \DateTime 
     */
    public function getTokenExpire()
    {
        return $this->tokenExpire;
    }
}
