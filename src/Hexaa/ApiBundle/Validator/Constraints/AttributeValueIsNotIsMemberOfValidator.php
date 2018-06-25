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

class AttributeValueIsNotIsMemberOfValidator extends ConstraintValidator
{

    /**
     * Checks if the passed value is valid.
     *
     * @param mixed      $value      The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     *
     * @api
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value->getAttributeSpec()) {
            $this->context->buildViolation($constraint->attributeSpecNotFoundMessage)
              ->addViolation();
            $this->context->buildViolation($constraint->attributeSpecNotFoundMessage)
              ->atPath("attribute_spec")
              ->addViolation();
        } else {
            /* @var $as \Hexaa\StorageBundle\Entity\AttributeSpec */
            $as = $value->getAttributeSpec();
            if ($as->getUri() == "urn:oid:1.3.6.1.4.1.5923.1.5.1.1") {
                $this->context->buildViolation($constraint->forbiddenAttributeValueMessage)
                  ->addViolation();
                $this->context->buildViolation($constraint->forbiddenAttributeValueMessage)
                  ->atPath("attribute_spec")
                  ->addViolation();
            }
        }
    }
}