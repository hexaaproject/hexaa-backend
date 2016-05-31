<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class EntitlementCanBeAddedToRole extends Constraint
{

    public $entitlementNotValidMessage = '%entitlement% can not be added to Role %role%, because organization %org% does not have that entitlement.';
    public $entitlementNotFoundMessage = 'Non-existent Entitlement id given';


    public function validatedBy()
    {
        return 'entitlement_can_be_added_to_role';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

?>