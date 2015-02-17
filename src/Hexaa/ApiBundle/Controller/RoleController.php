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


use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Hexaa\StorageBundle\Entity\News;
use Hexaa\StorageBundle\Entity\Principal;
use Hexaa\StorageBundle\Entity\Role;
use Hexaa\StorageBundle\Entity\RolePrincipal;
use Hexaa\StorageBundle\Form\RoleEntitlementType;
use Hexaa\StorageBundle\Form\RolePrincipalType;
use Hexaa\StorageBundle\Form\RoleType;
use JMS\Serializer\SerializerBuilder;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints\DateTime;


/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author  Soltész Balázs <solazs@sztaki.hu>
 *
 */
class RoleController extends HexaaController implements PersonalAuthenticatedController {

    /**
     * create new role
     *
     *
     * @Annotations\QueryParam(
     *   name="verbose",
     *   requirements="^([mM][iI][nN][iI][mM][aA][lL]|[nN][oO][rR][mM][aA][lL]|[eE][xX][pP][aA][nN][dD][eE][dD])",
     *   default="normal",
     *   description="Control verbosity of the response.")
     * @Annotations\QueryParam(
     *   name="admin",
     *   requirements="^([tT][rR][uU][eE]|[fF][aA][lL][sS][eE])",
     *   default=false,
     *   description="Run in admin mode")
     *
     * @ApiDoc(
     *   section = "Role",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when role has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when role is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements = {
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *     {"name"="name", "dataType"="string", "required"=true, "requirement"="\..+", "description"="role name"},
     *     {"name"="start_date", "dataType"="DateTime", "required"=false, "requirement"="\..+", "description"="role membership start date"},
     *     {"name"="end_date", "dataType"="DateTime", "required"=false, "requirement"="\..+", "description"="role membership end date"},
     *     {"name"="description", "dataType"="string", "required"=false, "description"="role description"},
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher role
     * @param integer               $id           Organization id
     *
     * @return null
     *
     */
    public function postOrganizationRoleAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                               ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());


        $o = $this->eh->get('Organization', $id, $loglbl);
        $r = new Role();
        $r->setOrganization($o);

        return $this->processForm($r, $loglbl, $request, "POST");
    }

    /**
     * get role details
     *
     *
     * @Annotations\QueryParam(
     *   name="verbose",
     *   requirements="^([mM][iI][nN][iI][mM][aA][lL]|[nN][oO][rR][mM][aA][lL]|[eE][xX][pP][aA][nN][dD][eE][dD])",
     *   default="normal",
     *   description="Control verbosity of the response.")
     * @Annotations\QueryParam(
     *   name="admin",
     *   requirements="^([tT][rR][uU][eE]|[fF][aA][lL][sS][eE])",
     *   default=false,
     *   description="Run in admin mode")
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
     * @param integer               $id           Role id
     *
     * @return Role
     */
    public function getRoleAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                  ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $r = $this->eh->get('Role', $id, $loglbl);

        return $r;
    }

    /**
     * get principals in role
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", default=0, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
     * @Annotations\QueryParam(
     *   name="verbose",
     *   requirements="^([mM][iI][nN][iI][mM][aA][lL]|[nN][oO][rR][mM][aA][lL]|[eE][xX][pP][aA][nN][dD][eE][dD])",
     *   default="normal",
     *   description="Control verbosity of the response.")
     * @Annotations\QueryParam(
     *   name="admin",
     *   requirements="^([tT][rR][uU][eE]|[fF][aA][lL][sS][eE])",
     *   default=false,
     *   description="Run in admin mode")
     *
     *
     * @ApiDoc(
     *   section = "Role",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when role is not found",
     *     409 = "Returned when Role member isolation is enabled"
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
     * @param integer               $id           Role id
     *
     * @return array
     */
    public function cgetRolePrincipalsAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        /* @var $r Role */
        $r = $this->eh->get('Role', $id, $loglbl);

        if ($r->getOrganization()->isIsolateRoleMembers() && !$r->getOrganization()->hasManager($p)) {
            $this->errorlog->error($loglbl . "Can not list members of organization where isolateRoleMembers is true. Role id=" . $r->getId());
            throw new HttpException(409, "Role member isolation is enabled, listing is forbidden.");
        } else {
            $items = $this->em->getRepository('HexaaStorageBundle:RolePrincipal')->findBy(array("role" => $r), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset'));

            if ($request->query->has('limit') || $request->query->has('offset')){
                $itemNumber = $this->em->createQueryBuilder()
                    ->select("COUNT(rp.id)")
                    ->from("HexaaStorageBundle:RolePrincipal", "rp")
                    ->where("rp.role = :r")
                    ->setParameter(":r", $r)
                    ->getQuery()
                    ->getSingleScalarResult();
                return array("item_number" => (int)$itemNumber, "items" => $items);
            } else {
                return $items;
            }

        }
    }

    /**
     * edit role preferences
     *
     *
     * @Annotations\QueryParam(
     *   name="verbose",
     *   requirements="^([mM][iI][nN][iI][mM][aA][lL]|[nN][oO][rR][mM][aA][lL]|[eE][xX][pP][aA][nN][dD][eE][dD])",
     *   default="normal",
     *   description="Control verbosity of the response.")
     * @Annotations\QueryParam(
     *   name="admin",
     *   requirements="^([tT][rR][uU][eE]|[fF][aA][lL][sS][eE])",
     *   default=false,
     *   description="Run in admin mode")
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
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Role id
     *
     *
     * @return View|Response
     */
    public function putRoleAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                  ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $r = $this->eh->get('Role', $id, $loglbl);

        return $this->processForm($r, $loglbl, $request, "PUT");
    }

    /**
     * edit role preferences
     *
     *
     * @Annotations\QueryParam(
     *   name="verbose",
     *   requirements="^([mM][iI][nN][iI][mM][aA][lL]|[nN][oO][rR][mM][aA][lL]|[eE][xX][pP][aA][nN][dD][eE][dD])",
     *   default="normal",
     *   description="Control verbosity of the response.")
     * @Annotations\QueryParam(
     *   name="admin",
     *   requirements="^([tT][rR][uU][eE]|[fF][aA][lL][sS][eE])",
     *   default=false,
     *   description="Run in admin mode")
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
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Role id
     *
     *
     * @return View|Response
     */
    public function patchRoleAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                    ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $r = $this->eh->get('Role', $id, $loglbl);

        return $this->processForm($r, $loglbl, $request, "PATCH");
    }

    private function processForm(Role $r, $loglbl, Request $request, $method = "PUT") {
        $statusCode = $r->getId() == null ? 201 : 204;

        $form = $this->createForm(new RoleType(), $r, array("method" => $method));
        $form->submit($request->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {

            $this->em->persist($r);
            $this->em->flush();
            $this->modlog->info($loglbl . "Role edited with id=" . $r->getId());

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
        $this->errorlog->error($loglbl . "Validation error: \n" . $this->get("serializer")->serialize($form->getErrors(false, true), "json"));

        return View::create($form, 400);
    }

    /**
     * delete role
     *
     *
     * @Annotations\QueryParam(
     *   name="verbose",
     *   requirements="^([mM][iI][nN][iI][mM][aA][lL]|[nN][oO][rR][mM][aA][lL]|[eE][xX][pP][aA][nN][dD][eE][dD])",
     *   default="normal",
     *   description="Control verbosity of the response.")
     * @Annotations\QueryParam(
     *   name="admin",
     *   requirements="^([tT][rR][uU][eE]|[fF][aA][lL][sS][eE])",
     *   default=false,
     *   description="Run in admin mode")
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
     * @param integer               $id           Role id
     *
     *
     */
    public function deleteRoleAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                     ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $r = $this->eh->get('Role', $id, $loglbl);
        $this->em->remove($r);
        $this->em->flush();
        $this->modlog->info($loglbl . "Role with id=" . $id . " deleted");
    }

    /**
     * add principal to role
     *
     * @Annotations\QueryParam(
     *   name="verbose",
     *   requirements="^([mM][iI][nN][iI][mM][aA][lL]|[nN][oO][rR][mM][aA][lL]|[eE][xX][pP][aA][nN][dD][eE][dD])",
     *   default="normal",
     *   description="Control verbosity of the response.")
     * @Annotations\QueryParam(
     *   name="admin",
     *   requirements="^([tT][rR][uU][eE]|[fF][aA][lL][sS][eE])",
     *   default=false,
     *   description="Run in admin mode")
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
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Role id
     * @param integer               $pid          Principal id
     *
     *
     * @return View|Response|void
     */
    public function putRolePrincipalsAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                            ParamFetcherInterface $paramFetcher, $id = 0, $pid = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " and pid=" . $pid . " by " . $p->getFedid());

        $r = $this->eh->get('Role', $id, $loglbl);
        $o = $r->getOrganization();
        $p = $this->eh->get('Principal', $pid, $loglbl);
        if (!$o->hasPrincipal($p)) {
            $this->errorlog->error($loglbl . "the requested Principal with id=" . $pid . " is not a member of the Organization");
            throw new HttpException(400, 'Principal is not a member of the organization');
        }
        $rp = $this->em->getRepository('HexaaStorageBundle:RolePrincipal')->findOneBy(array("principal" => $p, "role" => $r));
        if (!$rp) {
            $rp = new RolePrincipal();
        }
        $request->request->set('role', $id);
        $request->request->set('principal', $pid);

        return $this->processRPForm($rp, $p, $r, $loglbl, $request, "PUT");
    }

    private function processRPForm(RolePrincipal $rp, Principal $p, Role $r, $loglbl, Request $request, $method = "PUT") {
        $statusCode = $rp->getId() == null ? 201 : 204;

        $form = $this->createForm(new RolePrincipalType(), $rp, array("method" => $method));
        $form->submit($request->request->all(), 'PATCH' !== $method);

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

            // the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                    'get_role', array('id' => $rp->getRole()->getId()), true // absolute
                )
                );
            }

            return $response;
        }
        $this->errorlog->error($loglbl . "Validation error: \n" . $this->get("serializer")->serialize($form->getErrors(false, true), "json"));

        return View::create($form, 400);
    }

    /**
     * set principals of a role
     *
     *
     * @Annotations\QueryParam(
     *   name="verbose",
     *   requirements="^([mM][iI][nN][iI][mM][aA][lL]|[nN][oO][rR][mM][aA][lL]|[eE][xX][pP][aA][nN][dD][eE][dD])",
     *   default="normal",
     *   description="Control verbosity of the response.")
     * @Annotations\QueryParam(
     *   name="admin",
     *   requirements="^([tT][rR][uU][eE]|[fF][aA][lL][sS][eE])",
     *   default=false,
     *   description="Run in admin mode")
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
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Role id
     *
     *
     * @return View|Response
     */
    public function putRolePrincipalAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                           ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $r = $this->eh->get('Role', $id, $loglbl);

        return $this->processRRPForm($r, $loglbl, $request, "PUT");
    }

    private function processRRPForm(Role $r, $loglbl, Request $request, $method = "PUT") {
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();

        $errorList = array();

        if (!$request->request->has('principals') && !is_array($request->request->get('principals'))) {
            $errorList[] = "principals array is non-existent or is not an array.";
        } else {
            $principalRequests = $request->request->get('principals');

            $storedRPs = $r->getPrincipals()->toArray();

            $pids = array();
            $dateConstraint = new DateTime();
            $dateConstraint->message = "Invalid date";
            foreach($principalRequests as $principalRequest) {
                if (!isset($principalRequest["principal"])) {
                    $errorList[] = "Missing parameter: principal";
                } else if (!$principalRequest["principal"]) {
                    $errorList[] = "Invalid parameter: " . $principalRequest["principal"];
                } else {
                    $pids[] = $principalRequest["principal"];
                    if (isset($principalRequest["expiration"]) && ($principalRequest['expiration'] != null)) {
                        $validationErrors = $this->get('validator')->validateValue($principalRequest["expiration"], $dateConstraint);
                        if (count($validationErrors) != 0) {
                            $errorList[] = "Date " . $principalRequest['expiration'] . " is not a valid Date.";
                        }
                    }
                }
            }


            // Get the RPs that are in the set and are staying there
            if (count(array_filter($pids)) < 1) {
                $rps = array();
            } else {
                $rps = $this->em->createQueryBuilder()
                    ->select('rp')
                    ->from('HexaaStorageBundle:RolePrincipal', 'rp')
                    ->innerJoin('rp.principal', 'p')
                    ->where('p.id IN (:pids)')
                    ->andWhere('rp.role = :r')
                    ->setParameters(array(":pids" => $pids, ":r" => $r))
                    ->getQuery()
                    ->getResult();
            }

            // Add (and create) the new RPs
            foreach($principalRequests as $principalRequest) {
                $newid = true;
                foreach($rps as $rp) {
                    if ($rp->getPrincipal()->getId() == $principalRequest["principal"])
                        $newid = false;
                }

                if ($newid) {
                    $principal = $this->em->getRepository("HexaaStorageBundle:Principal")->find($principalRequest["principal"]);
                    if ($principal == null) {
                        $errorList[] = "Principal with id " . $principalRequest["principal"] . " does not exists!";
                    }
                    $newrp = new RolePrincipal();
                    $newrp->setPrincipal($principal);
                    if (isset($principalRequest["expiration"]) && ($principalRequest['expiration'] != null)) {
                        $newrp->setExpiration(new \DateTime($principalRequest["expiration"]));
                    }
                    $newrp->setRole($r);
                    $rps[] = $newrp;
                }

            }

            // Check that all Principals are members of the Organization
            foreach($rps as $rp) {
                if (!$r->getOrganization()->hasPrincipal($rp->getPrincipal())) {
                    $errorList[] = "Principal with id " . $rp->getPrincipal()->getId() . " is not member of the Organization, can't add!";
                }
            }

            // If no errors were found, persist changes, else return errors.
            if ($errorList == array()) {

                $removedRPs = array_diff($storedRPs, $rps);
                $addedRPs = array_diff($rps, $storedRPs);

                foreach($addedRPs as $rp) {
                    $this->em->persist($rp);
                }


                $statusCode = ($rps === $r->getPrincipals()->toArray()) ? 204 : 201;
                $ids = "[ ";
                foreach($rps as $rp) {
                    $ids = $ids . $rp->getPrincipal()->getFedid() . ", ";
                }

                $ids = substr($ids, 0, strlen($ids) - 2) . " ]";


                if ($statusCode !== 204) {

                    //Create News object to notify the user

                    if (count($addedRPs) > 0) {
                        $msg = "New principals added: ";
                        foreach($addedRPs as $addedRP) {
                            $msg = $msg . $addedRP->getPrincipal()->getFedid() . ", ";

                            $n = new News();
                            $n->setPrincipal($addedRP->getPrincipal());
                            $n->setTitle("You have been added to Role " . $r->getName());
                            $n->setMessage($p->getFedid() . " has added you to Role " . $r->getName());
                            $n->setTag("principal");
                            $this->em->persist($n);

                            $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());
                        }
                    } else {
                        $msg = "No new services requested, ";
                    }
                    if (count($removedRPs) > 0) {
                        $msg = "principals removed: ";
                        foreach($removedRPs as $removedRP) {
                            $msg = $msg . $removedRP->getPrincipal()->getFedid() . ', ';

                            $n = new News();
                            $n->setPrincipal($removedRP->getPrincipal());
                            $n->setTitle("You have been removed from Role " . $r->getName());
                            $n->setMessage($p->getFedid() . " has removed you from Role " . $r->getName());
                            $n->setTag("principal");
                            $this->em->persist($n);

                            $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());
                        }
                    } else {
                        $msg = $msg . "no principals removed. ";
                    }
                    $msg[strlen($msg) - 2] = '.';

                    $n = new News();
                    $n->setPrincipal($p);
                    $n->setOrganization($r->getOrganization());
                    $n->setTitle("Role members changed");
                    $n->setMessage($p->getFedid() . "has modified the members of Role " . $r->getName() . ': ' . $msg);
                    $n->setTag("organization");
                    $this->em->persist($n);

                    $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());
                }

                foreach($removedRPs as $rp) {
                    $this->em->remove($rp);
                }

                foreach($addedRPs as $rp) {
                    $this->em->persist($rp);
                }

                $this->modlog->info($loglbl . "Members of Role with id=" . $r->getId() . " has been set to " . $ids);
                $this->em->flush();
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

        }

        // Found some errors, return them.

        $response = new Response();
        $response->setStatusCode(400);
        $serializer = SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize(array("code" => 400, "errors" => $errorList), 'json');
        $this->errorlog->error('Validation error: ' . $jsonContent);
        $response->setContent($jsonContent);

        return $response;
    }

    /**
     * remove principal from role
     *
     *
     * @Annotations\QueryParam(
     *   name="verbose",
     *   requirements="^([mM][iI][nN][iI][mM][aA][lL]|[nN][oO][rR][mM][aA][lL]|[eE][xX][pP][aA][nN][dD][eE][dD])",
     *   default="normal",
     *   description="Control verbosity of the response.")
     * @Annotations\QueryParam(
     *   name="admin",
     *   requirements="^([tT][rR][uU][eE]|[fF][aA][lL][sS][eE])",
     *   default=false,
     *   description="Run in admin mode")
     *
     * @ApiDoc(
     *   section = "Role",
     *   resource = true,
     *   statusCodes = {
     *       204 = "Returned on success",
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
     * @param integer               $id           Role id
     * @param integer               $pid          Principal id
     *
     */
    public function deleteRolePrincipalAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                              ParamFetcherInterface $paramFetcher, $id = 0, $pid = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
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
     * @Annotations\QueryParam(
     *   name="verbose",
     *   requirements="^([mM][iI][nN][iI][mM][aA][lL]|[nN][oO][rR][mM][aA][lL]|[eE][xX][pP][aA][nN][dD][eE][dD])",
     *   default="normal",
     *   description="Control verbosity of the response.")
     * @Annotations\QueryParam(
     *   name="admin",
     *   requirements="^([tT][rR][uU][eE]|[fF][aA][lL][sS][eE])",
     *   default=false,
     *   description="Run in admin mode")
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
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Role id
     * @param integer               $eid          Entitlement id
     *
     *
     * @return Response
     */
    public function putRoleEntitlementsAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                              ParamFetcherInterface $paramFetcher, $id = 0, $eid = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " and eid=" . $eid . " by " . $p->getFedid());

        $r = $this->eh->get('Role', $id, $loglbl);
        $o = $r->getOrganization();
        $e = $this->eh->get('Entitlement', $eid, $loglbl);

        //collect entitlements of organization
        $oeps = $this->em->getRepository('HexaaStorageBundle:OrganizationEntitlementPack')->findBy(array("organization" => $o));
        $es = array();
        foreach($oeps as $oep) {
            $ep = $oep->getEntitlementPack();
            foreach($ep->getEntitlements() as $entitlement) {
                if (!in_array($entitlement, $es, true)) {
                    $es[] = $entitlement;
                }
            }
        }
        $es = array_filter($es);
        if (!in_array($e, $es)) {
            $this->errorlog->error($loglbl . "Organization (id=" . $o->getId() . ") does not have the requested Entitlement (id=" . $eid . ")");
            throw new HttpException(400, 'The organization does not have this entitlement!');
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
     * @Annotations\QueryParam(
     *   name="verbose",
     *   requirements="^([mM][iI][nN][iI][mM][aA][lL]|[nN][oO][rR][mM][aA][lL]|[eE][xX][pP][aA][nN][dD][eE][dD])",
     *   default="normal",
     *   description="Control verbosity of the response.")
     * @Annotations\QueryParam(
     *   name="admin",
     *   requirements="^([tT][rR][uU][eE]|[fF][aA][lL][sS][eE])",
     *   default=false,
     *   description="Run in admin mode")
     *
     * @ApiDoc(
     *   section = "Role",
     *   resource = true,
     *   statusCodes = {
     *       204 = "Returned on success",
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
     * @param integer               $id           Role id
     * @param integer               $eid          Entitlement id
     *
     */
    public function deleteRoleEntitlementsAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                                 ParamFetcherInterface $paramFetcher, $id = 0, $eid = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
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
     * @Annotations\QueryParam(
     *   name="verbose",
     *   requirements="^([mM][iI][nN][iI][mM][aA][lL]|[nN][oO][rR][mM][aA][lL]|[eE][xX][pP][aA][nN][dD][eE][dD])",
     *   default="normal",
     *   description="Control verbosity of the response.")
     * @Annotations\QueryParam(
     *   name="admin",
     *   requirements="^([tT][rR][uU][eE]|[fF][aA][lL][sS][eE])",
     *   default=false,
     *   description="Run in admin mode")
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
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Role id
     *
     *
     * @return View|Response
     */
    public function putRoleEntitlementAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                             ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $r = $this->eh->get('Role', $id, $loglbl);

        return $this->processREForm($r, $loglbl, $request, "PUT");
    }

    private function processREForm(Role $r, $loglbl, Request $request, $method = "PUT") {
        $store = $r->getEntitlements()->toArray();

        $form = $this->createForm(new RoleEntitlementType(), $r, array("method" => $method));
        $form->submit($request->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $statusCode = $store === $r->getEntitlements()->toArray() ? 204 : 201;
            $this->em->persist($r);
            $this->em->flush();
            $ids = "[ ";
            foreach($r->getEntitlements() as $e) {
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
        $this->errorlog->error($loglbl . "Validation error: \n" . $this->get("serializer")->serialize($form->getErrors(false, true), "json"));

        return View::create($form, 400);
    }

    /**
     * get entitlements in role
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", default=0, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
     * @Annotations\QueryParam(
     *   name="verbose",
     *   requirements="^([mM][iI][nN][iI][mM][aA][lL]|[nN][oO][rR][mM][aA][lL]|[eE][xX][pP][aA][nN][dD][eE][dD])",
     *   default="normal",
     *   description="Control verbosity of the response.")
     * @Annotations\QueryParam(
     *   name="admin",
     *   requirements="^([tT][rR][uU][eE]|[fF][aA][lL][sS][eE])",
     *   default=false,
     *   description="Run in admin mode")
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
     *   output="array<Hexaa\StorageBundle\Entity\Entitlement>"
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Role id
     *
     * @return array
     */
    public function cgetRoleEntitlementsAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        /* @var $r Role */
        $r = $this->eh->get('Role', $id, $loglbl);

        if ($request->query->has('limit') || $request->query->has('offset')){
            $retarr = array_slice($r->getEntitlements()->toArray(), $paramFetcher->get('offset'), $paramFetcher->get('limit'));
            return array("item_number" => (int)count($r->getEntitlements()->toArray()), "items" => $retarr);
        } else {
            return $r->getEntitlements();
        }
    }

}
