<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PrincipalCanBeAddedToRoleValidator extends ConstraintValidator {

    protected $em;
    protected $securityContext;

    public function __construct($em, $securityContext) {
        $this->em = $em;
        $this->securityContext = $securityContext;
    }

    public function validate($rps, Constraint $constraint) {
        foreach($rps as $rp) {
            $p = $rp->getPrincipal();

            $r = $rp->getRole();

            if (!$p) {
                $this->context->buildViolation($constraint->principalNotFoundMessage)
                    ->addViolation();
            } else {
                if (!$r->getOrganization()->hasPrincipal($p)) {
                    $this->context->buildViolation($constraint->notMemberMessage)
                        ->setParameter('%fedid%', $p->getFedid())
                        ->setParameter('%role%', $r->getName())
                        ->setParameter("%org%", $r->getOrganization()->getName())
                        ->addViolation();
                }
            }
        }
    }

}
