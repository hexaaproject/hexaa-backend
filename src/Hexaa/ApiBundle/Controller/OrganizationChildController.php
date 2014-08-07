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
use Hexaa\StorageBundle\Entity\Role;
use Hexaa\StorageBundle\Form\RoleType;
use Hexaa\StorageBundle\Entity\AttributeValueOrganization;
use Hexaa\StorageBundle\Form\AttributeValueOrganizationType;
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
     *   section = "Organization",
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
    public function cgetManagersAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[cgetOrganizationManagers] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());


        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        if ($request->getMethod() == "GET" && !$o) {
            $errorlog->error($loglbl . "the requested Organization with id=" . $id . " was not found");
            throw new HttpException(404, "Organization not found.");
        }
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
     *   section = "Organization",
     *   resource = true,
     *   statusCodes = {
     * 	   204 = "Returned on success",
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
    public function deleteManagerAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $pid) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[deleteOrganizationManager] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " and pid=" . $pid . " by " . $p->getFedid());

        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        if ($request->getMethod() == "DELETE" && !$o) {
            $errorlog->error($loglbl . "the requested Organization with id=" . $id . " was not found");
            throw new HttpException(404, "Organization not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p) && $pid != $p->getId()) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpExcetion(403, "Forbidden");
            return;
        }
        $p = $em->getRepository('HexaaStorageBundle:Principal')->find($pid);
        if (!$p) {
            $errorlog->error($loglbl . "the requested Principal with id=" . $pid . " was not found");
            throw new HttpException(404, "Principal not found.");
        }
        if ($o->hasManager($p)) {
            $o->removeManager($p);
            $em->persist($o);
            $em->flush();
            $modlog->info($loglbl . "Manager (id=" . $pid . ") was removed from Organization with id=" . $id);
        }
    }

    /**
     * add manager to organization
     *
     *
     * @ApiDoc(
     *   section = "Organization",
     *   resource = true,
     *   statusCodes = {
     * 	   201 = "Returned on success",
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
    public function putManagerAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $pid) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[putOrganizationManager] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " and pid=" . $pid . " by " . $p->getFedid());

        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        if ($request->getMethod() == "PUT" && !$o) {
            $errorlog->error($loglbl . "the requested Organization with id=" . $id . " was not found");
            throw new HttpException(404, "Organization not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpExcetion(403, "Forbidden");
            return;
        }
        $p = $em->getRepository('HexaaStorageBundle:Principal')->find($pid);
        if (!$p) {
            $errorlog->error($loglbl . "the requested Principal with id=" . $pid . " was not found");
            throw new HttpException(404, "Principal not found.");
        }
        if (!$o->hasManager($p)) {
            $o->addManager($p);
            $em->persist($o);
            $em->flush();
            $modlog->info($loglbl . "Manager (id=" . $pid . ") was added to Organization with id=" . $id);
        }
    }

    /**
     * get members of organization
     *
     *
     * @ApiDoc(
     *   section = "Organization",
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
    public function cgetMembersAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[cgetOrganizationMembers] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        if ($request->getMethod() == "GET" && !$o) {
            $errorlog->error($loglbl . "the requested Organization with id=" . $id . " was not found");
            throw new HttpException(404, "Organization not found.");
        }
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
     *   section = "Organization",
     *   resource = true,
     *   statusCodes = {
     * 	   204 = "Returned on success",
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
    public function deleteMemberAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $pid) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[deleteOrganizationMember] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " and pid=" . $pid . " by " . $p->getFedid());

        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        if ($request->getMethod() == "DELETE" && !$o) {
            $errorlog->error($loglbl . "the requested Organization with id=" . $id . " was not found");
            throw new HttpException(404, "Organization not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p) && $pid != $p->getId()) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        $p = $em->getRepository('HexaaStorageBundle:Principal')->find($pid);
        if (!$p) {
            $errorlog->error($loglbl . "the requested Principal with id=" . $pid . " was not found");
            throw new HttpException(404, "Principal not found.");
        }
        if ($o->hasPrincipal($p)) {
            $o->removePrincipal($p);
            $em->persist($o);
            $em->flush();
            $modlog->info($loglbl . "Member (id=" . $pid . ") was removed from Organization with id=" . $id);
        }
    }

    /**
     * add member to organization
     *
     *
     * @ApiDoc(
     *   section = "Organization",
     *   resource = true,
     *   statusCodes = {
     * 	   201 = "Returned on success",
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
    public function putMemberAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $pid) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[putOrganizationMember] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " and pid=" . $pid . " by " . $p->getFedid());

        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        if ($request->getMethod() == "PUT" && !$o) {
            $errorlog->error($loglbl . "the requested Organization with id=" . $id . " was not found");
            throw new HttpException(404, "Organization not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        $p = $em->getRepository('HexaaStorageBundle:Principal')->find($pid);
        if (!$p) {
            $errorlog->error($loglbl . "the requested Principal with id=" . $pid . " was not found");
            throw new HttpException(404, "Principal not found.");
        }
        if (!$o->hasPrincipal($p)) {
            $o->addPrincipal($p);
            $em->persist($o);
            $em->flush();
            $modlog->info($loglbl . "Member (id=" . $pid . ") was added to Organization with id=" . $id);
        }
    }

    /**
     * get roles of organization
     *
     *
     * @ApiDoc(
     *   section = "Organization",
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
    public function cgetRolesAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[cgetOrganizationRoles] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        if ($request->getMethod() == "GET" && !$o) {
            $errorlog->error($loglbl . "the requested Organization with id=" . $id . " was not found");
            throw new HttpException(404, "Organization not found.");
        }
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
     *   section = "Organization",
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
    public function cgetEntitlementsAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[cgetOrganizationEntitlements] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        if ($request->getMethod() == "GET" && !$o) {
            $errorlog->error($loglbl . "the requested Organization with id=" . $id . " was not found");
            throw new HttpException(404, "Organization not found.");
        }
        $oeps = $em->getRepository('HexaaStorageBundle:OrganizationEntitlementPack')->findBy(array(
            "organization" => $o,
            "status" => "accepted"));
        $retarr = array();
        foreach ($oeps as $oep) {
            $ep = $oep->getEntitlementPack();
            foreach ($ep->getEntitlements() as $e) {
                if (!in_array($e, $retarr, true)) {
                    $retarr[] = $e;
                }
            }
        }
        //$retarr = array_filter($retarr);
        //if (empty($retarr)) throw new HttpException(404, "Resource not found.");
        return $retarr;
    }

    /**
     * get entitlement packs of organization
     *
     *
     * @ApiDoc(
     *   section = "Organization",
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
    public function cgetEntitlementpacksAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[cgetOrganizationEntitlementPacks] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        if ($request->getMethod() == "GET" && !$o) {
            $errorlog->error($loglbl . "the requested Organization with id=" . $id . " was not found");
            throw new HttpException(404, "Organization not found");
        }
        $oeps = $em->getRepository('HexaaStorageBundle:OrganizationEntitlementPack')->findByOrganization($o);
        $retarr = array();
        foreach ($oeps as $oep) {
            $ep = $oep->getEntitlementPack();
            if (!in_array($ep, $retarr)) {
                $retarr[] = $ep;
            }
        }
        $retarr = array_filter($retarr);
        //if (empty($retarr)) throw new HttpException(404, "Resource not found.");
        return $oeps;
    }

    /**
     * Organization managers can request any public entitlement packs from
     * services with this call, however link status will be set to "pending".
     * 
     *
     *
     * @ApiDoc(
     *   section = "Organization",
     *   description = "request linking a public entitlement pack to an organization",
     *   resource = true,
     *   statusCodes = {
     *     201 = "Returned when successfully created new link",
     *     204 = "Returned when successfully modified link",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="epid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement package id"},
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
    public function putEntitlementpacksAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $epid) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[putOrganizationEntitlementPacks] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " and epid=" . $epid . " by " . $p->getFedid());

        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        if (!$o) {
            $errorlog->error($loglbl . "the requested Organization with id=" . $id . " was not found");
            throw new HttpException(404, "Organization not found");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpExcetion(403, "Forbidden");
            return;
        }
        $ep = $em->getRepository('HexaaStorageBundle:EntitlementPack')->find($epid);
        if (!$ep) {
            $errorlog->error($loglbl . "the requested EntitlementPack with id=" . $epid . " was not found");
            throw new HttpException(404, "EntitlementPack not found");
        }

        try {
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

        $oep->setStatus("pending");

        $statusCode = $oep->getId() == null ? 201 : 204;

        $em->persist($oep);
        $em->flush();

        $modlog->info($loglbl . "Entitlement Pack (id=" . $epid . ") link status was set to pending with Organization (id=" . $id . ")");

        $response = new Response();
        $response->setStatusCode($statusCode);

        // set the `Location` header only when creating new resources
        if (201 === $statusCode) {
            $response->headers->set('Location', $this->generateUrl(
                            'get_organization_entitlementpacks', array('id' => $oep->getId()), true // absolute
                    )
            );
        }

        return $response;
    }

    /**
     * Service managers can accept any requests to their public entitlement packs
     * with this call, setting them to be "accepted".
     * 
     *
     *
     * @ApiDoc(
     *   section = "Organization",
     *   description = "accept a link request of a public entitlement pack to an organization",
     *   resource = true,
     *   statusCodes = {
     *     201 = "Returned when successfully created new link",
     *     204 = "Returned when successfully modified link",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="epid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement package id"},
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
    public function putEntitlementpacksAcceptAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $epid) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[putOrganizationEntitlementPacksAccept] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " and epid=" . $epid . " by " . $p->getFedid());

        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        if (!$o) {
            $errorlog->error($loglbl . "the requested Organization with id=" . $id . " was not found");
            throw new HttpException(404, "Organization not found");
        }
        $ep = $em->getRepository('HexaaStorageBundle:EntitlementPack')->find($epid);
        if (!$ep) {
            $errorlog->error($loglbl . "the requested EntitlementPack with id=" . $epid . " was not found");
            throw new HttpException(404, "EntitlementPack not found");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$ep->getService()->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpExcetion(403, "Forbidden");
            return;
        }
        try {
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

        $oep->setStatus("accepted");
        $statusCode = $oep->getId() == null ? 201 : 204;

        $em->persist($oep);
        $em->flush();

        $modlog->info($loglbl . "Entitlement Pack (id=" . $epid . ") link status was set to accepted with Organization (id=" . $id . ")");

        $response = new Response();
        $response->setStatusCode($statusCode);

        // set the `Location` header only when creating new resources
        if (201 === $statusCode) {
            $response->headers->set('Location', $this->generateUrl(
                            'get_organization_entitlementpacks', array('id' => $oep->getId()), true // absolute
                    )
            );
        }

        return $response;
    }

    /**
     * link entitlement packs to organization by token
     *
     *
     * @ApiDoc(
     *   section = "Organization",
     *   resource = true,
     *   statusCodes = {
     *     201 = "Returned when successfully created new link",
     *     204 = "Returned when successfully modified link",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="token", "dataType"="string", "required"=true, "requirement"="\d+", "description"="entitlement package token"},
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
    public function putEntitlementpacksTokenAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $token) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[putOrganizationEntitlementPacksToken] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " and token=" . $token . " by " . $p->getFedid());

        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpExcetion(403, "Forbidden");
            return;
        }
        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        if (!$o) {
            $errorlog->error($loglbl . "the requested Organization with id=" . $id . " was not found");
            throw new HttpException(404, "Organization not found");
        }
        $ep = $em->getRepository('HexaaStorageBundle:EntitlementPack')->findOneByToken($token);
        if (!$ep) {
            $errorlog->error($loglbl . "the requested EntitlementPack with id=" . $epid . " was not found");
            throw new HttpException(404, "EntitlementPack not found");
        }

        try {
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
        $oep->setStatus("accepted");
        $statusCode = $oep->getId() == null ? 201 : 204;

        $em->persist($oep);
        $em->flush();

        $modlog->info($loglbl . "Entitlement Pack (id=" . $epid . ") link status was set to accepted with Organization (id=" . $id . ") by token linking");

        $response = new Response();
        $response->setStatusCode($statusCode);

        // set the `Location` header only when creating new resources
        if (201 === $statusCode) {
            $response->headers->set('Location', $this->generateUrl(
                            'get_organization_entitlementpacks', array('id' => $oep->getId()), true // absolute
                    )
            );
        }

        return $response;
    }

    /**
     * unlink entitlement packs from organization
     *
     *
     * @ApiDoc(
     *   section = "Organization",
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
    public function deleteEntitlementpacksAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $epid) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[deleteOrganizationEntitlementPacks] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " and epid=" . $epid . " by " . $p->getFedid());

        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        if (!$o) {
            $errorlog->error($loglbl . "the requested Organization with id=" . $id . " was not found");
            throw new HttpException(404, "Organization not found");
        }

        $ep = $em->getRepository('HexaaStorageBundle:EntitlementPack')->find($epid);
        if (!$ep) {
            $errorlog->error($loglbl . "the requested EntitlementPack with id=" . $epid . " was not found");
            throw new HttpException(404, "EntitlementPack not found");
        }

        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && (!$o->hasManager($p) && (!$ep->getService()->hasManager($p)))) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpExcetion(403, "Forbidden");
            return;
        }

        try {
            $oep = $em->getRepository('HexaaStorageBundle:OrganizationEntitlementPack')->createQueryBuilder('oep')
                    ->where('oep.organization = :o')
                    ->andwhere('oep.entitlementPack = :ep')
                    ->setParameters(array(':o' => $o, ':ep' => $ep))
                    ->getQuery()
                    ->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            $errorlog->error($loglbl . "no link found");
            throw new HttpException(404, "No link found");
            return;
        }


        $em->remove($oep);
        $em->flush();

        $modlog->info($loglbl . "Entitlement Pack (id=" . $epid . ") link with Organization (id=" . $id . ") was deleted");
    }

    /**
     * list available attribute specifications for organization
     *
     *
     * @ApiDoc(
     *   section = "Organization",
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
    public function cgetAttributespecsAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[cgetOrganizationAttributeSpecs] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        if (!$o) {
            $errorlog->error($loglbl . "the requested Organization with id=" . $id . " was not found");
            throw new HttpException(404, "Organization not found");
        }
        $oeps = $em->getRepository('HexaaStorageBundle:OrganizationEntitlementPack')->findByOrganization($o);
        $retarr = array();
        $ss = array();
        foreach ($oeps as $oep) {
            $s = $oep->getEntitlementPack()->getService();
            if (!in_array($s, $ss)) {
                $ss[] = $s;
            }
        }
        foreach ($ss as $s) {
            $sass = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findByService($s);
            foreach ($sass as $sas) {
                if (!in_array($sas->getAttributeSpec(), $retarr, true)) {
                    if ($sas->getAttributeSpec()->getMaintainer() == "manager") {
                        $retarr[] = $sas->getAttributeSpec();
                    }
                }
            }
        }
        $sass = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findByIsPublic(true);
        foreach ($sass as $sas) {
            if (!in_array($sas->getAttributeSpec(), $retarr, true)) {
                if ($sas->getAttributeSpec()->getMaintainer() == "manager") {
                    $retarr[] = $sas->getAttributeSpec();
                }
            }
        }
        $retarr = array_filter($retarr);
        //if (empty($retarr)) throw new HttpException(404, "Resource not found.");
        return $retarr;
    }

    /**
     * This call lists all attribute values of an organization which belongs to the specified attribute specifitacion.
     * 
     *
     * @ApiDoc(
     *   section = "Organization",
     *   description = "list all attribute values of an attribute specification for organization",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="asid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute specification id"},
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
    public function cgetAttributespecsAttributevalueorganizationsAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $asid) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[cgetOrganizationAttributeSpecsAttributeValueOrganization] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . "and asid=" . $asid . " by " . $p->getFedid());

        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        if (!$o) {
            $errorlog->error($loglbl . "the requested Organization with id=" . $id . " was not found");
            throw new HttpException(404, "Organization not found");
        }
        $oeps = $em->getRepository('HexaaStorageBundle:OrganizationEntitlementPack')->findByOrganization($o);
        $ass = array();
        $ss = array();
        foreach ($oeps as $oep) {
            $s = $oep->getEntitlementPack()->getService();
            if (!in_array($s, $ss, true)) {
                $ss[] = $s;
            }
        }
        foreach ($ss as $s) {
            $sass = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findByService($s);
            foreach ($sass as $sas) {
                if (!in_array($sas->getAttributeSpec(), $ass, true)) {
                    if ($sas->getAttributeSpec()->getMaintainer() == "manager") {
                        $ass[] = $sas->getAttributeSpec();
                    }
                }
            }
        }
        $sass = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findByIsPublic(true);
        foreach ($sass as $sas) {
            if (!in_array($sas->getAttributeSpec(), $ass, true)) {
                if ($sas->getAttributeSpec()->getMaintainer() == "manager") {
                    $ass[] = $sas->getAttributeSpec();
                }
            }
        }
        $ass = array_filter($ass);
        //if (empty($retarr)) throw new HttpException(404, "Resource not found.");
        $as = $em->getRepository('HexaaStorageBundle:AttributeSpec')->find($asid);
        if ($request->getMethod() == "GET" && !$as) {
            $errorlog->error($loglbl . "the requested AttributeSpec with id=" . $asid . " was not found");
            throw new HttpException(404, "AttributeSpec not found.");
        }
        if ($request->getMethod() == "GET" && !in_array($as, $ass, true)) {
            throw new HttpException(400, "the Attribute specification is not visible to the organization.");
        }
        $avos = $em->getRepository('HexaaStorageBundle:AttributeValueOrganization')
                ->findBy(array(
            "organization" => $o,
            "attributeSpec" => $as
                )
        );


        return $avos;
    }

    /**
     * list all attribute values of the organization
     *
     *
     * @ApiDoc(
     *   section = "Organization",
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
     * @return array
     */
    public function cgetAttributevalueorganizationAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[cgetOrganizationAttributeValueOrganization] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        if (!$o) {
            $errorlog->error($loglbl . "the requested Organization with id=" . $id . " was not found");
            throw new HttpException(404, "Organization not found.");
            return;
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasPrincipal($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }

        $avos = $em->getRepository('HexaaStorageBundle:AttributeValueOrganization')->findByOrganization($o);

        return $avos;
    }

    /**
     * create new role
     *
     *
     * @ApiDoc(
     *   section = "Role",
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
     *     {"name"="name", "dataType"="string", "required"=true, "requirement"="\..+", "description"="role name"},
     *     {"name"="start_date", "dataType"="DateTime", "required"=true, "requirement"="\..+", "description"="role membership start date"},
     *     {"name"="end_date", "dataType"="DateTime", "required"=false, "requirement"="\..+", "description"="role membership end date"},
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
    public function postRoleAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[postOrganizationRole] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());
        /*
          $s = $em->getRepository('HexaaStorageBundle:Role')->find($id);
          if (!$s) throw new HttpException(404, "Resource not found."); */
        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        if ($request->getMethod() == "POST" && !$o) {
            $errorlog->error($loglbl . "the requested Organization with id=" . $id . " was not found");
            throw new HttpException(404, "Organization not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpExcetion(403, "Forbidden");
            return;
        }
        $r = new Role();
        $r->setOrganization($o);
        return $this->processForm($r, $loglbl);
    }

    private function processForm(Role $r, $loglbl) {
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');

        $em = $this->getDoctrine()->getManager();
        $statusCode = $r->getId() == null ? 201 : 204;

        $form = $this->createForm(new RoleType(), $r);
        $form->bind($this->getRequest());

        if ($form->isValid()) {
            $em->persist($r);
            $em->flush();

            if (201 === $statusCode) {
                $modlog->info($loglbl . "New Role created with id=" . $r->getId());
            } else {
                $modlog->info($loglbl . "Role edited with id=" . $r->getId());
            }

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
     * create attribute value (for organization) details
     *
     *
     * @ApiDoc(
     *   section = "Attribute value (for organization)",
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
     *      {"name"="is_default","dataType"="boolean", "required"=false, "format"="true|false", "description"="set wether to automatically supply attribute value to new services or not"},
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
    public function postAttributevalueorganizationAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $asid) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[postOrganizationAttributeValueOrganization] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $as = $em->getRepository('HexaaStorageBundle:AttributeSpec')->find($asid);
        if (!$as) {
            $errorlog->error($loglbl . "the requested AttributeSpec with id=" . $asid . " was not found");
            throw new HttpException(404, 'AttributeSpec not found.');
        }
        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        if ($request->getMethod() == "POST" && !$o) {
            $errorlog->error($loglbl . "the requested Organization with id=" . $id . " was not found");
            throw new HttpException(404, "Organization not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpExcetion(403, "Forbidden");
            return;
        }
        $avo = new AttributeValueOrganization();
        $avo->setAttributeSpec($as);
        $avo->setOrganization($o);
        return $this->processAVOForm($avo, $loglbl);
    }

    private function processAVOForm(AttributeValueOrganization $avo, $loglbl) {
        $modlog = $this->get('monolog.logger.modification');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $statusCode = $avo->getId() == null ? 201 : 204;

        $form = $this->createForm(new AttributeValueOrganizationType(), $avo);
        $form->bind($this->getRequest());

        if ($form->isValid()) {
            $em->persist($avo);
            $em->flush();

            if (201 === $statusCode) {
                $modlog->info($loglbl . "New attribute value (for organization) was created with id=" . $avo->getId());
            } else {
                $modlog->info($loglbl . "Attribute value (for organization) edited with id=" . $avo->getId());
            }

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_attributevalueorganization', array('id' => $avo->getId()), true // absolute
                        )
                );
            }
            return $response;
        }
        $errorlog->error($loglbl . "Validation error");
        return View::create($form, 400);
    }

    /**
     * list all pending and rejected invitations of the specified organization
     *
     *
     * @ApiDoc(
     *   section = "Organization",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
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
     * @return array
     */
    public function cgetInvitationsAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[cgetOrganizationInvitations] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        if ($request->getMethod() == "GET" && !$o) {
            $errorlog->error($loglbl . "the requested Organization with id=" . $id . " was not found");
            throw new HttpException(404, "Organization not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        $is = $em->getRepository('HexaaStorageBundle:Invitation')->findByOrganization($o);
        return $is;
    }

}
