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
use Hexaa\StorageBundle\Entity\EntitlementPack;
use Hexaa\StorageBundle\Entity\LinkerToken;
use Hexaa\StorageBundle\Form\EntitlementPackType;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author  Soltész Balázs <solazs@sztaki.hu>
 */
class EntitlementpackController extends HexaaController implements PersonalAuthenticatedController {

    /**
     * create new entitlement pack
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
    public function postServiceEntitlementpackAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                                     ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl);

        $ep = new EntitlementPack();
        $ep->setService($s);

        return $this->processForm($ep, $loglbl, $request, "POST");
    }

    private function processForm(EntitlementPack $ep, $loglbl, Request $request, $method = "PUT") {
        $statusCode = $ep->getId() == null ? 201 : 204;

        $form = $this->createForm(new EntitlementPackType(), $ep, array("method" => $method));
        $form->submit($request->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $this->em->persist($ep);
            $this->em->flush();
            if (201 === $statusCode) {
                $this->modlog->info($loglbl . "New EntitlementPack has been created with id=" . $ep->getId());
            } else {
                $this->modlog->info($loglbl . "EntitlementPack has been edited with id=" . $ep->getId());
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
        $this->errorlog->error($loglbl . "Validation error: \n" . $this->get("serializer")->serialize($form->getErrors(false, true), "json"));

        return View::create($form, 400);
    }

    /**
     * get entitlement pack details
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
     *   section = "EntitlementPack",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement pack is not found"
     *   },
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement package id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   output="Hexaa\StorageBundle\Entity\EntitlementPack"
     * )
     *
     * @Annotations\Get(requirements={"id" = "\d+"})
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement pack
     * @param integer               $id           EntitlementPack id
     *
     * @return EntitlementPack
     */
    public function getEntitlementpackAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                             ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $ep = $this->eh->get('EntitlementPack', $id, $loglbl);

