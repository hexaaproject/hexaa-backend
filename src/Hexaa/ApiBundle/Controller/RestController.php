<?php

/**
 * Copyright 2014-2018 MTA SZTAKI, ugyeletes@sztaki.hu
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Hexaa\ApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Hexaa\ApiBundle\Validator\Constraints\ValidEntityid;
use Hexaa\StorageBundle\Entity\AttributeValueOrganization;
use Hexaa\StorageBundle\Entity\AttributeValuePrincipal;
use Hexaa\StorageBundle\Entity\Entitlement;
use Hexaa\StorageBundle\Entity\News;
use Hexaa\StorageBundle\Entity\PersonalToken;
use Hexaa\StorageBundle\Entity\Principal;
use Hexaa\StorageBundle\Entity\ServiceAttributeSpec;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;


/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author  Soltész Balázs <solazs@sztaki.hu>
 */
class RestController extends FOSRestController
{

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
    public function postTokenAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher
    ) {

        // Loggers & label
        $loglbl = "[postToken], ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $loginlog = $this->get('monolog.logger.login');
        $masterkey = $this->get('security.token_storage')->getToken()->getUser()->getUserName();
        $em = $this->getDoctrine()->getManager();

        if (!$request->request->has('fedid')) {
            $errorlog->error($loglbl."no fedid found");
            $accesslog->error($loglbl."called without fedid");
            throw new HttpException(400, 'no fedid found');
        }


        $fedid = urldecode($request->request->get('fedid'));
        $accesslog->info($loglbl."call with fedid=".$fedid);

        /** @var Principal $p */
        $p = $em->getRepository('HexaaStorageBundle:Principal')
          ->findOneByFedid($fedid);
        if (!$p) {
            $p = new Principal();
            $p->setFedid($fedid);
            $modlog->info($loglbl."new principal created with fedid=".$fedid);
        }

        if ($p->getEmail() == null) {
            if ($request->request->has('email')) {

                // Validate e-mail
                $email = $request->request->get('email');
                $emailConstraint = new EmailConstraint();
                $errors = $this->get('validator')->validate(
                  $email,
                  $emailConstraint
                );

                if (strlen($errors) > 2) {
                    $errorlog->error($loglbl.$errors);
                    throw new HttpException(400, $errors);
                } else {

                    $modlog->info(
                      $loglbl."principal's email has been set to email=".$request->request->get('email')." with fedid=".$fedid
                    );
                    $p->setEmail($email);
                    $em->persist($p);
                    $em->flush();
                }
            } else {
                $errorlog->error($loglbl."no mail found, but user has no mail, yet");
                throw new HttpException(400, 'no mail found');
            }
        }

        if ($request->request->has('display_name') && ($p->getDisplayName() == null)) {
            $modlog->info(
              $loglbl."principal's display name has been set to display_name=".$request->request->get(
                'display_name'
              )." with fedid=".$fedid
            );
            $p->setDisplayName($request->request->get('display_name'));
            $em->persist($p);
            $em->flush();
        }

        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $token = $p->getToken();

        if (!$token || $token->getMasterkey() != $masterkey) {
            $p->setToken(new PersonalToken($p->getFedid(), $masterkey));
            $em->persist($p);
            $em->flush();
            $modlog->info(
              $loglbl."generated new token of masterkey ".$p->getToken()->getMasterkey()." for principal with fedid=".$fedid
            );
        } else {
            if ($date > $token->getTokenExpire()) {
                $em->remove($token);
                $p->setToken(new PersonalToken($p->getFedid(), $masterkey));
                $em->persist($p);
                $em->flush();
                $modlog->info(
                  $loglbl."generated new token of masterkey ".$p->getToken()->getMasterkey()." for principal with fedid=".$fedid
                );
            }
        }
        $loginlog->info($loglbl."served token of masterkey ".$p->getToken()->getMasterkey()." for principal with fedid=".$fedid);

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
     *     404 = "Returned when service is not found",
     *     409 = "Service is not enabled"
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
     * @param Request $request the request object
     *
     * @return array|View
     */
    public function postAttributesAction(Request $request)
    {

        // Loggers & label
        $loglbl = "[attribute release], ";
        $accesslog = $this->get('monolog.logger.access');
        $modlog = $this->get('monolog.logger.modification');
        $errorlog = $this->get('monolog.logger.error');
        $releaselog = $this->get('monolog.logger.release');

        // Validate input
        if (!$request->request->has('fedid') && !$request->request->has("entityid")) {
            $accesslog->error($loglbl."no fedid and entityid found");
            throw new HttpException(400, 'no fedid and entityid found');
        }

        if (!$request->request->has('fedid')) {
            $accesslog->error($loglbl.'no fedid found, entityid="'.urldecode($request->request->get('entityid')).'"');
            throw new HttpException(400, 'no fedid found');
        }
        if (!$request->request->has("entityid")) {
            $accesslog->error($loglbl.'no entityid found, fedid="'.$request->request->get('fedid').'"');
            throw new HttpException(400, 'no entityid found');
        }

        $entityid = urldecode($request->request->get('entityid'));

        $entityidConstraint = new ValidEntityid();
        $errorList = $this->get('validator')->validate(
          $entityid,
          $entityidConstraint
        );

        if (count($errorList) != 0) {
            $accesslog->error($loglbl.'entityid validation error (value="'.$entityid.'")');
            $errarr = array();
            $errarr['code'] = 400;
            $errarr['message'] = "Validation Failed";
            $errarr['errors']['children']['fedid'] = array();
            $errarr['errors']['children']['entityid']['errors'] = array($errorList[0]->getMessage());

            return View::create($errarr, 400);
        }

        $fedid = urldecode($request->request->get('fedid'));

        $accesslog->info($loglbl."called with fedid=".$fedid." entityid=".$request->request->get('entityid'));

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->container->get('doctrine')->getManager();

        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid(urldecode($fedid));
        if (!$p) {
            $errorlog->error($loglbl."Principal with fedid=".$fedid." not found");
            throw new HttpException(404, "Principal with fedid=".$fedid." not found");
        }

        // Get Services
        $ss = $em->getRepository("HexaaStorageBundle:Service")->findBy(array('entityid' => $entityid));
        $ss = array_filter($ss);
        if (count($ss) < 1) {
            $errorlog->error($loglbl."Service with id=".$entityid." not found");
            throw new HttpException(404, "Service with id=".$entityid." not found");
        }
        // Input seems to be valid

        $avps = array();
        $avos = array();
        $retarr = array();
        $attrNames = array();

        /* @var $s \Hexaa\StorageBundle\Entity\Service */
        foreach ($ss as $s) {

            if (!$s->getIsEnabled()) {
                $errorlog->error($loglbl."Service ".$s->getName()." with entityid=".$entityid." is not enabled");
            } else {

                $hadIsMemberOf = false;

                // Get attribute spec - service connectors
                $sass = $em->createQueryBuilder()
                  ->select("sas")
                  ->from('HexaaStorageBundle:ServiceAttributeSpec', 'sas')
                  ->innerJoin('sas.service', 'service')
                  ->where("sas.service = :s")
                  ->andWhere('service.isEnabled = true')
                  ->setParameters(array("s" => $s))
                  ->getQuery()
                  ->getResult();

                //  Get AttributeValues of the principal
                /* @var $sas ServiceAttributeSpec */
                foreach ($sass as $sas) {

                    // If the attributeSpec is isMemberOf, then there is no need to query the DB, we'll just compute it later.
                    if ($sas->getAttributeSpec()->getUri() == 'urn:oid:1.3.6.1.4.1.5923.1.5.1.1') {
                        $hadIsMemberOf = true;
                    } else {
                        if ($sas->getAttributeSpec()->getMaintainer() === 'user') {
                            // Get the AttributeValuePrincipals for the ServiceAttributeSpec
                            $avps = $em->createQueryBuilder()
                              ->select('avp')
                              ->from('HexaaStorageBundle:AttributeValuePrincipal', 'avp')
                              ->innerJoin('avp.services', 'services')
                              ->where('avp.attributeSpec = :attributeSpec')
                              ->andWhere('avp.principal = :principal')
                              ->andWhere(':service MEMBER OF avp.services')
                              ->andWhere('services.isEnabled = true')
                              ->setParameters(
                                array(
                                  'attributeSpec' => $sas->getAttributeSpec(),
                                  'principal'     => $p,
                                  'service'       => $s,
                                )
                              )
                              ->getQuery()
                              ->getResult();
                        } else {
                            if ($sas->getAttributeSpec()->getMaintainer() === 'manager') {
                                // Get the AttributeValueOrganizations for the ServiceAttributeSpec
                                $avos = $em->createQueryBuilder()
                                  ->select("avo")
                                  ->from("HexaaStorageBundle:AttributeValueOrganization", "avo")
                                  ->innerJoin("avo.organization", "o")
                                  ->innerJoin('avo.services', 'services')
                                  ->where(":p MEMBER OF o.principals")
                                  ->andWhere("avo.attributeSpec = :attr_spec")
                                  ->andWhere(':service MEMBER OF avp.services')
                                  ->andWhere('services.isEnabled = true')
                                  ->setParameters(
                                    array(
                                      ":p"         => $p,
                                      ":attr_spec" => $sas->getAttributeSpec(),
                                      'service'    => $s,
                                    )
                                  )
                                  ->getQuery()
                                  ->getResult();
                            }
                        }
                    }

                }
                // Place the attributes in the return array
                /* @var $avp AttributeValuePrincipal */
                foreach ($avps as $avp) {
                    $retarr[$avp->getAttributeSpec()->getUri()] = array();
                    if (!in_array($avp->getAttributeSpec()->getName(), $attrNames)) {
                        $attrNames[] = $avp->getAttributeSpec()->getName();
                    }
                }

                /* @var $avp AttributeValuePrincipal */
                foreach ($avps as $avp) {
                    if (!in_array($avp->getValue(), $retarr[$avp->getAttributeSpec()->getUri()])) {
                        array_push($retarr[$avp->getAttributeSpec()->getUri()], $avp->getValue());
                    }
                }
                /* @var $avo AttributeValueOrganization */
                foreach ($avos as $avo) {
                    $retarr[$avo->getAttributeSpec()->getUri()] = array();
                    if (!in_array($avo->getAttributeSpec()->getName(), $attrNames)) {
                        $attrNames[] = $avo->getAttributeSpec()->getName();
                    }
                }

                /* @var $avp AttributeValueOrganization */
                foreach ($avos as $avo) {
                    if (!in_array($avo->getValue(), $retarr[$avo->getAttributeSpec()->getUri()])) {
                        array_push($retarr[$avo->getAttributeSpec()->getUri()], $avo->getValue());
                    }
                }

                // Compute the isMemberOf attribute if necessary
                if ($hadIsMemberOf) {
                    $os = $em->getRepository('HexaaStorageBundle:Organization')->findAllByRelatedPrincipalAndService($p, $s);

                    if (count($os) > 0) {
                        $retarr['urn:oid:1.3.6.1.4.1.5923.1.5.1.1'] = array();
                    }
                    /* @var $o \Hexaa\StorageBundle\Entity\Organization */
                    foreach ($os as $o) {
                        array_push(
                          $retarr['urn:oid:1.3.6.1.4.1.5923.1.5.1.1'],
                          $request->getSchemeAndHttpHost().$request->getBasePath()."/groups/"
                          .$o->getId()
                        );
                        $rps = $em->getRepository('HexaaStorageBundle:RolePrincipal')->findAllByOrganizationAndPrincipalStrict(
                          $o,
                          $p
                        );
                        /* @var $rp \Hexaa\StorageBundle\Entity\RolePrincipal */
                        foreach ($rps as $rp) {
                            array_push(
                              $retarr['urn:oid:1.3.6.1.4.1.5923.1.5.1.1'],
                              $request->getSchemeAndHttpHost().$request->getBasePath()."/groups/"
                              .$o->getId().'-'.$rp->getRole()->getId()
                            );
                        }
                    }
                    $attrNames[] = "isMemberOf";
                }

                $es = $em->getRepository('HexaaStorageBundle:Entitlement')->findAllByPrincipalAndServiceStrict($p, $s);

                if ((!isset($retarr['urn:oid:1.3.6.1.4.1.5923.1.1.1.7'])
                    || !is_array($retarr['urn:oid:1.3.6.1.4.1.5923.1.1.1.7']))
                  && count($es) > 0
                ) {
                    $retarr['urn:oid:1.3.6.1.4.1.5923.1.1.1.7'] = array();
                    $attrNames[] = 'eduPersonEntitlement';
                }
                /** @var Entitlement $e */
                foreach ($es as $e) {
                    $retarr['urn:oid:1.3.6.1.4.1.5923.1.1.1.7'][] = $e->getUri();
                }


            }
        }

        $releasedAttributes = "";
        foreach ($attrNames as $attrName) {
            $releasedAttributes = $releasedAttributes." ".$attrName.", ";
        }
        $releasedAttributes = substr($releasedAttributes, 0, strlen($releasedAttributes) - 2);
        $releaselog->info(
          $loglbl."released attributes [".$releasedAttributes." ] of user with fedid=".$fedid." to service with entityid=".$request->request->get(
            'entityid'
          )
        );

        foreach ($ss as $s) {
            if ($s->getIsEnabled()) {
                //Create News object to notify the user
                $n = new News();
                $n->setPrincipal($p);
                $n->setService($s);
                $n->setTitle("Attribute release");
                $n->setMessage(
                  "We have released some attributes (".$releasedAttributes." ) of ".$n->getPrincipal()->getFedid(
                  )." to service ".$s->getName()
                );
                $n->setTag("attribute_release");
                $em->persist($n);
                $em->flush();
                $modlog->info($loglbl."Created News object with id=".$n->getId()." about ".$n->getTitle());
            }
        }

        return $retarr;
    }

}
