<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class EntitlementURI extends Constraint {

    public $notValidURIMessage = '%uri% is not a valid URI. This URI must start with: %uri_prefix%';
    public $entitlementNotFoundMessage = 'Entitlement was not found';

    public function validatedBy() {
        return 'entitlement_uri';
    }

    public function getTargets() {
        return self::CLASS_CONSTRAINT;
    }

}

?>