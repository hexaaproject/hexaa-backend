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

namespace Hexaa\ApiBundle\Annotations;

use Doctrine\Common\Annotations\Annotation\Enum;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;


/**
 * @Annotation
 * @Target("METHOD")
 */
class InvokeHook
{
    /**
     * @var array<string>
     */
    public $types;

    /**
     * @var string
     * @Enum({
     *     "AttributeSpec",
     *     "AttributeValueOrganization",
     *     "AttributeValuePrincipal",
     *     "Consent",
     *     "Entitlement",
     *     "EntitlementPack",
     *     "Invitation",
     *     "Organization",
     *     "Principal",
     *     "Role",
     *     "Service",
     *     "Link"
     *     })
     * @Required
     */
    public $entity;

    /**
     * @var string
     * @Enum({
     *     "id",
     *     "eid",
     *     "epid",
     *     "sid",
     *     "service",
     *     "token",
     *     "fedid"
     *     })
     */
    public $id;

    /**
     * @var string
     * @Enum({
     *     "attributes",
     *     "request",
     *     "principal",
     *     "link"
     *     })
     * @Required
     */
    public $source;

    public function getInfo()
    {
        return array(
          'entity' => $this->entity,
          'id'     => $this->id,
          'source' => $this->source,
        );
    }
}