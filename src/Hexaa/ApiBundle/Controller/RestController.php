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

use Symfony\Component\HttpKernel\Exception\HttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\RouteRedirectView;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Hexaa\ApiBundle\Validator\Constraints\ValidEntityid;
use Hexaa\StorageBundle\Entity\Principal;
use Hexaa\StorageBundle\Entity\News;
use Hexaa\StorageBundle\Entity\Consent;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use \Hexaa\StorageBundle\Entity\PersonalToken;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class RestController extends FOSRestController {

    /**
     * <p>
     * Returns a user token to access HEXAA API with.
     * </p>
     * <p>
     * This API call uses master secret authentication<br />
     * To get your token you need to provide a one time api key and a federal ID.<br />
     * The API key is created by the following code:</p>
     * 
     * <p>
     * $time = new \DateTime();<br />
     * date_timezone_set($time, new \DateTimeZone('UTC'));<br />
     * $stamp = $time->format('Y-m-d H:i');<br />
     * $apiKey = hash('sha256', $config->getValue('hexaa_master_secret').$stamp);<br />
     * You can obtain the master secret from the HEXAA admin.
     * </p><p>
     * NOTE: The mail and display name parameters are processed only when there is no such data in the database about the user. Otherwise these values are ignored.
     * </p>
     *
     * @ApiDoc(
     *   description = "get a token for the API",
     *   section = "Other",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     400 = "Returned on bad request",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     *   tags = {"master key auth" = "#BF73E2"},
     *   requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="fedid", "dataType"="string", "required"=true, "description"="Federal ID of principal"},
     *      {"name"="email", "dataType"="string", "required"=false, "description"="Contact e-mail of principal"},
     *      {"name"="display_name", "dataType"="string", "required"=false, "description"="Displayable name of principal"},
     *      {"name"="apikey", "dataType"="string", "required"=true, "description"="API key generated from master secret"}
     *   }
     * )
     *
     * 
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return String
     */
    public function postTokenAction(Request $request, ParamFetcherInterface $paramFetcher) {

        // Loggers & label
        static $loglbl = "[postToken], ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $loginlog = $this->get('monolog.logger.login');
        $masterkey = $this->get('security.context')->getToken()->getUser()->getUserName();
        $em = $this->getDoctrine()->getManager();

        // TODO Call login hook here

        /*
         * TODO implement ip filter
         *  
          $ip = $request->getClientIp();
          if (!in_array($ip, $this->container->getParameter('hexaa_get_token_ips'))){
          throw new HttpException(403, 'Forbidden');
          return ;
          } */

        if (!$request->request->has('fedid')) {
            $errorlog->error($loglbl . "no fedid found");
            $accesslog->error($loglbl . "called without fedid");
            throw new HttpException(400, 'no fedid found');
            return;
        }



        $fedid = urldecode($request->request->get('fedid'));
        $accesslog->info($loglbl . "call with fedid=" . $fedid);

         
        $p = $em->getRepository('HexaaStorageBundle:Principal')
                ->findOneByFedid($fedid);
        if (!$p) {
            $p = new Principal();
            $p->setFedid($fedid);
            $modlog->info($loglbl . "new principal created with fedid=" . $fedid);
        }

        if ($p->getEmail() == null) {
            if ($request->request->has('email')) {

                // Validate e-mail
                $email = $request->request->get('email');
                $emailConstraint = new EmailConstraint();
                $errors = $this->get('validator')->validateValue(
                        $email, $emailConstraint
                );

                if (strlen($errors) > 2) {
                    $errorlog->error($loglbl . $errors);
                    throw new HttpException(400, $errors);
                } else {

                    $modlog->info($loglbl . "principal's email has been set to email=" . $request->request->get('email') . " with fedid=" . $fedid);
                    $p->setEmail($email);
                    $em->persist($p);
                    $em->flush();
                }
            } else {
                $errorlog->error($loglbl . "no mail found, but user has no mail, yet");
                throw new HttpException(400, 'no mail found');
            }
        }

        if ($request->request->has('display_name') && ($p->getDisplayName() == null)) {
            $modlog->info($loglbl . "principal's display name has been set to display_name=" . $request->request->get('display_name') . " with fedid=" . $fedid);
            $p->setDisplayName($request->request->get('display_name'));
            $em->persist($p);
            $em->flush();
        }

        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $token = $p->getToken();

        if (!$token) {
            $p->setToken(new PersonalToken($p->getFedid(), $masterkey));
            $em->persist($p);
            $em->flush();
            $modlog->info($loglbl . "generated new token of masterkey " . $p->getToken()->getMasterkey() . " for principal with fedid=" . $fedid);
        } else {
            if ($date > $token->getTokenExpire()) {
                $em->remove($token);
                $p->setToken(new PersonalToken($p->getFedid(), $masterkey));
                $em->persist($p);
                $em->flush();
                $modlog->info($loglbl . "generated new token of masterkey " . $p->getToken()->getMasterkey() . " for principal with fedid=" . $fedid);
            }
        }
        $loginlog->info($loglbl . "served token of masterkey " . $p->getToken()->getMasterkey() . " for principal with fedid=" . $fedid);
        return $p->getToken();
    }

    /**
     * <p>
     * Returns an associative array containing all attributes and entitlements.<br />
     * Used mainly by simplesamlphp to get attributes.
     * </p>
     * <p>
     * This API call uses master secret authentication<br />
     * To get your token you need to provide a one time api key and a federal ID.<br />
     * The API key is created by the following code:</p>
     * 
     * <p>
     * $time = new \DateTime();<br />
     * date_timezone_set($time, new \DateTimeZone('UTC'));<br />
     * $stamp = $time->format('Y-m-d H:i');<br />
     * $apiKey = hash('sha256', $config->getValue('hexaa_master_secret').$stamp);</p>
     * 
     * You can obtain the master secret from the HEXAA admin.
     * 
     *
     *
     * @ApiDoc(
     *   description = "get all attributes (including entitlements) for a principal per service",
     *   section = "Other",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     400 = "Returned on bad request",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     *   tags = {"master key auth" = "#BF73E2"},
     *   requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="fedid", "dataType"="string", "required"=true, "description"="Federal ID of principal"},
     *      {"name"="entityid", "dataType"="string", "required"=true, "description"="Entityid of a service"},
     *      {"name"="apikey", "dataType"="string", "required"=true, "description"="API key generated from master secret"}
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
    public function postAttributesAction(Request $request) {

        // Loggers & label
        static $loglbl = "[attribute release], ";
        $accesslog = $this->get('monolog.logger.access');
        $modlog = $this->get('monolog.logger.modification');
        $errorlog = $this->get('monolog.logger.error');
        $releaselog = $this->get('monolog.logger.release');

        if (!$request->request->has('fedid') && !$request->request->has("entityid")) {
            $accesslog->error($loglbl . "no fedid and entityid found");
            throw new HttpException(400, 'no fedid and entityid found');
            return;
        }

        if (!$request->request->has('fedid')) {
            $accesslog->error($loglbl . 'no fedid found, entityid="' . urldecode($request->request->get('entityid')) . '"');
            throw new HttpException(400, 'no fedid found');
            return;
        }
        if (!$request->request->has("entityid")) {
            $accesslog->error($loglbl . 'no entityid found, fedid="' . $request->request->get('fedid') . '"');
            throw new HttpException(400, 'no entityid found');
            return;
        }


        $entityid = urldecode($request->request->get('entityid'));

        $entityidConstraint = new ValidEntityid();
        $errorList = $this->get('validator')->validateValue(
                $entityid, $entityidConstraint
        );

        if (count($errorList) != 0) {
            $accesslog->error($loglbl . 'entityid validation error (value="' . $entityid . '")');
            $retarr = array();
            $retarr['code'] = 400;
            $retarr['message'] = "Validation Failed";
            $retarr['errors']['children']['fedid'] = array();
            $retarr['errors']['children']['entityid']['errors'] = array($errorList[0]->getMessage());
            return View::create($retarr, 400);
        }

        $fedid = urldecode($request->request->get('fedid'));

        $accesslog->info($loglbl . "called with fedid=" . $fedid . " entityid=" . $request->request->get('entityid'));

        $attrs = array();
        $retarr = array();
        $now = new \DateTime();
        $em = $this->container->get('doctrine')->getManager();

        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid(urldecode($fedid));
        if (!$p) {
            $errorlog->error($loglbl . "Principal with fedid=" . $fedid . " not found");
            throw new HttpException(404, "Principal with fedid=" . $fedid . " not found");
        }
        $ss = $em->getRepository("HexaaStorageBundle:Service")->findBy(array('entityid' => $entityid));
        $ss = array_filter($ss);
        if (count($ss) < 1) {
            $errorlog->error($loglbl . "Service with id=" . $entityid . " not found");
            throw new HttpException(404, "Service with id=" . $entityid . " not found");
        }

        foreach ($ss as $s) {

            if (!$s->getIsEnabled()) {
                $errorlog->error($loglbl . "Service with entityid=" . $entityid . " is not enabled");
                if (count($ss) === 1) {
                    throw new HttpException(400, "Service with entityid=" . $entityid . " is not enabled");
                }
            } else {

                // Get Consent object, or create it if it doesn't exist
                $c = $em->getRepository('HexaaStorageBundle:Consent')->findOneBy(array(
                    "principal" => $p,
                    "service" => $s
                ));
                if (!$c) {
                    $c = new Consent();
                    $c->setService($s);
                    $c->setPrincipal($p);
                    $em->persist($c);
                    $em->flush();
                }

                $sass = $em->createQuery('SELECT sas FROM HexaaStorageBundle:ServiceAttributeSpec sas WHERE sas.service=(:s) OR sas.isPublic=true')
                                ->setParameters(array("s" => $s))->getResult();
                $avps = array();
                // Get the values by principal
                foreach ($sass as $sas) {
                    $releaseAttributeSpec = $c->hasEnabledAttributeSpecs($sas->getAttributeSpec());
                    if ($this->container->getParameter('hexaa_consent_module') == false || $this->container->getParameter('hexaa_consent_module') == "false")
                        $releaseAttributeSpec = true;
                    if ($releaseAttributeSpec) {
                        if ($sas->getAttributeSpec()->getIsMultivalue()) {
                            $avps = array_merge($avps, $em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->findBy(
                                            array(
                                                "attributeSpec" => $sas->getAttributeSpec(),
                                                "principal" => $p
                                            )
                            ));
                        } else {
                            $tmps = $em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->findBy(
                                    array(
                                        "attributeSpec" => $sas->getAttributeSpec(),
                                        "principal" => $p
                                    )
                            );
                            foreach ($tmps as $tmp) {
                                if ($tmp->hasService($s) || ($tmp->getServices() == new \Doctrine\Common\Collections\ArrayCollection())) {
                                    $avps[] = $tmp;
                                }
                            }
                        }
                    }
                }
                $attrNames = array();
                // Place the attributes in the return array
                foreach ($avps as $avp) {
                    $retarr[$avp->getAttributeSpec()->getOid()] = array();
                    if (in_array($avp->getAttributeSpec()->getFriendlyName(), $attrNames)) {
                        $attrNames[] = $avp->getAttributeSpec()->getFriendlyName();
                    }
                }

                foreach ($avps as $avp) {
                    array_push($retarr[$avp->getAttributeSpec()->getOid()], $avp->getValue());
                }

                // Get the values by organization
                $avos = $em->getRepository('HexaaStorageBundle:AttributeValueOrganization')->findAll();
                foreach ($avos as $avo) {
                    if ($avo->hasService($s) || ($avo->getServices() == new \Doctrine\Common\Collections\ArrayCollection())) {
                        if (!array_key_exists($avo->getAttributeSpec()->getOid(), $retarr)) {
                            $retarr[$avo->getAttributeSpec()->getOid()] = array();
                        }
                        if (in_array($avo->getAttributeSpec()->getFriendlyName(), $attrNames)) {
                            $attrNames[] = $avo->getAttributeSpec()->getFriendlyName();
                        }
                        array_push($retarr[$avo->getAttributeSpec()->getOid()], $avo->getValue());
                    }
                }

                // Check if we have consent to entitlement release
                $releaseEntitlements = $c->getEnableEntitlements();
                if ($this->container->getParameter('hexaa_consent_module') == false || $this->container->getParameter('hexaa_consent_module') == "false")
                    $releaseEntitlements = true;
                if ($releaseEntitlements) {
                    $retarr['urn:oid:1.3.6.1.4.1.5923.1.1.1.7'] = array();
                    $attrNames[] = 'eduPersonEntitlement';
                    foreach ($em->getRepository('HexaaStorageBundle:Entitlement')->findAllByPrincipalAndService($p, $s) as $e) {
                        $retarr['urn:oid:1.3.6.1.4.1.5923.1.1.1.7'][] = $e->getUri();
                    }
                }
            }
        }

        $releasedAttributes = "";
        foreach ($attrNames as $attrName) {
            $releasedAttributes = $releasedAttributes . " " . $attrName . ", ";
        }
        $releasedAttributes = substr($releasedAttributes, 0, strlen($releasedAttributes) - 2);
        $releaselog->info($loglbl . "released attributes [" . $releasedAttributes . " ] of user with fedid=" . $fedid . " to service with entityid=" . $request->request->get('entityid'));

        //Create News object to notify the user
        $n = new News();
        $n->setPrincipal($p);
        $n->setService($s);
        $n->setTitle("Attribute release");
        $n->setMessage("We have released some attributes (" . $releasedAttributes . " ) of " . $n->getPrincipal()->getFedid() . " to service " . $s->getName());
        $n->setTag("attribute_release");
        $em->persist($n);
        $em->flush();
        $modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());

        return $retarr;
    }

}
