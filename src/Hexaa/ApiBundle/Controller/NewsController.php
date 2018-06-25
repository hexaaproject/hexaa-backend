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


use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Hexaa\StorageBundle\Entity\News;
use Hexaa\StorageBundle\Entity\Principal;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;


/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author  Soltész Balázs <solazs@sztaki.hu>
 */
class NewsController extends HexaaController implements PersonalAuthenticatedController
{

    /**
     * get news for the current user
     * Note: if tags, services and/or organizations are left empty, all of them will be returned.
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing news.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=20, description="How many news to return.")
     * @Annotations\QueryParam(name="tags", map=true, default={}, description="Tags to filter the query")
     * @Annotations\QueryParam(name="services", map=true, default={}, description="Service IDs to filter the query")
     * @Annotations\QueryParam(name="organizations", map=true, default={}, description="Organization IDs to filter the query")
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
     *   section = "News",
     *   resource = true,
     *   description = "get news for the current user",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return array
     */
    public function getPrincipalNewsAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();

        $tags = array_filter($paramFetcher->get('tags'));
        $services = array_filter($paramFetcher->get('services'));
        $organizations = array_filter($paramFetcher->get('organizations'));
        $this->accesslog->info(
          $loglbl."Called by ".$p->getFedid().", with tags[]=".var_export(
            $tags,
            true
          ).', services[]='.var_export(
            $services,
            true
          ).", organizations[]=".var_export($organizations, true)
        );

        $qb = $this->em->createQueryBuilder();
        $qb2 = $this->em->createQueryBuilder();

        $qb
          ->select('n')
          ->from('HexaaStorageBundle:News', 'n')
          ->leftJoin('n.service', 's')
          ->leftJoin('n.organization', 'o')
          ->where('(:p MEMBER OF s.managers) OR (:p MEMBER OF o.principals) OR (n.principal = :p)');

        $qb2
          ->select('COUNT(n.id)')
          ->from('HexaaStorageBundle:News', 'n')
          ->leftJoin('n.service', 's')
          ->leftJoin('n.organization', 'o')
          ->where('(:p MEMBER OF s.managers) OR (:p MEMBER OF o.principals) OR (n.principal = :p)');


        if (is_array($tags) && count($tags) > 0) {
            $qb->andWhere('n.tag IN(:tags)');
            $qb2->andWhere('n.tag IN(:tags)');
        }
        if (is_array($services) && count($services) > 0) {
            $qb->andWhere('s.id IN(:services)');
            $qb2->andWhere('s.id IN(:services)');
        }
        if (is_array($organizations) && count($organizations) > 0) {
            $qb->andWhere('o.id IN(:organizations)');
            $qb2->andWhere('o.id IN(:organizations)');
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
            $qb->andWhere('n.admin = 0');
            $qb2->andWhere('n.admin = 0');
        }
        $qb->orderBy('n.createdAt', 'DESC')
          ->setFirstResult($paramFetcher->get('offset'))
          ->setMaxResults($paramFetcher->get('limit'))
          ->setParameter("p", $p);

        $qb2->setParameter("p", $p);


        if (is_array($tags) && count($tags) > 0) {
            $qb->setParameter("tags", $tags);
            $qb2->setParameter("tags", $tags);
        }
        if (is_array($services) && count($services) > 0) {
            $qb->setParameter("services", $services);
            $qb2->setParameter("services", $services);
        }
        if (is_array($organizations) && count($organizations) > 0) {
            $qb->setParameter("organizations", $organizations);
            $qb2->setParameter("organizations", $organizations);
        }
        $news = $qb->getQuery()->getResult();

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $arrayItemNumber = $qb2->getQuery()->getSingleScalarResult();

