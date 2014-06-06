<?php

namespace Hexaa\ApiBundle\Controller;


use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Hexaa\StorageBundle\Entity\Principal;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SspController extends FOSRestController
{

    /**
     * @Rest\View()
     */
    public function getAttributesAction(Request $request)
    {
	$soid = urldecode($request->get('soid'));
        $fedid = urldecode($request->get('fedid'));
    
	$attrs = array();
	$retarr = array();
	$now = new \DateTime();
        $em = $this->container->get('doctrine')->getManager();
        
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid(urldecode($fedid));
        $s = $em->getRepository("HexaaStorageBundle:Service")->findOneByEntityid($soid);
        
        // Get the attributes required by the Service
        $savps = $em->getRepository('HexaaStorageBundle:ServiceAttributeValuePrincipal')->findBy(array('service' => $s, 'isAllowed' => true));
        $ids = array();
        foreach($savps as $savp)
        {
	  $id = $savp->getAttributeValuePrincipal()->getId();
          if(!in_array($id, $ids, true)){
	    array_push($ids, $id);
	  }
        }
        
        // Get the values by principal
        $avps = $em->createQuery('SELECT attvalp FROM HexaaStorageBundle:AttributeValuePrincipal attvalp WHERE attvalp.principal=(:p) AND attvalp.id in (:ids)')
	  ->setParameters(array('ids' => $ids, 'p' => $p))->getResult();
        
        // Place the attributes in the return array
        foreach($avps as $avp){
	  $retarr[$avp->getAttributeSpec()->getOid()] = array();
        }
        
        foreach($avps as $avp){
	  array_push($retarr[$avp->getAttributeSpec()->getOid()],$avp->getValue());
        }
        
        // Get the values by organization
        $avos = $em->getRepository('HexaaStorageBundle:AttributeValueOrganization')->findAll();
        foreach($avos as $avo){
	  if ($avo->hasService($s)){
	    if (!array_key_exists($avo->getAttributeSpec()->getOid(), $retarr)){
	      $retarr[$avo->getAttributeSpec()->getOid()] = array();
	    }
	    array_push($retarr[$avo->getAttributeSpec()->getOid()],$avo->getValue());
	  }
        }
        
        // Collect the entitlements of the service
        $eps = $em->getRepository('HexaaStorageBundle:EntitlementPack')->findByService($s);
        $es = array();
        foreach($eps as $ep){
	  foreach($ep->getEntitlements() as $e)
	  {
	    if (!in_array($e, $es, true)){
	      array_push($es, $e);
	    }
	  }
        }
        // Collect roles of principal
        $rps = $em->getRepository('HexaaStorageBundle:RolePrincipal')->findByPrincipal($p);
        
        $retarr['eduPersonEntitlement'] = array();
        
        // Cross reference entitlements with roles
        foreach($rps as $rp){
	  foreach($es as $e){
	    if (($rp->getRole()->hasEntitlement($e)) && ($rp->getRole()->getStartDate()<$now) && ($rp->getRole()->getEndDate()>$now)){
	      if (!in_array($e->getUri(), $retarr['eduPersonEntitlement'])){
		array_push($retarr['eduPersonEntitlement'], $e->getUri());
	      }
	    }
	  }
        }
        
        $retarr['HexaaApiKey'] = $p->getToken();

        return $retarr;
    }
}
?>