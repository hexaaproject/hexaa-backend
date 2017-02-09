<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EntitlementCanBeAddedToRoleValidator extends ConstraintValidator
{

    protected $em;
    protected $securityContext;

    public function __construct($em, $securityContext)
    {
        $this->em = $em;
        $this->securityContext = $securityContext;
    }

    public function validate($r, Constraint $constraint)
    {
        foreach ($r->getEntitlements() as $e) {
            if (!$e) {
                $this->context->buildViolation($constraint->entitlementNotFoundMessage)
                  ->addViolation();
            } else {
                /** @var Collection $es */
                $es = $this->em->getRepository('HexaaStorageBundle:Entitlement')->findAllByOrganization($r->getOrganization());
                if (!$es->contains($e)) {
                    $this->context->buildViolation($constraint->entitlementNotValidMessage)
                      ->setParameter("%entitlement%", $e->getName())
                      ->setParameter("%org%", $r->getOrganization()->getName())
                      ->setParameter("%role%", $r->getName())
                      ->addViolation();
                }
            }
        }
    }

}
