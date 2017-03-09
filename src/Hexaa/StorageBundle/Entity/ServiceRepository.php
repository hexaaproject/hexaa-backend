<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * ServiceRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ServiceRepository extends EntityRepository
{

    public function findAllByRelatedPrincipal(Principal $p, $limit = null, $offset = 0)
    {
        $ss = $this->getEntityManager()->createQueryBuilder()
          ->select('s')
          ->from('HexaaStorageBundle:Service', 's')
          ->innerJoin('s.links', 'link')
          ->innerJoin('link.organization', 'o')
          ->where(':p MEMBER OF o.principals ')
          ->andWhere("link.status='accepted'")
          ->andWhere("s.isEnabled=true")
          ->setFirstResult($offset)
          ->setMaxResults($limit)
          ->orderBy("s.name", "ASC")
          ->setParameters(array(":p" => $p))
          ->getQuery()
          ->getResult();

        return $ss;
    }

    public function findAllIdsByRelatedPrincipal(Principal $p, $limit = null, $offset = 0)
    {
        $ss = $this->getEntityManager()->createQueryBuilder()
          ->select('s.id')
          ->from('HexaaStorageBundle:Service', 's')
          ->innerJoin('s.links', 'link')
          ->innerJoin('link.organization', 'o')
          ->where(':p MEMBER OF o.principals ')
          ->andWhere("link.status='accepted'")
          ->andWhere("s.isEnabled=true")
          ->setFirstResult($offset)
          ->setMaxResults($limit)
          ->orderBy("s.name", "ASC")
          ->setParameters(array(":p" => $p))
          ->getQuery()
          ->getScalarResult();
        $retarr = array();
        foreach ($ss as $s) {
            $retarr[] = $s['id'];
        }

        return $retarr;
    }

    public function findAllIdsByRelatedOrganization(Organization $o, $limit = null, $offset = 0)
    {
        $ss = $this->getEntityManager()->createQueryBuilder()
          ->select('s.id')
          ->from('HexaaStorageBundle:Service', 's')
          ->innerJoin('s.links', 'link')
          ->where('link.organization = :o')
          ->andWhere("link.status='accepted'")
          ->andWhere("s.isEnabled=true")
          ->setFirstResult($offset)
          ->setMaxResults($limit)
          ->setParameters(array(":o" => $o))
          ->getQuery()
          ->getScalarResult();
        $retarr = array();
        foreach ($ss as $s) {
            $retarr[] = $s['id'];
        }

        return $retarr;
    }

}
