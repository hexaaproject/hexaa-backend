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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PrincipalController
 *
 * @author baloo
 */
class PrincipalController extends FOSRestController {
    /**
     * get info about current principal 
     *
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher organization
     *
     * @return Service
     */
    public function getPrincipalAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
        $em = $this->getDoctrine()->getManager();
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        return $p;
    }
    
    
    /**
     * TODO list available attribute specifications
     *
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher organization
     *
     * @return AttributeSpec
     */
    public function cgetPrincipalAttributespecsAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
	$em = $this->getDoctrine()->getManager();
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	$ss = $em->getRepository('HexaaStorageBundle:Service')->findAll();
        $os = $em->getRepository('HexaaStorageBundle:Organization')->findAll();
        
        // Collect Organizations where user is a memeber
        $psos = array();
        foreach ($os as $o) {
            if ($o->hasPrincipal($p) && !in_array($o, $os)){
                $psos[] = $o;
            }
        }
        
        // Collect connected entitlement packs
        $eps = array();
        foreach ($psos as $o){
            $oeps = $em->getRepository('HexaaStorageBundle:OrganizationEntitlementPack')->findByOrganization($o);
            foreach ($oeps as $oep) {
                $ep = $oep->getEntitlementPack();
                if ($oep->status == "accepted" && !in_array($ep,$eps)){
                    $eps[] = $ep;
                }
            }
        }
        
        // Collect connected services
        $css = array();
        foreach ($eps as $ep){
            $s = $ep->getService();
            if(!in_array($s, $css)){
                $css[] = $s;
            }
        }
       
        
        $ss = array_filter($ss);
	if (count($ss)<1) throw new HttpException(404, "Resource not found.");
        $retarr = array();
	foreach($ss as $s){
            $sass = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findByService($s);
            if (in_array($s, $css)) {
                foreach ($sass as $sas){
                    if (!in_array($sas, $retarr)){
                        $retarr[]=$sas;
                    }
                }
            } else {
                foreach ($sass as $sas){
                    if ((!in_array($sas, $retarr)) && ($sas->getIsPublic()==true)){
                        $retarr[]=$sas;
                    }
                }
            }
	}
        
        $retarr = array_filter($retarr);
	//if (count($retarr)<1) throw new HttpException(404, "Resource not found.");
	return $retarr;
    }
    
    /**
     * list all services where the user is a manager
     *
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher organization
     *
     * @return Service
     */
    public function cgetManagerServicesAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
	$em = $this->getDoctrine()->getManager();
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	$ss = $em->getRepository('HexaaStorageBundle:Service')->findAll();	
	$rets = array();
	foreach($ss as $s){
	  if ($s->hasManager($p)){
	    $rets[] = $s;
	  }
	}
        $rets = array_filter($rets);
	//if (count($rets)<1) throw new HttpException(404, "Resource not found.");
	return $rets;
    }
    
    /**
     * list all organizations where the user is a manager
     *
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher organization
     *
     * @return Organization
     */
    public function cgetManagerOrganizationsAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
	$em = $this->getDoctrine()->getManager();
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	$os = $em->getRepository('HexaaStorageBundle:Organization')->findAll();	
	$reto = array();
	foreach($os as $o){
	  if ($o->hasManager($p)){
	    $reto[] = $o;
	  }
	}
        $reto = array_filter($reto);
	//if (count($reto)<1) throw new HttpException(404, "Resource not found.");
	return $reto;
    }
    
    /**
     * list all organizations where the user is a member
     *
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher organization
     *
     * @return Organization
     */
    public function cgetPrincipalOrganizationsAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
	$em = $this->getDoctrine()->getManager();
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	$os = $em->getRepository('HexaaStorageBundle:Organization')->findAll();	
	$reto = array();
	foreach($os as $o){
	  if ($o->hasPrincipal($p)){
	    $reto[] = $o;
	  }
	}
        $reto = array_filter($reto);
	//if (count($reto)<1) throw new HttpException(404, "Resource not found.");
	return $reto;
    }
}
