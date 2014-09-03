<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * AttributeSpecRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AttributeSpecRepository extends EntityRepository
{
    public function findAllByPrincipal(Principal $p, $limit = null, $offset = 0)
    {
        $ass = $this->getEntityManager()->createQueryBuilder()
                ->select('attrspec')
                ->from('HexaaStorageBundle:AttributeSpec', 'attrspec')
                ->innerJoin('HexaaStorageBundle:ServiceAttributeSpec', 'sas', 'WITH', 'sas.attributeSpec = attrspec')
                ->innerJoin('sas.service', 's')
                ->innerJoin('HexaaStorageBundle:EntitlementPack', 'ep', 'WITH', 'ep.service = s')
                ->innerJoin('HexaaStorageBundle:OrganizationEntitlementPack', 'oep', 'WITH', 'oep.entitlementPack=ep')
                ->innerJoin('oep.organization', 'o')
                ->where(':p MEMBER OF o.principals')
                ->andWhere("oep.status = 'accepted'")
                ->andWhere("attrspec.maintainer = 'user'")
                ->setParameters(array("p" => $p))
                ->getQuery()
                ->getResult()
        ;

        // Add public attribute specifications
        $sass = $this->getEntityManager()->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findByIsPublic(true);
        foreach ($sass as $sas) {
            if ((!in_array($sas->getAttributeSpec(), $ass, true))) {
                if ($sas->getAttributeSpec()->getMaintainer() == "user") {
                    $ass[] = $sas->getAttributeSpec();
                }
            }
        }
        
        $ass = array_filter($ass);
        $ass = array_slice($ass, $offset, $limit);
        return $ass;
    }
    
    public function findAllByOrganization(Organization $o, $limit = null, $offset = 0)
    {
        
        $retarr = $this->getEntityManager()->createQueryBuilder()
                ->select('attrspec')
                ->from('HexaaStorageBundle:AttributeSpec', 'attrspec')
                ->innerJoin('HexaaStorageBundle:ServiceAttributeSpec', 'sas', 'WITH', 'sas.attributeSpec = attrspec')
                ->innerJoin('sas.service', 's')
                ->innerJoin('HexaaStorageBundle:EntitlementPack', 'ep', 'WITH', 'ep.service = s')
                ->innerJoin('HexaaStorageBundle:OrganizationEntitlementPack', 'oep', 'WITH', 'oep.entitlementPack=ep')
                ->innerJoin('oep.organization', 'o')
                ->where('o = :o')
                ->andWhere("oep.status = 'accepted'")
                ->andWhere("attrspec.maintainer = 'manager'")
                ->setParameters(array("o" => $o))
                ->getQuery()
                ->getResult()
        ;
        $sass = $this->getEntityManager()->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findByIsPublic(true);
        foreach ($sass as $sas) {
            if (!in_array($sas->getAttributeSpec(), $retarr, true)) {
                if ($sas->getAttributeSpec()->getMaintainer() == "manager") {
                    $retarr[] = $sas->getAttributeSpec();
                }
            }
        }
        $retarr = array_filter($retarr);
        $retarr = array_slice($retarr, $offset, $limit);
        return $retarr;
    }
}
