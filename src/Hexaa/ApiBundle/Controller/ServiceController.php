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

use Hexaa\StorageBundle\Form\ServiceType;
use Hexaa\StorageBundle\Entity\Service;
use Hexaa\StorageBundle\Entity\ServicePage;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class ServiceController extends FOSRestController implements ClassResourceInterface {

/**
     * list service preferences
     *
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     204 = "Returned when no service is connected to the user",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return Service
     */
    public function cgetAction(Request $request, ParamFetcherInterface $paramFetcher)
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
	//if (count($rets)<1) throw new HttpException(204, "No service is connected to the user.");
	return $rets;
    }
    
    /**
     * get service preferences
     *
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return Service
     */
    public function getAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
	$s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
	if (!$s) throw new HttpException(404, "Resource not found.");
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	if (!$s->hasManager($p)) {
	  throw new HttpException(403, "Forbidden");
	  return ;
	} 
	return $s;
    }
    
    /**
     * list service preferences for gui
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return ServicePage
     */
    public function getPageAction(Request $request, ParamFetcherInterface $paramFetcher, $id){
	$em = $this->getDoctrine()->getManager();
	$s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
	if (!$s) throw new HttpException(404, "Resource not found.");
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	if (!$s->hasManager($p)) {
	  throw new HttpException(403, "Forbidden");
	  return ;
	} 
	$sp = new ServicePage();
	$sp->setService($s);
	$ms = $s->getManagers();
	foreach($ms as $m){
	    $sp->addManager($m);
	}
	$ass = $em->getRepository("HexaaStorageBundle:ServiceAttributeSpec")
	->findByService($s);
	foreach($ass as $as){
	    $sp->addAttributeSpecification($as);
	}
	
	$eps = $em->getRepository("HexaaStorageBundle:EntitlementPack")->findByService($s);
	
	$es = array();
	foreach($eps as $ep){
	    $tmpes = $ep->getEntitlements();
	    foreach($tmpes as $tmpe){
		if (!in_array($tmpe, $es)){
		    array_push($es, $tmpe);
		}
	    }
	    $sp->addEntitlementPack($ep);
	}	
	
	foreach($es as $e){	
	    $sp->addEntitlement($e);
	}
	return $sp;
    }
    
    private function processForm(Service $s)
    {
	$em = $this->getDoctrine()->getManager();
        $statusCode = $s->getId()==null ? 201 : 204;

        $form = $this->createForm(new ServiceType(), $s);
        $form->bind($this->getRequest());

        if ($form->isValid()) {
	    if (201 === $statusCode) {
                $usr= $this->get('security.context')->getToken()->getUser();
                $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
                $s->setCreatedAt(new \DateTime());
                $s->addManager($p);
	    }
	    $s->setUpdatedAt(new \DateTime());
            $em->persist($s);
            $em->flush();

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location',
                    $this->generateUrl(
                        'get_service', array('id' => $s->getId()),
                        true // absolute
                    )
                );
            }

            return $response;
        }

        return View::create($form, 400);
    }

    
    /**
     * create new service
     *
     *
     * @ApiDoc(
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when service has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     *   requirements = {
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *   {"name"="name", "dataType"="string", "required"=true, "description"="service name"},
     *   {"name"="entityid", "dataType"="string", "required"=true, "description"="service entity id"},
     *   {"name"="url", "dataType"="string", "required"=false, "description"="service url"},
     *   {"name"="description", "dataType"="string", "required"=false, "description"="service description"},
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * 
     */
    public function postAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
	/*$em = $this->getDoctrine()->getManager();
	$s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
	if (!$s) throw new HttpException(404, "Resource not found.");*/
	return $this->processForm(new Service());
    }
    
    /**
     * edit service preferences
     *
     *
     * @ApiDoc(
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when service has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *   {"name"="name", "dataType"="string", "required"=true, "description"="service name"},
     *   {"name"="entityid", "dataType"="string", "required"=true, "description"="service entity id"},
     *   {"name"="url", "dataType"="string", "required"=false, "description"="service url"},
     *   {"name"="description", "dataType"="string", "required"=false, "description"="service description"},
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * 
     */
    public function putAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
	$s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
	if (!$s) throw new HttpException(404, "Resource not found.");
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	if (!$s->hasManager($p)) {
	  throw new HttpException(403, "Forbidden");
	  return ;
	} 
	return $this->processForm($s);
    }
    
    /**
     * delete service
     *
     *
     * @ApiDoc(
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when service has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * 
     */
    public function deleteAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
	$s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
	if (!$s) throw new HttpException(404, "Resource not found.");
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	if (!$s->hasManager($p)) {
	  throw new HttpException(403, "Forbidden");
	  return ;
	} 
	$em->remove($s);
	$em->flush();
    }
}
