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
            $ss = $this->em->getRepository('HexaaStorageBundle:Service')->findAll();
            $os = $this->em->getRepository('HexaaStorageBundle:Organization')->findAll();

            // Collect Organizations where user is a member
            $psos = array();
            foreach ($os as $o) {
                if ($o->hasPrincipal($p)) {
                    $psos[] = $o;
                }
            }

            // Collect connected entitlement packs
            $eps = array();
            foreach ($psos as $o) {
                $oeps = $this->em->getRepository('HexaaStorageBundle:OrganizationEntitlementPack')->findByOrganization($o);
                foreach ($oeps as $oep) {
                    $ep = $oep->getEntitlementPack();
                    if ($oep->getStatus() == "accepted" && !in_array($ep, $eps, true)) {
                        $eps[] = $ep;
                    }
                }
            }

            // Collect connected services
            $css = array();
            foreach ($eps as $ep) {
                $s = $ep->getService();
                if (!in_array($s, $css, true)) {
                    $css[] = $s;
                }
            }


            $ss = array_filter($ss);

            $ass = array();
            foreach ($ss as $s) {
                $sass = $this->em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findByService($s);
                if (in_array($s, $css, true)) {
                    foreach ($sass as $sas) {
                        if (!in_array($sas->getAttributeSpec(), $ass, true)) {
                            if ($sas->getAttributeSpec()->getMaintainer() == "user") {
                                $ass[] = $sas->getAttributeSpec();
                            }
                        }
                    }
                }
            }

            $sass = $this->em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findByIsPublic(true);
            foreach ($sass as $sas) {
                if ((!in_array($sas->getAttributeSpec(), $ass, true)) && ($sas->getIsPublic() == true)) {
                    if ($sas->getAttributeSpec()->getMaintainer() == "user") {
                        $ass[] = $sas->getAttributeSpec();
                    }
                }
            }
            
            //So we've got the user's attributeSpecs
            $ass = array_filter($ass);
            
            if (!in_array($as, $ass, true)){
                $this->context->addViolation($constraint->userMessage, array("%id%" => $value->getId()));
            }
        }
    }

}
