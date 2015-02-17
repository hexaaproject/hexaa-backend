<?php
/**
 * Created by PhpStorm.
 * User: baloo
 * Date: 2/4/15
 * Time: 10:37 AM
 */

namespace Hexaa\ApiBundle\Hook\ExpireHook;


use Doctrine\ORM\EntityManager;
use Monolog\Logger;

class ExpirePrincipalsHook extends ExpireHook {
    protected $principalExpirationLimit;

    public function __construct(EntityManager $entityManager, Logger $modlog, Logger $errorlog, $principalExpirationLimit) {
        $this->principalExpirationLimit = $principalExpirationLimit;
        parent::__construct($entityManager, $modlog, $errorlog);
    }

    public function runHook()
    {

        if ($this->principalExpirationLimit >= 0 ){
            $date = new \DateTime('now');
            date_timezone_set($date, new \DateTimeZone("UTC"));
            if ($this->principalExpirationLimit == 1){
                $date->modify("-".$this->principalExpirationLimit." day");
            } else {
                $date->modify("-".$this->principalExpirationLimit." days");
            }

            $principals = $this->em->createQueryBuilder()
                ->select("p")
                ->from('HexaaStorageBundle:Principal', 'p')
                ->leftJoin('p.token', 'token')
                ->where("token.updatedAt <= :date")
                ->setParameters(array(":date" => $date))
                ->getQuery()
                ->getResult()
                ;

            $fedids = array();
            foreach($principals as $principal) {
                $fedids[] = $principal->getFedid();
                $this->em->remove($principal);
            }

            $this->modlog->info("[ExpirePrincipalsHook] Removed the following principals because they have not logged in for a long time: " . implode(" ", $fedids));

            $this->em->flush();

        }
    }
}