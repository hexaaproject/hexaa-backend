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


use FOS\RestBundle\Routing\ClassResourceInterface;

use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Request\ParamFetcherInterface;

use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Hexaa\StorageBundle\Form\AttributeSpecType;
use Hexaa\StorageBundle\Entity\AttributeSpec;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class AttributespecController extends HexaaController implements ClassResourceInterface, PersonalAuthenticatedController {

    /**
     * Lists all attribute specifications
     * 
     *
     * 
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
     * 
     * @ApiDoc(
     *   section = "AttributeSpec",
     *   resource = true,
     *   description = "get all attribute specifications",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   output="array<Hexaa\StorageBundle\Entity\AttributeSpec>"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     *
     * @return array
     */
    public function cgetAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());

        $as = $this->em->getRepository('HexaaStorageBundle:AttributeSpec')->findBy(array(), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset'));
        return $as;
    }

    /**
     * get attribute specification details
     *
     *
     * @ApiDoc(
     *   section = "AttributeSpec",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute specification id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     * @param integer               $id           AttributeSpec id
     *
     * @return AttributeSpec
     */
    public function getAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                              ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "called with id=" . $id . " by " . $p->getFedid());

        $as = $this->eh->get('AttributeSpec', $id, $loglbl);
        return $as;
    }

    /**
     * Edit attribute specification preferences<br>
     * Note: admins only!
     *
     *
     * 
     * @ApiDoc(
     *   section = "AttributeSpec",
     *   resource = false,
     *   description = "edit attribute specification preferences",
     *   statusCodes = {
     *     204 = "Returned when attribute specification has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when attribute specification is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute specification id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  tags = {"admins"},
     *  parameters = {
     *      {"name"="oid","dataType"="string","required"=true,"description"="oid of attribute specification"},
     *      {"name"="friendly_name","dataType"="string","required"=true,"description"="displayable name of the attribute specification"},
     *      {"name"="maintainer","dataType"="enum","required"=true, "format"="user|manager", "description"="maintainer of the attribute"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"},
     *      {"name"="syntax","dataType"="string","required"=true,"description"="data type of connected values"},
     *      {"name"="is_multivalue","dataType"="boolean","required"=true,"format"="true|false","description"=""}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     * @param integer               $id           AttributeSpec id
     *
     * @return null
     * 
     */
    public function putAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                              ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "called with id=" . $id . " by " . $p->getFedid());

        $as = $this->eh->get('AttributeSpec', $id, $loglbl);
        return $this->processForm($as, $loglbl, $request , 'PUT');
    }

    /**
     * Edit attribute specification<br>
     * Note: admins only!
     *
     *
     * @ApiDoc(
     *   section = "AttributeSpec",
     *   resource = false,
     *   description = "edit attribute specification preferences",
     *   statusCodes = {
     *     204 = "Returned when attribute specification has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when attribute specification is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute specification id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  tags = {"admins"},
     *  parameters = {
     *      {"name"="oid","dataType"="string","required"=true,"description"="oid of attribute specification"},
     *      {"name"="friendly_name","dataType"="string","required"=true,"description"="displayable name of the attribute specification"},
     *      {"name"="maintainer","dataType"="enum","required"=true, "format"="user|manager", "description"="maintainer of the attribute"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"},
     *      {"name"="syntax","dataType"="string","required"=true,"description"="data type of connected values"},
     *      {"name"="is_multivalue","dataType"="boolean","required"=true,"format"="true|false","description"=""}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     * @param integer               $id           AttributeSpec id
     *
     * @return null
     * 
     */
    public function patchAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "called with id=" . $id . " by " . $p->getFedid());

        $as = $this->eh->get('AttributeSpec', $id, $loglbl);
        return $this->processForm($as, $loglbl, $request, 'PATCH');
    }

    /**
     * Create new attribute specification<br>
     * Note: admins only!
     *
     *
     * @ApiDoc(
     *   section = "AttributeSpec",
     *   resource = false,
     *   description = "create new attribute specification",
     *   statusCodes = {
     *     201 = "Returned when attribute specification has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when attribute specification is not found"
     *   },
     *   requirements = {
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *  tags = {"admins"},
     *  parameters = {
     *      {"name"="oid","dataType"="string","required"=true,"description"="oid of attribute specification"},
     *      {"name"="friendly_name","dataType"="string","required"=true,"description"="displayable name of the attribute specification"},
     *      {"name"="maintainer","dataType"="enum","required"=true, "format"="user|manager", "description"="maintainer of the attribute"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"},
     *      {"name"="syntax","dataType"="string","required"=true,"description"="data type of connected values"},
     *      {"name"="is_multivalue","dataType"="boolean","required"=true,"format"="true|false","description"=""}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     *
     * @return null
     * 
     */
    public function postAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                               ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());

        return $this->processForm(new AttributeSpec(), $loglbl, $request, "POST");
    }

    private function processForm(AttributeSpec $as, $loglbl, Request $request, $method = "PUT") {
        $statusCode = $as->getId() == null ? 201 : 204;

        $form = $this->createForm(new AttributeSpecType(), $as, array("method" => $method));
        $form->submit($request->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            if (201 === $statusCode) {
                $this->modlog->info($loglbl . "created new attributeSpec with id=" . $as->getId());
            }
            $this->modlog->info($loglbl . "updated attributeSpec with id=" . $as->getId());
            $this->em->persist($as);
            $this->em->flush();

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_attributespec', array('id' => $as->getId()), true // absolute
                        )
                );
            }

            return $response;
        }
        $this->errorlog->error($loglbl . "Validation error");
        return View::create($form, 400);
    }

    /**
     * Delete attribute an specification<br>
     * Note: admins only!
     *
     *
     * @ApiDoc(
     *   section = "AttributeSpec",
     *   resource = false,
     *   description = "delete attribute specification",
     *   statusCodes = {
     *     204 = "Returned when attribute specification has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when attribute specification is not found"
     *   },
     *   tags = {"admins"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute specification id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     * 
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     * @param integer               $id           AttributeSpec id
     *
     * 
     */
    public function deleteAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                 ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "called with id=" . $id . " by " . $p->getFedid());

        $as = $this->eh->get('AttributeSpec', $id, $loglbl);
        $this->modlog->info($loglbl . "deleted attributeSpec with id=" . $id);
        $this->em->remove($as);
        $this->em->flush();
    }

    /**
     * get connected services of the specified attribute specification
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
     * @ApiDoc(
     *   section = "AttributeSpec",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute specification id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     * @param integer               $id           AttributeSpec id
     *
     * @return array
     */
    public function getServiceAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "called with id=" . $id . " by " . $p->getFedid());

        $as = $this->eh->get('AttributeSpec', $id, $loglbl);
        
        $sas = $this->em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findBy(array("attributeSpec" => $as), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset'));

        return $sas;
    }

}
