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
class AttributeSpecByUserAndId extends Constraint
{

    public $maintainerMessage = 'AttributeSpec with id=%id% can not be linked to a Principal';
    public $userMessage = 'AttributeSpec with id=%id% is not linked to this Principal';
    public $notFoundMessage = 'Non-existent AttributeSpec id given';


    public function validatedBy()
    {
        return 'attribute_spec_by_user_and_id';
    }
}

?>