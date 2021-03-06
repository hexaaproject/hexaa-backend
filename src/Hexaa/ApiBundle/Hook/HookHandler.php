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

namespace Hexaa\ApiBundle\Hook;


use Doctrine\ORM\EntityManager;
use Hexaa\ApiBundle\Hook\ExpireHook\ExpireHook;
use Hexaa\ApiBundle\Hook\MasterKeyHook\MasterKeyHook;
use Monolog\Logger;

/**
 * Description of HookHandler
 *
 * @author solazs@sztaki.hu
 */
class HookHandler
{
    /* @var $errorlog Logger */
    protected $errorlog;

    public function _construct(Logger $errorlog)
    {
        $this->errorlog = $errorlog;
    }

    public function handleMasterKeyHook(MasterKeyHook $masterKeyHook)
    {
        return $masterKeyHook->runHook();
    }

    public function handleExpireHook(ExpireHook $expireHook)
    {
        $expireHook->runHook();
    }


}
