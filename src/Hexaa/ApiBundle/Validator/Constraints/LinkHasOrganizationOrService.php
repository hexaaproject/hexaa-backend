<?php
/**
 * Created by PhpStorm.
 * User: solazs
 * Date: 2016.11.02.
 * Time: 10:58
 */

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class LinkHasOrganizationOrService extends Constraint
{

    public $violationMessage = 'The link has to have either an organization or a service';

    public function validatedBy()
    {
        return 'link_has_organization_or_service';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

}
