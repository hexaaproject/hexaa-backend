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
use Hexaa\StorageBundle\Form\RoleEntitlementType;
use Hexaa\StorageBundle\Form\RolePrincipalType;
use Hexaa\StorageBundle\Form\RoleRolePrincipalType;
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
     *  },
     *   output="Hexaa\StorageBundle\Entity\Role"
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
        $loglbl = "[getRole] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $r = $em->getRepository('HexaaStorageBundle:Role')->find($id);
        if ($request->getMethod() == "GET" && !$r) {
            $errorlog->error($loglbl . "the requested Role with id=" . $id . " was not found");
            throw new HttpException(404, "Resource not found.");
        }
        $o = $r->getOrganization();
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasPrincipal($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        return $r;
    }

    /**
     * get principals in role
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
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
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\Principal>"
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
        $loglbl = "[getRolePrincipals] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $r = $em->getRepository('HexaaStorageBundle:Role')->find($id);
        if ($request->getMethod() == "GET" && !$r) {
            $errorlog->error($loglbl . "the requested Role with id=" . $id . " was not found");
            throw new HttpException(404, "Resource not found.");
        }
        $o = $r->getOrganization();
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasPrincipal($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        return $em->getRepository('HexaaStorageBundle:RolePrincipal')->findBy(array("role" => $r), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset'));
        //return $r->getPrincipals();
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
     *     {"name"="start_date", "dataType"="DateTime", "required"=false, "description"="organization entity id"},
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
        $loglbl = "[putRole] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $r = $em->getRepository('HexaaStorageBundle:Role')->find($id);
        if ($request->getMethod() == "PUT" && !$r) {
            $errorlog->error($loglbl . "the requested Role with id=" . $id . " was not found");
            throw new HttpException(404, "Resource not found.");
        }
        $o = $r->getOrganization();
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        return $this->processForm($r, $loglbl, "PUT");
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
     *     {"name"="start_date", "dataType"="DateTime", "required"=false, "description"="organization entity id"},
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
    public function patchAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $loglbl = "[patchRole] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $r = $em->getRepository('HexaaStorageBundle:Role')->find($id);
        if ($request->getMethod() == "PUT" && !$r) {
            $errorlog->error($loglbl . "the requested Role with id=" . $id . " was not found");
            throw new HttpException(404, "Resource not found.");
        }
        $o = $r->getOrganization();
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        return $this->processForm($r, $loglbl, "PATCH");
    }

    private function processForm(Role $r, $loglbl, $method = "PUT") {
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $statusCode = $r->getId() == null ? 201 : 204;

        $form = $this->createForm(new RoleType(), $r, array("method" => $method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {

            $em->persist($r);
            $em->flush();
            $modlog->info($loglbl . "Role edited with id=" . $r->getId());

            $response = new Response();
            $response->setStatusCode($statusCode);



            return $response;
        }
        $errorlog->error($loglbl . "Validation error");
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
        $loglbl = "[deleteRole] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $r = $em->getRepository('HexaaStorageBundle:Role')->find($id);
        if ($request->getMethod() == "DELETE" && !$r) {
            $errorlog->error($loglbl . "the requested Role with id=" . $id . " was not found");
            throw new HttpException(404, "Resource not found.");
        }
        $o = $r->getOrganization();
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        $em->remove($r);
        $em->flush();
        $modlog->info($loglbl . "Role with id=" . $id . " deleted");
    }

    /**
     * add principal to role
     *
     *
     * @ApiDoc(
     *   section = "Role",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when successful",
     *     204 = "Returned when principal is already a member",
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
    public function putPrincipalsAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $pid) {
        $loglbl = "[putRolePrincipals] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " and pid=" . $pid . " by " . $p->getFedid());

        $r = $em->getRepository('HexaaStorageBundle:Role')->find($id);
        if ($request->getMethod() == "PUT" && !$r) {
            $errorlog->error($loglbl . "the requested Role with id=" . $id . " was not found");
            throw new HttpException(404, "Role not found.");
        }
        $o = $r->getOrganization();
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        $p = $em->getRepository('HexaaStorageBundle:Principal')->find($pid);
        if (!$p) {
            $errorlog->error($loglbl . "the requested Principal with id=" . $pid . " was not found");
            throw new HttpException(404, "Principal not found.");
            return;
        }
        if (!$o->hasPrincipal($p)) {
            $errorlog->error($loglbl . "the requested Principal with id=" . $pid . " is not a member of the Organization");
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
        }
        return $this->processRPForm($rp, $p, $r, $loglbl, "PUT");
    }

    private function processRPForm(RolePrincipal $rp, Principal $p, Role $r, $loglbl, $method = "PUT") {
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $statusCode = $rp->getId() == null ? 201 : 204;

        $form = $this->createForm(new RolePrincipalType(), $rp, array("method" => $method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            if ($statusCode === 201){
                $rp->setRole($r);
            }
            $rp->setPrincipal($p);
            $em->persist($rp);
            $em->flush();

            if (201 === $statusCode) {
                $modlog->info($loglbl . "Principal (id=" . $p->getId() . " added to Role with id=" . $rp->getRole()->getId());
            } else {
                $modlog->info($loglbl . "Principal (id=" . $p->getId() . " is already a member of Role with id=" . $rp->getRole()->getId());
            }

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
        $errorlog->error($loglbl . "Validation error");
        return View::create($form, 400);
    }

    /**
     * set principals of a role
     *
     *
     * @ApiDoc(
     *   section = "Role",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when successful",
     *     204 = "Returned when principal is already a member",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when role is not found"
     *   },
     *   requirements ={
     *     {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="role id"},
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *     {"name"="principals[][expiration]", "dataType"="DateTime", "required"=false, "description"="expiration date (can be null)"},
     *     {"name"="principals[][principal]", "dataType"="integer", "format"="\d+", "required"=true, "description"="principal ID"}
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
    public function putPrincipalAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $loglbl = "[putRolePrincipal] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $r = $em->getRepository('HexaaStorageBundle:Role')->find($id);
        if ($request->getMethod() == "PUT" && !$r) {
            $errorlog->error($loglbl . "the requested Role with id=" . $id . " was not found");
            throw new HttpException(404, "Role not found.");
        }
        $o = $r->getOrganization();
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }

        return $this->processRRPForm($r, $loglbl, "PUT");
    }

    private function processRRPForm(Role $r, $loglbl, $method = "PUT") {
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();

        if ($this->getRequest()->request->has('principals')) {
            $ps = $this->getRequest()->request->get('principals');
            for ($i = 0; $i < count($ps); $i++) {
                $ps[$i]['role'] = $r->getId();
            }
            $this->getRequest()->request->set('principals', $ps);
        }

        $store = $r->getPrincipals()->toArray();



        $form = $this->createForm(new RoleRolePrincipalType(), $r, array("method" => $method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $statusCode = $store === $r->getPrincipals()->toArray() ? 204 : 201;
            $em->persist($r);
            $em->flush();
            $ids = "[ ";
            foreach ($r->getPrincipals() as $p) {
                $ids = $ids . $p->getId() . ", ";
            }
            $ids = substr($ids, 0, strlen($ids) - 2) . " ]";
            $modlog->info($loglbl . "Principals of Role with id=" . $r->getId()) . " has been set to " . $ids;
            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_role', array('id' => $r->getId()), true // absolute
                        )
                );
            }

            return $response;
        }
        $errorlog->error($loglbl . "Validation error");
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
        $loglbl = "[deleteRolePrincipal] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " and pid=" . $pid . " by " . $p->getFedid());

        $em = $this->getDoctrine()->getManager();
        $r = $em->getRepository('HexaaStorageBundle:Role')->find($id);
        if ($request->getMethod() == "DELETE" && !$r) {
            $errorlog->error($loglbl . "the requested Role with id=" . $id . " was not found");
            throw new HttpException(404, "Role not found!");
        }
        $o = $r->getOrganization();
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        $p = $em->getRepository('HexaaStorageBundle:Principal')->find($pid);
        if (!$p) {
            $errorlog->error($loglbl . "the requested Principal with id=" . $pid . " was not found");
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
            $modlog->info($loglbl . "Principal (id=" . $pid . ") was removed from Role with id=" . $id);
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
    public function putEntitlementsAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $eid) {
        $loglbl = "[putRoleEntitlements] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " and eid=" . $eid . " by " . $p->getFedid());

        $r = $em->getRepository('HexaaStorageBundle:Role')->find($id);
        if ($request->getMethod() == "PUT" && !$r) {
            $errorlog->error($loglbl . "the requested Role with id=" . $id . " was not found");
            throw new HttpException(404, "Role not found");
        }
        $o = $r->getOrganization();
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        $e = $em->getRepository('HexaaStorageBundle:Entitlement')->find($eid);
        if (!$e) {
            $errorlog->error($loglbl . "the requested Entitlement with id=" . $eid . " was not found");
            throw new HttpException(404, "Entitlement not found.");
            return;
        }

        //collect entitlements of organization
        $oeps = $em->getRepository('HexaaStorageBundle:OrganizationEntitlementPack')->findByOrganization($o);
        $es = array();
        foreach ($oeps as $oep) {
            $ep = $oep->getEntitlementPack();
            foreach ($ep->getEntitlements() as $entitlement) {
                if (!in_array($entitlement, $es, true)) {
                    $es[] = $entitlement;
                }
            }
        }
        $es = array_filter($es);
        if (!in_array($e, $es)) {
            $errorlog->error($loglbl . "Organization (id=" . $o->getId() . ") does not have the requested Entitlement (id=" . $eid . ")");
            throw new HttpException(400, 'The organization does not have this entitlement!');
            return;
        }
        $statusCode = !$r->hasEntitlement($e) ? 201 : 204;

        if (201 === $statusCode) {
            $r->addEntitlement($e);
            $em->persist($r);
            $em->flush();
            $modlog->info($loglbl . "Entitlement (id=" . $e->getId() . ") added to Role (id=" . $r->getId() . ")");
        } else {
            $modlog->info($loglbl . "Role (id=" . $r->getId() . ") already has Entitlement (id=" . $e->getId() . ")");
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
    public function deleteEntitlementsAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $eid) {
        $loglbl = "[deleteRoleEntitlement] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " and eid=" . $eid . " by " . $p->getFedid());

        $r = $em->getRepository('HexaaStorageBundle:Role')->find($id);
        if ($request->getMethod() == "DELETE" && !$r) {
            $errorlog->error($loglbl . "the requested Role with id=" . $id . " was not found");
            throw new HttpException(404, "Role not found!");
        }
        $o = $r->getOrganization();
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        $e = $em->getRepository('HexaaStorageBundle:Entitlement')->find($eid);
        if (!$e) {
            $errorlog->error($loglbl . "the requested Entitlement with id=" . $eid . " was not found");
            throw new HttpException(404, "Entitlement not found.");
            return;
        }
        if ($r->hasEntitlement($e)) {
            $r->removeEntitlement($e);
            $em->persist($r);
            $em->flush();

            $modlog->info($loglbl . "Entitlement (id=" . $e->getId() . ") removed from Role (id=" . $r->getId());
        }
    }

    /**
     * set entitlements of a role
     *
     *
     * @ApiDoc(
     *   section = "Role",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when successful",
     *     204 = "Returned when entitlements are already added",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when role is not found"
     *   },
     *   requirements ={
     *     {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="role id"},
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   input = "Hexaa\StorageBundle\Form\RoleEntitlementType"
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
    public function putEntitlementAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $loglbl = "[putRoleEntitlement] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $r = $em->getRepository('HexaaStorageBundle:Role')->find($id);
        if ($request->getMethod() == "PUT" && !$r) {
            $errorlog->error($loglbl . "the requested Role with id=" . $id . " was not found");
            throw new HttpException(404, "Role not found.");
        }
        $o = $r->getOrganization();
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }

        return $this->processREForm($r, $loglbl, "PUT");
    }

    private function processREForm(Role $r, $loglbl, $method = "PUT") {
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $store = $r->getEntitlements()->toArray();



        $form = $this->createForm(new RoleEntitlementType(), $r, array("method" => $method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $statusCode = $store === $r->getEntitlements()->toArray() ? 204 : 201;
            $em->persist($r);
            $em->flush();
            $ids = "[ ";
            foreach ($r->getEntitlements() as $e) {
                $ids = $ids . $e->getId() . ", ";
            }
            $ids = substr($ids, 0, strlen($ids) - 2) . " ]";
            $modlog->info($loglbl . "Entitlements of Role with id=" . $r->getId()) . " has been set to " . $ids;
            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_role', array('id' => $r->getId()), true // absolute
                        )
                );
            }

            return $response;
        }
        $errorlog->error($loglbl . "Validation error");
        return View::create($form, 400);
    }

    /**
     * get entitlements in role
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
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
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\Entitlement>"
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
    public function cgetEntitlementsAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $loglbl = "[getRoleEntitlements] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $r = $em->getRepository('HexaaStorageBundle:Role')->find($id);
        if ($request->getMethod() == "GET" && !$r) {
            $errorlog->error($loglbl . "the requested Role with id=" . $id . " was not found");
            throw new HttpException(404, "Role not found.");
        }
        $o = $r->getOrganization();
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasPrincipal($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        $retarr = array_slice($r->getEntitlements()->toArray(), $paramFetcher->get('offset'), $paramFetcher->get('limit'));
        return $retarr;
    }

}
