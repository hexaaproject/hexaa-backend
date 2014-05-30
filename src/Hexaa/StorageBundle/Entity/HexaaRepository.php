<?php
namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Hexaa\StorageBundle\Entity\Service;
use Hexaa\StorageBundle\Entity\AttributeSpec;
use Hexaa\StorageBundle\Entity\ServiceAttributeSpec;

class HexaaRepository extends EntityRepository
{
    public function findByServiceAllAttributeSpec($s)
    {
	$em = $this->getEntityManager();
	$sass = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findByService($s);
	$ids = array();
	foreach($sass as $sas){
	  array_push($ids, $sas->getAttributeSpec()->getId());
	}
	
	$ass = $em->createQuery('SELECT attspec FROM HexaaStorageBundle:AttributeSpec attspec WHERE attspec.id in (:ids)')
	  ->setParameter('ids', $ids)->getResult();
	 
        return $ass;
    }
    
    public function findByAttributeSpecAllService($as){
	$em = $this->getEntityManager();
	$sass = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findByAttributeSpec($as);
	$ids = array();
	foreach($sass as $sas){
	  array_push($ids, $sas->getService()->getId());
	}
	
	$ss = $em->createQuery('SELECT service FROM HexaaStorageBundle:Service service WHERE service.id in (:ids)')
	  ->setParameter('ids', $ids)->getResult();
	 
        return $ss;
    }
}

?>