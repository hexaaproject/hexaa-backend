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

namespace Hexaa\ApiBundle\Hook;


use Monolog\Logger;

/**
 * Description of HookHandler
 *
 * @author baloo
 */
class HookHandler {

    protected $masterkeys;
    protected $errorlog;
    protected $em;

    public function _construct($masterkeys, Logger $errorlog, $em) {
        $this->masterkeys = $masterkeys;
        $this->errorlog = $errorlog;
        $this->em = $em;
    }

    public function handleMasterKeyHook($name, $p, $_controller) {
        $className = __NAMESPACE__ . "\\MasterKeyHook\\" . $name;
        if (class_exists($className)) {
            $hook = new $className($this->em);
            return $hook->runHook($p, $_controller)===true;
        } else {
            $this->errorlog->error('[handleMasterKeyHook] Class named "' . $className . '" could not be found.');
        }
        return false;
    }

}
