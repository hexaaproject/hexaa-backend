<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class InvitationHasValidTarget extends Constraint {

    public $numberViolationMessage = 'Exactly one of organization_id or service_id must be defined.';
    public $roleNoOrganizationViolationMessage = 'No organization specified';
    public $roleBadOrganizationViolationMessage = 'Role %role% is not in the organization %organization%';
    public $serviceManagerViolationMessage = 'You must be a manager of %service% to invite members';
    public $organizationManagerViolationMessage = 'You must be a manager of %organization% to invite members';

    public function validatedBy() {
        return 'invitation_has_valid_target';
    }

    public function getTargets() {
        return self::CLASS_CONSTRAINT;
    }

}

?>