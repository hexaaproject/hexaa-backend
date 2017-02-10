<?php

/*
 * Copyright 2015 MTA-SZTAKI.
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
use Hexaa\StorageBundle\Entity\Hook;
use Hexaa\StorageBundle\Entity\Principal;
use Hexaa\StorageBundle\Form\HookType;
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
class HookController extends HexaaController implements PersonalAuthenticatedController
{

    /**
     * create new hook
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
     *   section = "Hook",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when hook has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when hook is not found"
     *   },
     *   tags = {"service manager" = "#4180B4", "organization manager" = "#4180B4"},
     *   requirement = {
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="url","dataType"="string","required"=true,"description"="URL of hook"},
     *      {"name"="name","dataType"="string","required"=true,"description"="displayable name of the hook"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"},
     *      {"name"="type","dataType"="enum","required"=true,"description"="action type"},
     *      {"name"="organization","dataType"="integer","required"=false,"description"="organization id"},
     *      {"name"="service","dataType"="integer","required"=false,"description"="service id"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return null
     *
     */
    public function postHookAction(
        Request $request,
        /** @noinspection PhpUnusedParameterInspection */
        ParamFetcherInterface $paramFetcher
    ) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());

        $h = new Hook();

        return $this->processForm($h, $loglbl, $request, "POST");
    }

    private function processForm(Hook $h, $loglbl, Request $request, $method = "PUT")
    {
        $statusCode = $h->getId() == null ? 201 : 204;

        $form = $this->createForm(new HookType(), $h, array("method" => $method));
        $form->submit($request->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $this->em->persist($h);
            $this->em->flush();
            if (201 === $statusCode) {
                $this->modlog->info($loglbl . "New Hook has been created with id=" . $h->getId());
            } else {
                $this->modlog->info($loglbl . "Hook has been edited with id=" . $h->getId());
            }

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                  'get_hook',
                  array('id' => $h->getId()),
                  UrlGeneratorInterface::ABSOLUTE_URL // absolute
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
     * get hook details
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
     *   section = "Hook",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when hook is not found"
     *   },
     *   tags = {"service manager" = "#4180B4", "organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="hook id"}
     *   }
     * )
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           hook id
     *
     * @return Hook
     */
    public function getHookAction(
        Request $request,
        /** @noinspection PhpUnusedParameterInspection */
        ParamFetcherInterface $paramFetcher,
        $id = 0
    ) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $h = $this->eh->get('Hook', $id, $loglbl);

        return $h;
    }

    /**
     * edit hook preferences
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
     *   section = "Hook",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when hook has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when hook is not found"
     *   },
     *   tags = {"service manager" = "#4180B4", "organization manager" = "#4180B4"},
     *   requirement = {
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="hook id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="url","dataType"="string","required"=true,"description"="URL of hook"},
     *      {"name"="name","dataType"="string","required"=true,"description"="displayable name of the hook"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"},
     *      {"name"="type","dataType"="enum","required"=true,"description"="action type"},
     *      {"name"="organization","dataType"="integer","required"=false,"description"="organization id"},
     *      {"name"="service","dataType"="integer","required"=false,"description"="service id"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           hook id
     *
     *
     * @return View|Response
     */
    public function putHookAction(
        Request $request,
        /** @noinspection PhpUnusedParameterInspection */
        ParamFetcherInterface $paramFetcher,
        $id = 0
    ) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $h = $this->eh->get('Hook', $id, $loglbl);

        return $this->processForm($h, $loglbl, $request, "PUT");
    }

    /**
     * edit hook preferences
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
     *   section = "Hook",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when hook has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when hook is not found"
     *   },
     *   tags = {"service manager" = "#4180B4", "organization manager" = "#4180B4"},
     *   requirement = {
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="hook id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="url","dataType"="string","required"=true,"description"="URL of hook"},
     *      {"name"="name","dataType"="string","required"=true,"description"="displayable name of the hook"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"},
     *      {"name"="type","dataType"="enum","required"=true,"description"="action type"},
     *      {"name"="organization","dataType"="integer","required"=false,"description"="organization id"},
     *      {"name"="service","dataType"="integer","required"=false,"description"="service id"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           hook id
     *
     *
     * @return View|Response
     */
    public function patchHookAction(
        Request $request,
        /** @noinspection PhpUnusedParameterInspection */
        ParamFetcherInterface $paramFetcher,
        $id = 0
    ) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $h = $this->eh->get('Hook', $id, $loglbl);

        return $this->processForm($h, $loglbl, $request, "PATCH");
    }

    /**
     * delete hook
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
     *   section = "Hook",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when hook has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when hook is not found"
     *   },
     *   tags = {"service manager" = "#4180B4", "organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="hook id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Hook id
     *
     *
     */
    public function deleteHookAction(
        Request $request,
        /** @noinspection PhpUnusedParameterInspection */
        ParamFetcherInterface $paramFetcher,
        $id = 0
    ) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $e = $this->eh->get('Hook', $id, $loglbl);
        $this->em->remove($e);
        $this->em->flush();
        $this->modlog->info($loglbl . "Hook has been deleted with id=" . $id);
    }

}
