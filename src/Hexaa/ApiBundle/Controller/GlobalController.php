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


use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;


/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class GlobalController extends HexaaController implements PersonalAuthenticatedController{
    
    /**
     * List all existing and enabled service entityIDs from HEXAA config
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
     * @ApiDoc(
     *   section = "Other",
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
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());

        $retarr = array_slice($this->container->getParameter('hexaa_service_entityids'), $paramFetcher->get('offset'), $paramFetcher->get('limit'));
        return $retarr;
    }    

    /**
     * get HEXAA backend properties
     *
     *
     * @ApiDoc(
     *   section = "Other",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
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
    public function getPropertiesAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                        ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());
        
        return array("version" => "0.19.3", "entitlement_base" => $this->container->getParameter("hexaa_entitlement_uri_prefix"));
    }
    
}
