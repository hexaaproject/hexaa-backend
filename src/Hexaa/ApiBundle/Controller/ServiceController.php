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
use Hexaa\ApiBundle\Validator\Constraints\SPContactMail;
use Hexaa\StorageBundle\Entity\News;
use Hexaa\StorageBundle\Entity\Service;
use Hexaa\StorageBundle\Form\NotifySPType;
use Hexaa\StorageBundle\Form\ServiceLogoType;
use Hexaa\StorageBundle\Form\ServiceType;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints\All;


/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author  Soltész Balázs <solazs@sztaki.hu>
 */
class ServiceController extends HexaaController implements ClassResourceInterface, PersonalAuthenticatedController {

    /**
     * Lists all services, where the user is a manager.
     * Lists all services if the user is a HEXAA admin
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
     *   section = "Service",
     *   description = "list services where the user is a manager",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     204 = "Returned when no service is connected to the user",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   output="array<Hexaa\StorageBundle\Entity\Service>"
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return Service
     */
    public function cgetAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());

        if (in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {

            $ss = $this->em->createQueryBuilder()
                ->select('s')
                ->from('HexaaStorageBundle:Service', 's')
                ->setFirstResult($paramFetcher->get('offset'))
                ->setMaxResults($paramFetcher->get('limit'))
                ->orderBy("s.name", "ASC")
                ->getQuery()
                ->getArrayResult();

            $itemNumber = $this->em->createQueryBuilder()
                ->select('COUNT(s.id)')
                ->from('HexaaStorageBundle:Service', 's')
                ->getQuery()
                ->getSingleScalarResult();
        } else {
            $ss = $this->em->createQueryBuilder()
                ->select('s')
                ->from('HexaaStorageBundle:Service', 's')
                ->where(':p MEMBER OF s.managers')
                ->setParameter('p', $p)
                ->setFirstResult($paramFetcher->get('offset'))
                ->setMaxResults($paramFetcher->get('limit'))
                ->orderBy("s.name", "ASC")
                ->getQuery()
                ->getArrayResult();
            $itemNumber = $this->em->createQueryBuilder()
                ->select('COUNT(s.id)')
                ->from('HexaaStorageBundle:Service', 's')
                ->where(':p MEMBER OF s.managers')
                ->setParameter('p', $p)
                ->getQuery()
                ->getSingleScalarResult();
        }

        if ($request->query->has('limit') || $request->query->has('offset')){
            return array("item_number" => (int)$itemNumber, "items" => $ss);
        } else {
            return $ss;
        }
    }

    /**
     * get service preferences
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
     *   section = "Service",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   output="Hexaa\StorageBundle\Entity\Service"
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Service id
     *
     * @return Service
     */
    public function getAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                              ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);

        return $s;
    }

    private function processForm(Service $s, $loglbl, Request $request, $method = "PUT") {
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $statusCode = $s->getId() == null ? 201 : 204;

        $form = $this->createForm(new ServiceType(), $s, array("method" => $method));
        $form->submit($request->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            if (201 === $statusCode) {
                /* @var $p \Hexaa\StorageBundle\Entity\Principal */
                $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();

                $s->addManager($p);
            } else {
                $uow = $this->em->getUnitOfWork();
                $uow->computeChangeSets(); // do not compute changes if inside a listener
                $changeSet = $uow->getEntityChangeSet($s);
            }
            $this->em->persist($s);

            //Create News object to notify the user
            $n = new News();
            $n->setService($s);
            $n->setPrincipal($p);
            if ($method == "POST") {
                $n->setTitle("New Service created");
                $n->setMessage("A new service named " . $s->getName() . " has been created");
            } else {
                $changedFields = "";
                foreach(array_keys($changeSet) as $fieldName) {
                    if ($changedFields == "") {
                        $changedFields = $fieldName;
                    } else {
                        $changedFields = $changedFields . ", " . $fieldName;
                    }
                }
                $n->setTitle("Service modified");
                $n->setMessage($p->getFedid() . " has modified service named " . $s->getName() . ". Changed fields: " . $changedFields . ".");
            }
            $n->setTag("service");
            $this->em->persist($n);
            $this->em->flush();
            $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());


            if (201 === $statusCode) {
                $this->modlog->info($loglbl . "New Service created with id=" . $s->getId());
            } else {
                $this->modlog->info($loglbl . "Service edited with id=" . $s->getId() . ", changed fields: " . $changedFields . ".");
            }

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                    'get_service', array('id' => $s->getId()), true // absolute
                )
                );
            }


            return $response;
        }
        $this->errorlog->error($loglbl . "Validation error: \n" . $this->get("serializer")->serialize($form->getErrors(false, true), "json"));

        return View::create($form, 400);
    }

    /**
     * create new service
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
     *   section = "Service",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when service has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     *   requirements = {
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *     {"name"="name", "dataType"="string", "required"=true, "description"="service name"},
     *     {"name"="entityid", "dataType"="string", "required"=true, "description"="service entity id"},
     *     {"name"="url", "dataType"="string", "required"=false, "description"="service url"},
     *     {"name"="description", "dataType"="string", "required"=false, "description"="service description"},
     *     {"name"="org_name", "dataType"="string", "required"=false, "description"="name of the organization providing the service"},
     *     {"name"="org_short_name", "dataType"="string", "required"=false, "description"="short name of the organization providing the service"},
     *     {"name"="org_url", "dataType"="string", "required"=false, "description"="home page of the organization providing the service"},
     *     {"name"="org_description", "dataType"="string", "required"=false, "description"="description of the organization providing the service"},
     *     {"name"="priv_url", "dataType"="string", "required"=false, "description"="service privacy policy URL"},
     *     {"name"="priv_description", "dataType"="string", "required"=false, "description"="short abstract of the privacy policy"},
     *     {"name"="tags", "dataType"="array", "required"=false, "description"="array of tags to append to service"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     *
     * @return View|Response
     */
    public function postAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                               ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());

        $s = new Service();
        $sd = $this->em->getRepository('HexaaStorageBundle:SecurityDomain')->findOneBy(array("scopedKeyName" => $p->getToken()->getMasterKey()));
        if ($sd) {
            $s->addSecurityDomain($sd);
        }

        return $this->processForm($s, $loglbl, $request, "POST");
    }

    /**
     * edit service preferences
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
     *   section = "Service",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when service has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *     {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *     {"name"="name", "dataType"="string", "required"=true, "description"="service name"},
     *     {"name"="entityid", "dataType"="string", "required"=true, "description"="service entity id"},
     *     {"name"="url", "dataType"="string", "required"=false, "description"="service url"},
     *     {"name"="description", "dataType"="string", "required"=false, "description"="service description"},
     *     {"name"="org_name", "dataType"="string", "required"=false, "description"="name of the organization providing the service"},
     *     {"name"="org_short_name", "dataType"="string", "required"=false, "description"="short name of the organization providing the service"},
     *     {"name"="org_url", "dataType"="string", "required"=false, "description"="home page of the organization providing the service"},
     *     {"name"="org_description", "dataType"="string", "required"=false, "description"="description of the organization providing the service"},
     *     {"name"="priv_url", "dataType"="string", "required"=false, "description"="service privacy policy URL"},
     *     {"name"="priv_description", "dataType"="string", "required"=false, "description"="short abstract of the privacy policy"},
     *     {"name"="tags", "dataType"="array", "required"=false, "description"="array of tags to append to service"}
     *  }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Service id
     *
     *
     * @return View|Response
     */
    public function putAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                              ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);

        return $this->processForm($s, $loglbl, $request, "PUT");
    }

    /**
     * edit service preferences
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
     *   section = "Service",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when service has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *     {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *     {"name"="name", "dataType"="string", "required"=true, "description"="service name"},
     *     {"name"="entityid", "dataType"="string", "required"=true, "description"="service entity id"},
     *     {"name"="url", "dataType"="string", "required"=false, "description"="service url"},
     *     {"name"="description", "dataType"="string", "required"=false, "description"="service description"},
     *     {"name"="org_name", "dataType"="string", "required"=false, "description"="name of the organization providing the service"},
     *     {"name"="org_short_name", "dataType"="string", "required"=false, "description"="short name of the organization providing the service"},
     *     {"name"="org_url", "dataType"="string", "required"=false, "description"="home page of the organization providing the service"},
     *     {"name"="org_description", "dataType"="string", "required"=false, "description"="description of the organization providing the service"},
     *     {"name"="priv_url", "dataType"="string", "required"=false, "description"="service privacy policy URL"},
     *     {"name"="priv_description", "dataType"="string", "required"=false, "description"="short abstract of the privacy policy"},
     *     {"name"="tags", "dataType"="array", "required"=false, "description"="array of tags to append to service"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Service id
     *
     *
     * @return View|Response
     */
    public function patchAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);

        return $this->processForm($s, $loglbl, $request, "PATCH");
    }

    /**
     * delete service
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
     *   section = "Service",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when service has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *     {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Service id
     *
     *
     */
    public function deleteAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                 ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);
        $this->em->remove($s);
        $this->em->flush();
        $this->modlog->info($loglbl . "Service with id=" . $id . " deleted");
    }

    /**
     * Upload a service logo<br><br>
     *
     * The uploaded image must be less than 6MB, and its size must be between 150x150 and 400x400.
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
     *   section = "Service",
     *   resource = false,
     *   description = "put service logo",
     *   statusCodes = {
     *     204 = "Returned when service has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *     {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *     {"name"="logo", "dataType"="file", "required"=false, "description"="service provider logo"}
     *  }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Service id
     *
     *
     * @return View|Response
     */
    public function postLogoAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                   ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);

        return $this->processLogoForm($s, $loglbl, $request, "POST");
    }

    private function processLogoForm(Service $s, $loglbl, Request $request, $method = "PUT") {
        $statusCode = $s->getId() == null ? 201 : 204;

        $form = $this->createForm(new ServiceLogoType(), $s, array("method" => $method));
        $form->submit($request->files->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $this->em->persist($s);

            //Create News object to notify the user
            $n = new News();
            $n->setService($s);
            $n->setTitle("Service logo modified");
            $n->setMessage("Logo of Service named " . $s->getName() . " has been modified");
            $n->setTag("service");
            $this->em->persist($n);
            $this->em->flush();
            $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());


            if (201 === $statusCode) {
                $this->modlog->info($loglbl . "New Service created with id=" . $s->getId());
            } else {
                $this->modlog->info($loglbl . "Service edited with id=" . $s->getId());
            }

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                    'get_service', array('id' => $s->getId()), true // absolute
                )
                );
            }

            return $response;
        }
        $this->errorlog->error($loglbl . "Validation error: \n" . $this->get("serializer")->serialize($form->getErrors(false, true), "json"));

        return View::create($form, 400);
    }

    /**
     * Notify SP manager to accept the usage of an entityID
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
     *   section = "Service",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when successful",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found",
     *     409 = "Returned when service is already enabled"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *     {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *     {"name"="contacts[]", "dataType"="array", "required"=true, "description"="array of SP contacts"},
     *     {"name"="contacts[surName]", "dataType"="string", "required"=true, "description"="displayable name of SP contact"},
     *     {"name"="contacts[email]", "dataType"="string", "required"=true, "description"="e-mail address of SP contact"},
     *     {"name"="contacts[type]", "dataType"="string", "required"=true, "description"="type of SP contact"}
     *
     *   }
     * )
     *
     *
     * @Annotations\View(statusCode=201)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Service id
     *
     *
     * @return View|void
     */
    public function putNotifyspAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                      ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);

        if ($s->getIsEnabled()) {
            $this->errorlog->error($loglbl . "Service is already enabled!");
            throw new HttpException(409, "Service is already enabled");
        }

        $postData = $request->request->all();

        $form = $this->createFormBuilder(array('contacts' => array()))
            ->add('contacts', 'collection', array(
                'type'        => new NotifySPType(),
                'allow_add'   => true,
                'constraints' => array(
                    new All(new SPContactMail(array('service' => $s)))
                )
            ))
            ->getForm();
        $form->submit($postData, false);

        if ($form->isValid()) {

            $contacts = $form->getData();

            $this->sendNotifyAdminEmail($s, $contacts['contacts'], $loglbl, $request);

            return null;
        }
        $this->errorlog->error($loglbl . "Validation error: \n" . $this->get("serializer")->serialize($form->getErrors(false, true), "json"));

        return View::create($form, 400);
    }

    private function sendNotifyAdminEmail(Service $s, $mails, $loglbl, Request $request) {
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $maillog = $this->get('monolog.logger.email');
        $baseUrl = $request->getHttpHost() . $request->getBasePath();
        foreach($mails as $email) {
            $message = \Swift_Message::newInstance()
                ->setSubject('[hexaa] ' . $this->get('translator')->trans('Request for HEXAA Service approval'))
                ->setFrom('hexaa@' . $baseUrl)
                ->setBody(
                    $this->renderView(
                        'HexaaApiBundle:Default:ServiceNotify.html.twig', array(
                            'creator' => $p,
                            'returl'  => $this->container->getParameter('hexaa_ui_url') . "/index.php?token=" . $s->getEnableToken(),
                            'service' => $s,
                        )
                    ), "text/html"
                );
            $message->setTo(array($email['email'] => $email["surName"]));

            $this->get('mailer')->send($message);
            $maillog->info($loglbl . "E-mail sent to " . $email["surName"] . " <" . $email['email'] . ">");
        }
    }

    /**
     * Enable service
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
     *   section = "Service",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when successful",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found",
     *     409 = "Returned when service is already enabled"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *     {"name"="token", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service enable token"},
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View(statusCode=201)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param string                $token        Service token
     *
     *
     */
    public function putEnableAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                    ParamFetcherInterface $paramFetcher, $token = "nullToken") {
        $loglbl = $request->attributes->get('_controller');
        $this->accesslog->info($loglbl . "Called with token=" . $token);

        $s = $this->em->getRepository('HexaaStorageBundle:Service')->findOneBy(array("enableToken" => $token));
        if (!$s) {
            $this->errorlog->error($loglbl . "the requested Service with token=" . $token . " was not found");
            throw new HttpException(404, "Service not found");
        }
        if ($s->getIsEnabled()) {
            $this->errorlog->error($loglbl . "the requested Service with token=" . $token . " is already enabled");
            throw new HttpException(409, "Service already enabled");
        }

        $s->setIsEnabled(true);

        $this->em->persist($s);
        $this->em->flush();
        $this->modlog->info($loglbl . 'Service with id=' . $s->getId() . ' has been enabled.');
    }

}
