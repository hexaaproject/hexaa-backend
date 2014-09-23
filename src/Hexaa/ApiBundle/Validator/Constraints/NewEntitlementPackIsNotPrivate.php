<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class NewEntitlementPackIsNotPrivate extends Constraint {

    public $notPublicMessage = '%ep% can not be added to organization %org%, because it is a private package. Use token linking!';
    public $entitlementPackNotFoundMessage = 'Non-existent EntitlementPack id given';


    public function validatedBy() {
        return 'new_entitlement_pack_is_not_private';
    }
}

?>