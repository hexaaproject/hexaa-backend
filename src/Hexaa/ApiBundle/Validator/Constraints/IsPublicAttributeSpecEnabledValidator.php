<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IsPublicAttributeSpecEnabledValidator extends ConstraintValidator {
    protected $isPublicAttrSpecEnabled;

    public function __construct($isPublicAttrSpecEnabled) {
        $this->isPublicAttrSpecEnabled = $isPublicAttrSpecEnabled;
    }

    public function validate($value, Constraint $constraint) {
        if ((!$this->isPublicAttrSpecEnabled) && $value) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
            $this->context->buildViolation($constraint->message)
                ->atPath("is_public")
                ->addViolation();
        }
    }

}
