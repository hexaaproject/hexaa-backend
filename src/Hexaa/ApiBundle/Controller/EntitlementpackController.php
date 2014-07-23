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
class EntitlementpackController extends FOSRestController implements ClassResourceInterface {

    
    /**
     * get entitlement pack details
     *
     *
     * @ApiDoc(
     *   section = "EntitlementPack",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement pack is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement package id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * @Annotations\Get(requirements={"id" = "\d+"})
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement pack
     *
     * @return EntitlementPack
     */
    public function getAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
	$ep = $em->getRepository('HexaaStorageBundle:EntitlementPack')->find($id);
	if ($request->getMethod()=="GET" && !$ep) throw new HttpException(404, "Resource not found.");
	$s = $ep->getService();
 	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	if (!in_array($p->getFedid(),$this->container->getParameter('hexaa_admins')) && !$s->hasManager($p)) {
	  throw new HttpException(403, "Forbidden");
	  return ;
	} 
	return $ep;
    }
    
    /**
     * get all public entitlement packages
     *
     *
     * @ApiDoc(
     *   section = "EntitlementPack",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement pack is not found"
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
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement pack
     *
     * @return array
     */
    public function cgetPublicAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
	$em = $this->getDoctrine()->getManager();
	$eps = $em->getRepository('HexaaStorageBundle:EntitlementPack')->findByType("public");
 	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	return $eps;
    }
  
      
    /**
     * edit entitlement pack preferences
     *
     *
     * @ApiDoc(
     *   section = "EntitlementPack",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when entitlement pack has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement pack is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement pack id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  parameters = {
     *      {"name"="name","dataType"="string","required"=true,"description"="displayable name of the entitlement pack"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"},
     *      {"name"="type","dataType"="string","required"=true,"format"="private|public","description"="visibility of the entitlement package"}
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
    public function putAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
	$ep = $em->getRepository('HexaaStorageBundle:EntitlementPack')->find($id);
	if ($request->getMethod()=="PUT" && !$ep) throw new HttpException(404, "Resource not found.");
	$s = $ep->getService();
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	if (!in_array($p->getFedid(),$this->container->getParameter('hexaa_admins')) && !$s->hasManager($p)) {
	  throw new HttpException(403, "Forbidden");
	  return ;
	} 
	return $this->processForm($ep);
    }
    
    private function processForm(EntitlementPack $ep)
    {
	$em = $this->getDoctrine()->getManager();
        $statusCode = $ep->getId()==null ? 201 : 204;

        $form = $this->createForm(new EntitlementPackType(), $ep);
        $form->bind($this->getRequest());

        if ($form->isValid()) {
	    if (201 === $statusCode) {
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
    
    /**
     * delete entitlement pack
     *
     *
     * @ApiDoc(
     *   section = "EntitlementPack",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when entitlement pack has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement pack is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement pack id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement pack
     *
     * 
     */
    public function deleteAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
	$ep = $em->getRepository('HexaaStorageBundle:EntitlementPack')->find($id);
	if ($request->getMethod()=="DELETE" && !$ep) throw new HttpException(404, "Resource not found.");
	$s = $ep->getService();
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	if (!in_array($p->getFedid(),$this->container->getParameter('hexaa_admins')) && !$s->hasManager($p)) {
	  throw new HttpException(403, "Forbidden");
	  return ;
	} 
	$em->remove($ep);
	$em->flush();
    }
}