            return array("item_number" => (int)$arrayItemNumber, "items" => $news);
        } else {
            return $news;
        }
    }

    /**
     * get news for the specified user<br>
     * Note: Admins only!
     * Note: if tags, services and/or organizations are left empty, all of them will be returned.
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing news.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, nullable=true, description="How many news to return.")
     * @Annotations\QueryParam(name="tags", map=true, default={}, description="Tags to filter the query")
     * @Annotations\QueryParam(name="services", map=true, default={}, description="Service IDs to filter the query")
     * @Annotations\QueryParam(name="organizations", map=true, default={}, description="Organization IDs to filter the query")
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
     *   section = "News",
     *   resource = true,
     *   desctiption = "get news for the specified user",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   requirements ={
     *      {"name"="pid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="principal id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   tags = {"admins"}
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $pid          Principal id
     *
     * @return array
     */
    public function cgetPrincipalsNewsAction(Request $request, ParamFetcherInterface $paramFetcher, $pid = 0)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();

        $tags = array_filter($paramFetcher->get('tags'));
        $services = array_filter($paramFetcher->get('services'));
        $organizations = array_filter($paramFetcher->get('organizations'));

        $this->accesslog->info(
          $loglbl."Called by ".$p->getFedid().", with pid=".$pid.", tags[]=".var_export(
            $tags,
            true
          ).', services[]='.var_export(
            $services,
            true
          ).", organizations[]=".var_export($organizations, true)
        );

        $p = $this->eh->get('Principal', $pid, $loglbl);

        $qb = $this->em->createQueryBuilder();
        $qb2 = $this->em->createQueryBuilder();

        $qb
          ->select('n')
          ->from('HexaaStorageBundle:News', 'n')
          ->leftJoin('n.service', 's')
          ->leftJoin('n.organization', 'o')
          ->where('(:p MEMBER OF s.managers) OR (:p MEMBER OF o.principals) OR (n.principal = :p)');

        $qb2
          ->select('COUNT(n.id)')
          ->from('HexaaStorageBundle:News', 'n')
          ->leftJoin('n.service', 's')
          ->leftJoin('n.organization', 'o')
          ->where('(:p MEMBER OF s.managers) OR (:p MEMBER OF o.principals) OR (n.principal = :p)');

        if (is_array($tags) && count($tags) > 0) {
            $qb->andWhere('n.tag IN(:tags)');
            $qb2->andWhere('n.tag IN(:tags)');
        }
        if (is_array($services) && count($services) > 0) {
            $qb->andWhere('s.id IN(:services)');
            $qb2->andWhere('s.id IN(:services)');
        }
        if (is_array($organizations) && count($organizations) > 0) {
            $qb->andWhere('o.id IN(:organizations)');
            $qb2->andWhere('o.id IN(:organizations)');
        }
        $qb->orderBy('n.createdAt', 'DESC')
          ->setFirstResult($paramFetcher->get('offset'))
          ->setMaxResults($paramFetcher->get('limit'))
          ->setParameter("p", $p);

        $qb2->setParameter("p", $p);

        if (is_array($tags) && count($tags) > 0) {
            $qb->setParameter("tags", $tags);
            $qb2->setParameter("tags", $tags);
        }
        if (is_array($services) && count($services) > 0) {
            $qb->setParameter("services", $services);
            $qb2->setParameter("services", $services);
        }
        if (is_array($organizations) && count($organizations) > 0) {
            $qb->setParameter("organizations", $organizations);
            $qb2->setParameter("organizations", $organizations);
        }
        $news = $qb->getQuery()
          ->getResult();


        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $qb2->getQuery()
              ->getSingleScalarResult();

            return array("item_number" => (int)$itemNumber, "items" => $news);
        } else {
            return $news;
        }
    }

    /**
     * get news for the specified service<br>
     * Note: if tags are left empty, all of them will be returned.
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing news.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, nullable=true, description="How many news to return.")
     * @Annotations\QueryParam(name="tags", map=true, default={}, description="Tags to filter the query")
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
     *   section = "News",
     *   resource = true,
     *   desctiption = "get news for the specified service",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="sid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
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
     * @return array
     */
    public function cgetServicesNewsAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();

        $tags = array_filter($paramFetcher->get('tags'));
        $this->accesslog->info(
          $loglbl."Called by ".$p->getFedid().", with id=".$id.", tags[]=".var_export(
            $tags,
            true
          )
        );

        $s = $this->eh->get('Service', $id, $loglbl);

        $qb = $this->em->createQueryBuilder();
        $qb2 = $this->em->createQueryBuilder();

        $qb
          ->select('n')
          ->from('HexaaStorageBundle:News', 'n')
          ->where('n.service = :s');
        $qb2
          ->select('COUNT(n.id)')
          ->from('HexaaStorageBundle:News', 'n')
          ->where('n.service = :s');
        if (is_array($tags) && count($tags) > 0) {
            $qb->andWhere('n.tag IN(:tags)');
            $qb2->andWhere('n.tag IN(:tags)');
        }
        $qb->orderBy('n.createdAt', 'DESC')
          ->setFirstResult($paramFetcher->get('offset'))
          ->setMaxResults($paramFetcher->get('limit'))
          ->setParameter("s", $s);
        $qb2->setParameter("s", $s);

        if (is_array($tags) && count($tags) > 0) {
            $qb->setParameter("tags", $tags);
            $qb2->setParameter("tags", $tags);
        }
        $news = $qb->getQuery()
          ->getResult();

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $qb2->getQuery()
              ->getSingleScalarResult();

            return array("item_number" => (int)$itemNumber, "items" => $news);
        } else {
            return $news;
        }
    }

    /**
     * get news for the specified organization<br>
     * Note: if tags are left empty, all of them will be returned.
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing news.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, nullable=true, description="How many news to return.")
     * @Annotations\QueryParam(name="tags", map=true, default={}, description="Tags to filter the query")
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
     *   section = "News",
     *   resource = true,
     *   desctiption = "get news for the specified organization",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   tags = {"organization member" = "#5BA578"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
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
     * @return array
     */
    public function cgetOrganizationsNewsAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();

        $tags = array_filter($paramFetcher->get('tags'));
        $this->accesslog->info(
          $loglbl."Called by ".$p->getFedid().", with id=".$id.", tags[]=".var_export(
            $tags,
            true
          )
        );

        $o = $this->eh->get('Organization', $id, $loglbl);

        $qb = $this->em->createQueryBuilder();
        $qb2 = $this->em->createQueryBuilder();

        $qb
          ->select('n')
          ->from('HexaaStorageBundle:News', 'n')
          ->where('n.organization = :o');
        $qb2
          ->select('COUNT(n.id)')
          ->from('HexaaStorageBundle:News', 'n')
          ->where('n.organization = :o');
        if (is_array($tags) && count($tags) > 0) {
            $qb->andWhere('n.tag IN(:tags)');
            $qb2->andWhere('n.tag IN(:tags)');
        }
        $qb->orderBy('n.createdAt', 'DESC')
          ->setFirstResult($paramFetcher->get('offset'))
          ->setMaxResults($paramFetcher->get('limit'))
          ->setParameter("o", $o);
        $qb2->setParameter("o", $o);

        if ((is_array($tags) && count($tags) > 0)) {
            $qb->setParameter("tags", $tags);
            $qb2->setParameter("tags", $tags);
        }
        $news = $qb->getQuery()
          ->getResult();

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $qb2->getQuery()
              ->getSingleScalarResult();

            return array("item_number" => (int)$itemNumber, "items" => $news);
        } else {
            return $news;
        }
    }

}
