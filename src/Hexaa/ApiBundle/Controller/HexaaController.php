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


use FOS\RestBundle\Controller\FOSRestController;

/**
 * Description of HexaaController
 *
 * @author baloo
 */
class HexaaController extends FOSRestController {
    protected $em;
    protected $eh;
    protected $accesslog;
    protected $errorlog;
    protected $modlog;
    
    public function setStuff($em, $eh, $accesslog, $errorlog, $modlog){
        $this->em = $em;
        $this->eh = $eh;
        $this->accesslog = $accesslog;
        $this->errorlog = $errorlog;
        $this->modlog = $modlog;
    }
}