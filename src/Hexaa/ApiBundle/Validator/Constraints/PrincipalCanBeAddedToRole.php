<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class PrincipalCanBeAddedToRole extends Constraint
{

    public $notMemberMessage = '%fedid% can not be added to Role %role%, because he/she is not a member of the organization %org%';
    public $principalNotFoundMessage = 'Non-existent Principal id given';


    public function validatedBy()
    {
        return 'principal_can_be_added_to_role';
    }
}

?>