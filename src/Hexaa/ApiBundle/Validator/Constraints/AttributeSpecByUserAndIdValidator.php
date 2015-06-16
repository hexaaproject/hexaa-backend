<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class AttributeSpecByUserAndIdValidator extends ConstraintValidator {

    protected $em;
    protected $securityContext;

    public function __construct($em, $securityContext) {
        $this->em = $em;
        $this->securityContext = $securityContext;
    }

    public function validate($value, Constraint $constraint) {
        $as = $value;

        // Check if AttributeSpec exists
        if (!$as) {
            $this->context->buildViolation($constraint->notFoundMessage)
                ->addViolation();
        } else {

            // Check if it can be linked to a user
            if ($as->getMaintainer() != "user") {
                $this->context->buildViolation($constraint->maintainerMessage)
                    ->setParameter("%id%",$value->getId())
                    ->addViolation();
            }

            // Check if the user can see it (if it's linked to the user or public)
            $usr = $this->securityContext->getToken()->getUser();
            $p = $this->em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());

            $ass = $this->em->getRepository('HexaaStorageBundle:AttributeSpec')->findAllByPrincipal($p);

            if (!in_array($as, $ass, true)) {
                $this->context->buildViolation($constraint->userMessage)
                    ->setParameter("%id%",$value->getId())
                    ->addViolation();
            }
        }
    }

}
