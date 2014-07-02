<?php
namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidEntityidValidator extends ConstraintValidator
{
    private $entityids;
    
    public function __construct($entityids){
        $this->entityids = $entityids;
         
    }
    
    public function validate($value, Constraint $constraint)
    {
        if (!in_array($value, $this->entityids)) {
            $this->context->addViolation(
                $constraint->message,
                array('%entityid%' => $value)
            );
        }
    }
}