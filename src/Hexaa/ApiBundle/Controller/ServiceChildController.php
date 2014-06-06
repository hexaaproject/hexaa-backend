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

use Hexaa\StorageBundle\Form\EntitlementPackType;
use Hexaa\StorageBundle\Entity\EntitlementPack;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class ServiceChildController extends FOSRestController {

    
    /**
     * get managers of service
     *
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
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
     * @param ParamFetcherInterface $paramFetcher param fetcher 
     *
     * @return array
     */
    public function cgetManagersAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
	$s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
	$p = $s->getManagers();
	if (!$p) throw new HttpException(404, "Resource not found.");
	return $p;
    }
    
    /**
     * get Attribute specifications linked to the service
     *
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
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
     * @param ParamFetcherInterface $paramFetcher param fetcher 
     *
     * @return array
     */
    public function cgetAttributespecsAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
	$s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	if (!$p) throw new HttpException(404, "Resource not found.");
        if ($s->hasManager($p)) {
            $retarr = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findByService($s);
        } else {
            $sass = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findByService($s);
            $retarr = array();
            foreach ($sass as $sas){
                if ((!in_array($sas, $retarr)) && ($sas->getIsPublic()==true)){
                    $retarr[]=$sas;
                }
            }
        }
	return $retarr;
    }
    
    /**
     * remove manager from service
     *
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes = {
     *	   204 = "Returned on success",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="pid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="principal id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher 
     *
     */
    public function deleteManagerAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $pid)
    {
	$em = $this->getDoctrine()->getManager();
	$s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
	$p = $em->getRepository('HexaaStorageBundle:Principal')->find($pid);
	if (!$p) throw new HttpException(404, "Resource not found.");
	if ($s->hasManager($p)){
	  $s->removeManager($p);
	  $em->persist($s);
	  $em->flush();
	}
    }
    
    /**
     * add manager to service
     *
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes = {
     *	   201 = "Returned on success",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="pid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="principal id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View(statusCode=201)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher 
     *
     */
    public function putManagerAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $pid)
    {
	$em = $this->getDoctrine()->getManager();
	$s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
	$p = $em->getRepository('HexaaStorageBundle:Principal')->find($pid);
	if (!$p) throw new HttpException(404, "Resource not found.");
	if (!$s->hasManager($p)){
	  $s->addManager($p);
	  $em->persist($s);
	  $em->flush();
	}
    }
    
    /**
     * get entitlements of service
     *
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement pack is not found"
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
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement pack
     *
     * @return EntitlementPack
     */
    public function cgetEntitlementsAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
	$s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
	$eps = $em->getRepository('HexaaStorageBundle:EntitlementPack')->findByService($s);
	$es = array();
	foreach($eps as $ep){
	  foreach($ep->getEntitlements() as $e){
	    if (!in_array($e, $es)){
	      $es[] = $e;
	    }
	  }
	}
	if (!$es) throw new HttpException(404, "Resource not found.");
	return $es;
    }
    
    
    /**
     * get entitlement packs of service
     *
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement pack is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View(serializerGroups={"api"})
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement pack
     *
     * @return EntitlementPack
     */
    public function cgetEntitlementpacksAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
	$s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
	$ep = $em->getRepository('HexaaStorageBundle:EntitlementPack')->findByService($s);
	if (!$ep) throw new HttpException(404, "Resource not found.");
	return $ep;
    }
    
    /**
     * create new entitlement pack
     *
     *
     * @ApiDoc(
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when entitlement pack has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement pack is not found"
     *   },
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement pack
     *
     * 
     */
    public function postEntitlementpackAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	/*$em = $this->getDoctrine()->getManager();
	$s = $em->getRepository('HexaaStorageBundle:EntitlementPack')->find($id);
	if (!$s) throw new HttpException(404, "Resource not found.");*/
	return $this->processForm(new EntitlementPack(), $id);
    }
    
    private function processForm(EntitlementPack $ep, $id)
    {
	$em = $this->getDoctrine()->getManager();
	$s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
        $statusCode = $ep->getId()==null ? 201 : 204;

        $form = $this->createForm(new EntitlementPackType(), $ep);
        $form->bind($this->getRequest());

        if ($form->isValid()) {
	    if (201 === $statusCode) {
	      $ep->setService($s);
	    }
            $em->persist($ep);
            $em->flush();

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location',
                    $this->generateUrl(
                        'get_entitlementpack', array('id' => $ep->getId()),
                        true // absolute
                    )
                );
            }

            return $response;
        }

        return View::create($form, 400);
    }
    
}
