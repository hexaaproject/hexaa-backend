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
class EntitlementCanBeAddedToRole extends Constraint
{

    public $entitlementNotValidMessage = '%entitlement% can not be added to Role %role%, because organization %org% does not have that entitlement.';
    public $entitlementNotFoundMessage = 'Non-existent Entitlement id given';


    public function validatedBy()
    {
        return 'entitlement_can_be_added_to_role';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

?>