        return $ep;
    }

    /**
     * Generate a new one-time entitlement pack token
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
     *   section = "EntitlementPack",
     *   description = "generate new entitlement pack token",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement pack is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement package id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     * @Annotations\Get("/entitlementpacks/{id}/token", requirements={"id" = "\d+"})
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement pack
     * @param integer               $id           EntitlementPack id
     *
     * @return EntitlementPack
     */
    public function getEntitlementpackTokenAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                                  ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $ep = $this->eh->get('EntitlementPack', $id, $loglbl);

        $token = new LinkerToken($ep);
        $this->em->persist($token);
        $this->em->flush();

        return $token;
    }

    /**
     * List unused tokens
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
     *   section = "EntitlementPack",
     *   description = "generate new entitlement pack token",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement pack is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement package id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     * @Annotations\Get("/entitlementpacks/{id}/tokens", requirements={"id" = "\d+"})
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement pack
     * @param integer               $id           EntitlementPack id
     *
     * @return EntitlementPack
     */
    public function cgetEntitlementpackTokensAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                                    ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $ep = $this->eh->get('EntitlementPack', $id, $loglbl);

        return $ep->getTokens();
    }

    /**
     * Get all public entitlement packages.
     * May list private ones as well, but only if the user (an organization where the user is at least member)
     * and the service has a Tag in common
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
     *   section = "EntitlementPack",
     *   resource = true,
     *   description="get all public entitlement packages",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement pack is not found"
     *   },
     *   requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   output="array<Hexaa\StorageBundle\Entity\EntitlementPack>"
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement pack
     *
     * @return array
     */
    public function cgetEntitlementpacksPublicAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());

        $qb2 = $this->em->createQueryBuilder();
        $qb2->select('serv')
            ->from("HexaaStorageBundle:Service", "serv")
            ->from('HexaaStorageBundle:Tag', 'tag')
            ->leftJoin('HexaaStorageBundle:Organization', "org", "with", "org MEMBER OF tag.organizations")
            ->where(":p MEMBER OF org.principals")
            ->andWhere("serv MEMBER OF tag.services");
        $subQuery = $qb2->getDQL();

        $qb1 = $this->em->createQueryBuilder();
        $qb1->select("ep")
            ->from("HexaaStorageBundle:EntitlementPack", "ep")
            ->leftJoin("HexaaStorageBundle:Service", "service", "with", "ep.service = service")
            ->where("service.isEnabled = true")
            ->where("ep.type = 'public' OR service in (" . $subQuery . ")")
            ->setFirstResult($paramFetcher->get("offset"))
            ->setMaxResults($paramFetcher->get("limit"))
            ->orderBy("ep.name", "ASC")
            ->setParameter(":p", $p);

        $eps = $qb1->getQuery()->getResult();

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $qb3 = $this->em->createQueryBuilder();
            $itemNumber = $qb3->select("COUNT(ep.id)")
                ->from("HexaaStorageBundle:EntitlementPack", "ep")
                ->leftJoin("HexaaStorageBundle:Service", "service", "with", "ep.service = service")
                ->where("service.isEnabled = true")
                ->where("ep.type = 'public' OR service in (" . $subQuery . ")")
                ->setParameter(":p", $p)
                ->getQuery()
                ->getSingleScalarResult();

            return array("item_number" => (int)$itemNumber, "items" => $eps);
        } else {
            return $eps;
        }
    }

    /**
     * edit entitlement pack preferences
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
     *   section = "EntitlementPack",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when entitlement pack has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement pack is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement pack id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="name","dataType"="string","required"=true,"description"="displayable name of the entitlement pack"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"},
     *      {"name"="type","dataType"="string","required"=true,"format"="private|public","description"="visibility of the entitlement package"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement pack
     * @param integer               $id           EntitlementPack id
     *
     *
     * @return View|Response
     */
    public function putEntitlementpackAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                             ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $ep = $this->eh->get('EntitlementPack', $id, $loglbl);

        return $this->processForm($ep, $loglbl, $request, "PUT");
    }

    /**
     * edit entitlement pack preferences
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
     *   section = "EntitlementPack",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when entitlement pack has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement pack is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement pack id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="name","dataType"="string","required"=true,"description"="displayable name of the entitlement pack"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"},
     *      {"name"="type","dataType"="string","required"=true,"format"="private|public","description"="visibility of the entitlement package"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement pack
     * @param integer               $id           EntitlementPack id
     *
     *
     * @return View|Response
     */
    public function patchEntitlementpackAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                               ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $ep = $this->eh->get('EntitlementPack', $id, $loglbl);

        return $this->processForm($ep, $loglbl, $request, "PATCH");
    }

    /**
     * delete entitlement pack
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
     * @InvokeHook("attribute_change")
     *
     * @ApiDoc(
     *   section = "EntitlementPack",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when entitlement pack has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement pack is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement pack id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement pack
     * @param integer               $id           EntitlementPack id
     *
     *
     */
    public function deleteEntitlementpackAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                                ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $ep = $this->eh->get('EntitlementPack', $id, $loglbl);

        // get affected entity for hook
        $request->attributes->set('_attributeChangeAffectedEntity',
            array("entity"    => "Organization",
                  "id"        => $this->em->getRepository('HexaaStorageBundle:Organization')->getIdsByEntitlementPack($ep),
                  'serviceId' => $ep->getServiceId()
            ));

        foreach($ep->getEntitlements() as $e) {
            $os = $this->em->createQueryBuilder()
                ->select("o")
                ->from("HexaaStorageBundle:Organization", "o")
                ->innerJoin("HexaaStorageBundle:OrganizationEntitlementPack", 'oep', 'WITH', 'o = oep.organization')
                ->where("oep.entitlementPack = :ep")
                ->setParameter(":ep", $ep)
                ->getQuery()
                ->getResult();
            foreach($os as $o) {
                $numberOfEPsWithSameEntitlement = $this->em->createQueryBuilder()
                    ->select('count(oep.id)')
                    ->from('HexaaStorageBundle:OrganizationEntitlementPack', 'oep')
                    ->leftJoin('oep.entitlementPack', 'ep')
                    ->where('oep.organization = :o')
                    ->andWhere(':e MEMBER OF ep.entitlements')
                    ->andWhere("ep != :ep")
                    ->andWhere("oep.status = 'accepted'")
                    ->setParameters(array(":e" => $e, ":o" => $o, ":ep" => $ep))
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
        }
        $this->em->remove($ep);
        $this->em->flush();
        $this->modlog->info($loglbl . "Entitlement Pack with id=" . $id . " has been deleted");
    }

}
