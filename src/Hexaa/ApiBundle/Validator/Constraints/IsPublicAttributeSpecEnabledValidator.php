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
            $this->context->addViolation($constraint->message);
            $this->context->addViolationAt("is_public", $constraint->message);
        }
    }

}
