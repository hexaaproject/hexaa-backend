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
use Hexaa\StorageBundle\Form\RolePrincipalType;
use Hexaa\StorageBundle\Entity\RolePrincipal;
use Hexaa\StorageBundle\Entity\Principal;
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
     *   section = "Role",
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
    public function getAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $r = $em->getRepository('HexaaStorageBundle:Role')->find($id);
        if ($request->getMethod()=="GET" && !$r)
            throw new HttpException(404, "Resource not found.");
        $o = $r->getOrganization();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasPrincipal($p)) {
            throw new HttpException(403, "Forbidden");
            return;
        }
        return $r;
    }

    /**
     * get principals in role
     *
     *
     * @ApiDoc(
     *   section = "Role",
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
     * @return array
     */
    public function getPrincipalsAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $r = $em->getRepository('HexaaStorageBundle:Role')->find($id);
        if ($request->getMethod()=="GET" && !$r)
            throw new HttpException(404, "Resource not found.");
        $o = $r->getOrganization();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasPrincipal($p)) {
            throw new HttpException(403, "Forbidden");
            return;
        }
        return $em->getRepository('HexaaStorageBundle:RolePrincipal')->findByRole($r);
    }

    /**
     * edit role preferences
     *
     *
     * @ApiDoc(
     *   section = "Role",
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
     *     {"name"="name", "dataType"="string", "required"=true, "description"="organization name"},
     *     {"name"="start_date", "dataType"="DateTime", "required"=true, "description"="organization entity id"},
     *     {"name"="end_date", "dataType"="DateTime", "required"=false, "description"="organization url"},
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
    public function putAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $r = $em->getRepository('HexaaStorageBundle:Role')->find($id);
        if ($request->getMethod()=="PUT" && !$r)
            throw new HttpException(404, "Resource not found.");
        $o = $r->getOrganization();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
            throw new HttpException(403, "Forbidden");
            return;
        }
        return $this->processForm($r);
    }

    private function processForm(Role $r) {
        $em = $this->getDoctrine()->getManager();
        $statusCode = $r->getId() == null ? 201 : 204;

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
     *   section = "Role",
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
    public function deleteAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $r = $em->getRepository('HexaaStorageBundle:Role')->find($id);
        if ($request->getMethod()=="DELETE" && !$r)
            throw new HttpException(404, "Resource not found.");
        $o = $r->getOrganization();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
            throw new HttpException(403, "Forbidden");
            return;
        }
        $em->remove($r);
        $em->flush();
    }

    /**
     * add principal to role
     *
     *
     * @ApiDoc(
     *   section = "Role",
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
    public function putPrincipalAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $pid) {
        $em = $this->getDoctrine()->getManager();
        $r = $em->getRepository('HexaaStorageBundle:Role')->find($id);
        if ($request->getMethod()=="PUT" && !$r)
            throw new HttpException(404, "Role not found.");
            $o = $r->getOrganization();
        $usr = $this->get('security.context')->getToken()->getUser();
        $usrp = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        if (!in_array($usrp->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($usrp)) {
            throw new HttpException(403, "Forbidden");
            return;
        }
        $p = $em->getRepository('HexaaStorageBundle:Principal')->find($pid);
        if (!$p) {
            throw new HttpException(404, "Resource not found.");
            return;
        }
        if (!$o->hasPrincipal($p)) {
            throw new HttpException(400, 'Principal is not a member of the organization');
            return;
        }
        try {
            $rp = $em->getRepository('HexaaStorageBundle:RolePrincipal')->createQueryBuilder('rp')
                    ->where('rp.role = :r')
                    ->andwhere('rp.principal = :p')
                    ->setParameters(array(':r' => $r, ':p' => $p))
                    ->getQuery()
                    ->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            $rp = new RolePrincipal();
            $rp->setRole($r);
        }
        return $this->processRPForm($rp, $p);
    }

    private function processRPForm(RolePrincipal $rp, Principal $p) {
        $em = $this->getDoctrine()->getManager();
        $statusCode = $rp->getId() == null ? 201 : 204;

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
                $response->headers->set('Location', $this->generateUrl(
                                'get_role', array('id' => $rp->getRole()->getId()), true // absolute
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
     *   section = "Role",
     *   resource = true,
     *   statusCodes = {
     * 	   204 = "Returned on success",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="role id"},
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
    public function deletePrincipalAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $pid) {
        $em = $this->getDoctrine()->getManager();
        $r = $em->getRepository('HexaaStorageBundle:Role')->find($id);
        if ($request->getMethod()=="DELETE" && !$r)
            throw new HttpException(404, "Role not found!");
        $o = $r->getOrganization();
        $usr = $this->get('security.context')->getToken()->getUser();
        $usrp = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        if (!in_array($usrp->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($usrp)) {
            throw new HttpException(403, "Forbidden");
            return;
        }
        $p = $em->getRepository('HexaaStorageBundle:Principal')->find($pid);
        if (!$p) {
            throw new HttpException(404, "Principal not found.");
            return;
        }
        $rp = $em->getRepository('HexaaStorageBundle:RolePrincipal')->createQueryBuilder('rp')
                ->where('rp.role = :r')
                ->andwhere('rp.principal = :p')
                ->setParameters(array(':r' => $r, ':p' => $p))
                ->getQuery()
                ->getOneOrNullResult();
        if (!$rp) {
            //do nothing?
        } else {
            $em->remove($rp);
            $em->flush();
        }
    }

    /**
     * add entitlement to role
     *
     *
     * @ApiDoc(
     *   section = "Role",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when successful",
     *     204 = "Returned when Role already has the entitlement",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when role is not found"
     *   },
     *   requirements ={
     *     {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="role id"},
     *     {"name"="eid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement id"},
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
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
    public function putEntitlementAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $eid) {
        $em = $this->getDoctrine()->getManager();
        $r = $em->getRepository('HexaaStorageBundle:Role')->find($id);
        if ($request->getMethod()=="PUT" && !$r)
            throw new HttpException(404, "Role not found");
        $o = $r->getOrganization();
        $usr = $this->get('security.context')->getToken()->getUser();
        $usrp = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        if (!in_array($usrp->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($usrp)) {
            throw new HttpException(403, "Forbidden");
            return;
        }
        $e = $em->getRepository('HexaaStorageBundle:Entitlement')->find($eid);
        if (!$e) {
            throw new HttpException(404, "Entitlement not found.");
            return;
        }

        //collect entitlements of organization
        $oeps = $em->getRepository('HexaaStorageBundle:OrganizationEntitlementPack')->findByOrganization($o);
        $es = array();
        foreach ($oeps as $oep) {
            $ep = $oep->getEntitlementPack();
            foreach ($ep->getEntitlements() as $e) {
                if (!in_array($e, $es)) {
                    $es[] = $e;
                }
            }
        }
        $es = array_filter($es);
        if (!in_array($e, $es)) {
            throw new HttpException(400, 'The organization does not have this entitlement!');
            return;
        }
        $statusCode = !$r->hasEntitlement($e) ? 201 : 204;

        if (201 === $statusCode) {
            $r->addEntitlement($e);
            $em->persist($r);
            $em->flush();
        }

        $response = new Response();
        $response->setStatusCode($statusCode);

        if (201 === $statusCode) {
            $response->headers->set('Location', $this->generateUrl(
                            'get_role', array('id' => $r->getId()), true // absolute
                    )
            );
        }

        return $response;
    }

    /**
     * remove entitlement from role
     *
     *
     * @ApiDoc(
     *   section = "Role",
     *   resource = true,
     *   statusCodes = {
     * 	   204 = "Returned on success",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="role id"},
     *      {"name"="eid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement id"},
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
    public function deleteEntitlementAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $eid) {
        $em = $this->getDoctrine()->getManager();
        $r = $em->getRepository('HexaaStorageBundle:Role')->find($id);
        if ($request->getMethod()=="DELETE" && !$r)
            throw new HttpException(404, "Role not found!");
        $o = $r->getOrganization();
        $usr = $this->get('security.context')->getToken()->getUser();
        $usrp = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        if (!in_array($usrp->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($usrp)) {
            throw new HttpException(403, "Forbidden");
            return;
        }
        $e = $em->getRepository('HexaaStorageBundle:Entitlement')->find($eid);
        if (!$e) {
            throw new HttpException(404, "Entitlement not found.");
            return;
        }
        if ($r->hasEntitlement($e)) {
            $r->removeEntitlement($e);
            $em->persist($r);
            $em->flush();
        }
    }
    
    /**
     * get entitlements in role
     *
     *
     * @ApiDoc(
     *   section = "Role",
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
     * @return array
     */
    public function getEntitlementsAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $r = $em->getRepository('HexaaStorageBundle:Role')->find($id);
        if ($request->getMethod()=="GET" && !$r)
            throw new HttpException(404, "Resource not found.");
        $o = $r->getOrganization();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasPrincipal($p)) {
            throw new HttpException(403, "Forbidden");
            return;
        }
        return $r->getEntitlements();
    }

}
