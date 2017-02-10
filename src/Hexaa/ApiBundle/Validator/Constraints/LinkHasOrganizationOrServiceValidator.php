<?php
/**
 * Created by PhpStorm.
 * User: solazs
 * Date: 2016.11.02.
 * Time: 11:13
 */

namespace Hexaa\ApiBundle\Validator\Constraints;

use Hexaa\StorageBundle\Entity\Link;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;


class LinkHasOrganizationOrServiceValidator extends ConstraintValidator
{

    /** @var $l Link */
    public function validate($l, Constraint $constraint)
    {
        if (!$l->getService() && !$l->getOrganization()) {
            $this->context->buildViolation($constraint->violationMessage)
              ->addViolation();
            $this->context->buildViolation($constraint->violationMessage)
              ->atPath('organization')
              ->addViolation();
            $this->context->buildViolation($constraint->violationMessage)
              ->atPath('service')
              ->addViolation();
        }
    }

}

