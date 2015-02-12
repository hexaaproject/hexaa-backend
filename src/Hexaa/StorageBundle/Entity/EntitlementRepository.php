<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * EntitlementRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EntitlementRepository extends EntityRepository {

    public function findAllByOrganization(Organization $o, $limit = null, $offset = 0) {
        $es = $this->getEntityManager()->createQueryBuilder()
            ->select('e')
            ->from('HexaaStorageBundle:Entitlement', 'e')
            ->from('HexaaStorageBundle:OrganizationEntitlementPack', 'oep')
            ->innerJoin('oep.entitlementPack', 'ep')
            ->where('oep.organization = :o')
            ->andWhere('e MEMBER OF ep.entitlements')
            ->andWhere("oep.status = 'accepted'")
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->setParameters(array('o' => $o))
            ->orderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $es;
    }

    public function findAllByPrincipal(Principal $p, $limit = null, $offset = 0) {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('e')
            ->from('HexaaStorageBundle:Entitlement', 'e')
            ->from('HexaaStorageBundle:RolePrincipal', 'rp')
            ->innerJoin('rp.role', 'r')
            ->where('e MEMBER OF r.entitlements ')
            ->andWhere('rp.principal = :p')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->setParameters(array("p" => $p))
            ->orderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllByPrincipalAndService(Principal $p, Service $s, $limit = null, $offset = 0) {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('e')
            ->from('HexaaStorageBundle:Entitlement', 'e')
            ->from('HexaaStorageBundle:RolePrincipal', 'rp')
            ->innerJoin('rp.role', 'r')
            ->where('e MEMBER OF r.entitlements ')
            ->andWhere('rp.principal = :p')
            ->andWhere('e.service = :s')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->setParameters(array("p" => $p, "s" => $s))
            ->orderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

}
