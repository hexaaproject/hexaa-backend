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
use Hexaa\ApiBundle\Annotations\HookHint;
use Hexaa\ApiBundle\Annotations\InvokeHook;
use Hexaa\StorageBundle\Entity\Entitlement;
use Hexaa\StorageBundle\Entity\Principal;
use Hexaa\StorageBundle\Form\EntitlementType;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author  Soltész Balázs <solazs@sztaki.hu>
 */
class EntitlementController extends HexaaController implements PersonalAuthenticatedController
{

    /**
     * create new entitlement
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
     *   section = "Entitlement",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when entitlement has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirement = {
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="uri","dataType"="string","required"=true,"description"="URI of entitlement"},
     *      {"name"="name","dataType"="string","required"=true,"description"="displayable name of the entitlement"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement
     * @param integer               $id           Service id
     *
     * @return null
     *
     */
    public function postServiceEntitlementAction(
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

        $e = new Entitlement();
        $e->setService($s);

        return $this->processForm($e, $loglbl, $request, "POST");
    }

    private function processForm(Entitlement $e, $loglbl, Request $request, $method = "PUT")
    {
        $statusCode = $e->getId() == null ? 201 : 204;

        $form = $this->createForm(new EntitlementType(), $e, array("method" => $method));
        $form->submit($request->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $this->em->persist($e);
            $this->em->flush();
            if (201 === $statusCode) {
                $this->modlog->info($loglbl."New Entitlement has been created with id=".$e->getId());
            } else {
                $this->modlog->info($loglbl."Entitlement has been edited with id=".$e->getId());
            }

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set(
                  'Location',
                  $this->generateUrl(
                    'get_entitlement',
                    array('id' => $e->getId()),
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
     * get entitlement details
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
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Entitlement id
     *
     * @return Entitlement
     */
    public function getEntitlementAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        $e = $this->eh->get('Entitlement', $id, $loglbl);

        return $e;
    }

    /**
     * edit entitlement preferences
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
     *     types={"attribute_change"},
     *     entity="Entitlement",
     *     id="id",
     *     source="attributes"
     *     )
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
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Entitlement id
     *
     *
     * @return View|Response
     */
    public function putEntitlementAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        $e = $this->eh->get('Entitlement', $id, $loglbl);

        return $this->processForm($e, $loglbl, $request, "PUT");
    }

    /**
     * edit entitlement preferences
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
     *     types={"attribute_change"},
     *     entity="Entitlement",
     *     id="id",
     *     source="attributes"
     *     )
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
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Entitlement id
     *
     *
     * @return View|Response
     */
    public function patchEntitlementAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        $e = $this->eh->get('Entitlement', $id, $loglbl);

        return $this->processForm($e, $loglbl, $request, "PATCH");
    }

    /**
     * delete entitlement
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
     *     entity="Entitlement",
     *     id="id",
     *     source="attributes"
     *     )
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
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Entitlement id
     *
     *
     */
    public function deleteEntitlementAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        $e = $this->eh->get('Entitlement', $id, $loglbl);

        $this->em->remove($e);
        $this->em->flush();
        $this->modlog->info($loglbl."Entitlement has been deleted with id=".$id);
    }

}
