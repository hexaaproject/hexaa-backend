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

use Doctrine\ORM\NoResultException;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Hexaa\StorageBundle\Entity\AttributeValueOrganization;
use Hexaa\StorageBundle\Entity\EntitlementPack;
use Hexaa\StorageBundle\Entity\News;
use Hexaa\StorageBundle\Entity\Organization;
use Hexaa\StorageBundle\Entity\OrganizationEntitlementPack;
use Hexaa\StorageBundle\Entity\Role;
use Hexaa\StorageBundle\Form\OrganizationManagerType;
use Hexaa\StorageBundle\Form\OrganizationPrincipalType;
use JMS\Serializer\SerializerBuilder;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Description of OrganizationChildController
 *
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class OrganizationChildController extends HexaaController implements PersonalAuthenticatedController {

    /**
     * get managers of organization
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
     * @ApiDoc(
     *   section = "Organization",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\Principal>"
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher
     * @param integer               $id           Organization id
     *
     * @return array
     */
    public function cgetManagersAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());


        $o = $this->eh->get('Organization', $id, $loglbl);

        if ($request->query->has('limit') || $request->query->has('offset')){
            $ps = array_slice($o->getManagers()->toArray(), $paramFetcher->get('offset'), $paramFetcher->get('limit'));
            return array("item_number" => (int)count($o->getManagers()->toArray()), "items" => $ps);
        } else {
            return $o->getManagers();
        }
    }

    /**
     * get number of organization managers
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
     *   section = "Organization",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
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
     * @param integer               $id           Organization id
     *
     * @return array
     */
    public function getManagerCountAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                          ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);
        $retarr = array("count" => count($o->getManagers()->toArray()));

        return $retarr;
    }

    /**
     * get number of organization members
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
     *   section = "Organization",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
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
     * @param integer               $id           Organization id
     *
     * @return array
     */
    public function getMemberCountAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                         ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);
        $retarr = array("count" => count($o->getPrincipals()->toArray()));

        return $retarr;
    }

    /**
     * remove manager from organization
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
     *   section = "Organization",
     *   resource = true,
     *   statusCodes = {
     *       204 = "Returned on success",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
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
     * @param integer               $id           Organization id
     * @param int                   $pid          Principal id
     */
    public function deleteManagerAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                        ParamFetcherInterface $paramFetcher, $id = 0, $pid = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " and pid=" . $pid . " by " . $p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);
        $p = $this->eh->get('Principal', $pid, $loglbl);
        if ($o->hasManager($p)) {
            $o->removeManager($p);
            $this->em->persist($o);

            //Create News object to notify the user
            $n = new News();
            $n->setPrincipal($p);
            $n->setOrganization($o);
            $n->setTitle("Organization management changed");
            $n->setMessage($p->getFedid() . " is no longer a manager of organization " . $o->getName());
            $n->setTag("organization_manager");
            $this->em->persist($n);
            $this->em->flush();
            $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());

            $this->modlog->info($loglbl . "Manager (id=" . $pid . ") was removed from Organization with id=" . $id);
        }
    }

    /**
     * add manager to organization
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
     *   section = "Organization",
     *   resource = true,
     *   statusCodes = {
     *       201 = "Returned on success",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="pid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="principal id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View(statusCode=201)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher
     * @param integer               $id           Organization id
     * @param integer               $pid          Principal id
     *
     */
    public function putManagersAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                      ParamFetcherInterface $paramFetcher, $id = 0, $pid = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " and pid=" . $pid . " by " . $p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);
        $p = $this->eh->get('Principal', $pid, $loglbl);
        if (!$o->hasManager($p)) {
            $o->addManager($p);
            $this->em->persist($o);

            //Create News object to notify the user
            $n = new News();
            $n->setPrincipal($p);
            $n->setOrganization($o);
            $n->setTitle("Organization management changed");
            $n->setMessage($p->getFedid() . " is now a manager of organization " . $o->getName());
            $n->setTag("organization_manager");
            $this->em->persist($n);
            $this->em->flush();
            $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());

            $this->modlog->info($loglbl . "Manager (id=" . $pid . ") was added to Organization with id=" . $id);
        }
    }

    /**
     * Set managers of an organization<br>
     * Only members can be added as managers. To add new people as managers, first add them as members!<br>
     * Note: Admins & organization managers only!
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
     *   section = "Organization",
     *   resource = false,
     *   description = "set managers of an organization",
     *   statusCodes = {
     *     201 = "Returned when successful",
     *     204 = "Returned when managers are already added",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when role is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *     {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   input = "Hexaa\StorageBundle\Form\OrganizationManagerType"
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
     */
    public function putManagerAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                     ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);

        return $this->processOMForm($o, $loglbl, $request, "PUT");
    }

    private function processOMForm(Organization $o, $loglbl, Request $request, $method = "PUT") {


        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $store = $o->getManagers()->toArray();

        $form = $this->createForm(new OrganizationManagerType(), $o, array("method" => $method));
        $form->submit($request->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $statusCode = $store === $o->getManagers()->toArray() ? 204 : 201;
            $ids = "[ ";
            foreach($o->getManagers() as $m) {
                $ids = $ids . $m->getId() . ", ";
            }
            $ids = substr($ids, 0, strlen($ids) - 2) . " ]";
            $this->modlog->info($loglbl . "Managers of Organization with id=" . $o->getId() . " has been set to " . $ids);

            $this->em->persist($o);

            if ($statusCode !== 204) {

                //Create News object to notify the user
                $removed = array_diff($store, $o->getManagers()->toArray());
                $added = array_diff($o->getManagers()->toArray(), $store);

                if (count($added) > 0) {
                    $msg = "New managers added: ";
                    foreach($added as $addedP) {
                        $msg = $msg . $addedP->getFedid() . ", ";

                        $n = new News();
                        $n->setPrincipal($p);
                        $n->setTitle("Organization management changed");
                        $n->setMessage("You are now a manager of organization" . $o->getName());
                        $n->setTag("organization_manager");
                        $this->em->persist($n);

                        $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());
                    }
                } else {
                    $msg = "No new managers added, ";
                }
                if (count($removed) > 0) {
                    $msg = "Managers removed: ";
                    foreach($removed as $removedP) {
                        $msg = $msg . $removedP->getFedid() . ', ';

                        $n = new News();
                        $n->setPrincipal($p);
                        $n->setTitle("Organization management changed");
                        $n->setMessage("You are no longer a manager of organization" . $o->getName());
                        $n->setTag("organization_manager");
                        $this->em->persist($n);

                        $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());
                    }
                } else {
                    $msg = $msg . "no managers removed. ";
                }
                $msg[strlen($msg) - 2] = '.';

                $n = new News();
                $n->setPrincipal($p);
                $n->setOrganization($o);
                $n->setTitle("Organization management changed");
                $n->setMessage($o->getName() . ': ' . $msg);
                $n->setTag("organization_manager");
                $this->em->persist($n);

                $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());
            }
            $this->em->flush();


            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                    'get_organization', array('id' => $o->getId()), true // absolute
                )
                );
            }

            return $response;
        }
        $this->errorlog->error($loglbl . "Validation error: \n" . $this->get("serializer")->serialize($form->getErrors(false, true), "json"));

        return View::create($form, 400);
    }

    /**
     * get members of organization
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
     * @ApiDoc(
     *   section = "Organization",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found",
     *     409 = "Returned when Organization member isolation is enabled"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\Principal>"
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher
     * @param integer               $id           Organization id
     *
     * @return array
     */
    public function cgetMembersAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        /* @var $o Organization */
        $o = $this->eh->get('Organization', $id, $loglbl);
        if ($o->isIsolateMembers() && !$o->hasManager($p) && !in_array($p->getFedid(), $this->container->getParameter("hexaa_admins"))) {
            $this->errorlog->error($loglbl . "Can not list members of organization where isolateMembers is true. Organization id=" . $o->getId());
            throw new HttpException(409, "Organization members are isolated, listing disabled.");
        } else {
            if ($request->query->has('limit') || $request->query->has('offset')){
                $ps = array_slice($o->getPrincipals()->toArray(), $paramFetcher->get('offset'), $paramFetcher->get('limit'));
                return array("item_number" => (int)count($o->getPrincipals()->toArray()), "items" => $ps);
            } else {
                return $o->getPrincipals();
            }
        }
    }

    /**
     * Remove member from organization<br>
     * Note: members may delete themselves.
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
     *   section = "Organization",
     *   resource = true,
     *   description = "remove member from organization",
     *   statusCodes = {
     *       204 = "Returned on success",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
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
     * @param integer               $id           Organization id
     * @param integer               $pid          Principal id
     *
     */
    public function deleteMemberAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                       ParamFetcherInterface $paramFetcher, $id = 0, $pid = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " and pid=" . $pid . " by " . $p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);
        $p = $this->eh->get('Principal', $pid, $loglbl);
        if ($o->hasPrincipal($p)) {
            $o->removePrincipal($p);
            $this->em->persist($o);

            //Remove principal from roles
            $rps = $this->em->getRepository('HexaaStorageBundle:RolePrincipal')->findAllByOrganizationAndPrincipal($o, $p);
            foreach($rps as $rp) {
                $this->em->remove($rp);
            }

            //Create News object to notify the user
            $n = new News();
            $n->setPrincipal($p);
            $n->setOrganization($o);
            $n->setTitle("Organization member list changed");
            $n->setMessage($p->getFedid() . " is no longer a member of organization " . $o->getName());
            $n->setTag("organization_member");
            $this->em->persist($n);
            $this->em->flush();
            $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());

            $this->modlog->info($loglbl . "Member (id=" . $pid . ") was removed from Organization with id=" . $id);
        }
    }

    /**
     * add member to organization
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
     *   section = "Organization",
     *   resource = true,
     *   statusCodes = {
     *       201 = "Returned on success",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="pid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="principal id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View(statusCode=201)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher
     * @param integer               $id           Organization id
     * @param integer               $pid          Principal id
     *
     */
    public function putMembersAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                     ParamFetcherInterface $paramFetcher, $id = 0, $pid = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " and pid=" . $pid . " by " . $p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);
        $p = $this->eh->get('Principal', $pid, $loglbl);
        if (!$o->hasPrincipal($p)) {
            $o->addPrincipal($p);
            $this->em->persist($o);

            //Create News object to notify the user
            $n = new News();
            $n->setPrincipal($p);
            $n->setOrganization($o);
            $n->setTitle("Organization member list changed");
            $n->setMessage($p->getFedid() . " is now a member of organization " . $o->getName());
            $n->setTag("organization_member");
            $this->em->persist($n);
            $this->em->flush();
            $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());

            $this->modlog->info($loglbl . "Member (id=" . $pid . ") was added to Organization with id=" . $id);
        }
    }

    /**
     * Set members of an organization
     * Note: Admins only!
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
     *   section = "Organization",
     *   resource = false,
     *   description = "set members of an organization",
     *   statusCodes = {
     *     201 = "Returned when successful",
     *     204 = "Returned when members are already added",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when role is not found"
     *   },
     *   tags = {"admins"},
     *   requirements ={
     *     {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   input = "Hexaa\StorageBundle\Form\OrganizationPrincipalType"
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
     *
     */
    public function putMemberAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                    ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);

        return $this->processOPForm($o, $loglbl, $request, "PUT");
    }

    private function processOPForm(Organization $o, $loglbl, Request $request, $method = "PUT") {


        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $store = $o->getPrincipals()->toArray();

        $form = $this->createForm(new OrganizationPrincipalType(), $o, array("method" => $method));
        $form->submit($request->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $statusCode = $store === $o->getPrincipals()->toArray() ? 204 : 201;
            if ($statusCode === 201) {
                //Remove principal from roles
                foreach($store as $principal) {
                    if (!$o->hasPrincipal($principal)) {
                        $rps = $this->em->getRepository('HexaaStorageBundle:RolePrincipal')->findAllByOrganizationAndPrincipal($o, $principal);
                        foreach($rps as $rp) {
                            $this->em->remove($rp);
                        }
                    }
                }
            }
            $this->em->persist($o);
            $ids = "[ ";
            foreach($o->getPrincipals() as $m) {
                $ids = $ids . $m->getId() . ", ";
            }
            $ids = substr($ids, 0, strlen($ids) - 2) . " ]";
            $this->modlog->info($loglbl . "Members of Organization with id=" . $o->getId() . " has been set to " . $ids);
            if ($statusCode !== 204) {

                //Create News object to notify the user
                $removed = array_diff($store, $o->getPrincipals()->toArray());
                $added = array_diff($o->getPrincipals()->toArray(), $store);

                if (count($added) > 0) {
                    $msg = "New members added: ";
                    foreach($added as $addedP) {
                        $msg = $msg . $addedP->getFedid() . ", ";

                        $n = new News();
                        $n->setPrincipal($p);
                        $n->setTitle("Organization membership changed");
                        $n->setMessage("You are now a member of organization" . $o->getName());
                        $n->setTag("organization_member");
                        $this->em->persist($n);

                        $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());
                    }
                } else {
                    $msg = "No new members added, ";
                }
                if (count($removed) > 0) {
                    $msg = $msg . "members removed: ";
                    foreach($removed as $removedP) {
                        $msg = $msg . $removedP->getFedid() . ', ';

                        $n = new News();
                        $n->setPrincipal($p);
                        $n->setTitle("Organization membership changed");
                        $n->setMessage("You are no longer a member of organization" . $o->getName());
                        $n->setTag("organization_member");
                        $this->em->persist($n);

                        $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());
                    }
                } else {
                    $msg = $msg . "no members removed. ";
                }
                $msg[strlen($msg) - 2] = '.';

                $n = new News();
                $n->setPrincipal($p);
                $n->setOrganization($o);
                $n->setTitle("Organization management changed");
                $n->setMessage($o->getName() . ': ' . $msg);
                $n->setTag("organization_manager");
                $this->em->persist($n);

                $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());
            }
            $this->em->flush();
            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                    'get_organization', array('id' => $o->getId()), true // absolute
                )
                );
            }

            return $response;
        }
        $this->errorlog->error($loglbl . "Validation error: \n" . $this->get("serializer")->serialize($form->getErrors(false, true), "json"));

        return View::create($form, 400);
    }

    /**
     * get roles of organization
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
     *   section = "Organization",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\Role>"
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher
     * @param integer               $id           Organization id
     *
     * @return array
     */
    public function cgetRolesAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);
        $rs = $this->em->getRepository('HexaaStorageBundle:Role')->findBy(array('organization' => $o), array("name" => "asc"), $paramFetcher->get('limit'), $paramFetcher->get('offset'));

        if ($request->query->has('limit') || $request->query->has('offset')){
            $itemNumber = $this->em->createQueryBuilder()->select("COUNT(r.id)")
                ->from('HexaaStorageBundle:Role', "r")
                ->where("r.organization = :o")
                ->setParameter(":o", $o)
                ->getQuery()
                ->getSingleScalarResult();
            return array("item_number" => (int)$itemNumber, "items" => $rs);
        } else {
            return $rs;
        }
    }

    /**
     * get entitlements of organization
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
     *   section = "Organization",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\Entitlement>"
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher
     * @param integer               $id           Organization id
     *
     * @return array
     */
    public function cgetEntitlementsAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);
        $es = $this->em->getRepository('HexaaStorageBundle:Entitlement')->findAllByOrganization($o, $paramFetcher->get('limit'), $paramFetcher->get('offset'));

        if ($request->query->has('limit') || $request->query->has('offset')){
            $itemNumber = $this->em->createQueryBuilder()
                ->select('COUNT(e.id)')
                ->from('HexaaStorageBundle:Entitlement', 'e')
                ->from('HexaaStorageBundle:OrganizationEntitlementPack', 'oep')
                ->innerJoin('oep.entitlementPack', 'ep')
                ->where('oep.organization = :o')
                ->andWhere('e MEMBER OF ep.entitlements')
                ->andWhere("oep.status = 'accepted'")
                ->setParameters(array('o' => $o))
                ->orderBy('e.name', 'ASC')
                ->getQuery()
                ->getSingleScalarResult();
            return array("item_number" => (int)$itemNumber, "items" => $es);
        } else {
            return $es;
        }
    }

    /**
     * get entitlement packs of organization
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
     *   section = "Organization",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\EntitlementPack>"
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher
     * @param integer               $id           Organization id
     *
     * @return array
     */
    public function cgetEntitlementpacksAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);
        $oeps = $this->em->getRepository('HexaaStorageBundle:OrganizationEntitlementPack')->findBy(array("organization" => $o), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset'));

        if ($request->query->has('limit') || $request->query->has('offset')){
            $itemNumber = $this->em->createQueryBuilder()
                ->select("COUNT(oep.id)")
                ->from("HexaaStorageBundle:OrganizationEntitlementPack", "oep")
                ->where("oep.organization = :o")
                ->setParameter(":o", $o)
                ->getQuery()
                ->getSingleScalarResult();
            return array("item_number" => (int)$itemNumber, "items" => $oeps);
        } else {
            return $oeps;
        }
    }

    /**
     * set entitlementPacks of an Organization
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
     *   section = "Organization",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when successful",
     *     204 = "Returned when there is no change",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when role is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *     {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *     {"name"="entitlement_packs[]", "dataType"="integer", "format"="\d+", "required"=true, "description"="EntitlementPack IDs"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Organization id
     *
     * @return null
     *
     *
     */
    public function putEntitlementpackAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                             ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);

        return $this->processOOEPForm($o, $loglbl, $request, "PUT");
    }

    private function processOOEPForm(Organization $o, $loglbl, Request $request, /** @noinspection PhpUnusedParameterInspection */
                                     $method = "PUT") {
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();

        $errorList = array();

        if (!$request->request->has('entitlement_packs') && !is_array($request->request->get('entitlement_packs'))) {
            $errorList[] = "entitlement_packs array is non-existent or is not an array.";
        } else {
            $epids = $request->request->get('entitlement_packs');

            $storedOEPS = $o->getEntitlementPacks()->toArray();


            // Get the OEPs that are in the set and are staying there
            $oeps = $this->em->createQueryBuilder()
                ->select('oep')
                ->from('HexaaStorageBundle:OrganizationEntitlementPack', 'oep')
                ->innerJoin('oep.entitlementPack', 'ep')
                ->where('ep.id IN (:epids)')
                ->andWhere('oep.organization = :o')
                ->setParameters(array(":epids" => $epids, ":o" => $o))
                ->getQuery()
                ->getResult();


            // Add (and create) the new OEPs
            foreach($epids as $epid) {
                $newId = true;
                foreach($oeps as $oep) {
                    if ($oep->getEntitlementPack()->getId() == $epid)
                        $newId = false;
                }

                if ($newId) {
                    $ep = $this->em->getRepository("HexaaStorageBundle:EntitlementPack")->find($epid);
                    if ($ep == null) {
                        $errorList[] = "EntitlementPack with id " . $epid . " does not exists!";
                    }
                    $newOEP = new OrganizationEntitlementPack();
                    $newOEP->setEntitlementPack($ep);
                    $newOEP->setOrganization($o);
                    $oeps[] = $newOEP;
                }

            }

            // If no errors were found, we persist, else return errors.
            if ($errorList == array()) {

                $removedOEPs = array_diff($storedOEPS, $oeps);
                $addedOEPs = array_diff($oeps, $storedOEPS);

                foreach($removedOEPs as $oep) {
                    foreach($oep->getEntitlementPack()->getEntitlements() as $e) {
                        $numberOfEPsWithSameEntitlement = $this->em->createQueryBuilder()
                            ->select('oep.id')
                            ->from('HexaaStorageBundle:OrganizationEntitlementPack', 'oep')
                            ->leftJoin('oep.entitlementPack', 'ep')
                            ->where('oep.organization = :o')
                            ->andWhere(':e MEMBER OF ep.entitlements')
                            ->andWhere("oep.status = 'accepted'")
                            ->setParameters(array(":e" => $e, ":o" => $o))
                            ->getQuery()
                            ->getResult();

                        if (count($numberOfEPsWithSameEntitlement) < 2) {
                            $roles = $this->em->createQueryBuilder()
                                ->select('r')
                                ->from('HexaaStorageBundle:Role', 'r')
                                ->where(':e MEMBER OF r.entitlements')
                                ->andWhere('r.organization = :o')
                                ->setParameters(array(":e" => $e, ":o" => $o))
                                ->getQuery()
                                ->getResult();

                            foreach($roles as $r) {
                                $r->removeEntitlement($e);
                                $this->em->persist($r);
                            }
                        }

                    }
                    $this->em->remove($oep);
                }

                foreach($addedOEPs as $oep) {
                    $this->em->persist($oep);
                }


                $statusCode = ($oeps === $o->getEntitlementPacks()->toArray()) ? 204 : 201;
                $ids = "[ ";
                foreach($oeps as $oep) {
                    $ids = $ids . $oep->getEntitlementPack()->getId() . ", ";
                }

                $ids = substr($ids, 0, strlen($ids) - 2) . " ]";


                if ($statusCode !== 204) {


                    //Create News object to notify the user

                    if (count($addedOEPs) > 0) {
                        $msg = "New services requested: ";
                        foreach($addedOEPs as $addedOEP) {
                            $msg = $msg . $addedOEP->getEntitlementPack()->getName() . ", ";

                            $n = new News();
                            $n->setService($addedOEP->getEntitlementPack()->getService());
                            $n->setTitle("Organization requests entitlement package " . $addedOEP->getEntitlementPack()->getName());
                            $n->setMessage("Organization" . $o->getName() . " has requested ");
                            $n->setTag("service");
                            $this->em->persist($n);

                            $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());
                        }
                    } else {
                        $msg = "No new services requested, ";
                    }
                    if (count($removedOEPs) > 0) {
                        $msg = "services removed: ";
                        foreach($removedOEPs as $removedOEP) {
                            $msg = $msg . $removedOEP->getEntitlementPack()->getName() . ', ';

                            $n = new News();
                            $n->setService($removedOEP->getEntitlementPack()->getService());
                            $n->setTitle("Organization has ceased using entitlement package " . $removedOEP->getEntitlementPack()->getName());
                            $n->setMessage("Organization" . $o->getName() . " has requested ");
                            $n->setTag("service");
                            $this->em->persist($n);

                            $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());
                        }
                    } else {
                        $msg = $msg . "no services removed. ";
                    }
                    $msg[strlen($msg) - 2] = '.';

                    $n = new News();
                    $n->setPrincipal($p);
                    $n->setOrganization($o);
                    $n->setTitle("Connected services changed");
                    $n->setMessage($p->getFedid() . "has modified the connected services of " . $o->getName() . ': ' . $msg);
                    $n->setTag("organization");
                    $this->em->persist($n);

                    $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());
                }

                $this->modlog->info($loglbl . "EntitlementPacks of Organization with id=" . $o->getId() . " has been set to " . $ids);
                $this->em->flush();
                $response = new Response();
                $response->setStatusCode($statusCode);

                // set the `Location` header only when creating new resources
                if (201 === $statusCode) {
                    $response->headers->set('Location', $this->generateUrl(
                        'get_organization', array('id' => $o->getId()), true // absolute
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
     * Organization managers can request any public entitlement packs from
     * services with this call, however link status will be set to "pending".
     *
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
     *   section = "Organization",
     *   description = "request linking a public entitlement pack to an organization",
     *   resource = true,
     *   statusCodes = {
     *     201 = "Returned when successfully created new link",
     *     204 = "Returned when successfully modified link",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="epid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement package id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher
     * @param integer               $id           Organization id
     * @param integer               $epid         EntitlementPack id
     *
     * @return array
     */
    public function putEntitlementpacksAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                              ParamFetcherInterface $paramFetcher, $id = 0, $epid = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " and epid=" . $epid . " by " . $p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);
        $ep = $this->eh->get('EntitlementPack', $epid, $loglbl);

        if (!$ep->getService()->getIsEnabled()) {
            $this->errorlog->error($loglbl . "Service " . $ep->getService()->getName() . " is not enabled, can't add its entitlementPack.");
            throw new HttpException(400, "Service " . $ep->getService()->getName() . " is not enabled.");
        }

        try {
            $oep = $this->em->getRepository('HexaaStorageBundle:OrganizationEntitlementPack')->createQueryBuilder('oep')
                ->where('oep.organization = :o')
                ->andwhere('oep.entitlementPack = :ep')
                ->setParameters(array(':o' => $o, ':ep' => $ep))
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $e) {
            $oep = new OrganizationEntitlementPack();
            $oep->setOrganization($o);
            $oep->setEntitlementPack($ep);
        }

        $oep->setStatus("pending");

        $statusCode = $oep->getId() == null ? 201 : 204;

        $this->em->persist($oep);

        //Create News object to notify the user
        $n = new News();
        $n->setOrganization($o);
        $n->setService($oep->getEntitlementPack()->getService());
        $n->setTitle("Entitlement package request");
        $n->setMessage($p->getFedid() . " from organization " . $o->getName() . " has requested entitlement pack " . $oep->getEntitlementPack()->getName() . " from service " . $oep->getEntitlementPack()->getService()->getName());
        $n->setTag("organization_entitlement_pack");
        $this->em->persist($n);
        $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());

        $this->modlog->info($loglbl . "Entitlement Pack (id=" . $epid . ") link status was set to pending with Organization (id=" . $id . ")");
        $this->em->flush();

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
     *   section = "Organization",
     *   description = "accept a link request of a public entitlement pack to an organization",
     *   resource = true,
     *   statusCodes = {
     *     201 = "Returned when successfully created new link",
     *     204 = "Returned when successfully modified link",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="epid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement package id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher
     * @param integer               $id           Organization id
     * @param integer               $epid         EntitlementPack id
     *
     * @return array
     */
    public function putEntitlementpacksAcceptAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                                    ParamFetcherInterface $paramFetcher, $id = 0, $epid = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " and epid=" . $epid . " by " . $p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);
        $ep = $this->eh->get('EntitlementPack', $epid, $loglbl);

        if (!$ep->getService()->getIsEnabled()) {
            $this->errorlog->error($loglbl . "Service " . $ep->getService()->getName() . " is not enabled, can't add its entitlementPack.");
            throw new HttpException(400, "Service " . $ep->getService()->getName() . " is not enabled.");
        }

        try {
            $oep = $this->em->getRepository('HexaaStorageBundle:OrganizationEntitlementPack')->createQueryBuilder('oep')
                ->where('oep.organization = :o')
                ->andwhere('oep.entitlementPack = :ep')
                ->setParameters(array(':o' => $o, ':ep' => $ep))
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $e) {
            $oep = new OrganizationEntitlementPack();
            $oep->setOrganization($o);
            $oep->setEntitlementPack($ep);
        }

        $oep->setStatus("accepted");
        $statusCode = $oep->getId() == null ? 201 : 204;

        $this->em->persist($oep);

        //Create News object to notify the user
        $n = new News();
        $n->setOrganization($o);
        $n->setService($oep->getEntitlementPack()->getService());
        $n->setTitle("Entitlement package request accepted");
        $n->setMessage("An entitlement pack " . $oep->getEntitlementPack()->getName() . " request from organization " . $o->getName() . " has been accepted by a manager of service " . $oep->getEntitlementPack()->getService()->getName());
        $n->setTag("organization_entitlement_pack");
        $this->em->persist($n);
        $this->em->flush();
        $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());

        $this->modlog->info($loglbl . "Entitlement Pack (id=" . $epid . ") link status was set to accepted with Organization (id=" . $id . ")");

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
     *   section = "Organization",
     *   resource = true,
     *   statusCodes = {
     *     201 = "Returned when successfully created new link",
     *     204 = "Returned when successfully modified link",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="token", "dataType"="string", "required"=true, "requirement"="\d+", "description"="entitlement package token"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher
     * @param integer               $id           Organization id
     * @param string                $token        EntitlementPack connector token
     *
     * @return array
     */
    public function putEntitlementpacksTokenAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                                   ParamFetcherInterface $paramFetcher, $id = 0, $token = "nullToken") {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " and token=" . $token . " by " . $p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);

        $linkerToken = $this->em->getRepository('HexaaStorageBundle:LinkerToken')->findOneByToken($token);
        if (!$linkerToken) {
            $this->errorlog->error($loglbl . "The requested linkerToken (" . $token . ") was not found");
            throw new HttpException(404, "Token not found");
        }

        $ep = $linkerToken->getEntitlementPack();

        if (!$ep->getService()->getIsEnabled()) {
            $this->errorlog->error($loglbl . "Service " . $ep->getService()->getName() . " is not enabled, can't add its entitlementPack.");
            throw new HttpException(400, "Service " . $ep->getService()->getName() . " is not enabled.");
        }

        try {
            $oep = $this->em->getRepository('HexaaStorageBundle:OrganizationEntitlementPack')->createQueryBuilder('oep')
                ->where('oep.organization = :o')
                ->andwhere('oep.entitlementPack = :ep')
                ->setParameters(array(':o' => $o, ':ep' => $ep))
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $e) {
            $oep = new OrganizationEntitlementPack();
            $oep->setOrganization($o);
            $oep->setEntitlementPack($ep);
        }
        $oep->setStatus("accepted");
        $statusCode = $oep->getId() == null ? 201 : 204;

        $this->em->persist($oep);
        $this->em->remove($linkerToken);
        $this->em->persist($ep);

        //Create News object to notify the user
        $n = new News();
        $n->setOrganization($o);
        $n->setService($oep->getEntitlementPack()->getService());
        $n->setTitle("Entitlement package connected");
        $n->setMessage("An entitlement pack " . $oep->getEntitlementPack()->getName() . " has been connected to organization " . $o->getName());
        $n->setTag("organization_entitlement_pack");
        $this->em->persist($n);
        $this->em->flush();
        $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());

        $this->modlog->info($loglbl . "Entitlement Pack (id=" . $ep->getId() . ") link status was set to accepted with Organization (id=" . $id . ") by token linking");

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
     *   section = "Organization",
     *   resource = true,
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4", "service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="epid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement package id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher
     * @param integer               $id           Organization id
     * @param integer               $epid         EntitlementPack id
     *
     * @return array
     */
    public function deleteEntitlementpacksAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                                 ParamFetcherInterface $paramFetcher, $id = 0, $epid = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " and epid=" . $epid . " by " . $p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);
        $ep = $this->eh->get('EntitlementPack', $epid, $loglbl);

        try {
            $oep = $this->em->getRepository('HexaaStorageBundle:OrganizationEntitlementPack')->createQueryBuilder('oep')
                ->where('oep.organization = :o')
                ->andwhere('oep.entitlementPack = :ep')
                ->setParameters(array(':o' => $o, ':ep' => $ep))
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $e) {
            $this->errorlog->error($loglbl . "No link found");
            throw new HttpException(404, "No link found");
        }

        foreach($oep->getEntitlementPack()->getEntitlements() as $e) {
            $numberOfEPsWithSameEntitlement = $this->em->createQueryBuilder()
                ->select('count(oep.id)')
                ->from('HexaaStorageBundle:OrganizationEntitlementPack', 'oep')
                ->leftJoin('oep.entitlementPack', 'ep')
                ->where('oep.organization != :o')
                ->andWhere(':e MEMBER OF ep.entitlements')
                ->setParameters(array(":e" => $e, ":o" => $o))
                ->getQuery()
                ->getSingleScalarResult();

            if ($numberOfEPsWithSameEntitlement == 0) {
                $roles = $this->em->createQueryBuilder()
                    ->select('r')
                    ->from('HexaaStorageBundle:Role', 'r')
                    ->where(':e MEMBER OF r.entitlements')
                    ->andWhere('r.organization = :o')
                    ->setParameters(array(":e" => $e, ":o" => $o))
                    ->getQuery()
                    ->getResult();

                foreach($roles as $r) {
                    $r->removeEntitlement($e);
                    $this->em->persist($r);
                }
            }

        }
        $this->em->remove($oep);

        //Create News object to notify the user
        $n = new News();
        $n->setOrganization($o);
        $n->setService($oep->getEntitlementPack()->getService());
        $n->setTitle("Entitlement package unlinked");
        $n->setMessage("An entitlement pack " . $oep->getEntitlementPack()->getName() . " has been unlinked from organization " . $o->getName());
        $n->setTag("organization_entitlement_pack");
        $this->em->persist($n);
        $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());

        $this->modlog->info($loglbl . "Entitlement Pack (id=" . $epid . ") link with Organization (id=" . $id . ") was deleted");
        $this->em->flush();
    }

    /**
     * list available attribute specifications for organization
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
     *   section = "Organization",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   output="array<Hexaa\StorageBundle\Entity\AttributeSpec>"
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher
     * @param integer               $id           Organization id
     *
     * @return array
     */
    public function cgetAttributespecsAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);
        $ass = $this->em->getRepository('HexaaStorageBundle:AttributeSpec')->findAllByOrganization($o);

        if ($request->query->has('limit') || $request->query->has('offset')){
            $retarr = array_slice($ass, $paramFetcher->get('offset'), $paramFetcher->get('limit'));
            return array("item_number" => (int)count($ass), "items" => $retarr);
        } else {
            return $ass;
        }
    }

    /**
     * This call lists all attribute values of an organization which belongs to the specified attribute specifitacion.
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
     *   section = "Organization",
     *   description = "list all attribute values of an attribute specification for organization",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="asid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute specification id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   output="array<Hexaa\StorageBundle\Entity\AttributeValueOrganization>"
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher
     * @param integer               $id           Organization id
     * @param integer               $asid         AttributeSpec id
     *
     * @return array
     */
    public function cgetAttributespecsAttributevalueorganizationsAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0, $asid = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . "and asid=" . $asid . " by " . $p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);
        $ass = $this->em->getRepository('HexaaStorageBundle:AttributeSpec')->findAllByOrganization($o);

        $as = $this->eh->get('AttributeSpec', $asid, $loglbl);
        if ($request->getMethod() == "GET" && !in_array($as, $ass, true)) {
            throw new HttpException(400, "the Attribute specification is not visible to the organization.");
        }
        $avos = $this->em->getRepository('HexaaStorageBundle:AttributeValueOrganization')
            ->findBy(array(
                "organization"  => $o,
                "attributeSpec" => $as
            ), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset')
            );

        if ($request->query->has('limit') || $request->query->has('offset')){
            $itemNumber = $this->em->createQueryBuilder()
                ->select('COUNT(avo.id)')
                ->from("HexaaStorageBundle:AttributeValueOrganization", 'avo')
                ->where("avo.attributeSpec = :as")
                ->andWhere('avo.organization = :o')
                ->setParameters(array(":as" => $as, ":o" => $o))
                ->getQuery()
                ->getSingleScalarResult();
            return array("item_number" => (int)$itemNumber, "items" => $avos);
        } else {
            return $avos;
        }
    }

    /**
     * list all attribute values of the organization
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
     *   section = "Organization",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when organization is not found"
     *   },
     *   tags = {"organization member" = "#5BA578"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   output="array<Hexaa\StorageBundle\Entity\AttributeValueOrganization>"
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher organization
     * @param integer               $id           Organization id
     *
     * @return array
     */
    public function cgetAttributevalueorganizationAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);

        $avos = $this->em->getRepository('HexaaStorageBundle:AttributeValueOrganization')->findBy(array("organization" => $o), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset'));

        if ($request->query->has('limit') || $request->query->has('offset')){
            $itemNumber = $this->em->createQueryBuilder()
                ->select('COUNT(avo.id)')
                ->from("HexaaStorageBundle:AttributeValueOrganization", 'avo')
                ->andWhere('avo.organization = :o')
                ->setParameters(array(":o" => $o))
                ->getQuery()
                ->getSingleScalarResult();
            return array("item_number" => (int)$itemNumber, "items" => $avos);
        } else {
            return $avos;
        }
    }

    /**
     * list all invitations of the specified organization
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
     *   section = "Organization",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   output="array<Hexaa\StorageBundle\Entity\Invitation>"
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher organization
     * @param integer               $id           Organization id
     *
     * @return array
     */
    public function cgetInvitationsAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);
        $is = $this->em->getRepository('HexaaStorageBundle:Invitation')->findBy(array("organization" => $o), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset'));

        if ($request->query->has('limit') || $request->query->has('offset')){
            $itemNumber = $this->em->createQueryBuilder()
                ->select('COUNT(invitation.id)')
                ->from("HexaaStorageBundle:Invitation", 'invitation')
                ->andWhere('invitation.organization = :o')
                ->setParameters(array(":o" => $o))
                ->getQuery()
                ->getSingleScalarResult();
            return array("item_number" => (int)$itemNumber, "items" => $is);
        } else {
            return $is;
        }
    }

}
