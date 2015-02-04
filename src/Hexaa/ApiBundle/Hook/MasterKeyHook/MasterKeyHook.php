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

namespace Hexaa\ApiBundle\Hook\MasterKeyHook;

use Doctrine\ORM\EntityManager;
use \Hexaa\StorageBundle\Entity\Principal;
use Monolog\Logger;

/**
 * Description of ExampleMasterKeyHook
 *
 * @author solazs@sztaki.hu
 */
abstract class MasterKeyHook {
    protected $em;
    protected $p;
    protected $_controller;


    public function __construct(EntityManager $entityManager, Principal $p, $_controller){
        $this->em = $entityManager;
        $this->p = $p;
        $this->_controller = $_controller;
    }

    public abstract function runHook();
    
}
