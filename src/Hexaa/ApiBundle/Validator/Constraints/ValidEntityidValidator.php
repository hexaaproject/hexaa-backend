<?php
namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidEntityidValidator extends ConstraintValidator
{
    private $entityids;

    public function __construct($entityids)
    {
        $this->entityids = $entityids;

    }

    public function validate($value, Constraint $constraint)
    {
        if (!array_key_exists($value, $this->entityids)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('%entityid%', $value)
                ->addViolation();
        }
    }
}