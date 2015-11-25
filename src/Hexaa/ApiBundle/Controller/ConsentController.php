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
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\View;
use Hexaa\ApiBundle\Annotations\InvokeHook;
use Hexaa\StorageBundle\Entity\Consent;
use Hexaa\StorageBundle\Entity\News;
use Hexaa\StorageBundle\Form\ConsentType;
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
class ConsentController extends HexaaController implements ClassResourceInterface, PersonalAuthenticatedController {

    /**
     * get consents of the current user
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
     *   section = "Consents",
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
     *   output="array<Hexaa\StorageBundle\Entity\Consent>"
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
    public function cgetAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());

        $cs = $this->em->getRepository('HexaaStorageBundle:Consent')->findBy(array("principal" => $p), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset'));

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $this->em->createQueryBuilder()
                ->select("COUNT(c.id)")
                ->from("HexaaStorageBundle:Consent", 'c')
                ->where("c.principal = :p")
                ->setParameter(":p", $p)
                ->getQuery()
                ->getSingleScalarResult();

            return array("item_number" => (int)$itemNumber, "items" => $cs);
        } else {
            return $cs;
        }
    }

    /**
     * get a consent of the current user
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
     *   section = "Consents",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="consent id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   output="Hexaa\StorageBundle\Entity\Consent"
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Consent id
     *
     * @return Consent
     */
    public function getAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                              ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $c = $this->eh->get('Consent', $id, $loglbl);

        return $c;
    }

    /**
     * get consent of the current user for a specific service
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
     *   section = "Consents",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="sid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   output="Hexaa\StorageBundle\Entity\Consent"
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $sid          Service id
     *
     * @return Consent
     */
    public function getServiceAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                     ParamFetcherInterface $paramFetcher, $sid = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $sid . " by " . $p->getFedid());

        $s = $this->eh->get('Service', $sid, $loglbl);
        $c = $this->em->getRepository('HexaaStorageBundle:Consent')->findOneBy(array(
            "principal" => $p,
            "service"   => $s
        ));
        if (!$c) {
            $c = new Consent();
            $c->setPrincipal($p);
            $c->setService($s);
            $this->em->persist($c);
            $this->em->flush();
        }

        return $c;
    }

    /**
     * Create a new consent.<br>
     * Note: Consents are idetified by principal-service pairs, which must be unique. If the requested new consent already exists, error 400 will be returned.
     *
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
     * @InvokeHook({"attribute_change", "user_removed", "user_added"})
     *
     * @ApiDoc(
     *   section = "Consents",
     *   resource = false,
     *   description = "create new consent",
     *   statusCodes = {
     *     201 = "Returned when consent has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     *   requirements = {
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *   {"name"="enable_entitlements", "dataType"="boolean", "required"=true, "description"="sets the release consent of entitlements"},
     *   {"name"="enabled_attribute_specs", "dataType"="array", "required"=true, "description"="array of the releasable attribute specifications"},
     *   {"name"="principal", "dataType"="integer", "format"="\d+", "required"=false, "description"="principal id, defaults to self if left blank"},
     *   {"name"="service", "dataType"="integer", "format"="\d+", "required"=true, "description"="service ID"},
     *  }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     *
     * @return View|Response
     */
    public function postAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                               ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());

        if ($request->request->has("service") && $request->request->get('service') != null) {
            $s = $this->em->getRepository('HexaaStorageBundle:Service')->find($request->request->get('service'));
            if (!$s) {
                // Oops, no such service... let the form handle it!
            } else {
                $c = $this->em->getRepository('HexaaStorageBundle:Consent')->findBy(array(
                    "principal" => $p,
                    "service"   => $s
                ));
                $c = array_filter($c);
                if (count($c) > 0) {
                    $this->errorlog->error($loglbl . 'Duplicate consents are not allowed... You may want to use PUT instead');
                    throw new HttpException(400, 'A consent already exists with this principal and service, please use the PUT method!');
                }
            }
        }

        return $this->processForm(new Consent(), $loglbl, $request, "POST");
    }

    private function processForm(Consent $c, $loglbl, Request $request, $method = "PUT") {
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $statusCode = $c->getId() == null ? 201 : 204;

        if (!$request->request->has('principal') || $request->request->get('principal') == null)
            $request->request->set("principal", $p->getId());

        $form = $this->createForm(new ConsentType(), $c, array("method" => $method));
        $form->submit($request->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            if (201 === $statusCode) {

            }
            $this->em->persist($c);

            //Create News object to notify the user
            $n = new News();
            $n->setPrincipal($p);
            $n->setTitle("You consented to the release of your data");
            $releaseable = "";
            foreach($c->getEnabledAttributeSpecs() as $as) {
                $releaseable = $releaseable . $as->getName() . ", ";
            }
            if ($c->getEnableEntitlements()) {
                $releaseable = $releaseable . "eduPersonEntitlement";
            } else {
                $releaseable = substr($releaseable, 0, strlen($releaseable) - 2);
            }
            $n->setMessage("You gave HEXAA permission to release the following attributes to service " . $c->getService()->getName() . ": " . $releaseable);
            $n->setTag("organization_manager");
            $this->em->persist($n);
            $this->em->flush();
            $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());

            if (201 === $statusCode) {
                $this->modlog->info($loglbl . "New Consent created with id=" . $c->getId());
            } else {
                $this->modlog->info($loglbl . "Consent edited with id=" . $c->getId());
            }

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                    'get_consent', array('id' => $c->getId()), true // absolute
                )
                );
            }

            return $response;
        }
        $this->errorlog->error($loglbl . "Validation error: \n" . $this->get("serializer")->serialize($form->getErrors(false, true), "json"));

        return View::create($form, 400);
    }

    /**
     * edit consent
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
     * @InvokeHook({"attribute_change", "user_removed", "user_added"})
     *
     * @ApiDoc(
     *   section = "Consents",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when consent has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *   {"name"="enable_entitlements", "dataType"="boolean", "required"=true, "description"="sets the release consent of entitlements"},
     *   {"name"="enabled_attribute_specs", "dataType"="array", "required"=true, "description"="array of the releasable attribute specifications"},
     *   {"name"="principal", "dataType"="integer", "format"="\d+", "required"=false, "description"="principal id, defaults to self if left blank"},
     *   {"name"="service", "dataType"="integer", "format"="\d+", "required"=true, "description"="service ID"},
     *  }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Consent id
     *
     *
     * @return View|Response
     */
    public function putAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                              ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());


        $c = $this->eh->get('Consent', $id, $loglbl);

        return $this->processForm($c, $loglbl, $request, "PUT");
    }

    /**
     * edit consent
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
     * @InvokeHook({"attribute_change", "user_removed", "user_added"})
     *
     * @ApiDoc(
     *   section = "Consents",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when consent has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *   {"name"="enable_entitlements", "dataType"="boolean", "required"=true, "description"="sets the release consent of entitlements"},
     *   {"name"="enabled_attribute_specs", "dataType"="array", "required"=true, "description"="array of the releasable attribute specifications"},
     *   {"name"="principal", "dataType"="integer", "format"="\d+", "required"=false, "description"="principal id, defaults to self if left blank"},
     *   {"name"="service", "dataType"="integer", "format"="\d+", "required"=true, "description"="service ID"},
     *  }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Consent id
     *
     *
     * @return View|Response
     */
    public function patchAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $c = $this->eh->get('Consent', $id, $loglbl);

        return $this->processForm($c, $loglbl, $request, "PATCH");
    }

}
