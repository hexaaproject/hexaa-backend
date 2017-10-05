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
use Hexaa\ApiBundle\Annotations\InvokeHook;
use Hexaa\StorageBundle\Entity\AttributeValueOrganization;
use Hexaa\StorageBundle\Entity\AttributeValuePrincipal;
use Hexaa\StorageBundle\Entity\Principal;
use Hexaa\StorageBundle\Entity\Service;
use Hexaa\StorageBundle\Entity\ServiceAttributeSpec;
use Hexaa\StorageBundle\Form\PrincipalType;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


/**
 * Description of PrincipalController
 *
 * @author solazs@sztaki.hu
 */
class PrincipalController extends HexaaController implements PersonalAuthenticatedController
{

    /**
     * get list of principals
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
     *   section = "Principal",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query"
     *   },
     *   tags = {"admins"},
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
    public function cgetPrincipalsAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called by ".$p->getFedid());

        $p = $this->em->getRepository('HexaaStorageBundle:Principal')->findBy(
          array(),
          array("fedid" => "asc"),
          $paramFetcher->get('limit'),
          $paramFetcher->get('offset')
        );

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $this->em->createQueryBuilder()
              ->select("COUNT(p.id)")
              ->from("HexaaStorageBundle:Principal", "p")
              ->getQuery()
              ->getSingleScalarResult();

            return array("item_number" => (int)$itemNumber, "items" => $p);
        } else {
            return $p;
        }
    }

    /**
     * get if current principal is a HEXAA admin
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
     *   section = "Principal",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
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
    public function getPrincipalIsadminAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called by ".$p->getFedid());

        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
            return array("is_admin" => false);
        }

        return array("is_admin" => true);
    }

    /**
     * get info about current principal
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
     *   section = "Principal",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return Principal
     */
    public function getPrincipalSelfAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called by ".$p->getFedid());

