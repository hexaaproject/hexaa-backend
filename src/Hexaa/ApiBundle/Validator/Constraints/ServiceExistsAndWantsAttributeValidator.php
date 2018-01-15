<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Doctrine\ORM\EntityManager;
use Hexaa\StorageBundle\Entity\AttributeValueOrganization;
use Hexaa\StorageBundle\Entity\AttributeValuePrincipal;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ServiceExistsAndWantsAttributeValidator extends ConstraintValidator
{
    /* @var $em EntityManager */
    protected $em;
    protected $securityContext;

    public function __construct($em, $securityContext)
    {
        $this->em = $em;
        $this->securityContext = $securityContext;
    }

    public function validate($av, Constraint $constraint)
    {

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
                $multipleValues = false;
                if ($as->getMaintainer() === 'user') {
                    if (!($av instanceof AttributeValuePrincipal)) {
                        $this->context->buildViolation($constraint->attributeSpecMaintainerMismatchOrganization)
                          ->addViolation();
                        $this->context->buildViolation($constraint->attributeSpecMaintainerMismatchOrganization)
                          ->atPath('maintainer')
                          ->addViolation();
                    } else {
                        /** @noinspection PhpUnhandledExceptionInspection */
                        $attributeValueCount = $this->em->createQueryBuilder()
                          ->select("count(avp.id)")
                          ->from("HexaaStorageBundle:AttributeValuePrincipal", "avp")
                          ->join("avp.attributeSpec", "attribute_spec")
                          ->where('attribute_spec = :a')
                          ->andWhere('avp.principal = :p')
                          ->setParameters(array(":p" => $av->getPrincipal(), ":a" => $as))
                          ->getQuery()
                          ->getSingleScalarResult();
                        if ($attributeValueCount === 1) {
                            /** @noinspection PhpUnhandledExceptionInspection
                             * @var $conflictingValue AttributeValuePrincipal
                             */
                            $conflictingValue = $this->em->createQueryBuilder()
                              ->select("avp")
                              ->from("HexaaStorageBundle:AttributeValuePrincipal", "avp")
                              ->join("avp.attributeSpec", "attribute_spec")
                              ->where('attribute_spec = :a')
                              ->andWhere('avp.principal = :p')
                              ->setParameters(array(":p" => $av->getPrincipal(), ":a" => $as))
                              ->getQuery()
                              ->getSingleResult();
                            if (!$av->getId()) {
                                $multipleValues = true;
                            } else {
                                $multipleValues = ($conflictingValue->getId() !== $av->getId());
                            }
                        }
                    }
                } else {
                    if (!($av instanceof AttributeValueOrganization)) {
                        $this->context->buildViolation($constraint->attributeSpecMaintainerMismatchPrincipal)
                          ->addViolation();
                        $this->context->buildViolation($constraint->attributeSpecMaintainerMismatchPrincipal)
                          ->atPath('maintainer')
                          ->addViolation();
                    } else {
                        /** @noinspection PhpUnhandledExceptionInspection */
                        $attributeValueCount = $this->em->createQueryBuilder()
                          ->select("count(avo.id)")
                          ->from("HexaaStorageBundle:AttributeValueOrganization", "avo")
                          ->join("avo.attributeSpec", "attribute_spec")
                          ->where('attribute_spec = :a')
                          ->andWhere('avo.organization = :o')
                          ->setParameters(array(":o" => $av->getOrganization(), ":a" => $as))
                          ->getQuery()
                          ->getSingleScalarResult();
                        if ($attributeValueCount === 1) {
                            /** @noinspection PhpUnhandledExceptionInspection
                             * @var $conflictingValue AttributeValueOrganization
                             */
                            $conflictingValue = $this->em->createQueryBuilder()
                              ->select("avo")
                              ->from("HexaaStorageBundle:AttributeValueOrganization", "avo")
                              ->join("avo.attributeSpec", "attribute_spec")
                              ->where('attribute_spec = :a')
                              ->andWhere('avo.organization = :o')
                              ->setParameters(array(":o" => $av->getOrganization(), ":a" => $as))
                              ->getQuery()
                              ->getSingleResult();
                            if (!$av->getId()) {
                                $multipleValues = true;
                            } else {
                                $multipleValues = ($conflictingValue->getId() !== $av->getId());
                            }
                        }
                    }
                }
                if ($attributeValueCount > 1 || $multipleValues) {
                    $this->context->buildViolation($constraint->attributeSpecIsSingleValueMessage)
                      ->addViolation();
                    $this->context->buildViolation($constraint->attributeSpecIsSingleValueMessage)
                      ->atPath('attribute_spec')
                      ->addViolation();
                }
            }

            foreach ($ss as $s) {
                if ($s) {
                    $sas = $this->em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findBy(
                      array(
                        "service"       => $s,
                        "attributeSpec" => $as,
                      )
                    );
                    if (!$sas) {
                        $sas = $this->em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findBy(
                          array(
                            "isPublic"      => true,
                            "attributeSpec" => $as,
                          )
                        );
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
