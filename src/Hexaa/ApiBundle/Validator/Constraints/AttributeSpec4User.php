<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class AttributeSpec4User extends Constraint {

    public $message = 'this AttributeSpec can not be linked to a principal';
/*
    public function validatedBy() {
        return 'attrspec4user';
    }
*/
}

?>