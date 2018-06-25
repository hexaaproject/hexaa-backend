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

use Hexaa\StorageBundle\Entity\Link;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;


class LinkHasOrganizationOrServiceValidator extends ConstraintValidator
{

    /** @var $l Link */
    public function validate($l, Constraint $constraint)
    {
        if (!$l->getService() && !$l->getOrganization()) {
            $this->context->buildViolation($constraint->violationMessage)
              ->addViolation();
            $this->context->buildViolation($constraint->violationMessage)
              ->atPath('organization')
              ->addViolation();
            $this->context->buildViolation($constraint->violationMessage)
              ->atPath('service')
              ->addViolation();
        }
    }

}

