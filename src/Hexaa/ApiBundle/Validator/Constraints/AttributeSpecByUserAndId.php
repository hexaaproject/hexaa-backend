<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class AttributeSpecByUserAndId extends Constraint {

    public $maintainerMessage = 'AttributeSpec with id=%id% can not be linked to a Principal';
    public $userMessage = 'AttributeSpec with id=%id% is not linked to this Principal';
    public $notFoundMessage = 'AttributeSpec with id=%id% could not be found';


    public function validatedBy() {
        return 'attribute_spec_by_user_and_id';
    }
}

?>