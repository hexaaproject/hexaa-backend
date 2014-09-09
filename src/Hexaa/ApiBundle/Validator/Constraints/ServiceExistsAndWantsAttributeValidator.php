<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ServiceExistsAndWantsAttributeValidator extends ConstraintValidator {

    protected $em;
    protected $securityContext;

    public function __construct($em, $securityContext) {
        $this->em = $em;
        $this->securityContext = $securityContext;
    }

    public function validate($avp, Constraint $constraint) {

        // Check if AttributeSpec and Service exists, throw error otherwise
        $ss = $avp->getServices();
        $as = $avp->getAttributeSpec();

        if (!$avp->getAttributeSpec()) {
            $this->context->addViolation($constraint->attributeSpecNotFoundMessage);
            $this->context->addViolationAt('attribute_spec',$constraint->attributeSpecNotFoundMessage);
        } else {
            foreach ($ss as $s) {
                if ($s) {
                    $sas = $this->em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findBy(array(
                        "service" => $s,
                        "attributeSpec" => $as
                    ));
                    if (!$sas) {
                        $sas = $this->em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findBy(array(
                            "isPublic" => true,
                            "attributeSpec" => $as
                        ));
                        if (!$sas) {
                            $this->context->addViolation($constraint->notWantedMessage, array("%sid%" => $s->getId(), "%asid%" => $as->getId()));
                        }
                    }
                } else {
                    if (!$avp->getService()) {
                        $this->context->addViolation($constraint->serviceNotFoundMessage);
                        $this->context->addViolationAt('service',$constraint->serviceNotFoundMessage);
                    }
                }
            }
        }
    }

}
