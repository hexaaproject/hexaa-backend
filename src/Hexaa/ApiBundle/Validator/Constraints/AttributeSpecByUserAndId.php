<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class AttributeSpecByUserAndId extends Constraint {

    public $maintainerMessage = 'AttributeSpec with id=%id% can not be linked to a Principal';
    public $userMessage = 'AttributeSpec with id=%id% is not linked to this Principal';
    public $notFoundMessage = 'Non-existent AttributeSpec id given';


    public function validatedBy() {
        return 'attribute_spec_by_user_and_id';
    }
}

?>