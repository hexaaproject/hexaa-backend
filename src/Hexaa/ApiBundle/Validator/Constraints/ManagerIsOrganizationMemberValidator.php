<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ManagerIsOrganizationMemberValidator extends ConstraintValidator {

    public function validate($o, Constraint $constraint) {
        foreach($o->getManagers() as $m) {
            if (!$m) {
                $this->context->addViolation($constraint->principalNotFoundMessage);
            } else {
                if (!$o->hasPrincipal($m)) {
                    $this->context->addViolation($constraint->notMemberMessage, array('%fedid%' => $m->getFedid(), "%org%" => $o->getName()));
                }
            }
        }
    }

}
