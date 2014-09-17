<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ManagerIsOrganizationMember extends Constraint {

    public $notMemberMessage = '%fedid% can not be added as a manager, because he/she is not a member of the organization %org%';
    public $principalNotFoundMessage = 'Non-existent Principal id given';


    public function validatedBy() {
        return 'manager_is_organization_member';
    }

    public function getTargets() {
        return self::CLASS_CONSTRAINT;
    }
}

?>