<?php
namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidEntityid extends Constraint {
    public $message = '%entityid% is not a valid Entityid.';

    public function validatedBy() {
        return 'validentityid';
    }
}

?>