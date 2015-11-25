<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;


class HookHasValidTargetValidator extends ConstraintValidator
{

    public function validate($h, Constraint $constraint)
    {
        if ($h->getService() != null) {
            if ($h->getOrganization() != null) {
                $this->context->buildViolation($constraint->numberViolationMessage)
                    ->addViolation();
                $this->context->buildViolation($constraint->numberViolationMessage)
                    ->atPath("organization")
                    ->addViolation();
                $this->context->buildViolation($constraint->numberViolationMessage)
                    ->atPath("service")
                    ->addViolation();
            }
        } else {
            if ($h->getOrganization() == null) {
                $this->context->buildViolation($constraint->numberViolationMessage)
                    ->addViolation();
                $this->context->buildViolation($constraint->numberViolationMessage)
                    ->atPath('organization')
                    ->addViolation();
                $this->context->buildViolation($constraint->numberViolationMessage)
                    ->atPath('service')
                    ->addViolation();
            }
        }
    }

}
