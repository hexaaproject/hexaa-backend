<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class NewEntitlementPackIsEnabledAndNotPrivateValidator extends ConstraintValidator
{

    protected $em;
    protected $securityContext;

    public function __construct($em, $securityContext)
    {
        $this->em = $em;
        $this->securityContext = $securityContext;
    }

    public function validate($oeps, Constraint $constraint)
    {
        foreach ($oeps as $oep) {
            $ep = $oep->getEntitlementPack();

            $o = $oep->getOrganization();

            if (!$ep) {
                $this->context->buildViolation($constraint->entitlementPackNotFoundMessage)
                    ->addViolation();
            } else {
                if (!$ep->getService()->getIsEnabled()) {
                    $this->context->buildViolation($constraint->notEnabledMessage)
                        ->setParameter("%ep%", $ep->getScopedName())
                        ->setParameter("%s%", $ep->getService()->getName())
                        ->setParameter("%org%", $o->getName())
                        ->addViolation();
                }
            }
        }
    }

}
