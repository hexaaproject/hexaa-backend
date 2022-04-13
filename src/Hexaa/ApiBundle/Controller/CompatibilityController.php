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

use FOS\RestBundle\Request\ParamFetcherInterface;
use Hexaa\ApiBundle\Annotations\InvokeHook;
use Hexaa\StorageBundle\Entity\EntitlementPack;
use Hexaa\StorageBundle\Entity\Link;
use Hexaa\StorageBundle\Entity\LinkerToken;
use Hexaa\StorageBundle\Entity\News;
use Hexaa\StorageBundle\Entity\Organization;
use Hexaa\StorageBundle\Entity\Role;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\Annotations;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


/**
 * Controller for all the now deprecated actions that must be kept due to compatibility issues.
 * These actions only simulate previous behaviour and should be deleted ASAP.
 *
 * Class CompatibilityController
 *
 * @package Hexaa\ApiBundle\Controller
 */
class CompatibilityController extends HexaaController implements PersonalAuthenticatedController
{

    /**
     * Generate a new one-time entitlement pack token
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
     *   section = "EntitlementPack",
     *   description = "generate new entitlement pack token",
     *   resource = true,
     *   deprecated = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement pack is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement package id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     * @Annotations\Get("/entitlementpacks/{id}/token", requirements={"id" = "\d+"})
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement pack
     * @param integer               $id           EntitlementPack id
     *
     * @return string
     */
    public function getEntitlementpackTokenAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        /** @var EntitlementPack $ep */
        $ep = $this->eh->get('EntitlementPack', $id, $loglbl);

        $link = new Link();
        $link->setService($ep->getService());
        $link->addEntitlementPack($ep);

        $token = new LinkerToken($link);
        $this->em->persist($token);
        $this->em->persist($link);
        $this->em->flush();

