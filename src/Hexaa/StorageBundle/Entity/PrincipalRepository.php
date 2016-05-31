<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * PrincipalRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PrincipalRepository extends EntityRepository
{

    public function findOneByPersonalToken($pt)
    {
        $p = $this->getEntityManager()->createQueryBuilder()
            ->select('p')
            ->from('HexaaStorageBundle:Principal', 'p')
            ->leftJoin('p.token', 'pt')
            ->where('pt.token = :pt')
            ->setParameters(array(':pt' => $pt))
            ->getQuery()
            ->getOneOrNullResult();

        return $p;
    }

    public function findAllByRelatedService(Service $s)
    {
        $ps = $this->getEntityManager()->createQueryBuilder()
            ->select('p')
            ->from('HexaaStorageBundle:Principal', 'p')
            ->innerJoin('HexaaStorageBundle:RolePrincipal', 'rp', 'WITH', 'rp.principal = p')
            ->innerJoin('rp.role', 'r')
            ->innerJoin('r.entitlements', 'e')
            ->innerJoin('e.service', 's')
            ->where('s = :s')
            ->setParameters(array(":s" => $s))
            ->getQuery()
            ->getResult();

        return $ps;
    }

    public function findAllByRelatedServiceIds(array $sIds)
    {
        $ps = $this->getEntityManager()->createQueryBuilder()
            ->select('p')
            ->from('HexaaStorageBundle:Principal', 'p')
            ->innerJoin('HexaaStorageBundle:RolePrincipal', 'rp', 'WITH', 'rp.principal = p')
            ->innerJoin('rp.role', 'r')
            ->innerJoin('r.entitlements', 'e')
            ->innerJoin('e.service', 's')
            ->where('s.id in (:sids)')
            ->setParameters(array(":sids" => $sIds))
            ->getQuery()
            ->getResult();

        return $ps;
    }

    public function getIdsByEntitlement(Entitlement $e)
    {
        $ids = $this->getEntityManager()->createQueryBuilder()
            ->select('p.id')
            ->from('HexaaStorageBundle:Principal', 'p')
            ->innerJoin('HexaaStorageBundle:RolePrincipal', 'rp', "WITH", "p = rp.principal")
            ->innerJoin('HexaaStorageBundle:Role', 'r')
            ->where(":e MEMBER OF r.entitlements")
            ->setParameter(':e', $e)
            ->getQuery()
            ->getScalarResult();
        $retarr = array();
        foreach ($ids as $id) {
            $retarr[] = $id['id'];
        }

        return $retarr;
    }

    public function getIdsByOrganization(Organization $o)
    {
        $ids = $this->getEntityManager()->createQueryBuilder()
            ->select('p.id')
            ->from('HexaaStorageBundle:Principal', 'p')
            ->innerJoin("HexaaStorageBundle:Organization", 'o')
            ->where('p MEMBER OF :o')
            ->setParameter(':o', $o)
            ->getQuery()
            ->getScalarResult();
        $pids = array();
        foreach ($ids as $hookPid) {
            $pids[] = $hookPid['id'];
        }

        return $pids;
    }
}
