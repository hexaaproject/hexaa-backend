<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ServiceExistsAndWantsAttributeValidator extends ConstraintValidator {
    /* @var $em EntityManager */
    protected $em;
    protected $securityContext;

    public function __construct($em, $securityContext) {
        $this->em = $em;
        $this->securityContext = $securityContext;
    }

    public function validate($av, Constraint $constraint) {

        // Check if AttributeSpec and Service exists, throw error otherwise
        $ss = $av->getServices();
        /* @var $as \Hexaa\StorageBundle\Entity\AttributeSpec */
        $as = $av->getAttributeSpec();

        if (!$av->getAttributeSpec()) {
            $this->context->addViolation($constraint->attributeSpecNotFoundMessage);
            $this->context->addViolationAt('attribute_spec', $constraint->attributeSpecNotFoundMessage);
        } else {
            if (!$as->getIsMultivalue()) {
                if ($as->getMaintainer() == "user") {
                    $avs = $this->em->createQueryBuilder()
                        ->select("avp")
                        ->from("HexaaStorageBundle:AttributeValuePrincipal", "avp")
                        ->leftJoin("avp.attributeSpec", 'attribute_spec')
                        ->where('attribute_spec = :a')
                        ->andWhere('avp.principal = :p')
                        ->setParameters(array(":p" => $av->getPrincipal(), ":a" => $as))
                        ->getQuery()
                        ->getOneOrNullResult();
                } else {
                    $avs = $this->em->createQueryBuilder()
                        ->select("avo")
                        ->from("HexaaStorageBundle:AttributeValueOrganization", "avo")
                        ->leftJoin("avo.attributeSpec", 'attribute_spec')
                        ->where('attribute_spec = :a')
                        ->andWhere('avo.organization = :o')
                        ->setParameters(array(":o" => $av->getOrganization(), ":a" => $as))
                        ->getQuery()
                        ->getOneOrNullResult();
                }
                if ($avs != null) {
                    $this->context->addViolation($constraint->attributeSpecIsSingleValueMessage);
                    $this->context->addViolationAt('attribute_spec', $constraint->attributeSpecIsSingleValueMessage);
                }
            }

            foreach($ss as $s) {
                if ($s) {
                    $sas = $this->em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findBy(array(
                        "service"       => $s,
                        "attributeSpec" => $as
                    ));
                    if (!$sas) {
                        $sas = $this->em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findBy(array(
                            "isPublic"      => true,
                            "attributeSpec" => $as
                        ));
                        if (!$sas) {
                            $this->context->addViolation($constraint->notWantedMessage, array("%sid%" => $s->getId(), "%asid%" => $as->getId()));
                        }
                    }
                } else {
                    $this->context->addViolation($constraint->serviceNotFoundMessage);
                    $this->context->addViolationAt('service', $constraint->serviceNotFoundMessage);
                }
            }
        }
    }

}
