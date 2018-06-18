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
use Hexaa\StorageBundle\Entity\Principal;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;


/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author  Soltész Balázs <solazs@sztaki.hu>
 */
class GlobalController extends HexaaController implements PersonalAuthenticatedController
{

    /**
     * List all existing and enabled service entityIDs from HEXAA config
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", default=0, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, nullable=true, description="How many items to return.")
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
    public function cgetEntityidsAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called by ".$p->getFedid());

        $entityIds = array();
        if ($this->container->hasParameter('hexaa_service_entityids')) {
            $entityIds = $this->container->getParameter('hexaa_service_entityids');
        }

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $retarr = array_slice(
              $entityIds,
              $paramFetcher->get('offset'),
              $paramFetcher->get('limit')
            );

            return array(
              "item_number" => (int)count($entityIds),
              "items"       => $retarr,
            );
        } else {
            return $entityIds;
        }
    }

    /**
     * List all tags
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", default=0, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, nullable=true, description="How many items to return.")
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
     *   section = "Other",
     *   description = "list tags",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
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
    public function cgetTagsAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called by ".$p->getFedid());

        $tags = $this->em->getRepository('HexaaStorageBundle:Tag')->findBy(
          array(),
          array("name" => "ASC"),
          $paramFetcher->get('limit'),
          $paramFetcher->get("offset")
        );

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $this->em->createQueryBuilder()
              ->select("COUNT(t.name)")
              ->from('HexaaStorageBundle:Tag', 't')
              ->getQuery()
              ->getSingleScalarResult();

            return array("item_number" => (int)$itemNumber, "items" => $tags);
        } else {
            return $tags;
        }
    }

    /**
     * List all scoped key names
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", default=0, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, nullable=true, description="How many items to return.")
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
     *   section = "Other",
     *   description = "list scoped key names",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   tags={"admins"}
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
    public function cgetScopedkeysAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called by ".$p->getFedid());

        $scopedKeyNames = array_values($this->container->getParameter("hexaa_master_secrets"));

        if ($request->query->has('limit') || $request->query->has('offset')) {
            return array(
              "item_number" => (int)count($scopedKeyNames),
              "items"       => array_slice($scopedKeyNames, $paramFetcher->get('offset'), $paramFetcher->get('limit')),
            );
        } else {
            return $scopedKeyNames;
        }
    }

    /**
     * Send text message to multiple HEXAA users by e-mail.
     * The backend uses the stored contact e-mail of users.
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
     * @Annotations\RequestParam(
     *   name="organization",
     *   requirements="\d+",
     *   nullable=true,
     *   default=null,
     *   description="organization ID")
     * @Annotations\RequestParam(
     *   name="role",
     *   requirements="\d+",
     *   nullable=true,
     *   default=null,
     *   description="role ID")
     * @Annotations\RequestParam(
     *   name="service",
     *   requirements="\d+",
     *   nullable=true,
     *   default=null,
     *   description="service ID")
     * @Annotations\RequestParam(
     *   name="target",
     *   requirements="^(user|manager|admin)",
     *   description="target user group to send message to")
     * @Annotations\RequestParam(
     *   name="subject",
     *   nullable=false,
     *   description="e-mail subject",
     * )
     * @Annotations\RequestParam(
     *   name="message",
     *   nullable=false,
     *   description="Message body (plain text)",
     * )
     *
     * @ApiDoc(
     *   section = "Other",
     *   resource = true,
     *   description="send mass message",
     *   statusCodes = {
     *     201 = "Returned when successfully created new link",
     *     204 = "Returned when successfully modified link",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     *   requirements ={
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher
     *
     * @return null
     */
    public function putMessageAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /* @var $p Principal */
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called by ".$p->getFedid());

        $form = $this->createForm('message');
        $form->submit($request->request->all(), true);

        if ($form->isValid()) {
            if ($p->getDisplayName() != null) {
                $from = array($p->getEmail() => $p->getDisplayName());
            } else {
                $from = array($p->getEmail());
            }
            $data = $form->getData();
            switch ($data['target']) {
                case "admin":
                    $targets = $this->em->createQueryBuilder()
                      ->select("p")
                      ->from("HexaaStorageBundle:Principal", "p")
                      ->where("p.fedid IN (:admins)")
                      ->setParameter(":admins", $this->container->getParameter('hexaa_admins'))
                      ->getQuery()
                      ->getResult();
                    break;
                case "manager":
                    if (isset($data['organization']) && $data['organization'] != null) {
                        $o = $this->em->getRepository('HexaaStorageBundle:Organization')->find($data['organization']);
                        $targets = $o->getManagers();
                    } elseif (isset($data['service']) && $data['service'] != null) {
                        $s = $this->em->getRepository('HexaaStorageBundle:Service')->find($data['service']);
                        $targets = $s->getManagers();
                    } else {
                        $targets = array();
                    }
                    break;
                case "user":
                    if (isset($data['organization']) && $data['organization'] != null) {
                        $o = $this->em->getRepository('HexaaStorageBundle:Organization')->find($data['organization']);
                        if (isset($data['role']) && $data['role'] != null) {
                            $r = $this->em->getRepository('HexaaStorageBundle:Role')->find($data['role']);
                            $targets = array();
                            foreach ($r->getPrincipals() as $principal) {
                                $targets[] = $principal;
                            }
                        } else {
                            $targets = $o->getPrincipals();
                        }
                    } else {
                        $targets = array();
                    }
                    break;
                default:
                    $targets = array();
            }

            /* @var $target Principal */
            foreach ($targets as $target) {
                if ($target->getDisplayName() != null) {
                    $to = array($target->getEmail() => $target->getDisplayName());
                } else {
                    $to = $target->getEmail();
                }
                $this->sendEmail($loglbl, $from, $to, $data['subject'], $data['message']);

            }

            return null;
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

    private function sendEmail($loglbl, $from, $to, $subject, $message)
    {
        $maillog = $this->get('monolog.logger.email');

        $mail = \Swift_Message::newInstance()
          ->setSubject('[hexaa] '.$subject)
          ->setFrom($from)
          ->setTo($to)
          ->setBody($message, "text/plain");

        $this->get('mailer')->send($mail);
        $maillog->info($loglbl."E-mail sent to ".var_export($to, true)." with subject: ".$subject);


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
    public function getPropertiesAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called by ".$p->getFedid());

        return array(
          "version"                       => "0.32.0+nohook",
          "entitlement_base"              => $this->container->getParameter("hexaa_entitlement_uri_prefix"),
          "public_attribute_spec_enabled" => $this->container->getParameter("hexaa_public_attribute_spec_enabled"),
        );
    }

}
