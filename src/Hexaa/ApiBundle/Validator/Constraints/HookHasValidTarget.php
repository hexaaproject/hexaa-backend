<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class HookHasValidTarget extends Constraint {

    public $numberViolationMessage = 'Exactly one of organization or service must be defined.';

    public function validatedBy() {
        return 'hook_has_valid_target';
    }

    public function getTargets() {
        return self::CLASS_CONSTRAINT;
    }

}

?>