<?php

namespace Hexaa\ApiBundle\Controller;



use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use FOS\RestBundle\Routing\ClassResourceInterface;

use FOS\RestBundle\Util\Codes;

use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\RouteRedirectView;

use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Hexaa\StorageBundle\Form\EntitlementType;
use Hexaa\StorageBundle\Entity\Entitlement;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class EntitlementpackEntitlementController extends FOSRestController {

    
    /**
     * get entitlements of entitlement pack
     *
     *
     * @ApiDoc(
     *   section = "EntitlementPack",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement pack id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement
     *
     * @return Entitlement
     */
    public function cgetEntitlementsAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
	$ep = $em->getRepository('HexaaStorageBundle:EntitlementPack')->find($id);
        if (!$ep) throw new HttpException(404, "Resource not found.");
	$e = $ep->getEntitlements();
	return $e;
    }
    
    /**
     * remove entitlement from entitlement pack
     *
     *
     * @ApiDoc(
     *   section = "EntitlementPack",
     *   resource = true,
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement pack id"},
     *      {"name"="eid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement
     *
     */
    public function deleteEntitlementAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $eid)
    {
	$em = $this->getDoctrine()->getManager();
	$ep = $em->getRepository('HexaaStorageBundle:EntitlementPack')->find($id);
        $s = $ep->getService();
        $usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        if (!in_array($p->getFedid(),$this->container->getParameter('hexaa_admins')) && !$s->hasManager($p)){
            throw new HttpException(403, "Forbidden");
            return ;
        }
	$e = $em->getRepository('HexaaStorageBundle:Entitlement')->find($eid);
	if (!$e) throw new HttpException(404, "Resource not found.");
	if ($ep->hasEntitlement($e)){
	  $ep->removeEntitlement($e);
	  $em->persist($ep);
	  $em->flush();
	}
    }
    
    /**
     * add entitlement to entitlement pack
     *
     *
     * @ApiDoc(
     *   section = "EntitlementPack",
     *   resource = true,
     *   statusCodes = {
     *     201 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement pack id"},
     *      {"name"="eid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View(statusCode=201)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement
     *
     */
    public function putEntitlementAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $eid)
    {
	$em = $this->getDoctrine()->getManager();
	$ep = $em->getRepository('HexaaStorageBundle:EntitlementPack')->find($id);
        $s = $ep->getService();
        $usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        if (!in_array($p->getFedid(),$this->container->getParameter('hexaa_admins')) && !$s->hasManager($p)){
            throw new HttpException(403, "Forbidden");
            return ;
        }
	$e = $em->getRepository('HexaaStorageBundle:Entitlement')->find($eid);
	if (!$e) throw new HttpException(404, "Resource not found.");
	if (!$ep->hasEntitlement($e)){
	  $ep->addEntitlement($e);
	  $em->persist($ep);
	  $em->flush();
	}
    }
    
}
