<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class AttributeSpec4Manager extends Constraint {

    public $message = 'this AttributeSpec can not be linked to an organization';
    public $notFoundMessage = "We couldn't find this AttributeSpec";
}

?>