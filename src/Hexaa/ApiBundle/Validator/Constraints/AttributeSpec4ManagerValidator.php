<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Hexaa\StorageBundle\Entity\AttributeSpec;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class AttributeSpec4ManagerValidator extends ConstraintValidator {

    public function validate($value, Constraint $constraint) {
        if (!$value instanceof AttributeSpec) {
            $this->context->buildViolation($constraint->notFoundMessage)
                ->addViolation();
            $this->context->buildViolation($constraint->notFoundMessage)
                ->atPath("attribute_spec")
                ->addViolation();
        }
        if ($value->getMaintainer() != "manager") {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
            $this->context->buildViolation($constraint->message)
                ->atPath("attribute_spec")
                ->addViolation();
        }
    }

}
