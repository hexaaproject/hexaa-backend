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

use Hexaa\StorageBundle\Form\OrganizationType;
use Hexaa\StorageBundle\Entity\Organization;
use Hexaa\StorageBundle\Entity\OrganizationPage;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class OrganizationController extends FOSRestController implements ClassResourceInterface {

/**
     * list organization preferences
     *
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     204 = "Returned when no organization is connected to the user",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
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
    public function cgetAction(Request $request, ParamFetcherInterface $paramFetcher)
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
	if (count($reto)<1) throw new HttpException(204, "No organization is linked to the user");
	return $reto;
    }
    
    /**
     * get organization preferences
     *
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when organization is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
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
    public function getAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	$o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
	if (!$o) {
	  throw new HttpException(404, "Resource not found.");
	  return;
	}
	if (!$o->hasPrincipal($p)){
	  throw new HttpException(403, "Forbidden");
	  return ;
	}
	return $o;
    }
    
    /**
     * list organization preferences for gui
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when organization is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
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
     * @return OrganizationPage
     */
    public function getPageAction(Request $request, ParamFetcherInterface $paramFetcher, $id){
	$em = $this->getDoctrine()->getManager();
	$o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
	if (!$o) throw new HttpException(404, "Resource not found.");
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	if (!$o->hasPrincipal($p)) {
	  throw new HttpException(403, "Forbidden");
	  return ;
	} 
	$op = new OrganizationPage();
	$op->setOrganization($o);
	$ms = $o->getManagers();
	foreach($ms as $m){
	    $op->addManager($m);
	}
	$ms = $o->getPrincipals();
	foreach($ms as $m){
	    $op->addPrincipal($m);
	}
	$rs = $em->getRepository("HexaaStorageBundle:Role")->findByOrganization($o);
	foreach($rs as $r){
	    $op->addRole($r);
	}
	
	$oeps = $em->getRepository("HexaaStorageBundle:OrganizationEntitlementPack")->findByOrganization($o);
	
	$es = array();
	foreach($oeps as $oep){
	    $tmpes = $oep->getEntitlementPack()->getEntitlements();
	    foreach($tmpes as $tmpe){
		if (!in_array($tmpe, $es)){
		    array_push($es, $tmpe);
		}
	    }
	    $op->addConnectedEntitlementPack($oep);
	}	
	
	foreach($es as $e){	
	    $op->addEntitlement($e);
	}
	return $op;	
    }
    
    private function processForm(Organization $o)
    {
	$em = $this->getDoctrine()->getManager();
        $statusCode = $o->getId()==null ? 201 : 204;

        $form = $this->createForm(new OrganizationType(), $o);
        $form->bind($this->getRequest());

        if ($form->isValid()) {
	    if (201 === $statusCode) {
              $usr= $this->get('security.context')->getToken()->getUser();
              $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	      $o->setCreatedAt(new \DateTime());
              $o->addManager($p);
              $o->addPrincipal($p);
	    }
            $em->persist($o);
            $em->flush();

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location',
                    $this->generateUrl(
                        'get_organization', array('id' => $o->getId()),
                        true // absolute
                    )
                );
            }

            return $response;
        }

        return View::create($form, 400);
    }

    
    /**
     * create new organization
     *
     *
     * @ApiDoc(
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when organization has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when organization is not found"
     *   },
     *   requirements = {
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="name","dataType"="string","required"=true,"description"="displayable name of the organization"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"}
     *   }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher organization
     *
     * 
     */
    public function postAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
	/*$em = $this->getDoctrine()->getManager();
	$s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
	if (!$s) throw new HttpException(404, "Resource not found.");*/
	
	return $this->processForm(new Organization());
    }
    
    /**
     * edit organization preferences
     *
     *
     * @ApiDoc(
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when organization has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when organization is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   parameters = {
     *      {"name"="name","dataType"="string","required"=true,"description"="displayable name of the organization"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"}
     *   }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher organization
     *
     * 
     */
    public function putAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
	$o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
	if (!$o) throw new HttpException(404, "Resource not found.");
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	if (!$o->hasManager($p)) throw new HttpException(403, "Forbidden");
	return $this->processForm($o);
    }
    
    /**
     * delete organization
     *
     *
     * @ApiDoc(
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when organization has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when organization is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher organization
     *
     * 
     */
    public function deleteAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
	$o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
	if (!$o) throw new HttpException(404, "Resource not found.");
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	if (!$o->hasManager($p)) {
	  throw new HttpException(403, "Forbidden");
	} else {
	  $em->remove($o);
	  $em->flush();
	}
    }
}
