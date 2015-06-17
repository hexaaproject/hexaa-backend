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
            $this->context->buildViolation($constraint->attributeSpecNotFoundMessage)
                ->addViolation();
            $this->context->buildViolation($constraint->attributeSpecNotFoundMessage)
                ->atPath('attribute_spec')
                ->addViolation();
        } else {
            if (!$as->getIsMultivalue()) {
                if ($as->getMaintainer() == "user") {
                    $avs = $this->em->createQueryBuilder()
                        ->select("avp")
                        ->from("HexaaStorageBundle:AttributeValuePrincipal", "avp")
                        ->where('avp.attributeSpec = :a')
                        ->andWhere('avp.principal = :p')
                        ->andWhere('avp != :av')
                        ->setParameters(array(":p" => $av->getPrincipal(), ":a" => $as, ":av" => $av))
                        ->getQuery()
                        ->getOneOrNullResult();
                } else {
                    $avs = $this->em->createQueryBuilder()
                        ->select("avo")
                        ->from("HexaaStorageBundle:AttributeValueOrganization", "avo")
                        ->where('avo.attributeSpec = :a')
                        ->andWhere('avo.organization = :o')
                        ->andWhere('avo != :av')
                        ->setParameters(array(":o" => $av->getOrganization(), ":a" => $as, ":av" => $av))
                        ->getQuery()
                        ->getOneOrNullResult();
                }
                if ($avs != null) {
                    $this->context->buildViolation($constraint->attributeSpecIsSingleValueMessage)
                        ->addViolation();
                    $this->context->buildViolation($constraint->attributeSpecIsSingleValueMessage)
                        ->atPath('attribute_spec')
                        ->addViolation();
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
                            $this->context->buildViolation($constraint->notWantedMessage)
                                ->setParameter("%sid%", $s->getId())
                                ->setParameter("%asid%", $as->getId())
                                ->addViolation();
                        }
                    }
                } else {
                    $this->context->buildViolation($constraint->serviceNotFoundMessage)
                        ->addViolation();
                    $this->context->buildViolation($constraint->serviceNotFoundMessage)
                        ->atPath('service')
                        ->addViolation();
                }
            }
        }
    }

}
