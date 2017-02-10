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
class LinkServiceChecksOut extends Constraint
{

    public $entitlementNotForServiceMessage = 'Entitlement with id %eid% does not belong to service with id %sid%.';
    public $entitlementPackNotForServiceMessage = 'Entitlement package with id %epid% does not belong to service with id %sid%.';

    public function validatedBy()
    {
        return 'link_service_checks_out';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

}
