<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EntitlementCanBeAddedToRoleValidator extends ConstraintValidator {

    protected $em;
    protected $securityContext;

    public function __construct($em, $securityContext) {
        $this->em = $em;
        $this->securityContext = $securityContext;
    }

    public function validate($r, Constraint $constraint) {
        foreach($r->getEntitlements() as $e) {
            if (!$e) {
                $this->context->addViolation($constraint->entitlementNotFoundMessage);
            } else {
                $es = $this->em->getRepository('HexaaStorageBundle:Entitlement')->findAllByOrganization($r->getOrganization());
                if (!in_array($e, $es, true)) {
                    $this->context->addViolation($constraint->entitlementNotValidMessage, array(
                        "%entitlement%" => $e->getName(),
                        "%org%"         => $r->getOrganization()->getName(),
                        "%role%"        => $r->getName()));
                }
            }
        }
    }

}
