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

namespace Hexaa\ApiBundle\Handler;

/**
 * Handler class for common queries and error handling.
 *
 * @author baloo
 */
class EntityHandler {

    private $em;
    private $errorlog;

    public function __construct($em, $errorlog) {
        $this->em = $em;
        $this->errorlog = $errorlog;
    }

    public function get($entityName, $id, $action) {
        $obj = $this->em->getRepository('HexaaStorageBundle:' . $entityName)->find($id);
        if (!$obj) {
            $this->errorlog->error('[' . $action . '] ' . $entityName . ' with id=' . $id . ' was not found');
            throw new HttpException(404, $entityName . ' not found');
        } else {
            return $obj;
        }
    }

}
