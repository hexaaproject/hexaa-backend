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

use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EntitlementCanBeAddedToRoleValidator extends ConstraintValidator
{

    /** @var  EntityManager */
    protected $em;
    protected $securityContext;

    public function __construct($em, $securityContext)
    {
        $this->em = $em;
        $this->securityContext = $securityContext;
    }

    public function validate($r, Constraint $constraint)
    {
        foreach ($r->getEntitlements() as $e) {
            if (!$e) {
                $this->context->buildViolation($constraint->entitlementNotFoundMessage)
                  ->addViolation();
            } else {
                $es = $this->em->getRepository('HexaaStorageBundle:Entitlement')->findAllByOrganization($r->getOrganization());
                if (!in_array($e, $es, true)) {
                    $this->context->buildViolation($constraint->entitlementNotValidMessage)
                      ->setParameter("%entitlement%", $e->getName())
                      ->setParameter("%org%", $r->getOrganization()->getName())
                      ->setParameter("%role%", $r->getName())
                      ->addViolation();
                }
            }
        }
    }

}
