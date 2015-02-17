<?php

/*
 * Copyright 2014 MTA-SZTAKI.
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

namespace Hexaa\ApiBundle\Security;

use Hexaa\StorageBundle\Entity\Principal;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Description of HexaaUser
 *
 * @author solazs
 */
class HexaaUser implements UserInterface, EquatableInterface {
    private $username;
    private $password;
    private $principal;
    private $salt;
    private $roles;


    public function __construct($username, $password, $salt, Principal $principal, array $roles = array()) {
        $this->username = $username;
        $this->password = $password;
        $this->principal = $principal;
        $this->salt = $salt;
        $this->roles = $roles;
    }

    public function getPrincipal() {
        return $this->principal;
    }

    public function getRoles() {
        return $this->roles;
    }

    public function getPassword() {
        return $this->password;
    }

    public function getSalt() {
        return $this->salt;
    }

    public function getUsername() {
        return $this->username;
    }

    public function eraseCredentials() {
    }

    public function isEqualTo(UserInterface $user) {
        if (!$user instanceof HexaaUser) {
            return false;
        }

        if ($this->password !== $user->getPassword()) {
            return false;
        }

        if ($this->salt !== $user->getSalt()) {
            return false;
        }

        if ($this->principal !== $user->getPrincipal()) {
            return false;
        }

        if ($this->username !== $user->getUsername()) {
            return false;
        }

        return true;
    }
}
