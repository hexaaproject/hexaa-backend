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

use Hexaa\StorageBundle\Form\RoleType;
use Hexaa\StorageBundle\Entity\Role;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class RoleController extends FOSRestController implements ClassResourceInterface {

    
    /**
     * get role details
     *
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when role is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="role id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher role
     *
     * @return Role
     */
    public function getAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
	$r = $em->getRepository('HexaaStorageBundle:Role')->find($id);
	if (!$r) throw new HttpException(404, "Resource not found.");
	$o = $r->getOrganization();
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	if (!$o->hasPrincipal($p)){
	  throw new HttpException(403, "Forbidden");
	  return ;
	}
	return $r;
    }
  
      
    /**
     * edit role preferences
     *
     *
     * @ApiDoc(
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when role has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when role is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="role id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   parameters = {
     *     {"name"="name", "dataType"="string", "required"=true, "requirement"="\..+", "description"="organization name"},
     *     {"name"="startDate", "dataType"="DateTime", "required"=true, "requirement"="\..+", "description"="organization entity id"},
     *     {"name"="endDate", "dataType"="DateTime", "required"=false, "requirement"="\..+", "description"="organization url"},
     *     {"name"="description", "dataType"="string", "required"=false, "description"="role description"},
     *  }
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
    public function putAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
	$r = $em->getRepository('HexaaStorageBundle:Role')->find($id);
	if (!$r) throw new HttpException(404, "Resource not found.");
	$o = $r->getOrganization();
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	if (!$o->hasManager($p)) {
	  throw new HttpException(403, "Forbidden");
	  return ;
	} 
	return $this->processForm($r);
    }
    
    private function processForm(Role $r)
    {
	$em = $this->getDoctrine()->getManager();
        $statusCode = $r->getId()==null ? 201 : 204;

        $form = $this->createForm(new RoleType(), $r);
        $form->bind($this->getRequest());

        if ($form->isValid()) {
	    
	    $em->persist($r);
            $em->flush();

            $response = new Response();
            $response->setStatusCode($statusCode);

            

            return $response;
        }

        return View::create($form, 400);
    }
   
     
    /**
     * delete role
     *
     *
     * @ApiDoc(
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when role has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when role is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="role id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher role
     *
     * 
     */ 
    public function deleteAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
	$em = $this->getDoctrine()->getManager();
	$r = $em->getRepository('HexaaStorageBundle:Role')->find($id);
	if (!$r) throw new HttpException(404, "Resource not found.");
	$o = $r->getService();
	$usr= $this->get('security.context')->getToken()->getUser();
	$p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
	if (!$o->hasManager($p)) {
	  throw new HttpException(403, "Forbidden");
	  return ;
	} 
	$em->remove($r);
	$em->flush();
    }
    
    /**
     * add principal to role
     *
     *
     * @ApiDoc(
     *   resource = false,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when role is not found"
     *   },
     *   requirements ={
     *     {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="role id"},
     *     {"name"="pid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="principal id"},
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *     {"name"="expiration", "dataType"="DateTime", "required"=false, "description"="expiration date"}
     *   }
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
    public function putPrincipalAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $pid)
    {
      $em = $this->getDoctrine()->getManager();
      $r = $em->getRepository('HexaaStorageBundle:Role')->find($id);
      $o = $r->getOrganization();
      $usr= $this->get('security.context')->getToken()->getUser();
      $usrp = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
      if (!$o->hasManager($usrp)){
        throw new HttpException(403, "Forbidden");
        return ;
      }
      $p = $em->getRepository('HexaaStorageBundle:Principal')->find($pid);
      if (!$p) {
        throw new HttpException(404, "Resource not found.");
        return ;
      }  
      if (!$o->hasPrincipal($p)) {
        throw new HttpException(400, 'Principal is not a member of the organization');
        return ;
      }
      try{
	$rp = $em->getRepository('HexaaStorageBundle:RolePrincipal')->createQueryBuilder('rp')
	  ->where('rp.role = :r')
	  ->andwhere('rp.principal = :p')
	  ->setParameters(array(':r' => $r, ':p' => $p))
	  ->getQuery()
	  ->getSingleResult();
	} catch(Exception $e) {
	  $rp = new RolePrincipal();
	  $rp->setRole($r);
	}
	  
          processRPForm($rp, $p);
        return $rp;    
    }
    
    private function processRPForm(RolePrincipal $rp, Principal $p)
    {
	$em = $this->getDoctrine()->getManager();
        $statusCode = $rp->getId()==null ? 201 : 204;

        $form = $this->createForm(new RolePrincipalType(), $rp);
        $form->bind($this->getRequest());

        if ($form->isValid()) {
	    if (201 === $statusCode) {
	    }
	    $rp->setPrincipal($p);
	    $em->persist($rp);
            $em->flush();

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location',
                    $this->generateUrl(
                        'get_role', array('id' => $rp->getRole()->getId()),
                        true // absolute
                    )
                );
            }

            return $response;
        }

        return View::create($form, 400);
    }
    
    /**
     * remove principal from role
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
    public function deletePrincipalAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $pid)
    {
      $em = $this->getDoctrine()->getManager();
      $r = $em->getRepository('HexaaStorageBundle:Role')->find($id);
      $o = $r->getOrganization();
      $usr= $this->get('security.context')->getToken()->getUser();
      $usrp = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
      if (!$o->hasManager($usrp)){
        throw new HttpException(403, "Forbidden");
        return ;
      }
      $p = $em->getRepository('HexaaStorageBundle:Principal')->find($pid);
      if (!$p) {
        throw new HttpException(404, "Resource not found.");
        return ;
      }
      $rp = $em->getRepository('HexaaStorageBundle:RolePrincipal')->createQueryBuilder('rp')
	  ->where('rp.role = :r')
	  ->andwhere('rp.principal = :p')
	  ->setParameters(array(':r' => $r, ':p' => $p))
	  ->getQuery()
	  ->getOneOrNullResult();
      $em->remove($rp);
      $em->flush();
      
    }
    
}