        return $token;
    }

    /**
     * link entitlement packs to organization by token
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
     *     types={"attribute_change", "user_added"},
     *     entity="Organization",
     *     id="id",
     *     source="attributes"
     *     )
     *
     * @ApiDoc(
     *   section = "Organization",
     *   resource = true,
     *   deprecated = true,
     *   statusCodes = {
     *     201 = "Returned when successfully created new link",
     *     204 = "Returned when successfully modified link",
     *     400 = "Returned upon bad request",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found",
     *     409 = "Returned when some conflict arises"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="token", "dataType"="string", "required"=true, "requirement"="\d+", "description"="entitlement package token"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher
     * @param integer               $id           Organization id
     * @param string                $token        EntitlementPack connector token
     *
     * @return Response
     */
    public function putOrganizationsEntitlementpacksTokenAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0,
      $token = "nullToken"
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." and token=".$token." by ".$p->getFedid());

        /** @var Organization $o */
        $o = $this->eh->get('Organization', $id, $loglbl);

        // Fetch the LinkerToken
        /** @var LinkerToken $linkerToken */
        $linkerToken = $this->em->getRepository('HexaaStorageBundle:LinkerToken')->findOneByToken($token);
        if (!$linkerToken) {
            $this->errorlog->error($loglbl."The requested linkerToken (".$token.") was not found");
            throw new HttpException(404, "Token not found");
        }

        $link = $linkerToken->getLink();

        // Make sure there is only one entitlement pack in the Link between the service and the organization
        if ($link->getEntitlementPacks()->count() != 1) {
            $this->errorlog->error(
              $loglbl.'Incorrect number (!=1) of entitlementPacks in the link indicated by the token, doing nothing.'
            );
            throw new HttpException(
              400,
              'Incorrect number (!=1) of entitlementPacks in the link indicated by the token, doing nothing.'
            );
        }

        // Make sure there is no entitlement in the Link between the service and the organization
        if ($link->getEntitlements()->count() != 0) {
            $this->errorlog->error(
              $loglbl.'Incorrect number (!=0) of entitlements in the link indicated by the token, doing nothing.'
            );
            throw new HttpException(
              400,
              'Incorrect number (!=0) of entitlements in the link indicated by the token, doing nothing.'
            );
        }
        /** @var EntitlementPack $ep */
        $ep = $link->getEntitlementPacks()->first();

        if (!$ep->getService()->getIsEnabled()) {
            $this->errorlog->error(
              $loglbl."Service ".$ep->getService()->getName()." is not enabled, can't add its entitlementPack."
            );
            throw new HttpException(409, "Service ".$ep->getService()->getName()." is not enabled.");
        }

        /** @var Link $properLink */
        $properLink = $this->em->getRepository('HexaaStorageBundle:Link')
          ->findOneBy(
            array(
              'service'      => $link->getService(),
              'organization' => $o,
            )
          );

        $removeLink = false;
        $statusCode = 201;

        if (!$properLink) {
            $link->setOrganization($o);
            $o->addLink($link);
            $this->em->persist($o);
            $link->setStatus('accepted');
            $properLink = $link;
        } else {
            if ($properLink->hasEntitlementPack($ep)) {
                $statusCode = 204;
            } else {
                if ($properLink->getStatus() !== 'accepted'
                  && ($properLink->getEntitlements()->count() != 0 || $properLink->getEntitlementPacks()->count() != 0)
                ) {
                    $this->errorlog->error(
                      $loglbl.'A pending link already exists between organization '.$o->getName()
                      .' and service'.$properLink->getService()->getName().'. Cannot set it accepted, this call only authorizes'
                      .' a single entitlement pack.'
                    );
                    throw new HttpException(
                      409, 'A pending link already exists between organization '.$o->getName()
                      .' and service'.$properLink->getService()->getName().'. Cannot set it accepted, this call only authorizes'
                      .' a single entitlement pack.'
                    );
                } else {
                    $properLink->addEntitlementPack($ep);
                    $properLink->setStatus('accepted');
                    $removeLink = true;
                }
            }
        }

        if ($removeLink) {
            $this->em->remove($link);
        }
        $this->em->remove($linkerToken);
        $this->em->persist($properLink);

        //Create News object to notify the user
        $n = new News();
        $n->setOrganization($o);
        $n->setService($link->getService());
        $n->setTitle("Entitlement package connected");
        $n->setMessage("An entitlement pack ".$ep->getName()." has been connected to organization ".$o->getName());
        $n->setTag("organization_entitlement_pack");
        $this->em->persist($n);
        $this->em->flush();
        $this->modlog->info($loglbl."Created News object with id=".$n->getId()." about ".$n->getTitle());

        $this->modlog->info(
          $loglbl."Entitlement Pack (id=".$ep->getId(
          ).") link status was set to accepted with Organization (id=".$id.") by token linking"
        );

        $response = new Response();
        $response->setStatusCode($statusCode);

        // set the `Location` header only when creating new resources
        if (201 === $statusCode) {
            $response->headers->set(
              'Location',
              $this->generateUrl(
                'get_link',
                array('id' => $properLink->getId()),
                UrlGeneratorInterface::ABSOLUTE_URL // absolute
              )
            );
        }

        return $response;
    }

    /**
     * Request an entitlement pack for an organization
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
     *   section = "Organization",
     *   description = "request an entitlement pack for an organization",
     *   resource = true,
     *   deprecated = true,
     *   statusCodes = {
     *     201 = "Returned when a new link was created",
     *     204 = "Returned when an existing link was modified",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement pack is not found",
     *     409 = "Returned when a link already exists"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="epid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement package id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     * @Annotations\View(statusCode=201)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement pack
     * @param integer               $id           Organization id
     * @param int                   $epid         EntitlementPack id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putOrganizationsEntitlementpacksAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0,
      $epid = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl.'Called with id='.$id.' and epid='.$epid.' by '.$p->getFedid());

        /** @var Organization $o */
        $o = $this->eh->get('Organization', $id, $loglbl);

        /** @var EntitlementPack $ep */
        $ep = $this->eh->get('EntitlementPack', $epid, $loglbl);

        /** @var Link $link */
        $link = $this->em->createQueryBuilder()
          ->select('link')
          ->from('HexaaStorageBundle:Link', 'link')
          ->where('link.organization = :org')
          ->andWhere(':ep MEMBER OF link.entitlementPacks')
          ->setParameters(array(':org' => $o, ':ep' => $ep))
          ->getQuery()
          ->getOneOrNullResult();

        if ($link && $link->getStatus() !== 'pending') {
            $this->errorlog->error(
              $loglbl.'An accepted link already exists between for organization with this entitlement pack,'
              .' please modify the existing link.'
            );
            throw new HttpException(
              409,
              'An accepted link already exists between for organization with this entitlement pack,'
              .' please modify the existing link.'
            );
        }

        $statusCode = 201;
        if (!$link) {
            $link = new Link();
        } else {
            $statusCode = 204;
        }
        $link->addEntitlementPack($ep);
        $link->setOrganization($o);
        $o->addLink($link);

        $this->em->persist($link);
        $this->em->persist($o);


        //Create News object to notify the user
        $n = new News();
        $n->setOrganization($o);
        $n->setService($ep->getService());
        $n->setTitle("New link created");
        $n->setMessage(
          ($statusCode === 201 ? 'A new link has been created' : 'The link was modified').
          ' between organization '.$o->getName().' and service '.$ep->getService()->getName()
        );
        $n->setTag("link");
        $this->em->persist($n);
        $this->em->flush();
        $this->modlog->info($loglbl."Created News object with id=".$n->getId()." about ".$n->getTitle());

        $this->modlog->info(
          $loglbl.($statusCode === 201 ? 'A new Link (id='.$link->getId().' ) has been created' : 'Link (id='.$link->getId()
            .' ) has been modified')
          .' to link Entitlement Pack (id='.$ep->getId().') to Organization (id='.$id.") by id linking"
        );

        $this->em->flush();

        $response = ($statusCode === 201 ? new Response(
          '', $statusCode, array(
            'Location' => $this->generateUrl(
              'get_link',
              array('id' => $link->getId()),
              UrlGeneratorInterface::ABSOLUTE_URL // absolute
            ),
          )
        ) : new Response('', $statusCode));

        return $response;

    }

    /**
     * accept linked entitlement packs to organization
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
     *     types={"attribute_change", "user_added"},
     *     entity="Organization",
     *     id="id",
     *     source="attributes"
     *     )
     *
     * @ApiDoc(
     *   section = "Organization",
     *   resource = true,
     *   deprecated = true,
     *   statusCodes = {
     *     201 = "Returned when successfully created new link",
     *     204 = "Returned when successfully modified link",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found",
     *     409 = "Returned if some conflict arises"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="epid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement package  id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher
     * @param integer               $id           Organization id
     * @param int                   $epid         Entitlement package id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putOrganizationsEntitlementpacksAcceptAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0,
      $epid = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." and epid=".$epid." by ".$p->getFedid());

        /** @var Organization $o */
        $o = $this->eh->get('Organization', $id, $loglbl);
        /** @var EntitlementPack $ep */
        $ep = $this->eh->get('EntitlementPack', $epid, $loglbl);

        /** @var Link $link */
        $link = $this->em->createQueryBuilder()
          ->select('link')
          ->from('HexaaStorageBundle:Link', 'link')
          ->where('link.organization = :org')
          ->andWhere(':ep MEMBER OF link.entitlementPacks')
          ->andWhere('link.service is NULL')
          ->setParameters(array(':org' => $o, ':ep' => $ep))
          ->getQuery()
          ->getOneOrNullResult();


        if (!$link) {
            $this->errorlog->error(
              $loglbl.'Could not find link to accept with organization (id='.$o->getId().') and entitlement pack (id='
              .$ep->getId()
            );
            throw new HttpException(
              404, 'Could not find link to accept with organization (id='.$o->getId().') and entitlement pack (id='
              .$ep->getId()
            );
        }

        // Make sure there is only one entitlement pack in the Link between the service and the organization
        if ($link->getStatus() === 'pending' && $link->getEntitlementPacks()->count() != 1) {
            $this->errorlog->error(
              $loglbl.'Incorrect number (!=1) of entitlementPacks in the link indicated by the token, doing nothing.'
            );
            throw new HttpException(
              400,
              'Incorrect number (!=1) of entitlementPacks in the link indicated by the token, doing nothing.'
            );
        }

        // Make sure there is no entitlement in the Link between the service and the organization
        if ($link->getStatus() === 'pending' && $link->getEntitlements()->count() != 0) {
            $this->errorlog->error(
              $loglbl.'Incorrect number (!=0) of entitlements in the link indicated by the token, doing nothing.'
            );
            throw new HttpException(
              400,
              'Incorrect number (!=0) of entitlements in the link indicated by the token, doing nothing.'
            );
        }
        /** @var EntitlementPack $ep */
        $ep = $link->getEntitlementPacks()->first();

        if (!$ep->getService()->getIsEnabled()) {
            $this->errorlog->error(
              $loglbl."Service ".$ep->getService()->getName()." is not enabled, can't add its entitlementPack."
            );
            throw new HttpException(409, "Service ".$ep->getService()->getName()." is not enabled.");
        }

        /** @var Link $properLink */
        $properLink = $this->em->getRepository('HexaaStorageBundle:Link')
          ->findOneBy(
            array(
              'service'      => $ep->getService(),
              'organization' => $o,
            )
          );

        $removeLink = false;
        $statusCode = 201;

        if (!$properLink) {
            $link->setOrganization($o);
            $link->setService($ep->getService());
            $link->setStatus('accepted');
            $properLink = $link;
        } else {
            if ($properLink->hasEntitlementPack($ep)) {
                $statusCode = 204;
            } else {
                if ($properLink->getStatus() !== 'accepted'
                  && ($properLink->getEntitlements()->count() != 0 || $properLink->getEntitlementPacks()->count() != 0)
                ) {
                    $this->errorlog->error(
                      $loglbl.'A pending link already exists between organization '.$o->getName()
                      .' and service'.$properLink->getService()->getName().'. Cannot set it accepted, this call only authorizes'
                      .' a single entitlement pack.'
                    );
                    throw new HttpException(
                      409, 'A pending link already exists between organization '.$o->getName()
                      .' and service'.$properLink->getService()->getName().'. Cannot set it accepted, this call only authorizes'
                      .' a single entitlement pack.'
                    );
                } else {
                    $properLink->addEntitlementPack($ep);
                    $properLink->setStatus('accepted');
                    $removeLink = true;
                }
            }
        }

        if ($removeLink) {
            $this->em->remove($link);
        }
        $this->em->persist($properLink);

        //Create News object to notify the user
        $n = new News();
        $n->setOrganization($o);
        $n->setService($properLink->getService());
        $n->setTitle("Entitlement package connected");
        $n->setMessage("An entitlement pack ".$ep->getName()." has been connected to organization ".$o->getName());
        $n->setTag("organization_entitlement_pack");
        $this->em->persist($n);
        $this->em->flush();
        $this->modlog->info($loglbl."Created News object with id=".$n->getId()." about ".$n->getTitle());

        $this->modlog->info(
          $loglbl."Entitlement Pack (id=".$ep->getId(
          ).") link status was set to accepted with Organization (id=".$id.") by id linking"
        );

        $response = new Response();
        $response->setStatusCode($statusCode);

        // set the `Location` header only when creating new resources
        if (201 === $statusCode) {
            $response->headers->set(
              'Location',
              $this->generateUrl(
                'get_link',
                array('id' => $properLink->getId()),
                UrlGeneratorInterface::ABSOLUTE_URL // absolute
              )
            );
        }

        return $response;
    }

    /**
     * unlink entitlement packs from organization
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
     *     types={"attribute_change", "user_removed"},
     *     entity="Organization",
     *     id="id",
     *     source="attributes"
     *     )
     *
     * @ApiDoc(
     *   section = "Organization",
     *   resource = true,
     *   deprecated = true,
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found",
     *     409 = "Returned when the organization doesn't have the entitlement package to be unlinked"
     *   },
     *   tags = {"organization manager" = "#4180B4", "service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="epid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement package id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher
     * @param integer               $id           Organization id
     * @param integer               $epid         EntitlementPack id
     */
    public function deleteOrganizationsEntitlementpacksAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0,
      $epid = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." and epid=".$epid." by ".$p->getFedid());

        /** @var Organization $o */
        $o = $this->eh->get('Organization', $id, $loglbl);
        /** @var EntitlementPack $ep */
        $ep = $this->eh->get('EntitlementPack', $epid, $loglbl);

        /** @var Link $link */
        $link = $this->em->createQueryBuilder()
          ->select('link')
          ->from('HexaaStorageBundle:Link', 'link')
          ->where('link.organization = :org')
          ->andWhere(':ep MEMBER OF link.entitlementPacks')
          ->setParameters(array(':org' => $o, ':ep' => $ep))
          ->getQuery()
          ->getOneOrNullResult();

        if (!$link || !$link->hasEntitlementPack($ep)) {
            $this->errorlog->error(
              $loglbl."Organization (id=".$o->getId().") does not have the requested EntitlementPack (id=".$epid.")"
            );
            throw new HttpException(409, 'The organization does not have this entitlement package!');
        }

        // Set affected entity for Hook
        $request->attributes->set(
          '_attributeChangeAffectedEntity',
          array(
            "entity"    => "Organization",
            "id"        => array($o->getId()),
            'serviceId' => $ep->getServiceId(),
          )
        );

        $link->removeEntitlementPack($ep);
        $ep->removeLink($link);
        foreach ($ep->getEntitlements() as $entitlement) {
            if (!$link->hasEntitlement($entitlement)) {
                /** @var Role $role */
                foreach ($link->getOrganization()->getRoles() as $role) {
                    $role->removeEntitlement($entitlement);
                    $this->em->persist($role);
                }
            }
        }

        $this->em->persist($link);
        $this->em->persist($ep);

        // Remove link if it's empty
        if ($link->getEntitlementPacks()->count() == 0 && $link->getEntitlements()->count() == 0) {
            $this->em->remove($link);
        }

        //Create News object to notify the user
        $n = new News();
        $n->setOrganization($o);
        $n->setService($ep->getService());
        $n->setTitle("Entitlement package unlinked");
        $n->setMessage("An entitlement pack ".$ep->getName()." has been unlinked from organization ".$o->getName());
        $n->setTag("organization_entitlement_pack");
        $this->em->persist($n);

        $this->em->flush();
        $this->modlog->info($loglbl."Entitlement Pack (id=".$epid.") link with Organization (id=".$id.") was deleted");
        $this->modlog->info($loglbl."Created News object with id=".$n->getId()." about ".$n->getTitle());
    }


}
