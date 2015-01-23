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
use Hexaa\StorageBundle\Form\EntitlementPackType;
use Hexaa\StorageBundle\Entity\EntitlementPack;
use Hexaa\StorageBundle\Form\EntitlementType;
use Hexaa\StorageBundle\Form\ServiceManagerType;
use Hexaa\StorageBundle\Entity\Entitlement;
use Hexaa\StorageBundle\Entity\Service;
use Hexaa\StorageBundle\Entity\News;
use Hexaa\StorageBundle\Form\ServiceAttributeSpecType;
use Hexaa\StorageBundle\Entity\ServiceAttributeSpec;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Hexaa\StorageBundle\Form\ServiceServiceAttributeSpecType;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class ServiceChildController extends HexaaController implements PersonalAuthenticatedController {

    /**
     * get managers of service
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
     * 
     * @ApiDoc(
     *   section = "Service",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
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
     * @param integer               $id           Service id
     *
     * @return array
     */
    public function cgetManagersAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);
        $retarr = array_slice($s->getManagers()->toArray(), $paramFetcher->get('offset'), $paramFetcher->get('limit'));
        return $retarr;
    }

    /**
     * get number of service managers
     *
     * 
     * @ApiDoc(
     *   section = "Service",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher
     * @param integer               $id           Service id
     *
     * @return array
     */
    public function getManagerCountAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);
        $retarr = array("count" => count($s->getManagers()->toArray()));
        return $retarr;
    }

    /**
     * get Attribute specifications linked to the service
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
     * 
     * @ApiDoc(
     *   section = "Service",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\AttributeSpec>"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher
     * @param integer               $id           Service id
     *
     * @return array
     */
    public function cgetAttributespecsAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);
        $retarr = $this->em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findBy(array("service" => $s), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset'));
        return $retarr;
    }

    /**
     * Get all EntitlementPack - Organization connections related to the service.
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
     * 
     * @ApiDoc(
     *   section = "Service",
     *   description = "get entitlementpack - organizations relations",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\Organization>"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher
     * @param integer               $id           Service id
     *
     * @return array
     */
    public function cgetEntitlementpackRequestsAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);

        $retarr = $this->em->createQueryBuilder()
                ->select('oep')
                ->from('HexaaStorageBundle:OrganizationEntitlementPack', 'oep')
                ->innerJoin('oep.organization', 'o')
                ->innerJoin('oep.entitlementPack', 'ep')
                ->where('ep.service = :s')
                ->setFirstResult($paramFetcher->get('offset'))
                ->setMaxResults($paramFetcher->get('limit'))
                ->orderBy('ep.name', 'ASC')
                ->setParameters(array("s" => $s))
                ->getQuery()
                ->getResult()
        ;

        return $retarr;
    }

    /**
     * Get all Organization connected (through some EntitlementPacks) to the service.
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
     * 
     * @ApiDoc(
     *   section = "Service",
     *   description = "get organizations linked to the service",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\Organization>"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher
     * @param integer               $id           Service id
     *
     * @return array
     */
    public function cgetOrganizationsAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);

        $retarr = $this->em->createQueryBuilder()
                ->select('o')
                ->from('HexaaStorageBundle:Organization', 'o')
                ->innerJoin('HexaaStorageBundle:OrganizationEntitlementPack', 'oep', 'WITH', 'oep.organization = o')
                ->innerJoin('oep.entitlementPack', 'ep')
                ->where("oep.status = 'accepted'")
                ->andWhere('ep.service = :s')
                ->setFirstResult($paramFetcher->get('offset'))
                ->setMaxResults($paramFetcher->get('limit'))
                ->orderBy('o.name', 'ASC')
                ->setParameters(array("s" => $s))
                ->getQuery()
                ->getResult()
        ;

        return $retarr;
    }

    /**
     * remove manager from service
     *
     *
     * @ApiDoc(
     *   section = "Service",
     *   resource = true,
     *   statusCodes = {
     * 	   204 = "Returned on success",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
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
     * @param integer               $id           Service id
     * @param integer               $pid          Principal id
     *
     */
    public function deleteManagerAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0, $pid = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " and pid=" . $pid . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);
        $p = $this->eh->get('Principal', $pid, $loglbl);
        if ($s->hasManager($p)) {
            $s->removeManager($p);
            $this->em->persist($s);

            //Create News object to notify the user
            $n = new News();
            $n->setPrincipal($p);
            $n->setService($s);
            $n->setTitle("Service management changed");
            $n->setMessage($p->getFedid() . " is no longer a manager of service " . $s->getName());
            $n->setTag("service_manager");
            $this->em->persist($n);
            $this->em->flush();
            $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());

            $this->modlog->info($loglbl . "Principal (id=" . $pid . ") removed from the managers of Service (id=" . $id . ")");
        }
    }

    /**
     * add manager to service
     *
     *
     * @ApiDoc(
     *   section = "Service",
     *   resource = true,
     *   statusCodes = {
     * 	   201 = "Returned on success",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
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
     * @param integer               $id           Service id
     * @param integer               $pid          Principal id
     *
     */
    public function putManagersAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0, $pid = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " and pid=" . $pid . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);
        $p = $this->eh->get('Principal', $pid, $loglbl);
        if (!$s->hasManager($p)) {
            $s->addManager($p);
            $this->em->persist($s);

            //Create News object to notify the user
            $n = new News();
            $n->setPrincipal($p);
            $n->setService($s);
            $n->setTitle("Service management changed");
            $n->setMessage($p->getFedid() . " is now a manager of service " . $s->getName());
            $n->setTag("service_manager");
            $this->em->persist($n);
            $this->em->flush();
            $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());

            $this->modlog->info($loglbl . "Principal (id=" . $pid . ") added to the managers of Service (id=" . $id . ")");
        }
    }

    /**
     * Set managers of an service
     * Note: Admins only!
     *
     *
     * @ApiDoc(
     *   section = "Service",
     *   resource = false,
     *   description = "set managers of a service",
     *   statusCodes = {
     *     201 = "Returned when successful",
     *     204 = "Returned when managers are already added",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when role is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *     {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   input = "Hexaa\StorageBundle\Form\ServiceManagerType"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher role
     * @param integer               $id           Service id
     *
     * @return null
     */
    public function putManagerAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);

        return $this->processSMForm($s, $loglbl, "PUT");
    }

    private function processSMForm(Service $s, $loglbl, $method = "PUT") {
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $store = $s->getManagers()->toArray();

        $form = $this->createForm(new ServiceManagerType(), $s, array("method" => $method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $statusCode = $store === $s->getManagers()->toArray() ? 204 : 201;
            $this->em->persist($s);
            $ids = "[ ";
            foreach ($s->getManagers() as $m) {
                $ids = $ids . $m->getId() . ", ";
            }
            $ids = substr($ids, 0, strlen($ids) - 2) . " ]";
            $this->modlog->info($loglbl . "Managers of Service with id=" . $s->getId() . " has been set to " . $ids);

            if ($statusCode !== 204) {

                //Create News object to notify the user
                $removed = array_diff($store, $s->getManagers()->toArray());
                $added = array_diff($s->getManagers()->toArray(), $store);

                if (count($added) > 0) {
                    $msg = "New managers added: ";
                    foreach ($added as $addedP) {
                        $msg = $msg . $addedP->getFedid() . ", ";
                        
                        $n = new News();
                        $n->setPrincipal($p);
                        $n->setTitle("Service management changed");
                        $n->setMessage("You are now a manager of service" . $s->getName());
                        $n->setTag("service_manager");
                        $this->em->persist($n);

                        $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());
                    }
                } else {
                    $msg = "No new managers addded, ";
                }
                if (count($removed) > 0) {
                    $msg = "Managers removed: ";
                    foreach ($removed as $removedP) {
                        $msg = $msg . $removedP->getFedid() . ', ';
                        
                        $n = new News();
                        $n->setPrincipal($p);
                        $n->setTitle("Service management changed");
                        $n->setMessage("You are no longer a manager of service" . $s->getName());
                        $n->setTag("service_manager");
                        $this->em->persist($n);

                        $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());
                    }
                } else {
                    $msg = $msg . "no managers removed. ";
                }
                $msg[strlen($msg) - 2] = '.';

                $n = new News();
                $n->setPrincipal($p);
                $n->setService($s);
                $n->setTitle("Service management changed");
                $n->setMessage($s->getName() . ': ' . $msg);
                $n->setTag("service_manager");
                $this->em->persist($n);

                $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());
            }
            $this->em->flush();
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
        $this->errorlog->error($loglbl . "Validation error");
        return View::create($form, 400);
    }

    /**
     * remove attribute specification from service
     *
     *
     * @ApiDoc(
     *   section = "Service",
     *   resource = true,
     *   statusCodes = {
     * 	   204 = "Returned on success",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="asid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute specification id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher
     * @param integer               $id           Service id
     * @param integer               $asid         AttributeSpec id
     *
     */
    public function deleteAttributespecAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0, $asid = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " and asid=" . $asid . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);
        $as = $this->eh->get('AttributeSpec', $asid, $loglbl);
        try {
            $sas = $this->em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->createQueryBuilder('sas')
                    ->where('sas.service = :s')
                    ->andwhere('sas.attributeSpec = :as')
                    ->setParameters(array(':s' => $s, ':as' => $as))
                    ->getQuery()
                    ->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            $this->errorlog->error($loglbl . "No service attributeSpec link was not found");
            throw new HttpException(404, "Resource not found.");
        }
        $this->em->remove($sas);


        //Create News object to notify the user
        $n = new News();
        $n->setService($s);
        $n->setAdmin();
        $n->setTitle("Attribute specification removed from service");
        $n->setMessage($sas->getAttributeSpec()->getFriendlyName() . " has been unlinked from service " . $s->getName());
        $n->setTag("service_attribute_spec");
        $this->em->persist($n);
        $this->em->flush();
        $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());

        $this->modlog->info($loglbl . "Attribute specification (id=" . $asid . ") removed from Service (id=" . $id . ")");
    }

    /**
     * add attribute specification to service
     *
     *
     * @ApiDoc(
     *   section = "Service",
     *   resource = true,
     *   statusCodes = {
     * 	   201 = "Returned on success",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="asid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute specification id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="is_public", "dataType"="boolean", "required"=true, "format"="true|false", "description"="Set wether to allow any or only connected users to set the attribute."}
     *   }
     * )
     *
     * 
     * @Annotations\View(statusCode=201)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher
     * @param integer               $id           Service id
     * @param integer               $asid         AttributeSpec id
     *
     * @return null
     */
    public function putAttributespecsAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0, $asid = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " and asid=" . $asid . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);

        $as = $this->eh->get('AttributeSpec', $asid, $loglbl);

        try {
            $sas = $this->em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->createQueryBuilder('sas')
                    ->where('sas.service = :s')
                    ->andwhere('sas.attributeSpec = :as')
                    ->setParameters(array(':s' => $s, ':as' => $as))
                    ->getQuery()
                    ->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            $sas = new ServiceAttributeSpec();
            $sas->setAttributeSpec($as);
            $sas->setService($s);
        }

        return $this->processSASForm($sas, $loglbl, "PUT");
    }

    private function processSASForm(ServiceAttributeSpec $sas, $loglbl, $method = "PUT") {
        $statusCode = $sas->getId() == null ? 201 : 204;

        $form = $this->createForm(new ServiceAttributeSpecType(), $sas, array("method" => $method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $this->em->persist($sas);

            //Create News object to notify the user
            $n = new News();
            $n->setService($sas->getService());
            $n->setAdmin();
            $n->setTitle("Attribute specification added to service");
            $n->setMessage($sas->getAttributeSpec()->getFriendlyName() . " has been linked to service " . $sas->getService()->getName());
            $n->setTag("service_attribute_spec");
            $this->em->persist($n);
            $this->em->flush();
            $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());

            if (201 === $statusCode) {
                $this->modlog->info($loglbl . "Attribute Spec (id=" . $sas->getAttributeSpec()->getId() . ") linked to Service (id=" . $sas->getService()->getId() . ")");
            } else {
                $this->modlog->info($loglbl . "Attribute Spec (id=" . $sas->getAttributeSpec()->getId() . ") is already linked to Service (id=" . $sas->getService()->getId() . ")");
            }

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_service', array('id' => $sas->getService()->getId()), true // absolute
                        )
                );
            }

            return $response;
        }
        $this->errorlog->error($loglbl . "Validation error");
        return View::create($form, 400);
    }

    /**
     * set attribute specifications of a service
     *
     *
     * @ApiDoc(
     *   section = "Service",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when successful",
     *     204 = "Returned when principal is already a member",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *     {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *     {"name"="attribute_specs[][attribute_spec]", "dataType"="DateTime", "required"=false, "description"="attributeSpec ID"},
     *     {"name"="attribute_specs[][is_public]", "dataType"="integer", "format"="\d+", "required"=true, "description"="principal ID"}
     *   }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher role
     * @param integer               $id           Service id
     *
     * @return null
     */
    public function putAttributespecAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);

        return $this->processSSASForm($s, $loglbl, "PUT");
    }

    private function processSSASForm(Service $s, $loglbl, $method = "PUT") {
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();

        $errorList = array();

        if (!$this->getRequest()->request->has('attribute_specs') && !is_array($this->getRequest()->request->get('attribute_specs'))) {
            $errorList[] = "entitlement_packs array is non-existent or is not an array.";
        } else {
            $asids = $this->getRequest()->request->get('attribute_specs');

            $storedSASs = $s->getAttributeSpecs()->toArray();


            // Get the ASs that are in the set and are staying there
            $sass = $this->em->createQueryBuilder()
                ->select('sas')
                ->from('HexaaStorageBundle:ServiceAttributeSpecs', 'sas')
                ->innerJoin('sas.attributeSpec', 'as')
                ->where('as.id IN (:asids)')
                ->andWhere('sas.service = :s')
                ->setParameters(array(":asids" => $asids, ":s" => $s))
                ->getQuery()
                ->getResult()
            ;


            // Add (and create) the new OEPs
            foreach($asids as $asid){
                $newid = true;
                foreach ($sass as $sas) {
                    if ($sas->getAttributeSpec()->getId() == $asid)
                        $newid = false;
                }

                if ($newid) {
                    $as = $this->em->getRepository("HexaaStorageBundle:AttributeSpec")->find($asid);
                    if ($as == null) {
                        $errorList[] = "AttributeSpec with id " . $asid . " does not exists!";
                    }
                    $newsas = new ServiceAttributeSpec();
                    $newsas->setAttributeSpec($as);
                    $newsas->setService($s);
                    $sass[] = $newsas;
                }

            }

            // If no errors were found, we persist, else return errors.
            if ($errorList == array()){

                $removedSASs = array_diff($storedSASs, $sass);
                $addedSASs = array_diff($sass, $storedSASs);

                foreach($removedSASs as $sas) {
                    // TBD: delete Attribute values?

                    $this->em->remove($sas);
                }

                foreach($addedSASs as $sas){
                    $this->em->persist($sas);
                }


                $statusCode = ($sass === $s->getAttributeSpecs()->toArray()) ? 204 : 201;
                $ids = "[ ";
                foreach ($sass as $sas) {
                    $ids = $ids . $sas->getAttributeSpec()->getId() . ", ";
                }

                $ids = substr($ids, 0, strlen($ids) - 2) . " ]";


                if ($statusCode !== 204) {
                    //Create News object to notify the user

                    if (count($addedSASs) > 0) {
                        $msg = "New attributes requested: ";
                        foreach ($addedSASs as $addedOEP) {
                            $msg = $msg . $addedOEP->getAttributeSpec()->getFriendlyName() . ", ";
                        }
                    } else {
                        $msg = "No new attributes requested, ";
                    }
                    if (count($removedSASs) > 0) {
                        $msg = "attributes removed: ";
                        foreach ($removedSASs as $removedOEP) {
                            $msg = $msg . $removedOEP->getAttributeSpecs()->getFriendlyName() . ', ';
                        }
                    } else {
                        $msg = $msg . "no attributes removed. ";
                    }
                    $msg[strlen($msg) - 2] = '.';

                    $n = new News();
                    $n->setPrincipal($p);
                    $n->setService($s);
                    $n->setTitle("Connected attributes changed");
                    $n->setMessage($p->getFedid() . "has modified the attributes of Service " . $s->getName() . ': ' . $msg);
                    $n->setTag("service");
                    $this->em->persist($n);

                    $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());
                }

                $this->modlog->info($loglbl . "AttributeSpecs of Service with id=" . $r->getId() . " has been set to " . $ids);
                $this->em->flush();
                $response = new Response();
                $response->setStatusCode($statusCode);

                // set the `Location` header only when creating new resources
                if (201 === $statusCode) {
                    $response->headers->set('Location', $this->generateUrl(
                        'get_service', array('id' => $o->getId()), true // absolute
                    )
                    );
                }

                return $response;

            }

        }

        // Found some errors, return them.

        $response = new Response();
        $response->setStatusCode(400);
        $serializer = \JMS\Serializer\SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize(array("code" => 400, "errors" => $errorList), 'json');
        $response->setContent($jsonContent);

        return $response;



