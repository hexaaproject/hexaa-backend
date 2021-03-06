<?php

/**
 * Copyright 2014-2018 MTA SZTAKI, ugyeletes@sztaki.hu
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Hexaa\ApiBundle\Handler;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Handler class for common queries and error handling.
 *
 * @author solazs@sztaki.hu
 */
class EntityHandler
{

    private $em;
    private $errorlog;

    public function __construct(EntityManager $em, Logger $errorlog)
    {
        $this->em = $em;
        $this->errorlog = $errorlog;
    }

    public function get($entityName = "EmptyName", $id = 0, $action = "EntityHandler", $referenceOnly = false)
    {
        if ($referenceOnly) {
            $obj = $this->em->getReference('HexaaStorageBundle:'.$entityName, $id);
        } else {
            $obj = $this->em->getRepository('HexaaStorageBundle:'.$entityName)->find($id);
        }
        if (!$obj) {
            if (strstr($action, '[') === false && strstr($action, ']') === false) {
                $action = '['.$action.'] ';
            }
            $this->errorlog->error($action.$entityName.' with id='.$id.' was not found');
            throw new HttpException(404, $entityName.' with id='.$id.' not found');
        } else {
            return $obj;
        }
    }

}
