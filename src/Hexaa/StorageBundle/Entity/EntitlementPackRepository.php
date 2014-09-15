<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * EntitlementRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EntitlementPackRepository extends EntityRepository {

    public function findOneByToken($token) {
        $eps = $this->getEntityManager()->createQueryBuilder()
                ->select('ep')
                ->from('HexaaStorageBundle:EntitlementPack', 'ep')
                ->where('ep.tokens IS NOT NULL')
                ->getQuery()
                ->getResult()
        ;
        
        $retep = null;
        
        foreach ($eps as $ep){
            if ($ep->hasToken($token)){
                $retep = $ep;
            }
        }
        return $retep;
    }

}
