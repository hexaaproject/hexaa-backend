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


class EntitlementURIValidator extends ConstraintValidator
{

    protected $uriPrefix;

    public function __construct($uriPrefix)
    {
        $this->uriPrefix = $uriPrefix;
    }

    public function validate($e, Constraint $constraint)
    {

        if (!$e) {
            $this->context->buildViolation($constraint->entitlementNotFoundMessage)
              ->addViolation();
        } else {
            if (!preg_match(
              '/^'.$this->uriPrefix.':'.$e->getService()->getId().':[a-zA-Z0-9-_:]+$/',
              $e->getUri()
            )
            ) {
                $this->context->buildViolation($constraint->notValidURIMessage)
                  ->setParameter("%uri%", $e->getUri())
                  ->setParameter(
                    "%uri_prefix%",
                    $this->uriPrefix.':'.$e->getService()->getId().":your_text_here"
                  )
                  ->addViolation();
                $this->context->buildViolation($constraint->notValidURIMessage)
                  ->atPath("uri")
                  ->setParameter("%uri%", $e->getUri())
                  ->setParameter(
                    "%uri_prefix%",
                    $this->uriPrefix.':'.$e->getService()->getId().":your_text_here"
                  )
                  ->addViolation();
            }
        }
    }

}
