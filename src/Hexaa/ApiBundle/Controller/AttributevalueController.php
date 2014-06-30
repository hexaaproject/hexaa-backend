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

use Hexaa\StorageBundle\Form\AttributeValuePrincipalType;
use Hexaa\StorageBundle\Entity\AttributeValuePrincipal;
use Hexaa\StorageBundle\Form\AttributeValueOrganizationType;
use Hexaa\StorageBundle\Entity\AttributeOrganizationPrincipal;
use Hexaa\StorageBundle\Form\ServiceAttributeValuePrincipalType;
use Hexaa\StorageBundle\Entity\ServiceAttributeValuePrincipal;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class AttributevalueController extends FOSRestController {
    
    
    /**
     * get attribute value (for principal) details
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
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value (for principal) id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     *
     * @return Role
     */
    public function getAttributevalueprincipalAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
        $asp = $em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->find($id);
	if (!$asp) throw new HttpException(404, "Resource not found.");
        $usr= $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	if (!in_array($p->getFedid(),$this->container->getParameter('hexaa_admins')) && $asp->getPrincipal()!=$p) {
	  throw new HttpException(403, "Forbidden");
	  return ;
	} 
	return $asp;
    }
    
    private function processAVPForm(AttributeValuePrincipal $avp)
    {
	$em = $this->getDoctrine()->getManager();
        $statusCode = $avp->getId()==null ? 201 : 204;

        $form = $this->createForm(new AttributeValuePrincipalType(), $avp);
        $form->bind($this->getRequest());

        if ($form->isValid()) {
	    if (201 === $statusCode) {
	    }
            $em->persist($avp);
            $em->flush();

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location',
                    $this->generateUrl(
                        'get_attributevalueprincipal', array('id' => $avp->getId()),
                        true // absolute
                    )
                );
            }
            return $response;
        }
        return View::create($form, 400);
    }
    
    /**
     * edit attribute value (for principal) details
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
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value (for principal) id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  parameters = {
     *      {"name"="isDefault","dataType"="boolean", "required"=false, "format"="true|false", "description"="set wether to automatically supply attribute value to new services or not"},
     *      {"name"="value", "dataType"="string", "required"=true, "description"="assigned value"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     *
     * @return AttributeValuePrincipal
     */
    public function putAttributevalueprincipalAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
        $avp = $em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->find($id);
	if (!$avp) throw new HttpException(404, "Resource not found.");
        $usr= $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	if (!in_array($p->getFedid(),$this->container->getParameter('hexaa_admins')) && $avp->getPrincipal()!=$p) {
	  throw new HttpException(403, "Forbidden");
	  return ;
        }
        return $this->processAVPForm($avp);
    } 
    
    /**
     * create attribute value (for principal) details
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
     *      {"name"="asid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute specification id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  parameters = {
     *      {"name"="isDefault","dataType"="boolean", "required"=false, "format"="true|false", "description"="set wether to automatically supply attribute value to new services or not"},
     *      {"name"="value", "dataType"="string", "required"=true, "description"="assigned value"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     *
     * @return Role
     */
    public function postAttributevalueprincipalAction(Request $request, ParamFetcherInterface $paramFetcher, $asid)
    {
        $em = $this->getDoctrine()->getManager();
        $as = $em->getRepository('HexaaStorageBundle:AttributeSpec')->find($asid);
        if(!$as) throw new HttpException(404, 'AttributeSpec not found.');
        $usr= $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $avp = new AttributeValuePrincipal();
        $avp->setAttributeSpec($as);
        $avp->setPrincipal($p);
        return $this->processAVPForm($avp);
    } 
    
    
    
    
    /**
     * delete attribute value (for principal)
     *
     *
     * @ApiDoc(
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when service has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
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
    public function deleteAttributevalueprincipalAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
	$avp = $em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->find($id);
	if (!$avp) throw new HttpException(404, "Resource not found.");
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	if (!in_array($p->getFedid(),$this->container->getParameter('hexaa_admins')) && $avp->getPrincipal()!=$p) {
	  throw new HttpException(403, "Forbidden");
	  return ;
	} 
	$em->remove($avp);
	$em->flush();
    }
    
    /**
     * get all consents for an attribute value (for principal)
     *
     *
     * @ApiDoc(
     *   resource = false,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
     *      {"name"="sid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
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
     * @return array
     *
     * 
     */
    public function cgetAttributevalueprincipalsConsentsAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $avp = $em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->find($id);
        if (!$avp) throw new HttpException(404, "Attribute value not found.");
        $usr= $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        if (!in_array($p->getFedid(),$this->container->getParameter('hexaa_admins')) && $avp->getPrincipal()!=$p) {
	  throw new HttpException(403, "Forbidden");
          return ;
        }
        
        $savp = $em->getRepository('HexaaStorageBundle:ServiceAttributeValuePrincipal')->findByAttributeValuePrincipal($avp);
        $savp = array_filter($savp);
        if (count($savp)<1){
            throw new HttpException(204, 'No consents');
        }
        return $savp;
    }
    
    /**
     * get attribute value (for principal) consent per service
     *
     *
     * @ApiDoc(
     *   resource = false,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
     *      {"name"="sid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
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
     * 
     */
    public function getAttributevalueprincipalServiceAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $sid)
    {
        $em = $this->getDoctrine()->getManager();
        $s = $em->getRepository('HexaaStorageBundle:Service')->find($sid);
        if (!$s) throw new HttpException(404, "Service not found.");
        $avp = $em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->find($id);
        if (!$avp) throw new HttpException(404, "Attribute value not found.");
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	if (!in_array($p->getFedid(),$this->container->getParameter('hexaa_admins')) && $avp->getPrincipal()!=$p) {
	  throw new HttpException(403, "Forbidden");
          return ;
        }
        
        try{
	$savp = $em->getRepository('HexaaStorageBundle:ServiceAttributeValuePrincipal')->createQueryBuilder('savp')
	  ->where('savp.service = :s')
	  ->andwhere('savp.attributeValuePrincipal = :avp')
	  ->setParameters(array(':s' => $s, ':avp' => $avp))
	  ->getQuery()
	  ->getSingleResult();
	} catch(Exception $e) {
	  $savp = new ServiceAttributeValuePrincipal();
	  $savp->setAttributeValuePrincipal($avp);
          $savp->setService($s);
	}
        return $savp;
        
    }
    
    /**
     * set attribute value (for principal) consent per service
     *
     *
     * @ApiDoc(
     *   resource = false,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
     *      {"name"="sid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  parameters = {
     *      {"name"="isAllowed", "dataType"="boolean", "required"=true, "format"="true|false", "description"="wether the user consents to the release of the attribute"}
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
    public function putAttributevalueprincipalServiceAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $sid)
    {
        $em = $this->getDoctrine()->getManager();
        $s = $em->getRepository('HexaaStorageBundle:Service')->find($sid);
        if (!$s) throw new HttpException(404, "Service not found.");
        $avp = $em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->find($id);
        if (!$avp) throw new HttpException(404, "Attribute value not found.");
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	if (!in_array($p->getFedid(),$this->container->getParameter('hexaa_admins')) && $avp->getPrincipal()!=$p) {
	  throw new HttpException(403, "Forbidden");
          return ;
        }
        
        try{
	$savp = $em->getRepository('HexaaStorageBundle:ServiceAttributeValuePrincipal')->createQueryBuilder('savp')
	  ->where('savp.service = :s')
	  ->andwhere('savp.attributeValuePrincipal = :avp')
	  ->setParameters(array(':s' => $s, ':avp' => $avp))
	  ->getQuery()
	  ->getSingleResult();
	} catch(Exception $e) {
	  $savp = new ServiceAttributeValuePrincipal();
	  $savp->setAttributeValuePrincipal($avp);
          $savp->setService($s);
	}
        
        processSAVPForm($savp);       
	
    }
    
    private function processSAVPForm(ServiceAttributeValuePrincipal $savp)
    {
	$em = $this->getDoctrine()->getManager();
        $statusCode = $sas->getId()==null ? 201 : 204;

        $form = $this->createForm(new ServiceAttributeValuePrincipalType(), $savp);
        $form->bind($this->getRequest());

        if ($form->isValid()) {
	    if (201 === $statusCode) {
	    }
	    $em->persist($savp);
            $em->flush();

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location',
                    $this->generateUrl(
                        'get_service_attributespecs', array('id' => $savp->getId()), //TODO
                        true // absolute
                    )
                );
            }

            return $response;
        }

        return View::create($form, 400);
    }
    
    /**
     * set attribute value (for principal) consent per service
     *
     *
     * @ApiDoc(
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned on successful delete",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
     *      {"name"="sid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
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
    public function deleteAttributevalueprincipalServiceAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $sid)
    {
        $em = $this->getDoctrine()->getManager();
        $s = $em->getRepository('HexaaStorageBundle:Service')->find($sid);
        if (!$s) throw new HttpException(404, "Service not found.");
        $avp = $em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->find($id);
        if (!$avp) throw new HttpException(404, "Attribute value not found.");
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	if (!in_array($p->getFedid(),$this->container->getParameter('hexaa_admins')) && $avp->getPrincipal()!=$p) {
	  throw new HttpException(403, "Forbidden");
          return ;
        }
        
        try{
	$savp = $em->getRepository('HexaaStorageBundle:ServiceAttributeValuePrincipal')->createQueryBuilder('savp')
	  ->where('savp.service = :s')
	  ->andwhere('sas.attributeValuePrincipal = :avp')
	  ->setParameters(array(':s' => $s, ':avp' => $avp))
	  ->getQuery()
	  ->getSingleResult();
	} catch(Exception $e) {
	  //do nothing at all...
	}
        
        $em->remove($savp);
        $em->flush();
    }
    
    /**
     * get attribute value (for organization) details
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
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value (for organization) id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     *
     * @return Role
     */
    public function getAttributevalueorganizationAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
        $aso = $em->getRepository('HexaaStorageBundle:AttributeValueOrganization')->find($id);
	if (!$aso) throw new HttpException(404, "Resource not found.");
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $o = $aso->getOrganization();
	if (!in_array($p->getFedid(),$this->container->getParameter('hexaa_admins')) && !($o->hasManager($p) && $o->hasPrincipal($p))) {
	  throw new HttpException(403, "Forbidden");
	  return ;
	} 
	return $aso;
    }
    
    private function processAVOForm(AttributeValueOrganization $avo)
    {
	$em = $this->getDoctrine()->getManager();
        $statusCode = $avo->getId()==null ? 201 : 204;

        $form = $this->createForm(new AttributeValueOrganizationType(), $avo);
        $form->bind($this->getRequest());

        if ($form->isValid()) {
	    if (201 === $statusCode) {
	    }
            $em->persist($avo);
            $em->flush();

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location',
                    $this->generateUrl(
                        'get_attributevalueorganization', array('id' => $avo->getId()),
                        true // absolute
                    )
                );
            }
            return $response;
        }
        return View::create($form, 400);
    }
    
    /**
     * edit attribute value (for organization) details
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
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value (for organization) id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  parameters = {
     *      {"name"="value", "dataType"="string", "required"=true, "description"="assigned value"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     *
     * @return AttributeValuePrincipal
     */
    public function putAttributevalueorganizationAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
        $avo = $em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->find($id);
	if (!$avo) throw new HttpException(404, "Resource not found.");
        $usr= $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	$o = $avo->getOrganization();
	if (!in_array($p->getFedid(),$this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
	  throw new HttpException(403, "Forbidden");
	  return ;
        }
        return $this->processAVOForm($avo);
    } 
    
    
    /**
     * delete attribute value (for organization)
     *
     *
     * @ApiDoc(
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when service has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
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
    public function deleteAttributevalueorganizationAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
	$avo = $em->getRepository('HexaaStorageBundle:AttributeValueOrganization')->find($id);
	if (!$avo) throw new HttpException(404, "Resource not found.");
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	$o = $aso->getOrganization();
	if (!in_array($p->getFedid(),$this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
	  throw new HttpException(403, "Forbidden");
	  return ;
	} 
	$em->remove($avo);
	$em->flush();
    }
}