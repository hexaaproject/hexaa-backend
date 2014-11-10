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

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class EntitlementpackController extends HexaaController implements ClassResourceInterface, PersonalAuthenticatedController {

    /**
     * get entitlement pack details
     *
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
     * @Annotations\Get("/entitlementpacks/{id}", requirements={"id" = "\d+"})
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement pack
     *
     * @return EntitlementPack
     */
    public function getAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
         
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
         
         
         
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
         
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $ep = $this->eh->get('EntitlementPack', $id, $loglbl);
        return $ep;
    }

    /**
     * Generate a new one-time entitlement pack token
     *
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
     *
     * @return EntitlementPack
     */
    public function getTokenAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
         
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
         
         
         
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
         
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $ep = $this->eh->get('EntitlementPack', $id, $loglbl);
        
        $token = $ep->generateToken();
        $this->em->persist($ep);
        $this->em->flush();
        return array('token' => $token);
    }

    /**
     * get all public entitlement packages
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
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
    public function cgetPublicAction(Request $request, ParamFetcherInterface $paramFetcher) {
         
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
         
         
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
         
        $this->accesslog->info($loglbl . "Called by ". $p->getFedid());

        $eps = $this->em->createQueryBuilder()
                ->select('ep')
                ->from('HexaaStorageBundle:EntitlementPack', 'ep')
                ->leftJoin('ep.service', 's')
                ->where('ep.type = :p')
                ->andWhere('s.isEnabled = true')
                ->setFirstResult($paramFetcher->get('offset'))
                ->setMaxResults($paramFetcher->get('limit'))
                ->setParameters(array('p' => "public"))
                ->getQuery()
                ->getResult()
        ;
        return $eps;
    }

    /**
     * edit entitlement pack preferences
     *
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
     *
     * 
     */
    public function putAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
         
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
         
         
         
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
         
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $ep = $this->eh->get('EntitlementPack', $id, $loglbl);
        return $this->processForm($ep, $loglbl, "PUT");
    }

    /**
     * edit entitlement pack preferences
     *
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
     *
     * 
     */
    public function patchAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
         
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
         
         
         
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
         
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $ep = $this->eh->get('EntitlementPack', $id, $loglbl);
        return $this->processForm($ep, $loglbl, "PATCH");
    }

    private function processForm(EntitlementPack $ep, $loglbl, $method = "PUT") {
         
        $modlog = $this->get('monolog.logger.modification');
         
        $statusCode = $ep->getId() == null ? 201 : 204;

        $form = $this->createForm(new EntitlementPackType(), $ep, array("method"=>$method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $this->em->persist($ep);
            $this->em->flush();
            if (201 === $statusCode) {
                $modlog->info($loglbl . "New EntitlementPack has been created with id=" . $ep->getId());
            } else {
                $modlog->info($loglbl . "EntitlementPack has been edited with id=" . $ep->getId());
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
     * delete entitlement pack
     *
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
     *
     * 
     */
    public function deleteAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
         
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
         
         
         
        $modlog = $this->get('monolog.logger.modification');
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
         
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $ep = $this->eh->get('EntitlementPack', $id, $loglbl);
        $this->em->remove($ep);
        $this->em->flush();
        $modlog->info($loglbl . "Entitlement Pack with id=" . $id . " has been deleted");
    }

}
