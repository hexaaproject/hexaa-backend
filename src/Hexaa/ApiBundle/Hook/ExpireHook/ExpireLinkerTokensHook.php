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

namespace Hexaa\ApiBundle\Hook\ExpireHook;


class ExpireLinkerTokensHook extends ExpireHook {
    private $loglbl = "[ExpireLinkerTokenHook] ";

    public function runHook() {
        $now = new \DateTime('now');
        date_timezone_set($now, new \DateTimeZone("UTC"));
        $linkerTokens = $this->em->createQueryBuilder()
            ->select('lt')
            ->from("HexaaStorageBundle:LinkerToken", 'lt')
            ->where("lt.expiresAt <= :now")
            ->setParameters(array(":now" => $now))
            ->getQuery()
            ->getResult();

        if (count($linkerTokens) > 0) {
            $this->modlog->info($this->loglbl . "Removed " . count($linkerTokens) . " from database.");
        }

        foreach($linkerTokens as $linkerToken) {
            $this->em->remove($linkerToken);
        }
        $this->em->flush();

    }
}