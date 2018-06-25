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
use Symfony\Component\Validator\ConstraintValidator;

class IsPublicAttributeSpecEnabledValidator extends ConstraintValidator
{
    protected $isPublicAttrSpecEnabled;

    public function __construct($isPublicAttrSpecEnabled)
    {
        $this->isPublicAttrSpecEnabled = $isPublicAttrSpecEnabled;
    }

    public function validate($value, Constraint $constraint)
    {
        if ((!$this->isPublicAttrSpecEnabled) && $value) {
            $this->context->buildViolation($constraint->message)
              ->addViolation();
            $this->context->buildViolation($constraint->message)
              ->atPath("is_public")
              ->addViolation();
        }
    }

}
