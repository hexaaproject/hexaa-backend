<?php

/*
 * Copyright 2014 baloo.
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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use JMS\Serializer\Annotation\Exclude;
use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

/**
 * Token used to authenticate through PersonalTokenAuth
 *
 * @author baloo
 *
 * @ORM\Table(name="principal", indexes={@ORM\Index(name="token_idx", columns={"token"})})
 * @ORM\Entity
 * @UniqueEntity("token")
 * @ORM\HasLifecycleCallbacks
 */
class PersonalToken {

    public function __construct($masterkey = "default") {
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
        $this->token = hash('sha256', $p->getFedid() . $date->format('Y-m-d H:i:s') . $uuid);
        $this->tokenExpire = $date;
    }

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
     * @ORM\Column(name="masterkey_name", type="string", length=255, nullable=true)
     * @Exclude
     */
    private $masterkey;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="token_expire", type="datetime", nullable=false)
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
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

}
