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
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\View;
use Hexaa\StorageBundle\Entity\News;
use Hexaa\StorageBundle\Entity\SecurityDomain;
use Hexaa\StorageBundle\Form\SecurityDomainOrganizationType;
use Hexaa\StorageBundle\Form\SecurityDomainServiceType;
use Hexaa\StorageBundle\Form\SecurityDomainType;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author  Soltész Balázs <solazs@sztaki.hu>
 */
class SecuritydomainController extends HexaaController implements ClassResourceInterface, PersonalAuthenticatedController {

    /**
     * Lists all security domains
     *
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
     *   section = "SecurityDomain",
     *   resource = true,
     *   description = "get all security domains",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   tags = {"admins"},
     *   output="array<Hexaa\StorageBundle\Entity\SecurityDomain>"
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher security domains
     *
     * @return array
     */
    public function cgetAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());

        $items = $this->em->getRepository('HexaaStorageBundle:SecurityDomain')->findBy(array(), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset'));

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $this->em->createQueryBuilder()
                ->select('COUNT(security_domain.id)')
                ->from('HexaaStorageBundle:SecurityDomain', 'security_domain')
                ->getQuery()
                ->getSingleScalarResult();

            return array("item_number" => (int)$itemNumber, "items" => $items);
        } else {
            return $items;
        }
    }

    /**
     * get security domain details
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
     *   section = "SecurityDomain",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="security domain id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   tags = {"admins"}
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher security domains
     * @param integer               $id           SecurityDomain id
     *
     * @return SecurityDomain
     */
    public function getAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                              ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "called with id=" . $id . " by " . $p->getFedid());

        $sd = $this->eh->get('SecurityDomain', $id, $loglbl);

        return $sd;
    }

    /**
     * Edit security domain preferences<br>
     * Note: admins only!
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
     *   section = "SecurityDomain",
     *   resource = false,
     *   description = "edit security domain preferences",
     *   statusCodes = {
     *     204 = "Returned when security domains has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when security domain is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="security domain id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  tags = {"admins"},
     *  parameters = {
     *      {"name"="name","dataType"="string","required"=true,"description"="displayable name of the security domain"},
     *      {"name"="scoped_key","dataType"="string","required"=true, "description"="identifier of the scoped key"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"}
     *  }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher security domain
     * @param integer               $id           SecurityDomain id
     *
     * @return null
     *
     */
    public function putAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                              ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "called with id=" . $id . " by " . $p->getFedid());

        $sd = $this->eh->get('SecurityDomain', $id, $loglbl);

        return $this->processForm($sd, $loglbl, $request, 'PUT');
    }

    /**
     * Edit security domain<br>
     * Note: admins only!
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
     *   section = "SecurityDomain",
     *   resource = false,
     *   description = "edit security domain preferences",
     *   statusCodes = {
     *     204 = "Returned when security domain has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when security domain is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="security domain id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  tags = {"admins"},
     *  parameters = {
     *      {"name"="name","dataType"="string","required"=true,"description"="displayable name of the security domain"},
     *      {"name"="scoped_key","dataType"="string","required"=true, "description"="identifier of the scoped key"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"}
     *  }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher security domains
     * @param integer               $id           SecurityDomain id
     *
     * @return null
     *
     */
    public function patchAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "called with id=" . $id . " by " . $p->getFedid());

        $sd = $this->eh->get('SecurityDomain', $id, $loglbl);

        return $this->processForm($sd, $loglbl, $request, 'PATCH');
    }

    /**
     * Create new security domain<br>
     * Note: admins only!
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
     *   section = "SecurityDomain",
     *   resource = false,
     *   description = "create new security domain",
     *   statusCodes = {
     *     201 = "Returned when security domain has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when security domain is not found"
     *   },
     *   requirements = {
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   tags = {"admins"},
     *   input = "Hexaa\StorageBundle\Form\SecurityDomainType"
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher security domains
     *
     * @return null
     *
     */
    public function postAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                               ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());

        return $this->processForm(new SecurityDomain(), $loglbl, $request, "POST");
    }

    private function processForm(SecurityDomain $sd, $loglbl, Request $request, $method = "PUT") {
        $statusCode = $sd->getId() == null ? 201 : 204;

        $form = $this->createForm(new SecurityDomainType(), $sd, array("method" => $method));
        $form->submit($request->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            if (201 === $statusCode) {
                $this->modlog->info($loglbl . "created new SecurityDomain with id=" . $sd->getId());
            }
            $this->modlog->info($loglbl . "updated SecurityDomain with id=" . $sd->getId());
            $this->em->persist($sd);
            $this->em->flush();

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                    'get_securitydomain', array('id' => $sd->getId()), true // absolute
                )
                );
            }

            return $response;
        }
        $this->errorlog->error($loglbl . "Validation error: \n" . $this->get("serializer")->serialize($form->getErrors(false, true), "json"));

        return View::create($form, 400);
    }

    /**
     * Delete a security domain<br>
     * Note: admins only!
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
     *   section = "SecurityDomain",
     *   resource = false,
     *   description = "delete security domain",
     *   statusCodes = {
     *     204 = "Returned when security domain has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when security domain is not found"
     *   },
     *   tags = {"admins"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="security domain id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher security domains
     * @param integer               $id           SecurityDomain id
     *
     *
     */
    public function deleteAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                 ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "called with id=" . $id . " by " . $p->getFedid());

        $sd = $this->eh->get('SecurityDomain', $id, $loglbl);
        $this->modlog->info($loglbl . "deleted SecurityDomain with id=" . $id);
        $this->em->remove($sd);
        $this->em->flush();
    }

    /**
     * Assign services to a security domain
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
     *   section = "SecurityDomain",
     *   resource = false,
     *   description = "set services of a security domain",
     *   statusCodes = {
     *     201 = "Returned when successful",
     *     204 = "Returned when managers are already added",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when role is not found"
     *   },
     *   tags = {"admins"},
     *   requirements ={
     *     {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="security domain id"},
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   input = "Hexaa\StorageBundle\Form\SecurityDomainServiceType"
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher role
     * @param integer               $id           SecurityDomain id
     *
     * @return null
     */
    public function putServicesAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                      ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        /* @var $sd SecurityDomain */
        $sd = $this->eh->get('SecurityDomain', $id, $loglbl);

        $store = $sd->getServices()->toArray();

        $form = $this->createForm(new SecurityDomainServiceType(), $sd, array("method" => "PUT"));
        $form->submit($request->request->all(), true);

        if ($form->isValid()) {
            $statusCode = $store === $sd->getServices()->toArray() ? 204 : 201;
            $this->em->persist($sd);
            $ids = "[ ";
            foreach($sd->getServices() as $s) {
                $ids = $ids . $s->getId() . ", ";
            }
            $ids = substr($ids, 0, strlen($ids) - 2) . " ]";
            $this->modlog->info($loglbl . "Services of Security Domain with id=" . $sd->getId() . " has been set to " . $ids);

            if ($statusCode !== 204) {

                //Create News object to notify the user
                $removed = array_diff($store, $sd->getServices()->toArray());
                $added = array_diff($sd->getServices()->toArray(), $store);

                if (count($added) > 0) {
                    $msg = "New services added: ";
                    foreach($added as $addedS) {
                        $msg = $msg . $addedS->getName() . ", ";

                        $n = new News();
                        $n->setService($addedS);
                        $n->setTitle("Service security domain changed");
                        $n->setMessage("This Service is now under the authority of Security Domain" . $sd->getName());
                        $n->setTag("service");
                        $this->em->persist($n);

                        $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());
                    }
                } else {
                    $msg = "No new services added, ";
                }
                if (count($removed) > 0) {
                    $msg = "Services removed: ";
                    foreach($removed as $removedS) {
                        $msg = $msg . $removedS->getName() . ', ';

                        $n = new News();
                        $n->setService($removedS);
                        $n->setTitle("Service security domain changed");
                        $n->setMessage("This Service is no longer under the authority of Security Domain " . $sd->getName());
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
                $n->setTitle("Services of Security Domain " . $sd->getName() . " changed");
                $n->setMessage($sd->getName() . ': ' . $msg);
                $n->setAdmin(true);
                $n->setTag("service");
                $this->em->persist($n);

                $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());
            }
            $this->em->flush();
            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                    'get_securitydomain', array('id' => $sd->getId()), true // absolute
                )
                );
            }

            return $response;
        }
        $this->errorlog->error($loglbl . "Validation error: \n" . $this->get("serializer")->serialize($form->getErrors(false, true), "json"));

        return View::create($form, 400);
    }

    /**
     * Assign organizations to a security domain
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
     *   section = "SecurityDomain",
     *   resource = false,
     *   description = "set organizations of a security domain",
     *   statusCodes = {
     *     201 = "Returned when successful",
     *     204 = "Returned when managers are already added",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when role is not found"
     *   },
     *   tags = {"admins"},
     *   requirements ={
     *     {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="security domain id"},
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   input = "Hexaa\StorageBundle\Form\SecurityDomainServiceType"
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher role
     * @param integer               $id           SecurityDomain id
     *
     * @return null
     */
    public function putOrganizationsAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                     ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        /* @var $sd SecurityDomain */
        $sd = $this->eh->get('SecurityDomain', $id, $loglbl);

        $store = $sd->getOrganizations()->toArray();

        $form = $this->createForm(new SecurityDomainOrganizationType(), $sd, array("method" => "PUT"));
        $form->submit($request->request->all(), true);

        if ($form->isValid()) {
            $statusCode = $store === $sd->getOrganizations()->toArray() ? 204 : 201;
            $this->em->persist($sd);

            $ids = "[ ";
            foreach($sd->getOrganizations() as $o) {
                $ids = $ids . $o->getId() . ", ";
            }
            $ids = substr($ids, 0, strlen($ids) - 2) . " ]";
            $this->modlog->info($loglbl . "Organizations of Security Domain with id=" . $sd->getId() . " has been set to " . $ids);

            if ($statusCode !== 204) {

                //Create News objects to notify everyone
                $removed = array_diff($store, $sd->getOrganizations()->toArray());
                $added = array_diff($sd->getOrganizations()->toArray(), $store);

                if (count($added) > 0) {
                    $msg = "New organizations added: ";
                    foreach($added as $addedO) {
                        $msg = $msg . $addedO->getName() . ", ";

                        $n = new News();
                        $n->setOrganization($addedO);
                        $n->setTitle("Organization security domain changed");
                        $n->setMessage("This Organization is now under the authority of Security Domain" . $sd->getName());
                        $n->setTag("organization");
                        $this->em->persist($n);

                        $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());
                    }
                } else {
                    $msg = "No new organizations added, ";
                }
                if (count($removed) > 0) {
                    $msg = "Organizations removed: ";
                    foreach($removed as $removedO) {
                        $msg = $msg . $removedO->getName() . ', ';

                        $n = new News();
                        $n->setOrganization($removedO);
                        $n->setTitle("Organization security domain changed");
                        $n->setMessage("This Organization is no longer under the authority of Security Domain " . $sd->getName());
                        $n->setTag("organization");
                        $this->em->persist($n);

                        $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());
                    }
                } else {
                    $msg = $msg . "no organizations removed. ";
                }
                $msg[strlen($msg) - 2] = '.';

                $n = new News();
                $n->setPrincipal($p);
                $n->setTitle("Organizations of Security Domain " . $sd->getName() . " changed");
                $n->setMessage($sd->getName() . ': ' . $msg);
                $n->setAdmin(true);
                $n->setTag("organization");
                $this->em->persist($n);

                $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());
            }

            $this->em->flush();
            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                    'get_securitydomain', array('id' => $sd->getId()), true // absolute
                )
                );
            }

            return $response;
        }
        $this->errorlog->error($loglbl . "Validation error: \n" . $this->get("serializer")->serialize($form->getErrors(false, true), "json"));

        return View::create($form, 400);
    }
}