<?php

/*
 * Copyright 2014 MTA SZTAKI.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\MaxDepth;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use Ramsey\Uuid\Uuid;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Token used to authenticate through PersonalTokenAuth
 *
 * @author solazs@sztaki.hu
 *
 * @ORM\Table(
 *   name="personal_token",
 *   indexes={
 *       @ORM\Index(name="token_idx", columns={"token"})
 *     },
 *   uniqueConstraints={
 *     @ORM\UniqueConstraint(name="token", columns={"token"})
 *   })
 * @ORM\Entity
 * @UniqueEntity("token")
 * @ORM\HasLifecycleCallbacks
 */
class PersonalToken
{

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=64, nullable=true)
     */
    private $token;
    /**
     * @var string
     *
     * @ORM\Column(name="masterkey_name", type="string", length=255, nullable=true)
     * @Exclude
     */
    private $masterkey;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="token_expire", type="datetime", nullable=false)
     */
    private $tokenExpire;
    /**
     * @ORM\OneToOne(targetEntity="Principal", mappedBy="token", cascade={"persist"}, orphanRemoval=true)
     * @Groups({"expanded"})
     * @MaxDepth(1)
     */
    private $principal;
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

    public function __construct($fedid, $masterkey = "default")
    {
        $this->masterkey = $masterkey;
        try {
            $uuid = Uuid::uuid4();
        } catch (UnsatisfiedDependencyException $e) {
            // Some dependency was not met. Either the method cannot be called on a
            // 32-bit system, or it can, but it relies on Moontoast\Math to be present.
            $uuid = uniqid();
        }
        $date = new \DateTime('now');
        date_timezone_set($date, new \DateTimeZone("UTC"));
        $date->modify('+1 hour');
        $this->token = hash('sha256', $fedid.$date->format('Y-m-d H:i:s').$uuid);
        $this->tokenExpire = $date;
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
     * @return PersonalToken
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

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
     * @return PersonalToken
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

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

    /**
     * Set tokenExpire
     *
     * @param \DateTime $tokenExpire
     * @return PersonalToken
     */
    public function setTokenExpire($tokenExpire)
    {
        $this->tokenExpire = $tokenExpire;

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
     * Set token
     *
     * @param string $token
     * @return PersonalToken
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get masterkey
     *
     * @return string
     */
    public function getMasterkey()
    {
        return $this->masterkey;
    }

    /**
     * Set masterkey
     *
     * @param string $masterkey
     * @return PersonalToken
     */
    public function setMasterkey($masterkey)
    {
        $this->masterkey = $masterkey;

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

    public function __toString()
    {
        return $this->token;
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
     * @return PersonalToken
     */
    public function setPrincipal(Principal $principal = null)
    {
        $this->principal = $principal;

        return $this;
    }
}
