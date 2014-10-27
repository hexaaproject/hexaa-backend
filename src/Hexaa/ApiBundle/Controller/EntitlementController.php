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
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\RouteRedirectView;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Hexaa\StorageBundle\Form\EntitlementType;
use Hexaa\StorageBundle\Entity\Entitlement;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class EntitlementController extends FOSRestController implements ClassResourceInterface, PersonalAuthenticatedController {

    /**
     * get entitlement details
     *
     *
     * @ApiDoc(
     *   section = "Entitlement",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement id"}
     *   },
     *   output="Hexaa\StorageBundle\Entity\Entitlement"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement
     *
     * @return Entitlement
     */
    public function getAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $eh = $this->get('hexaa.handler.entity_handler');
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $e = $eh->get('Entitlement', $id, $loglbl);
        return $e;
    }

    /**
     * edit entitlement preferences
     *
     *
     * @ApiDoc(
     *   section = "Entitlement",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when entitlement has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="uri","dataType"="string","required"=true,"description"="URI of entitlement"},
     *      {"name"="name","dataType"="string","required"=true,"description"="Displayable name of the entitlement"},
     *      {"name"="description","dataType"="string","required"=false,"description"="Description"}
     *   }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement
     *
     * 
     */
    public function putAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $eh = $this->get('hexaa.handler.entity_handler');
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $e = $eh->get('Entitlement', $id, $loglbl);
        return $this->processForm($e, $loglbl, "PUT");
    }

    /**
     * edit entitlement preferences
     *
     *
     * @ApiDoc(
     *   section = "Entitlement",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when entitlement has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="uri","dataType"="string","required"=true,"description"="URI of entitlement"},
     *      {"name"="name","dataType"="string","required"=true,"description"="Displayable name of the entitlement"},
     *      {"name"="description","dataType"="string","required"=false,"description"="Description"}
     *   }
     * ) 
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement
     *
     * 
     */
    public function patchAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $eh = $this->get('hexaa.handler.entity_handler');
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $e = $eh->get('Entitlement', $id, $loglbl);
        return $this->processForm($e, $loglbl, "PATCH");
    }

    private function processForm(Entitlement $e, $loglbl, $method="PUT") {
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $statusCode = $e->getId() == null ? 201 : 204;

        $form = $this->createForm(new EntitlementType(), $e, array("method"=>$method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $em->persist($e);
            $em->flush();
            if (201 === $statusCode) {
                $modlog->info($loglbl . "New Entitlement has been created with id=" . $e->getId());
            } else {
                $modlog->info($loglbl . "Entitlement has been edited with id=" . $e->getId());
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
        $errorlog->error($loglbl . "Validation error");
        return View::create($form, 400);
    }

    /**
     * delete entitlement
     *
     *
     * @ApiDoc(
     *   section = "Entitlement",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when entitlement has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     * 
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement
     *
     * 
     */
    public function deleteAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $eh = $this->get('hexaa.handler.entity_handler');
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $e = $eh->get('Entitlement', $id, $loglbl);
        $em->remove($e);
        $em->flush();
        $modlog->info($loglbl . "Entitlement has been deleted with id=" . $id);
    }

}
