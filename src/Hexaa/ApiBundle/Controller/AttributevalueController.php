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


use Doctrine\Common\Collections\ArrayCollection;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Hexaa\ApiBundle\Annotations\InvokeHook;
use Hexaa\StorageBundle\Entity\AttributeValueOrganization;
use Hexaa\StorageBundle\Entity\AttributeValuePrincipal;
use Hexaa\StorageBundle\Form\AttributeValueOrganizationType;
use Hexaa\StorageBundle\Form\AttributeValuePrincipalType;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;


/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author  Soltész Balázs <solazs@sztaki.hu>
 */
class AttributevalueController extends HexaaController implements PersonalAuthenticatedController
{

    /**
     * Get attribute value (for principal) details<br>
     * Note: only admins may query values for other than themselves.
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
     *   section = "Attribute value (for principal)",
     *   resource = true,
     *   description = "get attribute value (for principal) details",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value (for principal) id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   output = "Hexaa\StorageBundle\Entity\AttributeValuePrincipal"
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     * @param integer               $id           AttributeValuePrincipal id
     *
     * @return AttributeValuePrincipal
     */
    public function getAttributevalueprincipalAction(
        Request $request,
        /** @noinspection PhpUnusedParameterInspection */
        ParamFetcherInterface $paramFetcher,
        $id = 0
    ) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $avp = $this->eh->get('AttributeValuePrincipal', $id, $loglbl);

