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
use Hexaa\StorageBundle\Form\EntityidRequestType;
use Hexaa\StorageBundle\Entity\EntityidRequest;
use Hexaa\StorageBundle\Entity\News;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class EntityidController extends FOSRestController implements PersonalAuthenticatedController {

    /**
     * List all existing and enabled service entityIDs from HEXAA config
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
     * @ApiDoc(
     *   section = "EntityID",
     *   description = "list service entityIDs",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
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
    public function cgetEntityidsAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        $retarr = array_slice(array_keys($this->container->getParameter('hexaa_service_entityids')), $paramFetcher->get('offset'), $paramFetcher->get('limit'));
        return $retarr;
    }

    /**
     * List all entityID requests for HEXAA admins, <br>
     * list all entityID requests of the current user for non-admins.
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
     * @ApiDoc(
     *   section = "EntityID",
     *   description = "list entityID requests",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     * requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\EntityidRequest>"
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
    public function cgetEntityidrequestsAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        if (in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
            $er = $em->getRepository('HexaaStorageBundle:EntityidRequest')->findBy(array(), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset'));
        } else {
            $er = $em->getRepository('HexaaStorageBundle:EntityidRequest')->findBy(array("requester" => $p), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset'));
        }
        return $er;
    }

    /**
     * get entity request<br><br>
     * 
     * Note: Admins may query requests that were requested by other than him/herself
     *
     *
     * @ApiDoc(
     *   section = "EntityID",
     *   description = "get entity request",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entityidRequest id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   output="Hexaa\StorageBundle\Entity\EntityidRequest"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return EntityidRequest
     */
    public function getEntityidrequestAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $er = $em->getRepository('HexaaStorageBundle:EntityidRequest')->find($id);
        if (!$er) {
            $errorlog->error($loglbl . "the requested EntityIDrequest with id=" . $id . " was not found");
            throw new HttpException(404, "EntityidRequest not found.");
            return;
        }
        return $er;
    }

    private function processForm(EntityidRequest $er, $loglbl, $method = "PUT") {
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $statusCode = $er->getId() == null ? 201 : 204;

        $form = $this->createForm(new EntityidRequestType(), $er, array("method" => $method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            if (201 === $statusCode) {
                $usr = $this->get('security.context')->getToken()->getUser();
                $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
                $er->setRequester($p);
            }
            $er->setStatus("pending");
            $em->persist($er);

            //Create News object to notify the user
            $n = new News();
            $n->setPrincipal($p);
            $n->setAdmin();
            if ($method == "POST") {
                $n->setTitle("New EntityID request created");
                $n->setMessage($p->getFedid() . " requested a new EntityID: " . $er->getEntityid());
            } else {
                $n->setTitle("EntityID request modified");
                $n->setMessage($p->getFedid() . " modified an EntityID request for: " . $er->getEntityid());
            }
            $n->setTag("entityid");
            $em->persist($n);
            $em->flush();
            $modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());


            if (201 === $statusCode) {
                $modlog->info($loglbl . "New EntityID request has been created with id=" . $er->getId());
            } else {
                $modlog->info($loglbl . "EntityID request has been edited with id=" . $er->getId());
            }

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_organization', array('id' => $er->getId()), true // absolute
                        )
                );
            }

            return $response;
        }
        $errorlog->error($loglbl . "Validation error");
        return View::create($form, 400);
    }

    /**
     * create new entityid request
     *
     *
     * @ApiDoc(
     *   section = "EntityID",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when entityid request has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   requirements = {
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="entityid","dataType"="string","required"=true,"description"="entityID to be requested"},
     *      {"name"="message","dataType"="string","required"=false,"description"="message to the HEXAA admin (metadata, etc.)"}
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
     */
    public function postEntityidrequestAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        return $this->processForm(new EntityidRequest(), $loglbl, "POST");
    }

    /**
     * edit entityid request preferences<br><br>
     * 
     * Note: Admins may query requests that were requested by other than him/herself
     *
     *
     * @ApiDoc(
     *   section = "EntityID",
     *   resource = false,
     *   description = "edit entityid request preferences",
     *   statusCodes = {
     *     204 = "Returned when entityid request has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entityid request id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   parameters = {
     *      {"name"="entityid","dataType"="string","required"=true,"description"="entityID to be requested"},
     *      {"name"="message","dataType"="string","required"=false,"description"="message to the HEXAA admin (metadata, etc.)"}
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
     */
    public function putEntityidrequestAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $er = $em->getRepository('HexaaStorageBundle:EntityidRequest')->find($id);
        if (!$er) {
            $errorlog->error($loglbl . "the requested EntityIDrequest with id=" . $id . " was not found");
            throw new HttpException(404, "EntityidRequest not found.");
        }
        return $this->processForm($er, $loglbl, "PUT");
    }

    /**
     * edit entityid request preferences<br><br>
     * 
     * Note: Admins may query requests that were requested by other than him/herself
     *
     *
     * @ApiDoc(
     *   section = "EntityID",
     *   resource = false,
     *   description = "edit entityid request preferences",
     *   statusCodes = {
     *     204 = "Returned when entityid request has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entityid request id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   parameters = {
     *      {"name"="entityid","dataType"="string","required"=true,"description"="entityID to be requested"},
     *      {"name"="message","dataType"="string","required"=false,"description"="message to the HEXAA admin (metadata, etc.)"}
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
     */
    public function patchEntityidrequestAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $er = $em->getRepository('HexaaStorageBundle:EntityidRequest')->find($id);
        if (!$er) {
            $errorlog->error($loglbl . "the requested EntityIDrequest with id=" . $id . " was not found");
            throw new HttpException(404, "EntityidRequest not found.");
        }
        return $this->processForm($er, $loglbl, "PATCH");
    }

    /**
     * delete entityid request<br><br>
     * 
     * Note: Admins may query requests that were requested by other than him/herself
     *
     *
     * @ApiDoc(
     *   section = "EntityID",
     *   resource = false,
     *   description = "delete entityid request",
     *   statusCodes = {
     *     204 = "Returned when entityid request has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entityid request id"},
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
    public function deleteEntityidrequestAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $er = $em->getRepository('HexaaStorageBundle:EntityidRequest')->find($id);
        if (!$er) {
            $errorlog->error($loglbl . "the requested EntityIDrequest with id=" . $id . " was not found");
            throw new HttpException(404, "EntityidRequest not found.");
        }
        $em->remove($er);

        //Create News object to notify the user
        $n = new News();
        $n->setPrincipal($p);
        $n->setAdmin();
        $n->setTitle("New EntityID request cancelled");
        $n->setMessage($p->getFedid() . " cancelled an EntityID request");
        $n->setTag("entityid");
        $em->persist($n);
        $em->flush();
        $modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());

        $modlog->info($loglbl . "EntityID request (id=" . $id . ") has been deleted");
    }

    /**
     * Mark entityID request as accepted
     *
     *
     * @ApiDoc(
     *   section = "EntityID",
     *   description = "accept entity request",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   tags = {"admins"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entityidRequest id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   output="Hexaa\StorageBundle\Entity\EntityidRequest"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return EntityidRequest
     */
    public function getEntityidrequestAcceptAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $er = $em->getRepository('HexaaStorageBundle:EntityidRequest')->find($id);
        if ($request->getMethod() == "GET" && !$er) {
            $errorlog->error($loglbl . "the requested EntityIDrequest with id=" . $id . " was not found");
            throw new HttpException(404, "EntityidRequest not found.");
            return;
        }
        $er->setStatus("accepted");
        $em->persist($er);

        //Create News object to notify the user
        $n = new News();
        $n->setPrincipal($er->getRequester());
        $n->setAdmin();
        $n->setTitle("EntityID request accepted");
        $n->setMessage("EntityID request for " . $er->getEntityid() . " has been accepted!");
        $n->setTag("entityid");
        $em->persist($n);
        $em->flush();
        $modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());

        $modlog->info($loglbl . "EntityID request (id=" . $id . ") has been marked as accepted");
        return $er;
    }

    /**
     * Mark entityID request as rejected
     *
     *
     * @ApiDoc(
     *   section = "EntityID",
     *   description = "reject entity request",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   tags = {"admins"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entityidRequest id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   output="Hexaa\StorageBundle\Entity\EntityidRequest"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return EntityidRequest
     */
    public function getEntityidrequestRejectAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $er = $em->getRepository('HexaaStorageBundle:EntityidRequest')->find($id);
        if ($request->getMethod() == "GET" && !$er) {
            $errorlog->error($loglbl . "the requested EntityIDrequest with id=" . $id . " was not found");
            throw new HttpException(404, "EntityidRequest not found.");
            return;
        }
        $er->setStatus("rejected");
        $em->persist($er);

        //Create News object to notify the user
        $n = new News();
        $n->setPrincipal($er->getRequester());
        $n->setAdmin();
        $n->setTitle("EntityID request rejected");
        $n->setMessage("EntityID request for " . $er->getEntityid() . " has been rejected.");
        $n->setTag("entityid");
        $em->persist($n);
        $em->flush();
        $modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());

        $modlog->info($loglbl . "EntityID request (id=" . $id . ") has been marked as rejected");
        return $er;
    }

}
