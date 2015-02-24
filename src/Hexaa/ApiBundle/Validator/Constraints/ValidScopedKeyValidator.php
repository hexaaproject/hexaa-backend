<?php
namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidScopedKeyValidator extends ConstraintValidator {
    private $scopedKeys;

    public function __construct($scopedKeys) {
        $this->scopedKeys = $scopedKeys;

    }

    public function validate($value, Constraint $constraint) {
        if (!in_array($value, $this->scopedKeys)) {
            $this->context->addViolation(
                $constraint->message,
                array('%scopedkey%' => $value)
            );
        }
    }
}