        return $p;
    }

    /**
     * get info about principal by id
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
     *   section = "Principal",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "description"="id of principal"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Principal id
     *
     * @return Principal
     */
    public function getPrincipalIdAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        $p = $this->eh->get('Principal', $id, $loglbl);

        return $p;
    }

    /**
     * get info about a principal by fedid
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
     *   section = "Principal",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="fedid", "dataType"="string", "required"=true, "description"="Federal ID of principal"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $fedid        Principal fedid
     *
     * @return Principal
     */
    public function getPrincipalFedidAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $fedid
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with fedid=".$fedid." by ".$p->getFedid());

        $p = $this->em->getRepository('HexaaStorageBundle:Principal')->findOneBy(array("fedid" => $fedid));
        if ($request->getMethod() == "GET" && !$p) {
            $this->errorlog->error($loglbl."the requested Principal with fedid=".$fedid." was not found");
            throw new HttpException(404, "Principal not found");
        }

        return $p;
    }

    /**
     * list all invitations of the current principal
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
     *   section = "Principal",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
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
    public function cgetPrincipalInvitationsAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called by ".$p->getFedid());

        $is = $this->em->getRepository('HexaaStorageBundle:Invitation')->findBy(
          array("inviter" => $p),
          array(),
          $paramFetcher->get('limit'),
          $paramFetcher->get('offset')
        );

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $this->em->createQueryBuilder()
              ->select("COUNT(i.id)")
              ->from("HexaaStorageBundle:Invitation", "i")
              ->where("i.inviter = :p")
              ->setParameter(":p", $p)
              ->getQuery()
              ->getSingleScalarResult();

            return array("item_number" => (int)$itemNumber, "items" => $is);
        } else {
            return $is;
        }
    }

    /**
     * list available attribute specifications
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
     *   section = "Principal",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
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
    public function cgetPrincipalAttributespecsAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called by ".$p->getFedid());

        $ass = $this->em->getRepository('HexaaStorageBundle:AttributeSpec')->findAllByPrincipal($p);

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $items = array_slice($ass, $paramFetcher->get('offset'), $paramFetcher->get('limit'));

            return array("item_number" => (int)count($ass), "items" => $items);
        } else {
            return $ass;
        }
    }

    /**
     * list available attribute values of the current principal and the specified attribute specification
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
     *   section = "Principal",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="asid", "dataType"="integer", "requirement"="\d+", "description"="attribute specification id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @param int                   $asid         AttributeSpec id
     * @return array
     */
    public function cgetPrincipalAttributespecsAttributevalueprincipalsAction(
      Request $request,
      ParamFetcherInterface $paramFetcher,
      $asid = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with asid=".$asid." by ".$p->getFedid());

        // Get attribute specifications from organization membership
        $ass = $this->em->getRepository('HexaaStorageBundle:AttributeSpec')->findAllByPrincipal($p);

        $as = $this->eh->get('AttributeSpec', $asid, $loglbl);
        if ($request->getMethod() == "GET" && !in_array($as, $ass, true)) {
            throw new HttpException(400, "the Attribute specification is not visible to the user.");
        }
        $avps = $this->em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')
          ->findBy(
            array(
              "principal"     => $p,
              "attributeSpec" => $as,
            ),
            array(),
            $paramFetcher->get('limit'),
            $paramFetcher->get('offset')
          );

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $this->em->createQueryBuilder()
              ->select("COUNT(avp.id)")
              ->from("HexaaStorageBundle:AttributeValuePrincipal", 'avp')
              ->where('avp.principal = :p')
              ->andWhere("avp.attributeSpec = :as")
              ->setParameters(array(":p" => $p, ":as" => $as))
              ->getQuery()
              ->getSingleScalarResult();

            return array("item_number" => (int)$itemNumber, "items" => $avps);
        } else {
            return $avps;
        }
    }

    /**
     * list all attribute values of the principal
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
     *   section = "Principal",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher principal
     *
     * @return array
     */
    public function cgetPrincipalAttributevalueprincipalAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called by ".$p->getFedid());

        $avps = $this->em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->findBy(
          array("principal" => $p),
          array(),
          $paramFetcher->get('limit'),
          $paramFetcher->get('offset')
        );

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $this->em->createQueryBuilder()
              ->select("COUNT(avp.id)")
              ->from("HexaaStorageBundle:AttributeValuePrincipal", 'avp')
              ->where('avp.principal = :p')
              ->setParameters(array(":p" => $p))
              ->getQuery()
              ->getSingleScalarResult();

            return array("item_number" => (int)$itemNumber, "items" => $avps);
        } else {
            return $avps;
        }
    }

    /**
     * list all services where the user is a manager
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
     *   section = "Principal",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
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
    public function cgetManagerServicesAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called by ".$p->getFedid());

        $rets = $this->em->createQueryBuilder()
          ->select('s')
          ->from('HexaaStorageBundle:Service', 's')
          ->innerJoin('s.managers', 'm')
          ->where(':p MEMBER OF s.managers ')
          ->setFirstResult($paramFetcher->get('offset'))
          ->setMaxResults($paramFetcher->get('limit'))
          ->orderBy("s.name", "ASC")
          ->setParameters(array("p" => $p))
          ->getQuery()
          ->getResult();

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $this->em->createQueryBuilder()
              ->select('COUNT(s.id)')
              ->from('HexaaStorageBundle:Service', 's')
              ->innerJoin('s.managers', 'm')
              ->where(':p MEMBER OF s.managers ')
              ->setParameters(array("p" => $p))
              ->getQuery()
              ->getSingleScalarResult();

            return array("item_number" => (int)$itemNumber, "items" => $rets);
        } else {
            return $rets;
        }
    }

    /**
     * list all organizations where the user is a manager
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
     *   section = "Principal",
     *   resource = true,
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
    public function cgetManagerOrganizationsAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called by ".$p->getFedid());

        $reto = $this->em->createQueryBuilder()
          ->select('o')
          ->from('HexaaStorageBundle:Organization', 'o')
          ->innerJoin('o.managers', 'm')
          ->where(':p MEMBER OF o.managers')
          ->setFirstResult($paramFetcher->get('offset'))
          ->setMaxResults($paramFetcher->get('limit'))
          ->orderBy("o.name", "ASC")
          ->setParameters(array(":p" => $p))
          ->getQuery()
          ->getResult();

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $this->em->createQueryBuilder()
              ->select('COUNT(o.id)')
              ->from('HexaaStorageBundle:Organization', 'o')
              ->innerJoin('o.managers', 'm')
              ->where(':p MEMBER OF o.managers')
              ->setParameters(array(":p" => $p))
              ->getQuery()
              ->getSingleScalarResult();

            return array("item_number" => (int)$itemNumber, "items" => $reto);
        } else {
            return $reto;
        }
    }

    /**
     * list all organizations where the user is a member
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
     *   section = "Principal",
     *   resource = true,
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
    public function cgetMemberOrganizationsAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called by ".$p->getFedid());

        $reto = $this->em->getRepository("HexaaStorageBundle:Organization")
          ->findAllByMember($p, $paramFetcher->get('limit'), $paramFetcher->get('offset'));

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $this->em->createQueryBuilder()
              ->select('COUNT(o.id)')
              ->from('HexaaStorageBundle:Organization', 'o')
              ->innerJoin('o.principals', 'm')
              ->where(':p MEMBER OF o.principals')
              ->setParameters(array(":p" => $p))
              ->getQuery()
              ->getSingleScalarResult();

            return array("item_number" => (int)$itemNumber, "items" => $reto);
        } else {
            return $reto;
        }
    }

    /**
     * list all entitlements of the user
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
     *   section = "Principal",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
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
    public function cgetPrincipalEntitlementsAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called by ".$p->getFedid());

        $es = $this->em->getRepository('HexaaStorageBundle:Entitlement')->findAllByPrincipal(
          $p,
          $paramFetcher->get('limit'),
          $paramFetcher->get('offset')
        );

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $this->em->createQueryBuilder()
              ->select('COUNT(e.id)')
              ->from('HexaaStorageBundle:Entitlement', 'e')
              ->from('HexaaStorageBundle:RolePrincipal', 'rp')
              ->innerJoin('rp.role', 'r')
              ->where('e MEMBER OF r.entitlements ')
              ->andWhere('rp.principal = :p')
              ->setParameters(array("p" => $p))
              ->getQuery()
              ->getSingleScalarResult();

            return array("item_number" => (int)$itemNumber, "items" => $es);
        } else {
            return $es;
        }
    }

    /**
     * list entitlements of the user from the given service
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
     *   section = "Principal",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found",
     *     409 = "Service is not enabled"
     *   },
     * requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @param                       $sid          Service id
     * @return array
     */
    public function cgetPrincipalServiceEntitlementsAction(Request $request, ParamFetcherInterface $paramFetcher, $sid)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called by ".$p->getFedid()." with sid=".$sid);

        /* @var $s Service */
        $s = $this->eh->get('Service', $sid, $loglbl);
        if (!$s->getIsEnabled()) {
            $this->errorlog->error($loglbl."Service with id=".$sid."is not enabled, returning HTTP 400.");
            throw new HttpException(409, "Service is not enabled");
        }

        $es = $this->em->getRepository('HexaaStorageBundle:Entitlement')->findAllByPrincipalAndService(
          $p,
          $s,
          $paramFetcher->get('limit'),
          $paramFetcher->get('offset')
        );

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $this->em->createQueryBuilder()
              ->select('COUNT(e.id)')
              ->from('HexaaStorageBundle:Entitlement', 'e')
              ->from('HexaaStorageBundle:RolePrincipal', 'rp')
              ->innerJoin('rp.role', 'r')
              ->where('e MEMBER OF r.entitlements ')
              ->andWhere('rp.principal = :p')
              ->andWhere('e.service = :s')
              ->setParameters(array("p" => $p, "s" => $s))
              ->getQuery()
              ->getSingleScalarResult();

            return array("item_number" => (int)$itemNumber, "items" => $es);
        } else {
            return $es;
        }
    }

    /**
     * list attributes (Organization and principal attribute values + entitlements) of the user from the given service
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
     *   section = "Principal",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found",
     *     409 = "Service is not enabled"
     *   },
     * requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @param                       $sid          Service id
     * @return array
     */
    public function cgetPrincipalServiceAttributesAction(Request $request, ParamFetcherInterface $paramFetcher, $sid)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called by ".$p->getFedid()." with sid=".$sid);

        /* @var $s Service */
        $s = $this->eh->get('Service', $sid, $loglbl);
        if (!$s->getIsEnabled()) {
            $this->errorlog->error($loglbl."Service with id=".$sid."is not enabled, returning HTTP 400.");
            throw new HttpException(409, "Service is not enabled");
        }

        $es = $this->em->getRepository('HexaaStorageBundle:Entitlement')->findAllByPrincipalAndService($p, $s);

        $sass = $this->em->createQueryBuilder()
          ->select("sas")
          ->from("HexaaStorageBundle:ServiceAttributeSpec", "sas")
          ->where("sas.service = :s")
          ->setParameter(":s", $s)
          ->getQuery()
          ->getResult();;

        $retarr = array();
        if (count($es) >= 1) {
            $retarr['urn:oid:1.3.6.1.4.1.5923.1.1.1.7'] = $es;
        }

        /* @var $sas ServiceAttributeSpec */
        foreach ($sass as $sas) {
            $avps = array();
            $tmps = $this->em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->findBy(
              array(
                "attributeSpec" => $sas->getAttributeSpec(),
                "principal"     => $p,
              )
            );
            foreach ($tmps as $tmp) {
                if ($tmp->hasService($s) || ($tmp->getServices()->count() == 0)) {
                    $avps[] = $tmp;
                }
            }


            if (!array_key_exists($sas->getAttributeSpec()->getUri(), $retarr)) {
                $retarr[$sas->getAttributeSpec()->getUri()] = array();
            }
            foreach ($avps as $avp) {
                /* @var $avp AttributeValuePrincipal */
                if (!in_array($avp->getValue(), $retarr[$sas->getAttributeSpec()->getUri()])) {
                    array_push($retarr[$sas->getAttributeSpec()->getUri()], $avp->getValue());
                }
            }

            $avos = array();
            $tmps = $this->em->createQueryBuilder()
              ->select("avo")
              ->from("HexaaStorageBundle:AttributeValueOrganization", "avo")
              ->join("avo.organization", "o")
              ->where(":p MEMBER OF o.principals")
              ->andWhere("avo.attributeSpec = :attr_spec")
              ->setParameters(
                array(
                  ":p"         => $p,
                  ":attr_spec" => $sas->getAttributeSpec(),
                )
              )
              ->getQuery()
              ->getResult();

            foreach ($tmps as $tmp) {
                if ($tmp->hasService($s) || ($tmp->getServices()->count() == 0)) {
                    $avos[] = $tmp;
                }
            }

            foreach ($avos as $avo) {
                /* @var $avo AttributeValueOrganization */
                if (!in_array($avo->getValue(), $retarr[$sas->getAttributeSpec()->getUri()]) &&
                  ($avo->hasService($s) || ($avo->getServices()->count() == 0))
                ) {
                    array_push($retarr[$sas->getAttributeSpec()->getUri()], $avo->getValue());
                }
            }
        }

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = count($retarr);

            return array(
              "item_number" => (int)$itemNumber,
              "items"       => array_slice($retarr, $paramFetcher->get('offset'), $paramFetcher->get('limit')),
            );
        } else {
            return $retarr;
        }
    }

    /**
     * list all services connected to the user through Entitlement Packs
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
     *   section = "Principal",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
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
    public function cgetPrincipalServicesRelatedAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called by ".$p->getFedid());

        $ss = $this->em->getRepository('HexaaStorageBundle:Service')->findAllByRelatedPrincipal(
          $p,
          $paramFetcher->get('limit'),
          $paramFetcher->get('offset')
        );

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $this->em->createQueryBuilder()
              ->select('COUNT(s.id)')
              ->from('HexaaStorageBundle:Service', 's')
              ->innerJoin('s.links', 'link')
              ->innerJoin('link.organization', 'o')
              ->where(':p MEMBER OF o.principals ')
              ->andWhere("link.status='accepted'")
              ->andWhere("s.isEnabled=true")
              ->setParameters(array(":p" => $p))
              ->getQuery()
              ->getSingleScalarResult();

            return array("item_number" => (int)$itemNumber, "items" => $ss);
        } else {
            return $ss;
        }
    }

    /**
     * list all entitlement packs connected to the user through Organization membership
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
     *   section = "Principal",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
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
    public function cgetPrincipalEntitlementpackRelatedAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called by ".$p->getFedid());

        $eps = $this->em->getRepository('HexaaStorageBundle:EntitlementPack')->findAllByRelatedPrincipal(
          $p,
          $paramFetcher->get('limit'),
          $paramFetcher->get('offset')
        );

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $this->em->createQueryBuilder()
              ->select('COUNT(ep.id)')
              ->from('HexaaStorageBundle:EntitlementPack', 'ep')
              ->innerJoin('HexaaStorageBundle:OrganizationEntitlementPack', 'oep', 'WITH', 'oep.entitlementPack = ep')
              ->leftJoin('oep.organization', 'o')
              ->leftJoin("ep.service", "s")
              ->where(':p MEMBER OF o.principals ')
              ->andWhere("oep.status='accepted'")
              ->andWhere("s.isEnabled=true")
              ->setParameters(array(":p" => $p))
              ->getQuery()
              ->getSingleScalarResult();

            return array("item_number" => (int)$itemNumber, "items" => $eps);
        } else {
            return $eps;
        }
    }

    /**
     * list all roles of the user
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
     *   section = "Principal",
     *   resource = true,
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
    public function cgetPrincipalRolesAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called by ".$p->getFedid());

        $rs = $this->em->createQueryBuilder()
          ->select('r')
          ->from('HexaaStorageBundle:Role', 'r')
          ->innerJoin('HexaaStorageBundle:RolePrincipal', 'rp', 'WITH', 'rp.role = r')
          ->where('rp.principal = :p')
          ->setFirstResult($paramFetcher->get('offset'))
          ->setMaxResults($paramFetcher->get('limit'))
          ->orderBy("r.name", "ASC")
          ->setParameters(array("p" => $p))
          ->getQuery()
          ->getResult();

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $this->em->createQueryBuilder()
              ->select('COUNT(r.id)')
              ->from('HexaaStorageBundle:Role', 'r')
              ->innerJoin('HexaaStorageBundle:RolePrincipal', 'rp', 'WITH', 'rp.role = r')
              ->where('rp.principal = :p')
              ->setParameters(array("p" => $p))
              ->getQuery()
              ->getSingleScalarResult();

            return array("item_number" => (int)$itemNumber, "items" => $rs);
        } else {
            return $rs;
        }
    }

    /**
     * create new principal
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
     *   section = "Principal",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when principal has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when organization is not found"
     *   },
     *   tags = {"admins"},
     *   requirements = {
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="fedid","dataType"="string","required"=true,"description"="Federal ID of principal"},
     *      {"name"="email","dataType"="string","required"=true,"description"="Contact e-mail address of principal"},
     *      {"name"="display_name","dataType"="string","required"=true,"description"="Displayable name of principal"}
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
    public function postPrincipalAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called by ".$p->getFedid());


        return $this->processForm(new Principal(), $loglbl, $request, "POST");
    }

    private function processForm(Principal $p, $loglbl, Request $request, $method = "PUT")
    {
        $statusCode = $p->getId() == null ? 201 : 204;

        $form = $this->createForm(new PrincipalType(), $p, array("method" => $method));
        $form->submit($request->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $this->em->persist($p);
            $this->em->flush();

            if (201 === $statusCode) {
                $this->modlog->info($loglbl."New Principal created with id=".$p->getId());
            } else {
                $this->modlog->info($loglbl."Principal edited with id=".$p->getId());
            }


            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set(
                  'Location',
                  $this->generateUrl(
                    'get_principal_id',
                    array('id' => $p->getId()),
                    UrlGeneratorInterface::ABSOLUTE_URL // absolute
                  )
                );
            }

            return $response;
        }

        $this->errorlog->error(
          $loglbl."Validation error: \n".$this->get("serializer")->serialize(
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
     * principal edit by id
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
     *     entity="Principal",
     *     source="principal"
     *     )
     *
     * @ApiDoc(
     *   section = "Principal",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when principal has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when organization is not found"
     *   },
     *   tags = {"admins"},
     *   requirements = {
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="principal id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="fedid","dataType"="string","required"=true,"description"="Federal ID of principal"},
     *      {"name"="email","dataType"="string","required"=true,"description"="Contact e-mail address of principal"},
     *      {"name"="display_name","dataType"="string","required"=true,"description"="Displayable name of principal"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Principal id
     *
     *
     * @return View|Response
     */
    public function putPrincipalAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        $toEdit = $this->eh->get('Principal', $id, $loglbl);

        return $this->processForm($toEdit, $loglbl, $request, "PUT");
    }

    /**
     * Principal edit by id<br>
     * Note: principals may edit their own e-mail and displayable name without being admins.
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
     *     entity="Principal",
     *     id="id",
     *     source="attributes"
     *     )
     *
     * @ApiDoc(
     *   section = "Principal",
     *   resource = false,
     *   description = "principal edit by id",
     *   statusCodes = {
     *     201 = "Returned when principal has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when organization is not found"
     *   },
     *   tags = {"admins"},
     *   requirements = {
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="principal id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="fedid","dataType"="string","required"=true,"description"="Federal ID of principal"},
     *      {"name"="email","dataType"="string","required"=true,"description"="Contact e-mail address of principal"},
     *      {"name"="display_name","dataType"="string","required"=true,"description"="Displayable name of principal"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Principal id
     *
     *
     * @return View|Response
     */
    public function patchPrincipalAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        /** @var Principal $toEdit */
        $toEdit = $this->eh->get('Principal', $id, $loglbl);


        if (!($request->attributes->has('_security.level') && $request->attributes->get('_security.level') === 'admin')) {
            /** Principals can't edit their own fedid unless they are admins */
            if ($request->request->has('fedid')
              && $request->request->get('fedid') !== $p->getFedid()) {
                $this->errorlog->error($loglbl."User ".$p->getFedid()." is not permitted to modify his/her own fedid");
                throw new HttpException(403, "Modifying your own fedid is forbidden.");
            }
            /** Principals can't edit other users unless they are admins */
            if ($p->getId() !== $toEdit->getId()) {
                $this->errorlog->error($loglbl."User ".$p->getFedid()." is not permitted to modify other users' data.");
                throw new HttpException(403, "Modifying others is forbidden.");
            }
        }

        return $this->processForm($toEdit, $loglbl, $request, "PATCH");
    }

    /**
     * principal self delete
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
     *     types={"user_removed"},
     *     entity="Principal",
     *     source="principal"
     *     )
     *
     *
     * @ApiDoc(
     *   section = "Principal",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when principal has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     *
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     *
     */
    public function deletePrincipalAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called by ".$p->getFedid());

        $this->em->remove($p);
        $this->em->flush();
        $this->modlog->info($loglbl."Principal with id=".$p->getId()." deleted him/herself");
    }

    /**
     * delete principal by fedid
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
     *
     * @InvokeHook(
     *     types={"attribute_change", "user_removed", "user_added"},
     *     entity="Principal",
     *     id="fedid",
     *     source="attributes"
     *     )
     *
     *
     * @ApiDoc(
     *   section = "Principal",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when principal has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   tags = {"admins"},
     *   requirements ={
     *      {"name"="fedid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="federal ID of principal to delete"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $fedid        Principal fedid
     *
     *
     */
    public function deletePrincipalFedidAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $fedid
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with fedid=".$fedid." by ".$p->getFedid());

        $toDelete = $this->em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($fedid);
        if ($request->getMethod() == "DELETE" && !$toDelete) {
            $this->errorlog->error($loglbl."the requested Principal with fedid=".$fedid." was not found");
            throw new HttpException(404, "Principal not found");
        }

        // set affected entity for Hook
        $request->attributes->set(
          '_attributeChangeAffectedEntity',
          array(
            "entity" => "Service",
            "id"     => $this->em->getRepository('HexaaStorageBundle:Service')->findAllIdsByRelatedPrincipal($toDelete),
            "fedid"  => $toDelete->getFedid(),
          )
        );

        $this->em->remove($toDelete);
        $this->em->flush();
        $this->modlog->info($loglbl."Principal with fedid=".$fedid." has been deleted");
    }

    /**
     * delete principal by id
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
     *
     * @InvokeHook(
     *     types={"user_removed"},
     *     entity="Principal",
     *     id="id",
     *     source="attributes"
     *     )
     *
     *
     * @ApiDoc(
     *   section = "Principal",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when principal has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   tags = {"admins"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="principal id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Principal id
     *
     *
     */
    public function deletePrincipalIdAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        $toDelete = $this->eh->get('Principal', $id, $loglbl);

        // set affected entity for Hook
        $request->attributes->set(
          '_attributeChangeAffectedEntity',
          array(
            "entity" => "Service",
            "id"     => $this->em->getRepository('HexaaStorageBundle:Service')->findAllIdsByRelatedPrincipal($toDelete),
            "fedid"  => $toDelete->getFedid(),
          )
        );

        $this->em->remove($toDelete);
        $this->em->flush();
        $this->modlog->info($loglbl."Principal with id=".$id." has been deleted");
    }

}
