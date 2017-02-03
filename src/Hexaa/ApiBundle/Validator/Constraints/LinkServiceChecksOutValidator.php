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


class LinkServiceChecksOutValidator extends ConstraintValidator
{

    /** @var $l Link */
    public function validate($l, Constraint $constraint)
    {
        if (!$l->getService()) {
            $sid = null;
        } else {
            $sid = $l->getService()->getId();
        }
        foreach ($l->getEntitlements() as $entitlement) {
            if ($entitlement->getService()->getId() != $sid) {
                $this->context->buildViolation($constraint->entitlementNotForServiceMessage)
                  ->setParameters(
                    array(
                      '%eid%' => $entitlement->getId(),
                      '%sid%' => $sid,
                    )
                  )
                  ->addViolation();
                $this->context->buildViolation($constraint->entitlementNotForServiceMessage)
                  ->atPath("entitlements")
                  ->setParameters(
                    array(
                      '%eid%' => $entitlement->getId(),
                      '%sid%' => $sid,
                    )
                  )
                  ->addViolation();
            }
        }
        foreach ($l->getEntitlementPacks() as $entitlementPack) {
            if ($entitlementPack->getService()->getId() != $sid) {
                $this->context->buildViolation($constraint->entitlementPackNotForServiceMessage)
                  ->setParameters(
                    array(
                      '%epid%' => $entitlementPack->getId(),
                      '%sid%'  => $sid,
                    )
                  )
                  ->addViolation();
                $this->context->buildViolation($constraint->entitlementPackNotForServiceMessage)
                  ->atPath("entitlement_packs")
                  ->setParameters(
                    array(
                      '%epid%' => $entitlementPack->getId(),
                      '%sid%'  => $sid,
                    )
                  )
                  ->addViolation();
            }
        }
    }

}

