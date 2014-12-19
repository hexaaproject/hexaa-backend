<?php

/*
 * Copyright 2014 MTA-SZTAKI.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Hexaa\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations;
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
class RoleController extends HexaaController implements ClassResourceInterface, PersonalAuthenticatedController {

    /**
     * get role details
     *
     *
     * @ApiDoc(
     *   section = "Role",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when role is not found"
     *   },
     *   tags = {"organization member" = "#5BA578"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="role id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   output="Hexaa\StorageBundle\Entity\Role"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer $id Role id
     *
     * @return Role
     */
    public function getAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $r = $this->eh->get('Role', $id, $loglbl);
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
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when role is not found"
     *   },
     *   tags = {"organization member" = "#5BA578"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="role id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   output="array<Hexaa\StorageBundle\Entity\Principal>"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer $id Role id
     *
     * @return array
     */
    public function cgetPrincipalsAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $r = $this->eh->get('Role', $id, $loglbl);
        return $this->em->getRepository('HexaaStorageBundle:RolePrincipal')->findBy(array("role" => $r), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset'));
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
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when role is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="role id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *     {"name"="name", "dataType"="string", "required"=true, "description"="organization name"},
     *     {"name"="start_date", "dataType"="DateTime", "required"=false, "description"="organization entity id"},
     *     {"name"="end_date", "dataType"="DateTime", "required"=false, "description"="organization url"},
     *     {"name"="description", "dataType"="string", "required"=false, "description"="role description"},
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request $request the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer $id Role id
     *
     *
     * @return View|Response
     */
    public function putAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $r = $this->eh->get('Role', $id, $loglbl);
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
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when role is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="role id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *     {"name"="name", "dataType"="string", "required"=true, "description"="organization name"},
     *     {"name"="start_date", "dataType"="DateTime", "required"=false, "description"="organization entity id"},
     *     {"name"="end_date", "dataType"="DateTime", "required"=false, "description"="organization url"},
     *     {"name"="description", "dataType"="string", "required"=false, "description"="role description"},
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request $request the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer $id Role id
     *
     *
     * @return View|Response
     */
    public function patchAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $r = $this->eh->get('Role', $id, $loglbl);
        return $this->processForm($r, $loglbl, "PATCH");
    }

    private function processForm(Role $r, $loglbl, $method = "PUT") {
        $statusCode = $r->getId() == null ? 201 : 204;

        $form = $this->createForm(new RoleType(), $r, array("method" => $method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {

            $this->em->persist($r);
            $this->em->flush();
            $this->modlog->info($loglbl . "Role edited with id=" . $r->getId());

            $response = new Response();
            $response->setStatusCode($statusCode);



            return $response;
        }
        $this->errorlog->error($loglbl . "Validation error");
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
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when role is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="role id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     * 
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer $id Role id
     *
     * 
     */
    public function deleteAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $r = $this->eh->get('Role', $id, $loglbl);
        $this->em->remove($r);
        $this->em->flush();
        $this->modlog->info($loglbl . "Role with id=" . $id . " deleted");
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
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when role is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
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
     * @param Request $request the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer $id Role id
     * @param integer $pid Principal id
     *
     *
     * @return View|Response|void
     */
    public function putPrincipalsAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0, $pid = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " and pid=" . $pid . " by " . $p->getFedid());

        $r = $this->eh->get('Role', $id, $loglbl);
        $o = $r->getOrganization();
        $p = $this->eh->get('Principal', $pid, $loglbl);
        if (!$o->hasPrincipal($p)) {
            $this->errorlog->error($loglbl . "the requested Principal with id=" . $pid . " is not a member of the Organization");
            throw new HttpException(400, 'Principal is not a member of the organization');
            return;
        }
        $rp = $this->em->getRepository('HexaaStorageBundle:RolePrincipal')->findOneBy(array("principal" => $p, "role" => $r));
        if (!$rp) {
            $rp = new RolePrincipal();
        }
        $request->request->set('role', $id);
        $request->request->set('principal', $pid);
        return $this->processRPForm($rp, $p, $r, $loglbl, "PUT");
    }

    private function processRPForm(RolePrincipal $rp, Principal $p, Role $r, $loglbl, $method = "PUT") {
        $statusCode = $rp->getId() == null ? 201 : 204;

        $form = $this->createForm(new RolePrincipalType(), $rp, array("method" => $method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $this->em->persist($rp);
            $this->em->flush();


            if (201 === $statusCode) {
                $this->modlog->info($loglbl . "Principal (id=" . $p->getId() . " added to Role with id=" . $rp->getRole()->getId());
            } else {
                $this->modlog->info($loglbl . "Principal (id=" . $p->getId() . " is already a member of Role with id=" . $rp->getRole()->getId());
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
        $this->errorlog->error($loglbl . "Validation error");
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
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when role is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
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
     * @param Request $request the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer $id Role id
     *
     *
     * @return View|Response
     */
    public function putPrincipalAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $r = $this->eh->get('Role', $id, $loglbl);

        return $this->processRRPForm($r, $loglbl, "PUT");
    }

    private function processRRPForm(Role $r, $loglbl, $method = "PUT") {
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
            $this->em->persist($r);
            $this->em->flush();
            $ids = "[ ";
            foreach ($r->getPrincipals() as $p) {
                $ids = $ids . $p->getId() . ", ";
            }
            $ids = substr($ids, 0, strlen($ids) - 2) . " ]";
            $this->modlog->info($loglbl . "Principals of Role with id=" . $r->getId() . " has been set to " . $ids);
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
        $this->errorlog->error($loglbl . "Validation error");
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
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="role id"},
     *      {"name"="pid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="principal id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     * 
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher
     * @param integer $id Role id
     * @param integer $pid Principal id
     *
     */
    public function deletePrincipalAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0, $pid = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " and pid=" . $pid . " by " . $p->getFedid());

        $r = $this->eh->get('Role', $id, $loglbl);
        $p = $this->eh->get('Principal', $pid, $loglbl);
        $rp = $this->em->getRepository('HexaaStorageBundle:RolePrincipal')->createQueryBuilder('rp')
                ->where('rp.role = :r')
                ->andwhere('rp.principal = :p')
                ->setParameters(array(':r' => $r, ':p' => $p))
                ->getQuery()
                ->getOneOrNullResult();
        if (!$rp) {
            //do nothing?
        } else {
            $this->em->remove($rp);
            $this->em->flush();
            $this->modlog->info($loglbl . "Principal (id=" . $pid . ") was removed from Role with id=" . $id);
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
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when role is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
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
     * @param Request $request the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer $id Role id
     * @param integer $eid Entitlement id
     *
     *
     * @return Response
     */
    public function putEntitlementsAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0, $eid = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " and eid=" . $eid . " by " . $p->getFedid());

        $r = $this->eh->get('Role', $id, $loglbl);
        $o = $r->getOrganization();
        $e = $this->eh->get('Entitlement', $eid, $loglbl);

        //collect entitlements of organization
        $oeps = $this->em->getRepository('HexaaStorageBundle:OrganizationEntitlementPack')->findByOrganization($o);
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
            $this->errorlog->error($loglbl . "Organization (id=" . $o->getId() . ") does not have the requested Entitlement (id=" . $eid . ")");
            throw new HttpException(400, 'The organization does not have this entitlement!');
            return;
        }
        $statusCode = !$r->hasEntitlement($e) ? 201 : 204;

        if (201 === $statusCode) {
            $r->addEntitlement($e);
            $this->em->persist($r);
            $this->em->flush();
            $this->modlog->info($loglbl . "Entitlement (id=" . $e->getId() . ") added to Role (id=" . $r->getId() . ")");
        } else {
            $this->modlog->info($loglbl . "Role (id=" . $r->getId() . ") already has Entitlement (id=" . $e->getId() . ")");
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
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="role id"},
     *      {"name"="eid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     * 
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer $id Role id
     * @param integer $eid Entitlement id
     *
     */
    public function deleteEntitlementsAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0, $eid = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " and eid=" . $eid . " by " . $p->getFedid());

        $r = $this->eh->get('Role', $id, $loglbl);
        $e = $this->eh->get('Entitlement', $eid, $loglbl);
        if ($r->hasEntitlement($e)) {
            $r->removeEntitlement($e);
            $this->em->persist($r);
            $this->em->flush();

            $this->modlog->info($loglbl . "Entitlement (id=" . $e->getId() . ") removed from Role (id=" . $r->getId());
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
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when role is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
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
     * @param Request $request the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer $id Role id
     *
     *
     * @return View|Response
     */
    public function putEntitlementAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $r = $this->eh->get('Role', $id, $loglbl);

        return $this->processREForm($r, $loglbl, "PUT");
    }

    private function processREForm(Role $r, $loglbl, $method = "PUT") {
        $store = $r->getEntitlements()->toArray();

        $form = $this->createForm(new RoleEntitlementType(), $r, array("method" => $method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $statusCode = $store === $r->getEntitlements()->toArray() ? 204 : 201;
            $this->em->persist($r);
            $this->em->flush();
            $ids = "[ ";
            foreach ($r->getEntitlements() as $e) {
                $ids = $ids . $e->getId() . ", ";
            }
            $ids = substr($ids, 0, strlen($ids) - 2) . " ]";
            $this->modlog->info($loglbl . "Entitlements of Role with id=" . $r->getId() . " has been set to " . $ids);
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
        $this->errorlog->error($loglbl . "Validation error");
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
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when role is not found"
     *   },
     *   tags = {"organization member" = "#5BA578"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="role id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   output="array<Hexaa\StorageBundle\Entity\Entitlement>"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer $id Role id
     *
     * @return array
     */
    public function cgetEntitlementsAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $r = $this->eh->get('Role', $id, $loglbl);
        $retarr = array_slice($r->getEntitlements()->toArray(), $paramFetcher->get('offset'), $paramFetcher->get('limit'));
        return $retarr;
    }

}
