<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Hexaa\StorageBundle\Entity\AttributeSpec;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class AttributeSpec4UserValidator extends ConstraintValidator
{

    public function validate($value, Constraint $constraint)
    {
        if ($value == null || !$value instanceof AttributeSpec) {
            $this->context->buildViolation($constraint->notFoundMessage)
              ->addViolation();
            $this->context->buildViolation($constraint->notFoundMessage)
              ->atPath("attribute_spec")
              ->addViolation();
        } else if ($value->getMaintainer() != "user") {
            $this->context->buildViolation($constraint->message)
              ->addViolation();
            $this->context->buildViolation($constraint->message)
              ->atPath("attribute_spec")
              ->addViolation();
        }
    }

}
