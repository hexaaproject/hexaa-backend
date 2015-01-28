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

namespace Hexaa\ApiBundle\Controller;


use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\FOSRestController;
use Hexaa\ApiBundle\Handler\EntityHandler;
use Monolog\Logger;

/**
 * Description of HexaaController
 *
 * @author solazs@sztaki.hu
 */
class HexaaController extends FOSRestController {
    /* @var $em \Doctrine\ORM\EntityManager */
    protected $em;
    /* @var $eh \Hexaa\ApiBundle\Handler\EntityHandler */
    protected $eh;
    /* @var $accesslog \Monolog\Logger */
    protected $accesslog;
    /* @var $errorlog \Monolog\Logger */
    protected $errorlog;
    /* @var $modlog \Monolog\Logger */
    protected $modlog;

    /**
     * @param EntityManager $em
     * @param EntityHandler $eh
     * @param Logger $accesslog
     * @param Logger $errorlog
     * @param Logger $modlog
     */
    public function setStuff(EntityManager $em, EntityHandler $eh, Logger $accesslog, Logger $errorlog, Logger $modlog){
        $this->em = $em;
        $this->eh = $eh;
        $this->accesslog = $accesslog;
        $this->errorlog = $errorlog;
        $this->modlog = $modlog;
    }
}
