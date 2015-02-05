<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class IsPublicAttributeSpecEnabled extends Constraint {

    public $message = 'public Attribute Specification linking is disabled in configuration';
}

?>