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


use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Request\ParamFetcherInterface;

use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Hexaa\StorageBundle\Entity\Principal;
use Hexaa\StorageBundle\Form\PrincipalType;

/**
 * Description of PrincipalController
 *
 * @author baloo
 */
class PrincipalController extends HexaaController {

    /**
     * get list of principals
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
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
     *   },
     *   output="array<Hexaa\StorageBundle\Entity\Principal>"
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
    public function cgetPrincipalsAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());

        $p = $this->em->getRepository('HexaaStorageBundle:Principal')->findBy(array(), array("fedid" => "asc"), $paramFetcher->get('limit'), $paramFetcher->get('offset'));
        return $p;
    }

    /**
     * get if current principal is a HEXAA admin
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
    public function getPrincipalIsadminAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                              ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());

        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
            return array("is_admin" => false);
        }
        return array("is_admin" => true);
    }

    /**
     * get info about current principal 
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
     *  },
     *   output="Hexaa\StorageBundle\Entity\Principal"
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
    public function getPrincipalSelfAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                           ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());

        return $p;
    }

    /**
     * get info about principal by id
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
     *      {"name"="id", "dataType"="integer", "required"=true, "description"="id of principal"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   output="Hexaa\StorageBundle\Entity\Principal"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer $id Principal id
     *
     * @return Principal
     */
    public function getPrincipalIdAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                         ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $p = $this->eh->get('Principal', $id, $loglbl);
        return $p;
    }

    /**
     * get info about a principal by fedid
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
     *      {"name"="fedid", "dataType"="string", "required"=true, "description"="Federal ID of principal"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   output="Hexaa\StorageBundle\Entity\Principal"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer $fedid Principal fedid
     *
     * @return Principal
     */
    public function getPrincipalFedidAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                            ParamFetcherInterface $paramFetcher, $fedid) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with fedid=" . $fedid . " by " . $p->getFedid());

        $p = $this->em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($fedid);
        if ($request->getMethod() == "GET" && !$p) {
            $this->errorlog->error($loglbl . "the requested Principal with fedid=" . $fedid . " was not found");
            throw new HttpException(404, "Principal not found");
        }
        return $p;
    }

    /**
     * list all invitations of the current principal
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
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
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\Invitation>"
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
    public function cgetPrincipalInvitationsAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());

        $is = $this->em->getRepository('HexaaStorageBundle:Invitation')->findBy(array("inviter" => $p), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset'));
        return $is;
    }

    /**
     * list available attribute specifications
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
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
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\AttributeSpec>"
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
    public function cgetPrincipalAttributespecsAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());

        return $this->em->getRepository('HexaaStorageBundle:AttributeSpec')->findAllByPrincipal($p, $paramFetcher->get('limit'), $paramFetcher->get('offset'));
    }

    /**
     * list available attribute values of the current principal and the specified attribute specification
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
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
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\AttributeValuePrincipal>"
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
    public function cgetPrincipalAttributespecsAttributevalueprincipalsAction(Request $request, ParamFetcherInterface $paramFetcher, $asid = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with asid=" . $asid . " by " . $p->getFedid());

        // Get attribute specifications from organization membership
        $ass = $this->em->getRepository('HexaaStorageBundle:AttributeSpec')->findAllByPrincipal($p);

        $as = $this->eh->get('AttributeSpec', $asid, $loglbl);
        if ($request->getMethod() == "GET" && !in_array($as, $ass, true)) {
            throw new HttpException(400, "the Attribute specification is not visible to the user.");
        }
        $avps = $this->em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')
                ->findBy(array(
            "principal" => $p,
            "attributeSpec" => $as
                ), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset')
        );
        return $avps;
    }

    /**
     * list all attribute values of the principal
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
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
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\AttributeValuePrincipal>"
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
    public function cgetPrincipalAttributevalueprincipalAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());

        $avps = $this->em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->findBy(array("principal" => $p), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset'));

        return $avps;
    }

    /**
     * list all services where the user is a manager
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
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
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\Service>"
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
    public function cgetManagerServicesAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());

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
                ->getResult()
        ;
        return $rets;
    }

    /**
     * list all organizations where the user is a manager
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
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
     *   },
     *   output="array<Hexaa\StorageBundle\Entity\Organization>"
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
    public function cgetManagerOrganizationsAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());

        $reto = $this->em->createQueryBuilder()
                ->select('o')
                ->from('HexaaStorageBundle:Organization', 'o')
                ->innerJoin('o.principals', 'm')
                ->where(':p MEMBER OF o.managers ')
                ->setFirstResult($paramFetcher->get('offset'))
                ->setMaxResults($paramFetcher->get('limit'))
                ->orderBy("o.name", "ASC")
                ->setParameters(array("p" => $p))
                ->getQuery()
                ->getResult()
        ;
        return $reto;
    }

    /**
     * list all organizations where the user is a member
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
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
     *   },
     *   output="array<Hexaa\StorageBundle\Entity\Organization>"
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
    public function cgetMemberOrganizationsAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());

        $reto = $this->em->createQueryBuilder()
                ->select('o')
                ->from('HexaaStorageBundle:Organization', 'o')
                ->innerJoin('o.principals', 'm')
                ->where(':p MEMBER OF o.principals ')
                ->setFirstResult($paramFetcher->get('offset'))
                ->setMaxResults($paramFetcher->get('limit'))
                ->orderBy("o.name", "ASC")
                ->setParameters(array("p" => $p))
                ->getQuery()
                ->getResult()
        ;
        return $reto;
    }

    /**
     * list all entitlements of the user
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
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
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\Entitlement>"
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
    public function cgetPrincipalEntitlementsAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());

        return $this->em->getRepository('HexaaStorageBundle:Entitlement')->findAllByPrincipal($p, $paramFetcher->get('limit'), $paramFetcher->get('offset'));
    }

    /**
     * list all services connected to the user through Entitlement Packs
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
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
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\Service>"
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
    public function cgetPrincipalServicesRelatedAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());

        $ss = $this->em->getRepository('HexaaStorageBundle:Service')->findAllByRelatedPrincipal($p, $paramFetcher->get('limit'), $paramFetcher->get('offset'));
        return $ss;
    }

    /**
     * list all roles of the user
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
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
     *   },
     *   output="array<Hexaa\StorageBundle\Entity\Role>"
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
    public function cgetPrincipalRolesAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());

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
                ->getResult()
        ;
        return $rs;
    }

    private function processForm(Principal $p, $loglbl, $method = "PUT") {
        $statusCode = $p->getId() == null ? 201 : 204;

        $form = $this->createForm(new PrincipalType(), $p, array("method" => $method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $this->em->persist($p);
            $this->em->flush();

            if (201 === $statusCode) {
                $this->modlog->info($loglbl . "New Principal created with id=" . $p->getId());
            } else {
                $this->modlog->info($loglbl . "Principal edited with id=" . $p->getId());
            }


            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_principal_id', array('id' => $p->getId()), true // absolute
                        )
                );
            }

            return $response;
        }

        $this->errorlog->error($loglbl . "Validation error");
        return View::create($form, 400);
    }

    /**
     * create new principal
     *
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
     * @param Request $request the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     *
     * @return View|Response
     */
    public function postPrincipalAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                        ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());


        return $this->processForm(new Principal(), $loglbl, "POST");
    }

    /**
     * principal edit by id
     *
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
     * @param Request $request the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer $id Principal id
     *
     *
     * @return View|Response
     */
    public function putPrincipalAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                       ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $toEdit = $this->eh->get('Principal', $id, $loglbl);
        return $this->processForm($toEdit, $loglbl, "PUT");
    }

    /**
     * Principal edit by id<br>
     * Note: principals may edit their e-mail and displayable name.
     *
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
     * @param Request $request the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer $id Principal id
     *
     *
     * @return View|Response
     */
    public function patchPrincipalAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                         ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $toEdit = $this->eh->get('Principal', $id, $loglbl);

        if ($request->request->has('fedid') && $request->request->get('fedid') != $p->getFedid() && $p === $toEdit && !in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
            $this->errorlog->error($loglbl . "User " . $p->getFedid() . " is not permitted to modify his/her own fedid");
            throw new HttpException(403, "You are forbidden to modify your fedid");
        }
        return $this->processForm($toEdit, $loglbl, "PATCH");
    }

    /**
     * principal self delete
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
    public function deletePrincipalAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                          ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());

        $this->em->remove($p);
        $this->em->flush();
        $this->modlog->info($loglbl . "Principal with id=" . $p->getId() . " deleted him/herself");
    }

    /**
     * delete principal by fedid
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
     * @param integer $fedid Principal fedid
     *
     * 
     */
    public function deletePrincipalFedidAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                               ParamFetcherInterface $paramFetcher, $fedid) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with fedid=" . $fedid . " by " . $p->getFedid());

        $toDelete = $this->em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($fedid);
        if ($request->getMethod() == "DELETE" && !$toDelete) {
            $this->errorlog->error($loglbl . "the requested Principal with fedid=" . $fedid . " was not found");
            throw new HttpException(404, "Principal not found");
        }
        $this->em->remove($toDelete);
        $this->em->flush();
        $this->modlog->info($loglbl . "Principal with fedid=" . $fedid . " has been deleted");
    }

    /**
     * delete principal by id
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
     * @param integer $id Principal id
     *
     * 
     */
    public function deletePrincipalIdAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                            ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $toDelete = $this->eh->get('Principal', $id, $loglbl);
        $this->em->remove($toDelete);
        $this->em->flush();
        $this->modlog->info($loglbl . "Principal with id=" . $id . " has been deleted");
    }

}
