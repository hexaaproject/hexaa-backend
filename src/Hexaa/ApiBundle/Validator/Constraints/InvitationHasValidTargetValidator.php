<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Security\Core\SecurityContextInterface;

class InvitationHasValidTargetValidator extends ConstraintValidator {

    protected $em;
    protected $securityContext;

    public function __construct($em, $securityContext) {
        $this->em = $em;
        $this->securityContext = $securityContext;
    }

    public function validate($i, Constraint $constraint) {
        $usr = $this->securityContext->getToken()->getUser();
        $p = $this->em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        if ($i->getService() != null) {
            if ($i->getOrganization() != null) {
                $this->context->addViolation(
                        $constraint->numberViolationMessage
                );
                $this->context->addViolationAt(
                        'organization', $constraint->numberViolationMessage
                );
                $this->context->addViolationAt(
                        'service', $constraint->numberViolationMessage
                );
            }

            if ($i->getRole() != null) {
                $this->context->addViolation(
                        $constraint->roleNoOrganizationViolationMessage
                );
                $this->context->addViolationAt(
                        'role', $constraint->roleNoOrganizationViolationMessage
                );
            }

            if (!$i->getService()->hasManager($p) && !in_array ($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
                $this->context->addViolation(
                        $constraint->serviceManagerViolationMessage, array('%service%' => $i->getService()->getName())
                );
                $this->context->addViolationAt(
                        'service', $constraint->serviceManagerViolationMessage, array('%service%' => $i->getService()->getName())
                );
            }
        } else {
            if ($i->getOrganization() == null) {
                $this->context->addViolation(
                        $constraint->numberViolationMessage
                );
                $this->context->addViolationAt(
                        'organization', $constraint->numberViolationMessage
                );
                $this->context->addViolationAt(
                        'service', $constraint->numberViolationMessage
                );
            } else {
                if (!$i->getOrganization()->hasManager($p) && !in_array ($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
                    $this->context->addViolation(
                            $constraint->organizationManagerViolationMessage, array('%organization%' => $i->getOrganization()->getName())
                    );
                    $this->context->addViolationAt(
                            'organization', $constraint->organizationManagerViolationMessage, array('%organization%' => $i->getOrganization()->getName())
                    );
                }

                if (($i->getRole() != null) && ($i->getRole()->getOrganization() != $i->getOrganization())) {
                    $this->context->addViolation(
                            $constraint->roleNoOrganizationViolationMessage
                    );
                    $this->context->addViolationAt(
                            'role', $constraint->roleBadOrganizationViolationMessage, array('%role%' => $i->getRole()->getName(), '%organization%' => $i->getOrganization()->getName())
                    );
                }
            }
        }
    }

}
