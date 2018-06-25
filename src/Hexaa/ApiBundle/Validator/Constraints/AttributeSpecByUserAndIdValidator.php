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

class AttributeSpecByUserAndIdValidator extends ConstraintValidator
{

    protected $em;
    protected $securityContext;

    public function __construct($em, $securityContext)
    {
        $this->em = $em;
        $this->securityContext = $securityContext;
    }

    public function validate($value, Constraint $constraint)
    {
        $as = $value;

        // Check if AttributeSpec exists
        if (!$as) {
            $this->context->buildViolation($constraint->notFoundMessage)
              ->addViolation();
        } else {

            // Check if it can be linked to a user
            if ($as->getMaintainer() != "user") {
                $this->context->buildViolation($constraint->maintainerMessage)
                  ->setParameter("%id%", $value->getId())
                  ->addViolation();
            }

            // Check if the user can see it (if it's linked to the user or public)
            $usr = $this->securityContext->getToken()->getUser();
            $p = $this->em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());

            $ass = $this->em->getRepository('HexaaStorageBundle:AttributeSpec')->findAllByPrincipal($p);

            if (!in_array($as, $ass, true)) {
                $this->context->buildViolation($constraint->userMessage)
                  ->setParameter("%id%", $value->getId())
                  ->addViolation();
            }
        }
    }

}
