<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

/**
 * EntitlementRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EntitlementPackRepository extends EntityRepository {

    public function findOneByToken($token) {
        $ep = $this->getEntityManager()->createQueryBuilder()
            ->select('ep')
            ->from('HexaaStorageBundle:EntitlementPack', 'ep')
            ->leftJoin("ep.tokens", "tokens")
            ->where('tokens.token = :t')
            ->setParameters(array(":t" => $token))
            ->getQuery()
            ->getOneOrNullResult();

        return $ep;
    }

    public function findAllByRelatedPrincipal(Principal $p, $limit = null, $offset = 0) {
        $eps = $this->getEntityManager()->createQueryBuilder()
            ->select('ep')
            ->from('HexaaStorageBundle:EntitlementPack', 'ep')
            ->innerJoin('HexaaStorageBundle:OrganizationEntitlementPack', 'oep', 'WITH', 'oep.entitlementPack = ep')
            ->leftJoin('oep.organization', 'o')
            ->leftJoin("ep.service", "s")
            ->where(':p MEMBER OF o.principals ')
            ->andWhere("oep.status='accepted'")
            ->andWhere("s.isEnabled=true")
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy("ep.name", "ASC")
            ->setParameters(array(":p" => $p))
            ->getQuery()
            ->getResult();

        return $eps;
    }

}
