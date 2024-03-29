<?php

/**
 * Copyright 2014-2018 MTA SZTAKI, ugyeletes@sztaki.hu
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
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
use Hexaa\ApiBundle\Annotations\InvokeHook;
use Hexaa\StorageBundle\Entity\Entitlement;
use Hexaa\StorageBundle\Entity\EntitlementPack;
use Hexaa\StorageBundle\Entity\News;
use Hexaa\StorageBundle\Entity\Principal;
use Hexaa\StorageBundle\Entity\Service;
use Hexaa\StorageBundle\Entity\ServiceAttributeSpec;
use Hexaa\StorageBundle\Form\ServiceAttributeSpecType;
use Hexaa\StorageBundle\Form\ServiceManagerType;
use JMS\Serializer\SerializerBuilder;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author  Soltész Balázs <solazs@sztaki.hu>
 */
class ServiceChildController extends HexaaController implements PersonalAuthenticatedController
{

    /**
     * get managers of service
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", default=0, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, nullable=true, description="How many items to return.")
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
    public function cgetManagersAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        /* @var $s Service */
        $s = $this->eh->get('Service', $id, $loglbl);

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $retarr = array_slice(
              $s->getManagers()->toArray(),
              $paramFetcher->get('offset'),
              $paramFetcher->get('limit')
            );

