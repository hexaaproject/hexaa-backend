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

use Hexaa\StorageBundle\Entity\Invitation;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;


class InvitationHasValidTargetValidator extends ConstraintValidator
{

    protected $em;
    protected $securityContext;
    protected $hexaa_admins;

    public function __construct($em, $securityContext, $hexaa_admins)
    {
        $this->em = $em;
        $this->securityContext = $securityContext;
        $this->hexaa_admins = $hexaa_admins;
    }

    /**
     * @param Invitation                              $i
     * @param \Symfony\Component\Validator\Constraint $constraint
     */
    public function validate($i, Constraint $constraint)
    {
        $usr = $this->securityContext->getToken()->getUser();
        $p = $this->em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        if ($i->getService() != null) {
            if ($i->getOrganization() != null) {
                $this->context->buildViolation($constraint->numberViolationMessage)
                  ->addViolation();
                $this->context->buildViolation($constraint->numberViolationMessage)
                  ->atPath("organization")
                  ->addViolation();
                $this->context->buildViolation($constraint->numberViolationMessage)
                  ->atPath("service")
                  ->addViolation();
            }

            if ($i->getRole() != null) {
                $this->context->buildViolation($constraint->roleNoOrganizationViolationMessage)
                  ->addViolation();
                $this->context->buildViolation($constraint->roleNoOrganizationViolationMessage)
                  ->atPath('role')
                  ->addViolation();
            }

            if (!$i->getService()->hasManager($p) && !in_array($p->getFedid(), $this->hexaa_admins)) {
                $this->context->addViolation(
                  $constraint->serviceManagerViolationMessage,
                  array('%service%' => $i->getService()->getName())
                );
                $this->context->buildViolation($constraint->serviceManagerViolationMessage)
                  ->atPath('service')
                  ->setParameter('%service%', $i->getService()->getName())
                  ->addViolation();
            }
        } else {
            if ($i->getOrganization() == null) {
                $this->context->buildViolation($constraint->numberViolationMessage)
                  ->addViolation();
                $this->context->buildViolation($constraint->numberViolationMessage)
                  ->atPath('organization')
                  ->addViolation();
                $this->context->buildViolation($constraint->numberViolationMessage)
                  ->atPath('service')
                  ->addViolation();
            } else {
                if (!$i->getOrganization()->hasManager($p) && !in_array($p->getFedid(), $this->hexaa_admins)) {
                    $this->context->buildViolation($constraint->organizationManagerViolationMessage)
                      ->setParameter('%organization%', $i->getOrganization()->getName())
                      ->addViolation();
                    $this->context->buildViolation($constraint->organizationManagerViolationMessage)
                      ->atPath("organization")
                      ->setParameter('%organization%', $i->getOrganization()->getName())
                      ->addViolation();
                }

                if (($i->getRole() != null) && ($i->getRole()->getOrganization() != $i->getOrganization())) {
                    $this->context->buildViolation($constraint->roleNoOrganizationViolationMessage)
                      ->addViolation();
                    $this->context->buildViolation($constraint->roleBadOrganizationViolationMessage)
                      ->atPath('role')
                      ->setParameters(
                        array(
                          '%role%'         => $i->getRole()->getName(),
                          '%organization%' => $i->getOrganization()->getName(),
                        )
                      )
                      ->addViolation();
                }
            }
        }
    }

}
