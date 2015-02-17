<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Hexaa\StorageBundle\Entity\AttributeSpec;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class AttributeSpec4ManagerValidator extends ConstraintValidator {

    public function validate($value, Constraint $constraint) {
        if (!$value instanceof AttributeSpec) {
            $this->context->addViolation($constraint->notFoundMessage);
            $this->context->addViolationAt("attribute_spec", $constraint->notFoundMessage);
        }
        if ($value->getMaintainer() != "manager") {
            $this->context->addViolation($constraint->message);
            $this->context->addViolationAt("attribute_spec", $constraint->message);
        }
    }

}