            return array("item_number" => (int)$s->getManagers()->toArray(), "items" => $retarr);
        } else {
            return $s->getManagers();
        }
    }

    /**
     * get number of service managers
     * use GET /api/service/{id}/managers?limit=0
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
     *   deprecated = true,
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
    public function getManagerCountAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);
        $retarr = array("count" => count($s->getManagers()->toArray()));

        return $retarr;
    }

    /**
     * get Attribute specifications linked to the service
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", default=0, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, nullable=true, description="How many items to return.")
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
    public function cgetAttributespecsAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);
        $retarr = $this->em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')
          ->findBy(array("service" => $s), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset'));

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $this->em->createQueryBuilder()
              ->select("COUNT(sas.id)")
              ->from("HexaaStorageBundle:ServiceAttributeSpec", "sas")
              ->where("sas.service = :s")
              ->setParameter(":s", $s)
              ->getQuery()
              ->getSingleScalarResult();

            return array("item_number" => (int)$itemNumber, "items" => $retarr);
        } else {
            return $retarr;
        }
    }

    /**
     * Get all organization links related to the service.
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", default=0, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, nullable=true, description="How many items to return.")
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
     *   description = "get all organization links",
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
    public function cgetLinkRequestsAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        /** @var Service $s */
        $s = $this->eh->get('Service', $id, $loglbl);

        $retarr = $this->em->createQueryBuilder()
          ->select('link')
          ->from('HexaaStorageBundle:Link', 'link')
          ->innerJoin('link.organization', 'organization')
          ->where('link.service = :s')
          ->setFirstResult($paramFetcher->get('offset'))
          ->setMaxResults($paramFetcher->get('limit'))
          ->orderBy('organization.name', 'ASC')
          ->setParameters(array("s" => $s))
          ->getQuery()
          ->getResult();

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $this->em->createQueryBuilder()
              ->select('COUNT(link.id)')
              ->from('HexaaStorageBundle:Link', 'link')
              ->where('link.service = :s')
              ->setParameters(array("s" => $s))
              ->getQuery()
              ->getSingleScalarResult();

            return array("item_number" => (int)$itemNumber, "items" => $retarr);
        } else {
            return $retarr;
        }
    }

    /**
     * Get all Organization connected (through some Links) to the service.
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", default=0, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, nullable=true, description="How many items to return.")
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
    public function cgetOrganizationsAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);

        $retarr = $this->em->createQueryBuilder()
          ->select('organization')
          ->from('HexaaStorageBundle:Organization', 'organization')
          ->innerJoin('organization.links', 'links')
          ->where("links.status = 'accepted'")
          ->andWhere('links.service = :s')
          ->setFirstResult($paramFetcher->get('offset'))
          ->setMaxResults($paramFetcher->get('limit'))
          ->orderBy('organization.name', 'ASC')
          ->setParameters(array("s" => $s))
          ->getQuery()
          ->getResult();

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $this->em->createQueryBuilder()
              ->select('COUNT(o.id)')
              ->from('HexaaStorageBundle:Organization', 'o')
              ->innerJoin('o.links', 'links')
              ->where("links.status = 'accepted'")
              ->andWhere('links.service = :s')
              ->setParameters(array("s" => $s))
              ->getQuery()
              ->getSingleScalarResult();

            return array("item_number" => (int)$itemNumber, "items" => $retarr);
        } else {
            return $retarr;
        }
    }

    /**
     * remove manager from service
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
     *       204 = "Returned on success",
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
    public function deleteManagerAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0,
      $pid = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." and pid=".$pid." by ".$p->getFedid());

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
            $n->setMessage($p->getFedid()." is no longer a manager of service ".$s->getName());
            $n->setTag("service_manager");
            $this->em->persist($n);
            $this->em->flush();
            $this->modlog->info($loglbl."Created News object with id=".$n->getId()." about ".$n->getTitle());

            $this->modlog->info($loglbl."Principal (id=".$pid.") removed from the managers of Service (id=".$id.")");
        }
    }

    /**
     * add manager to service
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
     *       201 = "Returned on success",
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
    public function putManagersAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0,
      $pid = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." and pid=".$pid." by ".$p->getFedid());

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
            $n->setMessage($p->getFedid()." is now a manager of service ".$s->getName());
            $n->setTag("service_manager");
            $this->em->persist($n);
            $this->em->flush();
            $this->modlog->info($loglbl."Created News object with id=".$n->getId()." about ".$n->getTitle());

            $this->modlog->info($loglbl."Principal (id=".$pid.") added to the managers of Service (id=".$id.")");
        }
    }

    /**
     * Set managers of an service
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
    public function putManagerAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);

        return $this->processSMForm($s, $loglbl, $request, "PUT");
    }

    private function processSMForm(Service $s, $loglbl, Request $request, $method = "PUT")
    {
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $store = $s->getManagers()->toArray();

        $form = $this->createForm(ServiceManagerType::class, $s, array("method" => $method));
        $form->submit($request->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $statusCode = $store === $s->getManagers()->toArray() ? 204 : 201;
            $this->em->persist($s);
            $ids = "[ ";
            foreach ($s->getManagers() as $m) {
                $ids = $ids.$m->getId().", ";
            }
            $ids = substr($ids, 0, strlen($ids) - 2)." ]";
            $this->modlog->info($loglbl."Managers of Service with id=".$s->getId()." has been set to ".$ids);

            if ($statusCode !== 204) {

                //Create News object to notify the user
                $removed = array_diff($store, $s->getManagers()->toArray());
                $added = array_diff($s->getManagers()->toArray(), $store);

                if (count($added) > 0) {
                    $msg = "New managers added: ";
                    foreach ($added as $addedP) {
                        $msg = $msg.$addedP->getFedid().", ";

                        $n = new News();
                        $n->setPrincipal($p);
                        $n->setTitle("Service management changed");
                        $n->setMessage("You are now a manager of service".$s->getName());
                        $n->setTag("service_manager");
                        $this->em->persist($n);

                        $this->modlog->info($loglbl."Created News object with id=".$n->getId()." about ".$n->getTitle());
                    }
                } else {
                    $msg = "No new managers added, ";
                }
                if (count($removed) > 0) {
                    $msg = "Managers removed: ";
                    foreach ($removed as $removedP) {
                        $msg = $msg.$removedP->getFedid().', ';

                        $n = new News();
                        $n->setPrincipal($p);
                        $n->setTitle("Service management changed");
                        $n->setMessage("You are no longer a manager of service".$s->getName());
                        $n->setTag("service_manager");
                        $this->em->persist($n);

                        $this->modlog->info($loglbl."Created News object with id=".$n->getId()." about ".$n->getTitle());
                    }
                } else {
                    $msg = $msg."no managers removed. ";
                }
                $msg[strlen($msg) - 2] = '.';

                $n = new News();
                $n->setPrincipal($p);
                $n->setService($s);
                $n->setTitle("Service management changed");
                $n->setMessage($s->getName().': '.$msg);
                $n->setTag("service_manager");
                $this->em->persist($n);

                $this->modlog->info($loglbl."Created News object with id=".$n->getId()." about ".$n->getTitle());
            }
            $this->em->flush();
            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set(
                  'Location',
                  $this->generateUrl(
                    'get_service',
                    array('id' => $s->getId()),
                    UrlGeneratorInterface::ABSOLUTE_URL // absolute
                  )
                );
            }

            return $response;
        }
        $this->errorlog->error(
          $loglbl."Validation error: \n".$this->get('jms_serializer')->serialize(
            $form->getErrors(
              false,
              true
            ),
            "json"
          )
        );

        return View::create($form, 400);
    }

    /**
     * remove attribute specification from service
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
     * @InvokeHook(
     *     types={"attribute_change", "user_removed"},
     *     entity="Service",
     *     id="id",
     *     source="attributes"
     *     )
     *
     * @ApiDoc(
     *   section = "Service",
     *   resource = true,
     *   statusCodes = {
     *       204 = "Returned on success",
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
    public function deleteAttributespecAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0,
      $asid = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." and asid=".$asid." by ".$p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);
        $as = $this->eh->get('AttributeSpec', $asid, $loglbl);
        try {
            $sas = $this->em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->createQueryBuilder('sas')
              ->where('sas.service = :s')
              ->andwhere('sas.attributeSpec = :as')
              ->setParameters(array(':s' => $s, ':as' => $as))
              ->getQuery()
              ->getSingleResult();
        } catch (NoResultException $e) {
            $this->errorlog->error($loglbl."No service attributeSpec link was not found");
            throw new HttpException(404, "Resource not found.");
        }

        // Set affected entity for Hook
        $request->attributes->set(
          '_attributeChangeAffectedEntity',
          array("entity" => "AttributeSpec", "serviceId" => $s->getId(), "id" => array($as->getId()))
        );
        $this->em->remove($sas);


        //Create News object to notify the user
        $n = new News();
        $n->setService($s);
        $n->setAdmin();
        $n->setTitle("Attribute specification removed from service");
        $n->setMessage($sas->getAttributeSpec()->getName()." has been unlinked from service ".$s->getName());
        $n->setTag("service_attribute_spec");
        $this->em->persist($n);
        $this->em->flush();
        $this->modlog->info($loglbl."Created News object with id=".$n->getId()." about ".$n->getTitle());

        $this->modlog->info($loglbl."Attribute specification (id=".$asid.") removed from Service (id=".$id.")");
    }

    /**
     * add attribute specification to service
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
     * @InvokeHook(
     *     types={"attribute_change", "user_added"},
     *     entity="Service",
     *     id="id",
     *     source="attributes"
     *     )
     *
     * @ApiDoc(
     *   section = "Service",
     *   resource = true,
     *   statusCodes = {
     *       201 = "Returned on success",
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
    public function putAttributespecsAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0,
      $asid = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." and asid=".$asid." by ".$p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);

        $as = $this->eh->get('AttributeSpec', $asid, $loglbl);

        try {
            $sas = $this->em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->createQueryBuilder('sas')
              ->where('sas.service = :s')
              ->andwhere('sas.attributeSpec = :as')
              ->setParameters(array(':s' => $s, ':as' => $as))
              ->getQuery()
              ->getSingleResult();
        } catch (NoResultException $e) {
            $sas = new ServiceAttributeSpec();
            $sas->setAttributeSpec($as);
            $sas->setService($s);
        }

        return $this->processSASForm($sas, $loglbl, $request, "PUT");
    }

    private function processSASForm(ServiceAttributeSpec $sas, $loglbl, Request $request, $method = "PUT")
    {
        $statusCode = $sas->getId() == null ? 201 : 204;

        $form = $this->createForm(ServiceAttributeSpecType::class, $sas, array("method" => $method));
        $form->submit($request->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $this->em->persist($sas);

            //Create News object to notify the user
            $n = new News();
            $n->setService($sas->getService());
            $n->setAdmin();
            $n->setTitle("Attribute specification added to service");
            $n->setMessage($sas->getAttributeSpec()->getName()." has been linked to service ".$sas->getService()->getName());
            $n->setTag("service_attribute_spec");
            $this->em->persist($n);
            $this->em->flush();
            $this->modlog->info($loglbl."Created News object with id=".$n->getId()." about ".$n->getTitle());

            if (201 === $statusCode) {
                $this->modlog->info(
                  $loglbl."Attribute Spec (id=".$sas->getAttributeSpec()->getId().") linked to Service (id=".$sas->getService()
                    ->getId().")"
                );
            } else {
                $this->modlog->info(
                  $loglbl."Attribute Spec (id=".$sas->getAttributeSpec()->getId(
                  ).") is already linked to Service (id=".$sas->getService()->getId().")"
                );
            }

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set(
                  'Location',
                  $this->generateUrl(
                    'get_service',
                    array('id' => $sas->getService()->getId()),
                    UrlGeneratorInterface::ABSOLUTE_URL // absolute
                  )
                );
            }

            return $response;
        }
        $this->errorlog->error(
          $loglbl."Validation error: \n".$this->get('jms_serializer')->serialize(
            $form->getErrors(
              false,
              true
            ),
            "json"
          )
        );

        return View::create($form, 400);
    }

    /**
     * set attribute specifications of a service
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
     * @InvokeHook(
     *     types={"attribute_change", "user_removed", "user_added"},
     *     entity="Service",
     *     id="id",
     *     source="attributes"
     *     )
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
     *     {"name"="attribute_specs[][attribute_spec]", "dataType"="integer", "required"=true, "description"="attributeSpec ID"},
     *     {"name"="attribute_specs[][is_public]", "dataType"="boolean", "format"="\d+", "required"=false, "description"="Set wether to allow any or only connected users to set the attribute."}
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
    public function putAttributespecAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);

        return $this->processSSASForm($s, $loglbl, $request, "PUT");
    }

    private function processSSASForm(Service $s, $loglbl, Request $request, $method = "PUT")
    {
        /* @var $p \Hexaa\StorageBundle\Entity\Principal */
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();

        $errorList = array();

        if (!$request->request->has('attribute_specs') && !is_array($request->request->get('attribute_specs'))) {
            $errorList[] = "attribute_specs array is non-existent or is not an array.";
        } else {
            $attrRequests = $request->request->get('attribute_specs');

            $asids = array();
            foreach ($attrRequests as $attrRequest) {
                if ((!isset($attrRequest['attribute_spec']))) {
                    $errorList[] = "invalid request: ".$this->get('jms_serializer')->serialize($attrRequest, 'json');
                } else {
                    $asids[] = $attrRequest['attribute_spec'];
                }
            }

            $storedSASs = $s->getAttributeSpecs()->toArray();

            if (count($attrRequests) < 1) {
                $sass = array();
            } else {
                // Get the SASs that are in the set and are staying there
                $sass = $this->em->createQueryBuilder()
                  ->select('sas')
                  ->from('HexaaStorageBundle:ServiceAttributeSpec', 'sas')
                  ->innerJoin('sas.attributeSpec', 'attrspec')
                  ->where('attrspec.id IN (:asids)')
                  ->andWhere('sas.service = :s')
                  ->setParameters(array(":asids" => $asids, ":s" => $s))
                  ->getQuery()
                  ->getResult();
            }


            // Add (and create) the new SASs
            foreach ($attrRequests as $attrRequest) {
                $newid = true;
                /* @var $sas ServiceAttributeSpec */
                foreach ($sass as $sas) {
                    if ($sas->getAttributeSpecId() == $attrRequest['attribute_spec']) {
                        $newid = false;
                    }
                }

                if ($newid) {
                    $as = $this->em->getRepository("HexaaStorageBundle:AttributeSpec")->find($attrRequest['attribute_spec']);
                    if ($as == null) {
                        $errorList[] = "AttributeSpec with id ".$attrRequest['attribute_spec']." does not exists!";
                    }
                    $newsas = new ServiceAttributeSpec();
                    $newsas->setAttributeSpec($as);
                    if ($this->container->getParameter('hexaa_public_attribute_spec_enabled')) {
                        if (isset($attrRequest['is_public'])) {
                            $newsas->setIsPublic($attrRequest['is_public']);
                        }
                    } else {
                        $newsas->setIsPublic(false);
                    }
                    $newsas->setService($s);
                    $sass[] = $newsas;
                }

            }

            // If no errors were found, we persist, else return errors.
            if ($errorList == array()) {

                $removedSASs = array_diff($storedSASs, $sass);
                $addedSASs = array_diff($sass, $storedSASs);

                foreach ($addedSASs as $sas) {
                    $this->em->persist($sas);
                }


                $statusCode = ($sass === $s->getAttributeSpecs()->toArray()) ? 204 : 201;
                $ids = "[ ";
                foreach ($sass as $sas) {
                    $ids = $ids.$sas->getAttributeSpec()->getId().", ";
                }

                $ids = substr($ids, 0, strlen($ids) - 2)." ]";

                if ($statusCode !== 204) {
                    //Create News object to notify the user

                    if (count($addedSASs) > 0) {
                        $msg = "New attributes requested: ";
                        foreach ($addedSASs as $addedSAS) {
                            $msg = $msg.$addedSAS->getAttributeSpec()->getName().", ";
                        }
                    } else {
                        $msg = "No new attributes requested, ";
                    }
                    if (count($removedSASs) > 0) {
                        $msg = "attributes removed: ";
                        foreach ($removedSASs as $removedSAS) {
                            $msg = $msg.$removedSAS->getAttributeSpec()->getName().', ';
                        }
                    } else {
                        $msg = $msg."no attributes removed. ";
                    }
                    $msg[strlen($msg) - 2] = '.';

                    $n = new News();
                    $n->setPrincipal($p);
                    $n->setService($s);
                    $n->setTitle("Connected attributes changed");
                    $n->setMessage($p->getFedid()."has modified the attributes of Service ".$s->getName().': '.$msg);
                    $n->setTag("service");
                    $this->em->persist($n);

                    $this->modlog->info($loglbl."Created News object with id=".$n->getId()." about ".$n->getTitle());
                }

                foreach ($removedSASs as $sas) {
                    $this->em->remove($sas);
                }

                $this->modlog->info($loglbl."AttributeSpecs of Service with id=".$s->getId()." has been set to ".$ids);
                $this->em->flush();
                $response = new Response();
                $response->setStatusCode($statusCode);

                // set the `Location` header only when creating new resources
                if (201 === $statusCode) {
                    $response->headers->set(
                      'Location',
                      $this->generateUrl(
                        'get_service',
                        array('id' => $s->getId()),
                        UrlGeneratorInterface::ABSOLUTE_URL // absolute
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
        $this->errorlog->error('Validation error: '.$jsonContent);
        $response->setContent($jsonContent);

        return $response;
    }

    /**
     * get entitlements of service
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", default=0, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, nullable=true, description="How many items to return.")
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
     * @return array
     */
    public function cgetEntitlementsAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);
        $es = $this->em->getRepository('HexaaStorageBundle:Entitlement')
          ->findBy(
            array("service" => $s),
            array("name" => 'asc'),
            $paramFetcher->get('limit'),
            $paramFetcher->get('offset')
          );

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $this->em->createQueryBuilder()
              ->select("COUNT(e.id)")
              ->from("HexaaStorageBundle:Entitlement", 'e')
              ->where("e.service = :s")
              ->setParameter(":s", $s)
              ->getQuery()
              ->getSingleScalarResult();

            return array("item_number" => (int)$itemNumber, "items" => $es);
        } else {
            return $es;
        }
    }

    /**
     * get entitlement packs of service
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", default=0, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, nullable=true, description="How many items to return.")
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
     *  }
     * )
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement pack
     * @param integer               $id           Service id
     *
     * @return array
     */
    public function cgetEntitlementpacksAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);
        $ep = $this->em->getRepository('HexaaStorageBundle:EntitlementPack')
          ->findBy(
            array("service" => $s),
            array("name" => 'asc'),
            $paramFetcher->get('limit'),
            $paramFetcher->get('offset')
          );

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $this->em->createQueryBuilder()
              ->select("COUNT(ep.id)")
              ->from("HexaaStorageBundle:EntitlementPack", 'ep')
              ->where("ep.service = :s")
              ->setParameter(":s", $s)
              ->getQuery()
              ->getSingleScalarResult();

            return array("item_number" => (int)$itemNumber, "items" => $ep);
        } else {
            return $ep;
        }
    }

    /**
     * list all invitations of the specified service
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", default=0, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, nullable=true, description="How many items to return.")
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
     *   }
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
    public function cgetInvitationsAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);
        $is = $this->em->getRepository('HexaaStorageBundle:Invitation')
          ->findBy(array("service" => $s), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset'));

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $this->em->createQueryBuilder()
              ->select("COUNT(invitation.id)")
              ->from("HexaaStorageBundle:Invitation", 'invitation')
              ->where("invitation.service = :s")
              ->setParameter(":s", $s)
              ->getQuery()
              ->getSingleScalarResult();

            return array("item_number" => (int)$itemNumber, "items" => $is);
        } else {
            return $is;
        }
    }

}
