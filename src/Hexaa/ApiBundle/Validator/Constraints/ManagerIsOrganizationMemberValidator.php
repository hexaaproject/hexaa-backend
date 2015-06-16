<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ManagerIsOrganizationMemberValidator extends ConstraintValidator {

    public function validate($o, Constraint $constraint) {
        foreach($o->getManagers() as $m) {
            if (!$m) {
                $this->context->buildViolation($constraint->principalNotFoundMessage)
                    ->addViolation();
            } else {
                if (!$o->hasPrincipal($m)) {
                    $this->context->buildViolation($constraint->notMemberMessage)
                        ->setParameter('%fedid%', $m->getFedid())
                        ->setParameter("%org%", $o->getName())
                        ->addViolation();
                }
            }
        }
    }

}
