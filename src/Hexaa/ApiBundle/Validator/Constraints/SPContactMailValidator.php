<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;


class SPContactMailValidator extends ConstraintValidator {

    protected $entityids;

    public function __construct($entityids) {
        $this->entityids = $entityids;
    }

    public function validate($value, Constraint $constraint) {
        $s = $constraint->getService();
        $entityid = $this->entityids[$s->getEntityid()];
        $valid = false;
        foreach($entityid as $contact) {
            if ($value == $contact) {
                $valid = true;
            }
        }

        if (!$valid) {
            $this->context->addViolation($constraint->invalidMessage, array('%surName%' => $contact['surName'], "%entityid%" => $s->getEntityid()));
        }
    }

}
