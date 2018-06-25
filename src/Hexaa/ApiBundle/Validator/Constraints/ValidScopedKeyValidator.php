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

class ValidScopedKeyValidator extends ConstraintValidator
{
    private $scopedKeys;

    public function __construct($scopedKeys)
    {
        $this->scopedKeys = $scopedKeys;

    }

    public function validate($value, Constraint $constraint)
    {
        if (!in_array($value, $this->scopedKeys)) {
            $this->context->buildViolation($constraint->message)
              ->setParameter('%scopedkey%', $value)
              ->addViolation();
        }
    }
}