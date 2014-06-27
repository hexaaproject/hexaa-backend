<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Hexaa\ApiBundle\Controller;


use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\RouteRedirectView;
use FOS\RestBundle\View\View;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Hexaa\StorageBundle\Form\EntitlementPackType;
use Hexaa\StorageBundle\Entity\EntitlementPack;
use Hexaa\StorageBundle\Entity\OrganizationEntitlementPack;
use Hexaa\StorageBundle\Form\OrganizationEntitlementPackType;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of OrganizationChildController
 *
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class OrganizationChildController extends FOSRestController {
    /**
     * get managers of organization
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
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
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
	$o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
	$p = $o->getManagers();
        //$p = array_filter($p);
	//if (empty($p)) throw new HttpException(404, "Resource not found.");
	return $p;
    }
    
    /**
     * remove manager from organization
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
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
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
	$o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        $usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        if (!$o->hasManager($p)){
            throw new HttpExcetion(403, "Forbidden");
            return ;
        }
	$p = $em->getRepository('HexaaStorageBundle:Principal')->find($pid);
	if (!$p) throw new HttpException(404, "Resource not found.");
	if ($o->hasManager($p)){
	  $o->removeManager($p);
	  $em->persist($o);
	  $em->flush();
	}
    }
    
    /**
     * add manager to organization
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
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
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
	$o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        $usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        if (!$o->hasManager($p)){
            throw new HttpExcetion(403, "Forbidden");
            return ;
        }
	$p = $em->getRepository('HexaaStorageBundle:Principal')->find($pid);
	if (!$p) throw new HttpException(404, "Principal not found.");
	if (!$o->hasManager($p)){
	  $o->addManager($p);
	  $em->persist($o);
	  $em->flush();
	}
    }
    
    /**
     * get members of organization
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
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
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
    public function cgetMembersAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
	$o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
	$p = $o->getPrincipals();
        //$p = array_filter($p);
	//if (empty($p)) throw new HttpException(404, "Resource not found.");
	return $p;
    }
    
    /**
     * remove member from organization
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
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
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
    public function deleteMemberAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $pid)
    {
	$em = $this->getDoctrine()->getManager();
	$o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
	$usr= $this->get('security.context')->getToken()->getUser();
	$usrp = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	if (!$o->hasManager($usrp)){
	  throw new HttpException(403, "Forbidden");
	  return ;
	}
	$p = $em->getRepository('HexaaStorageBundle:Principal')->find($pid);
	if (!$p) throw new HttpException(404, "Resource not found.");
	if ($o->hasPrincipal($p)){
	  $o->removePrincipal($p);
	  $em->persist($o);
	  $em->flush();
	}
    }
    
    /**
     * add member to organization
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
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
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
    public function putMemberAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $pid)
    {
	$em = $this->getDoctrine()->getManager();
	$o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
	$usr= $this->get('security.context')->getToken()->getUser();
	$usrp = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	if (!$o->hasManager($usrp)){
	  throw new HttpException(403, "Forbidden");
	  return ;
	}
	$p = $em->getRepository('HexaaStorageBundle:Principal')->find($pid);
	if (!$p) throw new HttpException(404, "Resource not found.");
	if (!$o->hasPrincipal($p)){
	  $o->addPrincipal($p);
	  $em->persist($o);
	  $em->flush();
	}
    }
    
    
    /**
     * get roles of organization
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
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
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
    public function cgetRolesAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
	$o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
	$rs = $em->getRepository('HexaaStorageBundle:Role')->findByOrganization($o);
        $rs = array_filter($rs);
	//if (empty($rs)) throw new HttpException(404, "Resource not found.");
	return $rs;
    }
    
    
    /**
     * get entitlements of organization
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
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
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
    public function cgetEntitlementsAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
	$o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
	$oeps = $em->getRepository('HexaaStorageBundle:OrganizationEntitlementPack')->findByOrganization($o);
        $retarr = array();
        foreach ($oeps as $oep) {
            $ep = $oep->getEntitlementPack();
            foreach ($ep->getEntitlements() as $e) {
                if (!in_array($e, $retarr)){
                    $retarr[] = $e;
                }
            }
        }        
        $retarr = array_filter($retarr);
	//if (empty($retarr)) throw new HttpException(404, "Resource not found.");
	return $retarr;
    }
    
    
    /**
     * get entitlement packs of organization
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
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
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
    public function cgetEntitlementpacksAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
	$o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
	$oeps = $em->getRepository('HexaaStorageBundle:OrganizationEntitlementPack')->findByOrganization($o);
        $retarr = array();
        foreach ($oeps as $oep) {
            $ep = $oep->getEntitlementPack();
            if (!in_array($ep, $retarr)){
                $retarr[] = $ep;
            }            
        }        
        $retarr = array_filter($retarr);
	//if (empty($retarr)) throw new HttpException(404, "Resource not found.");
	return $retarr;
    }
    
    /**
     * link entitlement packs to organization
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
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="epid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement package id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  parameters = {
     *      {"name"="status", "dataType"="enum", "required"=true, "format"="accepted|pending", "description"="status of acceptance"}
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
    public function putEntitlementpacksAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $epid)
    {
        $em = $this->getDoctrine()->getManager();
        $usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        if (!$o->hasManager($p)){
            throw new HttpExcetion(403, "Forbidden");
            return ;
        }
        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        $ep = $em->getRepository('HexaaStorageBundle:EntitlementPack')->find($epid);
        if (!$ep) throw new HttpException(404, "EntitlementPack not found");
        
        try{
	$oep = $em->getRepository('HexaaStorageBundle:OrganizationEntitlementPack')->createQueryBuilder('oep')
	  ->where('oep.organization = :o')
	  ->andwhere('oep.entitlementPack = :ep')
	  ->setParameters(array(':o' => $o, ':ep' => $ep))
	  ->getQuery()
	  ->getSingleResult();
	} catch (\Doctrine\ORM\NoResultException $e) {
	  $oep = new OrganizationEntitlementPack();
	  $oep->setOrganization($o);
          $oep->setEntitlementPack($ep);
	}
        
        return $this->proccessOEPForm($oep);
        
    }
        
    private function processOEPForm(OrganizationEntitlementPack $oep ){
        $statusCode = $oep->getId()==null ? 201 : 204;

        $form = $this->createForm(new OrganizationEntitlementPackType(), $oep);
        $form->bind($this->getRequest());

        if ($form->isValid()) {
	    if (201 === $statusCode) {
	      $oep->setCreatedAt(new \DateTime());
	    }
            if ($oep->getStatus()=='accepted'){
                if (!$ep->getService()->hasManager($p)){
                    throw new HttpException(403, "Forbidden");
                }
            }
	    $em->persist($oep);
            $em->flush();

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location',
                    $this->generateUrl(
                        'get_organization_entitlementpacks', array('id' => $oep->getId()),
                        true // absolute
                    )
                );
            }

            return $response;
        }

        return View::create($form, 400);
        
    }
    
    
    /**
     * unlink entitlement packs from organization
     *
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="epid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement package id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  parameters = {
     *      {"name"="status", "dataType"="enum", "required"=true, "format"="accepted|pending", "description"="status of acceptance"}
     *  }
     * )
     *
     * 
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher 
     *
     * @return array
     */
    public function deleteEntitlementpacksAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $epid)
    {
        $em = $this->getDoctrine()->getManager();
        $usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        if (!$o->hasManager($p)){
            throw new HttpExcetion(403, "Forbidden");
            return ;
        }
        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        $ep = $em->getRepository('HexaaStorageBundle:EntitlementPack')->find($epid);
        if (!$ep) throw new HttpException(404, "EntitlementPack not found");
        
        try{
	$oep = $em->getRepository('HexaaStorageBundle:OrganizationEntitlementPack')->createQueryBuilder('oep')
	  ->where('oep.organization = :o')
	  ->andwhere('oep.entitlementPack = :ep')
	  ->setParameters(array(':o' => $o, ':ep' => $ep))
	  ->getQuery()
	  ->getSingleResult();
	} catch (\Doctrine\ORM\NoResultException $e) {
	  throw new HttpException(404, "No link found");
          return ;
	}
        
        $em->remove($oep);
        $em->persist();
        
    }
    
    
    /**
     * get available attribute specifications for organization
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
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
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
	$o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
	$oeps = $em->getRepository('HexaaStorageBundle:OrganizationEntitlementPack')->findByOrganization($o);
        $retarr = array();
        $ss = array();
        foreach ($oeps as $oep) {
            $s = $oep->getEntitlementPack()->getService();
            if (!in_array($s, $ss)){
                $ss[] = $s;
            }
        }
        foreach ($ss as $s) {
            $sass = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findByService($s);
            foreach ($sass as $sas) {
                if (!in_array($sas, $retarr)){
                    $retarr[] = $sas;
                }
            }
        }
        $sass = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findByIsPublic(true);
        foreach ($sass as $sas) {
            if (!in_array($sas, $retarr)){
                $retarr[] = $sas;
            }
        }
        $retarr = array_filter($retarr);
	//if (empty($retarr)) throw new HttpException(404, "Resource not found.");
	return $retarr;
    }
    
    
    /**
     * create new role
     *
     *
     * @ApiDoc(
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when role has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when role is not found"
     *   },
     *   requirements = {
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}    
     *   },
     *   parameters = {
     *     {"name"="name", "dataType"="string", "required"=true, "requirement"="\..+", "description"="organization name"},
     *     {"name"="startDate", "dataType"="DateTime", "required"=true, "requirement"="\..+", "description"="organization entity id"},
     *     {"name"="endDate", "dataType"="DateTime", "required"=false, "requirement"="\..+", "description"="organization url"},
     *     {"name"="description", "dataType"="string", "required"=false, "description"="role description"},
     *  }
     *   
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher role
     *
     * 
     */
    public function postRoleAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();/*
	$s = $em->getRepository('HexaaStorageBundle:Role')->find($id);
	if (!$s) throw new HttpException(404, "Resource not found.");*/
        $usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        if (!$o->hasManager($p)){
            throw new HttpExcetion(403, "Forbidden");
            return ;
        }
	return $this->processForm(new Role(), $id);
    }
    
    private function processForm(Role $r, $id)
    {
	$em = $this->getDoctrine()->getManager();
        $statusCode = $r->getId()==null ? 201 : 204;

        $form = $this->createForm(new RoleType(), $r);
        $form->bind($this->getRequest());

        if ($form->isValid()) {
	    if (201 === $statusCode) {
	      $o = $this->getDoctrine()->getManager->getRepository('HexaaStorageBundle:Organization')->find($id);
	      $r->setOrganization($o);
	      $r->setCreatedAt(new \DateTime());
	    }
	    $em->persist($r);
            $em->flush();

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location',
                    $this->generateUrl(
                        'get_role', array('id' => $r->getId()),
                        true // absolute
                    )
                );
            }

            return $response;
        }

        return View::create($form, 400);
    }
    
    
    /**
     * create attribute value (for organization) details
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
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
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
    public function postAttributevalueorganizationAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $asid)
    {
        $em = $this->getDoctrine()->getManager();
        $as = $em->getRepository('HexaaStorageBundle:AttributeSpec')->find($asid);
        if(!$as) throw new HttpException(404, 'AttributeSpec not found.');
        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        $usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        if (!$o->hasManager($p)){
            throw new HttpExcetion(403, "Forbidden");
            return ;
        }
        $avo = new AttributeValueOrganization();
        $avo->setAttributeSpec($as);
        $avo->setOrganization($o);
        return $this->processAVOForm($avo);
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
    
}
