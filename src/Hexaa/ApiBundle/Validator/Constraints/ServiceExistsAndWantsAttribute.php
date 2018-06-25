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

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ServiceExistsAndWantsAttribute extends Constraint
{

    public $notWantedMessage = 'Service with id=%sid% does not want AttributeSpec with id=%asid%';
    public $serviceNotFoundMessage = 'Non-existent Service id given';
    public $attributeSpecNotFoundMessage = 'Non-existent attribute specification id given';
    public $attributeSpecIsSingleValueMessage = "Can't add more than one values to a non-multivalue attribute";
    public $attributeSpecMaintainerMismatchPrincipal = "Can't assign maintainer to manager to AttributeSpec with an AttributeValuePrincipal already assigned.";
    public $attributeSpecMaintainerMismatchOrganization = "Can't assign maintainer to user to AttributeSpec with an AttributeValueOrganization already assigned.";

    public function validatedBy()
    {
        return 'service_exists_and_wants_attribute';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

?>