        return $avp;
    }

    /**
     * Edit attribute value (for principal) details<br><br>
     *
     * Note: only HEXAA admins are allowed to add or edit attributes for other than themselves.
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
     * @InvokeHook({"attribute_change", "user_removed", "user_added"})
     *
     * @ApiDoc(
     *   section = "Attribute value (for principal)",
     *   resource = true,
     *   description = "edit attribute value (for principal) details",
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value (for principal) id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  parameters = {
     *      {"name"="services","dataType"="array", "required"=true, "description"="IDs of Services to give the value to. If empty, the value will be given to all services."},
     *      {"name"="value", "dataType"="string", "required"=true, "description"="assigned value"},
     *      {"name"="attribute_spec", "dataType"="integer", "required"=true, "description"="attribute specification id"},
     *      {"name"="principal", "dataType"="integer", "required"=false, "description"="ID of principal. If left blank, it will default to self."},
     *
     *  }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     * @param integer               $id           AttributeValuePrincipal id
     *
     * @return AttributeValuePrincipal
     */
    public function putAttributevalueprincipalAction(
        Request $request,
        /** @noinspection PhpUnusedParameterInspection */
        ParamFetcherInterface $paramFetcher,
        $id = 0
    ) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $avp = $this->eh->get('AttributeValuePrincipal', $id, $loglbl);

        return $this->processAVPForm($avp, $loglbl, $request, "PUT");
    }

    private function processAVPForm(AttributeValuePrincipal $avp, $loglbl, Request $request, $method = "PUT")
    {
        $statusCode = $avp->getId() == null ? 201 : 204;
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();

        if (!$request->request->has('principal') && $method !== "POST") {
            $request->request->set('principal', $p->getId());
        }

        if (!$request->request->has('principal') || $request->request->get('principal') == null) {
            $request->request->set("principal", $p->getId());
        }


        $form = $this->createForm(new AttributeValuePrincipalType(), $avp, array("method" => $method));
        $form->submit($request->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $this->em->persist($avp);
            $this->em->flush();

            if (201 === $statusCode) {
                $this->modlog->info($loglbl . "New attribute value (for principal) was created with id=" . $avp->getId());
            } else {
                $this->modlog->info($loglbl . "Attribute value (for principal) was edited with id=" . $avp->getId());
            }

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                    'get_attributevalueprincipal', array('id' => $avp->getId()), true // absolute
                )
                );
            }

            return $response;
        }

        $this->errorlog->error($loglbl . "Validation error: \n" . $this->get("serializer")->serialize($form->getErrors(false,
                true), "json"));

        return View::create($form, 400);
    }

    /**
     * Edit attribute value (for principal) details<br><br>
     *
     * Note: only HEXAA admins are allowed to add or edit attributes for other than themselves.
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
     * @InvokeHook({"attribute_change", "user_removed", "user_added"})
     *
     * @ApiDoc(
     *   section = "Attribute value (for principal)",
     *   resource = true,
     *   description = "edit attribute value (for principal) details",
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value (for principal) id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  parameters = {
     *      {"name"="services","dataType"="array", "required"=true, "description"="IDs of Services to give the value to. If empty, the value will be given to all services."},
     *      {"name"="value", "dataType"="string", "required"=true, "description"="assigned value"},
     *      {"name"="attribute_spec", "dataType"="integer", "required"=true, "description"="attribute specification id"},
     *      {"name"="principal", "dataType"="integer", "required"=false, "description"="ID of principal. If left blank, it will default to self."},
     *
     *  }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     * @param integer               $id           AttributeValuePrincipal id
     *
     * @return AttributeValuePrincipal
     */
    public function patchAttributevalueprincipalAction(
        Request $request,
        /** @noinspection PhpUnusedParameterInspection */
        ParamFetcherInterface $paramFetcher,
        $id = 0
    ) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $avp = $this->eh->get('AttributeValuePrincipal', $id, $loglbl);

        return $this->processAVPForm($avp, $loglbl, $request, "PATCH");
    }

    /**
     * Create attribute value (for principal)<br><br>
     *
     * Note: only HEXAA admins are allowed to add or edit attributes for other than themselves.
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
     *   section = "Attribute value (for principal)",
     *   description = "create attribute value (for principal)",
     *   resource = true,
     *   statusCodes = {
     *     201 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  parameters = {
     *      {"name"="services","dataType"="array", "required"=true, "description"="IDs of Services to give the value to. If empty, the value will be given to all services."},
     *      {"name"="value", "dataType"="string", "required"=true, "description"="assigned value"},
     *      {"name"="attribute_spec", "dataType"="integer", "required"=true, "description"="attribute specification id"},
     *      {"name"="principal", "dataType"="integer", "required"=false, "description"="ID of principal. If left blank, it will default to self."}
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
     */
    public function postAttributevalueprincipalAction(
        Request $request,
        /** @noinspection PhpUnusedParameterInspection */
        ParamFetcherInterface $paramFetcher
    ) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());

        $avp = new AttributeValuePrincipal();

        return $this->processAVPForm($avp, $loglbl, $request, "POST");
    }

    /**
     * Delete attribute value (for principal)<br>
     * Note: only admins may delete values for other than themselves.
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
     * @InvokeHook({"attribute_change", "user_removed"})
     *
     * @ApiDoc(
     *   section = "Attribute value (for principal)",
     *   resource = false,
     *   description = "delete attribute value (for principal)",
     *   statusCodes = {
     *     204 = "Returned when value has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     *
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param int                   $id           AVP id
     */
    public function deleteAttributevalueprincipalAction(
        Request $request,
        /** @noinspection PhpUnusedParameterInspection */
        ParamFetcherInterface $paramFetcher,
        $id = 0
    ) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $avp = $this->eh->get('AttributeValuePrincipal', $id, $loglbl);

        $this->em->remove($avp);
        $this->em->flush();

        $this->modlog->info($loglbl . "Attribute value (for principal) was deleted with id=" . $id);
    }

    /**
     * get all services linked to the specified attribute value (for principal)<br>
     * Note: only admins may query values for other than themselves.
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
     *
     * @ApiDoc(
     *   section = "Attribute value (for principal)",
     *   resource = false,
     *   description = "get all services linked to the specified attribute value (for principal)",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           AttributeValuePrincipal id
     *
     * @return array
     *
     *
     */
    public function cgetAttributevalueprincipalsServicesAction(
        Request $request,
        ParamFetcherInterface $paramFetcher,
        $id = 0
    ) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        /* @var $avp AttributeValuePrincipal */
        $avp = $this->eh->get('AttributeValuePrincipal', $id, $loglbl);

        $services = $avp->getServices();


        if ($request->query->has('limit') || $request->query->has('offset')) {
            return array(
                "item_number" => (int)count($services),
                "items"       => array_slice($services->toArray(), $paramFetcher->get('offset'),
                    $paramFetcher->get('limit'))
            );
        } else {
            return $services;
        }
    }

    /**
     * Get if the specified attribute value (for principal) will be released to a specific service.<br>
     * Note: This doesn't check consents.<br>
     * Note: only admins may query values for other than themselves.
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
     *   section = "Attribute value (for principal)",
     *   description = "get if the specified attribute value (for principal) will be released to a specific service",
     *   resource = false,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
     *      {"name"="sid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   output="Hexaa\StorageBundle\Entity\Service"
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           AttributeValuePrincipal id
     * @param int                   $sid          Service id
     * @return array
     */
    public function getAttributevalueprincipalsServiceAction(
        Request $request,
        /** @noinspection PhpUnusedParameterInspection */
        ParamFetcherInterface $paramFetcher,
        $id = 0,
        $sid = 0
    ) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " sid=" . $sid . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $sid, $loglbl);
        $avp = $this->eh->get('AttributeValuePrincipal', $id, $loglbl);

        if ($avp->hasService($s) || $avp->getServices() == new ArrayCollection()) {
            return $s;
        } else {
            return (object)null;
        }
    }

    /**
     * Add service to attribute value (for principal) <br>
     * Note: only admins may query values for other than themselves.
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
     * @InvokeHook({"attribute_change", "user_removed", "user_added"})
     *
     * @ApiDoc(
     *   section = "Attribute value (for principal)",
     *   resource = false,
     *   description = "add service to attribute value (for principal)",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
     *      {"name"="sid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     *
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           AttributeValuePrincipal id
     *
     * @param int                   $sid          Service id
     * @return null
     * @throws HttpException
     */
    public function putAttributevalueprincipalsServiceAction(
        Request $request,
        /** @noinspection PhpUnusedParameterInspection */
        ParamFetcherInterface $paramFetcher,
        $id = 0,
        $sid = 0
    ) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " sid=" . $sid . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $sid, $loglbl);
        $avp = $this->eh->get('AttributeValuePrincipal', $id, $loglbl);

        $sas = $this->em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findBy(array(
            "service"       => $s,
            "attributeSpec" => $avp->getAttributeSpec()
        ));

        $valid = false;

        if (!$sas) {
            // no such attribute at the service... maybe it's public 
            $sass = $this->em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findBy(array(
                "isPublic"      => true,
                "attributeSpec" => $avp->getAttributeSpec()
            ));
            if (!$sass) {
                // invalid -> 400 err
            } else {
                $valid = true;
            }  // ok, there is a public one
        } else {
            $valid = true;
        }

        if (!$valid) {
            $this->errorlog->error($loglbl . "Service (id=" . $sid . ") does not require this attribute (id=" . $id);
            throw new HttpException(400, "This service doesn't want this attribute.");
        }

        //stuff seems valid

        if (!$avp->hasService($s)) {
            $avp->addService($s);
            $this->em->persist($avp);
            $this->em->flush();

            $this->modlog->info($loglbl . "Release of attribute value (for principal) with id=" . $id . " to Service with id=" . $sid . " has been allowed");

            $response = new Response();
            $response->setStatusCode(201);


            $response->headers->set('Location', $this->generateUrl(
                'get_attributevalueprincipal', array('id' => $avp->getId()), true // absolute
            )
            );

            return $response;
        } else {
            $response = new Response();
            $response->setStatusCode(204);

            return $response;
        }
    }

    /**
     * Remove service from attribute value (for principal)<br>
     * Note: only admins may query values for other than themselves.
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
     * @InvokeHook({"attribute_change", "user_removed", "user_added"})
     *
     * @ApiDoc(
     *   section = "Attribute value (for principal)",
     *   resource = false,
     *   description = "remove service from attribute value (for principal)",
     *   statusCodes = {
     *     204 = "Returned on successful delete",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
     *      {"name"="sid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     *
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           AttributeValuePrincipal id
     *
     * @param int                   $sid          Service id
     */
    public function deleteAttributevalueprincipalServiceAction(
        Request $request,
        /** @noinspection PhpUnusedParameterInspection */
        ParamFetcherInterface $paramFetcher,
        $id = 0,
        $sid = 0
    ) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();

        $this->accesslog->info($loglbl . "Called with id=" . $id . " sid=" . $sid . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $sid, $loglbl);
        $avp = $this->eh->get('AttributeValuePrincipal', $id, $loglbl);

        if ($avp->hasService($s)) {
            $avp->removeService($s);

            $this->em->persist($avp);
            $this->em->flush();

            $this->modlog->info($loglbl . "Release of attribute value (for principal) with id=" . $id . " to Service with id=" . $sid . " has been set to denied");
        }
    }

    /**
     * Get attribute value (for organization) details
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
     *   section = "Attribute value (for organization)",
     *   resource = true,
     *   description = "get attribute value (for organization) details",
     *   tags = {"organization member" = "#5BA578"},
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value (for organization) id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *    },
     *    output="Hexaa\StorageBundle\Entity\AttributeValueOrganization"
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     * @param integer               $id           AttributeValueOrganization id
     *
     * @return AttributeValueOrganization
     */
    public function getAttributevalueorganizationAction(
        Request $request,
        /** @noinspection PhpUnusedParameterInspection */
        ParamFetcherInterface $paramFetcher,
        $id = 0
    ) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();

        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $aso = $this->eh->get('AttributeValueOrganization', $id, $loglbl);

        return $aso;
    }

    /**
     * Edit an attribute value (for organization)
     * Note: If services array is empty, the value will be released to all services.
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
     * @InvokeHook({"attribute_change", "user_removed", "user_added"})
     *
     * @ApiDoc(
     *   section = "Attribute value (for organization)",
     *   resource = true,
     *   description = "edit attribute value (for organization) details",
     *   tags = {"organization manager" = "#4180B4"},
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value (for organization) id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  parameters = {
     *      {"name"="value", "dataType"="string", "required"=true, "description"="assigned value"},
     *      {"name"="services", "dataType"="array", "required"=true, "description"="array of service IDs to give the value to"},
     *      {"name"="attribute_spec", "dataType"="integer", "required"=true, "description"="attribute specification id"},
     *      {"name"="organization", "dataType"="integer", "required"=true, "description"="ID of the organization"}
     *  }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     * @param integer               $id           AttributeValueOrnization id
     *
     *
     * @return View|Response
     */
    public function putAttributevalueorganizationAction(
        Request $request,
        /** @noinspection PhpUnusedParameterInspection */
        ParamFetcherInterface $paramFetcher,
        $id = 0
    ) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $avo = $this->eh->get('AttributeValueOrganization', $id, $loglbl);

        return $this->processAVOForm($avo, $loglbl, $request, "PUT");
    }

    private function processAVOForm(AttributeValueOrganization $avo, $loglbl, Request $request, $method = "PUT")
    {
        $statusCode = $avo->getId() == null ? 201 : 204;

        if (!$request->request->has('organization') && $method != "POST") {
            $request->request->set('organization', $avo->getOrganization()->getId());
        }

        $form = $this->createForm(new AttributeValueOrganizationType(), $avo, array("method" => $method));
        $form->submit($request->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $this->em->persist($avo);
            $this->em->flush();

            if (201 === $statusCode) {
                $this->modlog->info($loglbl . "New attribute value (for organization) was created with id=" . $avo->getId());
            } else {
                $this->modlog->info($loglbl . "Attribute value (for organization) was edited with id=" . $avo->getId());
            }

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                    'get_attributevalueorganization', array('id' => $avo->getId()), true // absolute
                )
                );
            }

            return $response;
        }
        $this->errorlog->error($loglbl . "Validation error: \n" . $this->get("serializer")->serialize($form->getErrors(false,
                true), "json"));

        return View::create($form, 400);
    }

    /**
     * Edit an attribute value (for organization)
     * Note: If services array is empty, the value will be released to all services.
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
     * @InvokeHook({"attribute_change", "user_removed", "user_added"})
     *
     * @ApiDoc(
     *   section = "Attribute value (for organization)",
     *   resource = true,
     *   description = "edit attribute value (for organization) details",
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value (for organization) id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="value", "dataType"="string", "required"=true, "description"="assigned value"},
     *      {"name"="services", "dataType"="array", "required"=true, "description"="array of service IDs to give the value to"},
     *      {"name"="attribute_spec", "dataType"="integer", "required"=true, "description"="attribute specification id"},
     *      {"name"="organization", "dataType"="integer", "required"=true, "description"="ID of the organization"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     * @param integer               $id           AttributeValueOrnization id
     *
     * @return AttributeValuePrincipal
     */
    public function patchAttributevalueorganizationAction(
        Request $request,
        /** @noinspection PhpUnusedParameterInspection */
        ParamFetcherInterface $paramFetcher,
        $id = 0
    ) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $avo = $this->eh->get('AttributeValueOrganization', $id, $loglbl);

        return $this->processAVOForm($avo, $loglbl, $request, "PATCH");
    }

    /**
     * create attribute value (for organization) details
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
     * @InvokeHook({"attribute_change", "user_added"})
     *
     * @ApiDoc(
     *   section = "Attribute value (for organization)",
     *   resource = true,
     *   statusCodes = {
     *     201 = "Returned when successful",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="value", "dataType"="string", "required"=true, "description"="assigned value"},
     *      {"name"="services", "dataType"="array", "required"=true, "description"="array of service IDs to give the value to"},
     *      {"name"="attribute_spec", "dataType"="integer", "required"=true, "description"="attribute specification id"},
     *      {"name"="organization", "dataType"="integer", "required"=true, "description"="ID of the organization"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     *
     *
     * @return View|Response
     */
    public function postAttributevalueorganizationAction(
        Request $request,
        /** @noinspection PhpUnusedParameterInspection */
        ParamFetcherInterface $paramFetcher
    ) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());

        if ($request->request->has('organization') && $request->request->get('organization') != null) {
            $o = $this->em->getRepository('HexaaStorageBundle:Organization')->find($request->request->get('organization'));

            if ($request->getMethod() == "POST" && !$o) {
                $this->errorlog->error($loglbl . "The requested Organization with id=" . $request->request->get('organization') . " was not found");
                throw new HttpException(404, "Organization not found.");
            }
        }
        $avo = new AttributeValueOrganization();
        $avo->setOrganization($o);

        return $this->processAVOForm($avo, $loglbl, $request, "POST");
    }

    /**
     * delete attribute value (for organization)
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
     * @InvokeHook({"attribute_change", "user_removed"})
     *
     * @ApiDoc(
     *   section = "Attribute value (for organization)",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when service has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           AttributeValueOrnization id
     *
     *
     */
    public function deleteAttributevalueorganizationAction(
        Request $request,
        /** @noinspection PhpUnusedParameterInspection */
        ParamFetcherInterface $paramFetcher,
        $id = 0
    ) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $avo = $this->eh->get('AttributeValueOrganization', $id, $loglbl);

        $this->em->remove($avo);
        $this->em->flush();

        $this->modlog->info($loglbl . "Attribute value (for organization) was removed with id=" . $id);
    }

    /**
     * get all services linked to the specified attribute value (for organization)
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
     *
     * @ApiDoc(
     *   section = "Attribute value (for organization)",
     *   resource = false,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   tags = {"organization member" = "#5BA578"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           AttributeValueOrnization id
     *
     * @return array
     *
     *
     */
    public function cgetAttributevalueorganizationsServicesAction(
        Request $request,
        ParamFetcherInterface $paramFetcher,
        $id = 0
    ) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        /* @var $avo AttributeValueOrganization */
        $avo = $this->eh->get('AttributeValueOrganization', $id, $loglbl);

        $services = $avo->getServices();

        if ($request->query->has('limit') || $request->query->has('offset')) {
            return array(
                "item_number" => (int)count($services),
                "items"       => array_slice($services->toArray(), $paramFetcher->get('offset'),
                    $paramFetcher->get('limit'))
            );
        } else {
            return $services;
        }
    }


    /**
     * get if the specified attribute value (for organization) will be released to a specific service
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
     *   section = "Attribute value (for organization)",
     *   resource = false,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   tags = {"organization member" = "#5BA578"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
     *      {"name"="sid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   output="Hexaa\StorageBundle\Entity\Service"
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           AttributeValueOrnization id
     * @param int                   $sid          Service id
     * @return object
     */
    public function getAttributevalueorganizationServiceAction(
        Request $request,
        /** @noinspection PhpUnusedParameterInspection */
        ParamFetcherInterface $paramFetcher,
        $id = 0,
        $sid = 0
    ) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " sid=" . $sid . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $sid, $loglbl);
        $avo = $this->eh->get('AttributeValueOrganization', $id, $loglbl);

        if ($avo->hasService($s) || $avo->getServices() == new ArrayCollection()) {
            return $s;
        } else {
            return (object)null;
        }
    }

    /**
     * add service to attribute value (for organization)
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
     * @InvokeHook({"attribute_change", "user_removed", "user_added"})
     *
     * @ApiDoc(
     *   section = "Attribute value (for organization)",
     *   resource = false,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
     *      {"name"="sid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           AttributeValueOrnization id
     *
     *
     * @param int                   $sid          Service id
     * @return Response|void
     */
    public function putAttributevalueorganizationServiceAction(
        Request $request,
        /** @noinspection PhpUnusedParameterInspection */
        ParamFetcherInterface $paramFetcher,
        $id = 0,
        $sid = 0
    ) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " sid=" . $sid . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $sid, $loglbl);
        $avo = $this->eh->get('AttributeValueOrganization', $id, $loglbl);

        $sas = $this->em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findBy(array(
            "service"       => $s,
            "attributeSpec" => $avo->getAttributeSpec()
        ));

        $valid = false;

        if (!$sas) {
            // no such attribute at the service... maybe it's public 
            $sass = $this->em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findBy(array(
                "isPublic"      => true,
                "attributeSpec" => $avo->getAttributeSpec()
            ));
            if (!$sass) {
                // invalid -> 400 err
            } else {
                $valid = true;
            }  // ok, there is a public one
        } else {
            $valid = true;
        }

        if (!$valid) {
            $this->errorlog->error($loglbl . "Service (id=" . $sid . ") does not require this attribute (id=" . $id);
            throw new HttpException(400, "This service doesn't want this attribute.");
        }

        // stuff seems valid

        if (!$avo->hasService($s)) {
            $avo->addService($s);
            $this->em->persist($avo);
            $this->em->flush();

            $this->modlog->info($loglbl . "Release of attribute value (for organization) with id=" . $id . " to Service with id=" . $sid . " has been allowed");

            $response = new Response();
            $response->setStatusCode(201);


            $response->headers->set('Location', $this->generateUrl(
                'get_attributevalueorganization', array('id' => $avo->getId()), true // absolute
            )
            );

            return $response;
        } else {
            $response = new Response();
            $response->setStatusCode(204);

            return $response;
        }
    }

    /**
     * remove service from attribute value (for organization)
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
     * @InvokeHook({"attribute_change", "user_removed", "user_added"})
     *
     * @ApiDoc(
     *   section = "Attribute value (for organization)",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned on successful delete",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
     *      {"name"="sid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           AttributeValueOrnization id
     * @param integer               $sid          Service id
     *
     *
     */
    public function deleteAttributevalueorganizationServiceAction(
        Request $request,
        /** @noinspection PhpUnusedParameterInspection */
        ParamFetcherInterface $paramFetcher,
        $id = 0,
        $sid = 0
    ) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " sid=" . $sid . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $sid, $loglbl);
        $avo = $this->eh->get('AttributeValueOrganization', $id, $loglbl);

        if ($avo->hasService($s)) {
            $avo->removeService($s);
            $this->em->persist($avo);
            $this->em->flush();

            $this->modlog->info($loglbl . "Release of attribute value (for organization) with id=" . $id . " to Service with id=" . $sid . " has been set to denied");
        }
    }

}
