<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class AttributeSpec4UserValidator extends ConstraintValidator {

    public function validate($value, Constraint $constraint) {
        if (!$value) {
            $this->context->addViolation("attribute_spec", $constraint->notFoundMessage);
        }
        if ($value->getMaintainer() != "user") {
            $this->context->addViolation($constraint->message);
            $this->context->addViolationAt("maintainer", $constraint->message);
        }
    }

}
