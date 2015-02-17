<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class IsPublicAttributeSpecEnabled extends Constraint {

    public $message = 'public Attribute Specification linking is disabled in configuration';


    public function validatedBy() {
        return 'is_attribute_spec_allowed_to_be_private';
    }
}

?>