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
            $this->context->addViolation($constraint->notFoundMessage);
        } else {

            // Check if it can be linked to a user
            if ($as->getMaintainer() != "user") {
                $this->context->addViolation($constraint->maintainerMessage, array("%id%" => $value->getId()));
            }

            // Check if the user can see it (if it's linked to the user or public)
            $usr = $this->securityContext->getToken()->getUser();
            $p = $this->em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());

            $ass = $this->em->getRepository('HexaaStorageBundle:AttributeSpec')->findAllByPrincipal($p);

            if (!in_array($as, $ass, true)) {
                $this->context->addViolation($constraint->userMessage, array("%id%" => $value->getId()));
            }
        }
    }

}
