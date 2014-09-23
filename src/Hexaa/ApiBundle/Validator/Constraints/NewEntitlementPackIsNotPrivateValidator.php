<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class NewEntitlementPackIsNotPrivateValidator extends ConstraintValidator {

    protected $em;
    protected $securityContext;

    public function __construct($em, $securityContext) {
        $this->em = $em;
        $this->securityContext = $securityContext;
    }

    public function validate($oeps, Constraint $constraint) {
        foreach ($oeps as $oep) {
            $ep = $oep->getEntitlementPack();

            $o = $oep->getOrganization();

            if (!$ep) {
                $this->context->addViolation($constraint->entitlementPackNotFoundMessage);
            } else {
                if ($ep->getType() == "private" && $oep->getStatus()=="pending"){
                    $this->context->addViolation($constraint->notPublicMessage, array("%ep%" => $ep->getScopedName(), "%org%" => $o->getName()));
                }
            }
        }
    }

}
