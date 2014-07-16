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
use Hexaa\StorageBundle\Form\EntitlementType;
use Hexaa\StorageBundle\Entity\Entitlement;
use Hexaa\StorageBundle\Form\ServiceAttributeSpecType;
use Hexaa\StorageBundle\Entity\ServiceAttributeSpec;

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
     *   section = "Service",
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
        if ($request->getMethod()=="GET" && !$s) throw new HttpException(404, "Service not found.");
	$p = $s->getManagers();
	//if (!$p) throw new HttpException(404, "Resource not found.");
	return $p;
    }
    
    /**
     * get Attribute specifications linked to the service
     *
     *
     * @ApiDoc(
     *   section = "Service",
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
        if ($request->getMethod()=="GET" && !$s) throw new HttpException(404, "Service not found.");
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	if (!$p) throw new HttpException(404, "Principal not found.");
            $retarr = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findByService($s);
	return $retarr;
    }
    
    /**
     * Get all EntitlementPack - Organization connections related to the service.
     *
     *
     * @ApiDoc(
     *   section = "Service",
     *   description = "get organizations linked to the service",
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
    public function cgetOrganizationsAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
	$s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
        if ($request->getMethod()=="GET" && !$s) throw new HttpException(404, "Service not found.");
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $eps = $em->getRepository('HexaaStorageBundle:EntitlementPack')->findByService($s);
        $oeps = $em->getRepository('HexaaStorageBundle:OrganizationEntitlementPack')->findAll();
        $retarr = array();
        foreach ($oeps as $oep){
            foreach ($eps as $ep){
                if ($oep->getEntitlementPack()===$ep) {
                    array_push($retarr, $oep);
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
     *   section = "Service",
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
        if ($request->getMethod()=="DELETE" && !$s) throw new HttpException(404, "Service not found.");
        $usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        if (!in_array($p->getFedid(),$this->container->getParameter('hexaa_admins')) && !$s->hasManager($p)){
            throw new HttpException(403, "Forbidden");
            return ;
        }
	$p = $em->getRepository('HexaaStorageBundle:Principal')->find($pid);
	if ($request->getMethod()=="DELETE" && !$p) throw new HttpException(404, "Principal not found.");
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
     *   section = "Service",
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
        if ($request->getMethod()=="PUT" && !$s) throw new HttpException(404, "Service not found.");
        $usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        if (!in_array($p->getFedid(),$this->container->getParameter('hexaa_admins')) && !$s->hasManager($p)){
            throw new HttpException(403, "Forbidden");
            return ;
        }
	$p = $em->getRepository('HexaaStorageBundle:Principal')->find($pid);
	if ($request->getMethod()=="PUT" && !$p) throw new HttpException(404, "Resource not found.");
	if (!$s->hasManager($p)){
	  $s->addManager($p);
	  $em->persist($s);
	  $em->flush();
	}
    }
    
    
    /**
     * remove attribute specification from service
     *
     *
     * @ApiDoc(
     *   section = "Service",
     *   resource = true,
     *   statusCodes = {
     *	   204 = "Returned on success",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="asid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute specification id"},
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
    public function deleteAttributespecAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $asid)
    {
	$em = $this->getDoctrine()->getManager();
	$s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
        if ($request->getMethod()=="DELETE" && !$s) throw new HttpException(404, "Service not found.");
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	if (!in_array($p->getFedid(),$this->container->getParameter('hexaa_admins')) && !$s->hasManager($p)) {
	  throw new HttpException(403, "Forbidden");
          return ;
        }
        $as = $em->getRepository('HexaaStorageBundle:AttributeSpec')->find($asid);
        if (!$as) throw new HttpException(404,"AttributeSpec not found");
        try{
	$sas = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->createQueryBuilder('sas')
	  ->where('sas.service = :s')
	  ->andwhere('sas.attributeSpec = :as')
	  ->setParameters(array(':s' => $s, ':as' => $as))
	  ->getQuery()
	  ->getSingleResult();
	} catch(\Doctrine\ORM\NoResultException $e) {
	  throw new HttpException(404, "Resource not found.");
	}
	$em->remove($sas);
	$em->flush();
	
    }
    
    /**
     * add attribute specification to service
     *
     *
     * @ApiDoc(
     *   section = "Service",
     *   resource = true,
     *   statusCodes = {
     *	   201 = "Returned on success",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="asid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute specification id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  parameters = {
     *      {"name"="is_public", "dataType"="boolean", "required"=true, "format"="true|false", "description"="Set wether to allow any or only connected users to set the attribute."}
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
    public function putAttributespecAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $asid)
    {
	$em = $this->getDoctrine()->getManager();
	$s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
        if ($request->getMethod()=="PUT" && !$s) throw new HttpException(404, "Service not found.");
        if (!$s) throw new HttpException(404, "Service not found");
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	if (!in_array($p->getFedid(),$this->container->getParameter('hexaa_admins')) && !$s->hasManager($p)) {
	  throw new HttpException(403, "Forbidden");
          return ;
        }
        
        $as = $em->getRepository('HexaaStorageBundle:AttributeSpec')->find($asid);
	if (!$as) throw new HttpException(404, "AttributeSpec not found.");
        
        try{
	$sas = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->createQueryBuilder('sas')
	  ->where('sas.service = :s')
	  ->andwhere('sas.attributeSpec = :as')
	  ->setParameters(array(':s' => $s, ':as' => $as))
	  ->getQuery()
	  ->getSingleResult();
	} catch(\Doctrine\ORM\NoResultException $e) {
	  $sas = new ServiceAttributeSpec();
	  $sas->setAttributeSpec($as);
          $sas->setService($s);
	}
        
        return $this->processSASForm($sas);
        
	
    }
    
    private function processSASForm(ServiceAttributeSpec $sas)
    {
	$em = $this->getDoctrine()->getManager();
        $statusCode = $sas->getId()==null ? 201 : 204;

        $form = $this->createForm(new ServiceAttributeSpecType(), $sas);
        $form->bind($this->getRequest());

        if ($form->isValid()) {
	    if (201 === $statusCode) {
	    }
	    $em->persist($sas);
            $em->flush();

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location',
                    $this->generateUrl(
                        'get_service', array('id' => $sas->getService()->getId()),
                        true // absolute
                    )
                );
            }

            return $response;
        }

        return View::create($form, 400);
    }
    
    /**
     * get entitlements of service
     *
     *
     * @ApiDoc(
     *   section = "Service",
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
        if (!$s) throw new HttpException(404, "Resource not found.");        
	$es = $em->getRepository('HexaaStorageBundle:Entitlement')->findByService($s);
	
	//if (!$es) throw new HttpException(404, "Resource not found.");
	return $es;
    }
    
    
    /**
     * get entitlement packs of service
     *
     *
     * @ApiDoc(
     *   section = "Service",
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
        if (!$s) throw new HttpException(404, "Resource not found.");
	$ep = $em->getRepository('HexaaStorageBundle:EntitlementPack')->findByService($s);
	//if (!$ep) throw new HttpException(404, "Resource not found.");
	return $ep;
    }
    
    /**
     * create new entitlement pack
     *
     *
     * @ApiDoc(
     *   section = "EntitlementPack",
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
     *  },
     *  parameters = {
     *      {"name"="name","dataType"="string","required"=true,"description"="Displayable name of the entitlement package"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"},
     *      {"name"="type","dataType"="string","required"=true,"format"="private|public","description"="Visibility of the entitlement package"},
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
	$em = $this->getDoctrine()->getManager();/*
	$s = $em->getRepository('HexaaStorageBundle:EntitlementPack')->find($id);
	if (!$s) throw new HttpException(404, "Resource not found.");*/
        $usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	if (!in_array($p->getFedid(),$this->container->getParameter('hexaa_admins')) && !$s->hasManager($p)) {
	  throw new HttpException(403, "Forbidden");
          return ;
        }
	return $this->processForm(new EntitlementPack(), $id);
    }
    
    private function processForm(EntitlementPack $ep, $id)
    {
	$em = $this->getDoctrine()->getManager();
	$s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
        if (!$s) throw new HttpException(404, "Service not found.");
        $statusCode = $ep->getId()==null ? 201 : 204;

        $form = $this->createForm(new EntitlementPackType(), $ep);
        $form->bind($this->getRequest());

        if ($form->isValid()) {
	    if (201 === $statusCode) {
                $ep->setToken(uniqid());
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

    
    /**
     * create new entitlement
     *
     *
     * @ApiDoc(
     *   section = "Entitlement",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when entitlement has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement is not found"
     *   },
     *   requirement = {
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}    
     *   },
     *  parameters = {
     *      {"name"="uri","dataType"="string","required"=true,"description"="URI of entitlement"},
     *      {"name"="name","dataType"="string","required"=true,"description"="displayable name of the entitlement"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"}
     *  }        
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement
     *
     * 
     */
    public function postEntitlementAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();/*
	$s = $em->getRepository('HexaaStorageBundle:Entitlement')->find($id);
	if (!$s) throw new HttpException(404, "Resource not found.");*/
        $usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	if (!in_array($p->getFedid(),$this->container->getParameter('hexaa_admins')) && !$s->hasManager($p)) {
	  throw new HttpException(403, "Forbidden");
          return ;
        }
	return $this->processEForm(new Entitlement(), $id);
    }
    
    private function processEForm(Entitlement $e, $id)
    {
	$em = $this->getDoctrine()->getManager();
        $statusCode = $e->getId()==null ? 201 : 204;

        $form = $this->createForm(new EntitlementType(), $e);
        $form->bind($this->getRequest());

        if ($form->isValid()) {
	    if (201 === $statusCode) {
                $s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
                if (!$s) throw new HttpException(404, "Service not found.");
                $e->setService($s);
	    }
	    $em->persist($e);
            $em->flush();

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location',
                    $this->generateUrl(
                        'get_entitlement', array('id' => $e->getId()),
                        true // absolute
                    )
                );
            }

            return $response;
        }

        return View::create($form, 400);
    }
    
}

