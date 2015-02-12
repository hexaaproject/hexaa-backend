<?php
namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidScopedKey extends Constraint {
    public $message = '%scopedkey% is not a valid ScopedKey.';

    public function validatedBy() {
        return 'valid_scoped_key';
    }
}

?>