/* Let's do this without forms...
        if ($this->getRequest()->request->has('attribute_specs')) {
            $ass = $this->getRequest()->request->get('attribute_specs');
            for ($i = 0; $i < count($ass); $i++) {
                $ass[$i]['service'] = $s->getId();
            }
            $this->getRequest()->request->set('attribute_specs', $ass);
        }

        $store = $s->getAttributeSpecs()->toArray();



        $form = $this->createForm(new ServiceServiceAttributeSpecType(), $s, array("method" => $method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $statusCode = $store === $s->getAttributeSpecs()->toArray() ? 204 : 201;
            $this->em->persist($s);
            $this->em->flush();
            $ids = "[ ";
            foreach ($s->getAttributeSpecs() as $p) {
                $ids = $ids . $p->getId() . ", ";
            }
            $ids = substr($ids, 0, strlen($ids) - 2) . " ]";
            $this->modlog->info($loglbl . "AttributeSpecs of Service with id=" . $s->getId() . " has been set to " . $ids);
            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_role', array('id' => $s->getId()), true // absolute
                        )
                );
            }

            return $response;
        }
        $this->errorlog->error($loglbl . "Validation error");
        return View::create($form, 400);*/
    }

    /**
     * get entitlements of service
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
     * 
     * @ApiDoc(
     *   section = "Service",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement pack is not found"
     *   },
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   output="array<Hexaa\StorageBundle\Entity\Entitlement>"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement pack
     * @param integer               $id           Service id
     *
     * @return array
     */
    public function cgetEntitlementsAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);
        $es = $this->em->getRepository('HexaaStorageBundle:Entitlement')->findBy(array("service" => $s), array("name" => 'asc'), $paramFetcher->get('limit'), $paramFetcher->get('offset'));
        return $es;
    }

    /**
     * get entitlement packs of service
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
     * 
     * @ApiDoc(
     *   section = "Service",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement pack is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\EntitlementPack>"
     * )
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement pack
     * @param integer               $id           Service id
     *
     * @return array
     */
    public function cgetEntitlementpacksAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);
        $ep = $this->em->getRepository('HexaaStorageBundle:EntitlementPack')->findBy(array("service" => $s), array("name" => 'asc'), $paramFetcher->get('limit'), $paramFetcher->get('offset'));
        return $ep;
    }

    /**
     * create new entitlement pack
     *
     *
     * @ApiDoc(
     *   section = "EntitlementPack",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when entitlement pack has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement pack is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="name","dataType"="string","required"=true,"description"="Displayable name of the entitlement package"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"},
     *      {"name"="type","dataType"="string","required"=true,"format"="private|public","description"="Visibility of the entitlement package"},
     *   }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement pack
     * @param integer               $id           Service id
     *
     * @return null
     *
     * 
     */
    public function postEntitlementpackAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);

        $ep = new EntitlementPack();
        $ep->setService($s);
        return $this->processForm($ep, $loglbl, "POST");
    }

    private function processForm(EntitlementPack $ep, $loglbl, $method = "PUT") {
        $statusCode = $ep->getId() == null ? 201 : 204;

        $form = $this->createForm(new EntitlementPackType(), $ep, array("method" => $method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $this->em->persist($ep);
            $this->em->flush();

            if (201 === $statusCode) {
                $this->modlog->info($loglbl . "New Entitlement Pack created with id=" . $ep->getId());
            } else {
                $this->modlog->info($loglbl . "Entitlement Pack edited with id=" . $ep->getId());
            }

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_entitlementpack', array('id' => $ep->getId()), true // absolute
                        )
                );
            }

            return $response;
        }
        $this->errorlog->error($loglbl . "Validation error");
        return View::create($form, 400);
    }

    /**
     * create new entitlement
     *
     *
     * @ApiDoc(
     *   section = "Entitlement",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when entitlement has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirement = {
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}    
     *   },
     *   parameters = {
     *      {"name"="uri","dataType"="string","required"=true,"description"="URI of entitlement"},
     *      {"name"="name","dataType"="string","required"=true,"description"="displayable name of the entitlement"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"}
     *   }        
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement
     * @param integer               $id           Service id
     *
     * @return null
     * 
     */
    public function postEntitlementAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);

        $e = new Entitlement();
        $e->setService($s);

        return $this->processEForm($e, $loglbl, "POST");
    }

    private function processEForm(Entitlement $e, $loglbl, $method = "PUT") {



        $statusCode = $e->getId() == null ? 201 : 204;

        $form = $this->createForm(new EntitlementType(), $e, array("method" => $method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $this->em->persist($e);
            $this->em->flush();

            if (201 === $statusCode) {
                $this->modlog->info($loglbl . "New Entitlement created with id=" . $e->getId());
            } else {
                $this->modlog->info($loglbl . "Entitlement edited with id=" . $e->getId());
            }

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_entitlement', array('id' => $e->getId()), true // absolute
                        )
                );
            }

            return $response;
        }

        $this->errorlog->error($loglbl . "Validation error");
        return View::create($form, 400);
    }

    /**
     * list all invitations of the specified service
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
     * 
     * @ApiDoc(
     *   section = "Service",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
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
     * @param integer               $id           Service id
     *
     * @return array
     */
    public function cgetInvitationsAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);
        $is = $this->em->getRepository('HexaaStorageBundle:Invitation')->findBy(array("service" => $s), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset'));
        return $is;
    }

}
