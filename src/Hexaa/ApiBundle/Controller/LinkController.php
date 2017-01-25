<?php

/*
 * 2016 MTA-SZTAKI.
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


use Doctrine\Common\Collections\Collection;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Hexaa\ApiBundle\Annotations\InvokeHook;
use Hexaa\StorageBundle\Entity\LinkerToken;
use Hexaa\StorageBundle\Entity\News;
use Hexaa\StorageBundle\Entity\Link;
use Hexaa\StorageBundle\Entity\Principal;
use Hexaa\StorageBundle\Entity\Role;
use Hexaa\StorageBundle\Form\LinkType;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author  Soltész Balázs <solazs@sztaki.hu>
 */
class LinkController extends HexaaController implements PersonalAuthenticatedController
{

    /**
     * Lists all links of a service
     *
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
     *
     * @ApiDoc(
     *   section = "Service",
     *   resource = false,
     *   description = "get all links of a service",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   output="array<Hexaa\StorageBundle\Entity\Link>"
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher links
     * @param integer               $id           service ID
     *
     * @return array
     */
    public function cgetServiceLinkAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called by ".$p->getFedid());

        $s = $this->eh->get('Service', $id, $loglbl, true);

        $items = $this->em->getRepository('HexaaStorageBundle:Link')->findBy(
          array('service' => $s),
          array(),
          $paramFetcher->get('limit'),
          $paramFetcher->get('offset')
        );

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $this->em->createQueryBuilder()
              ->select('COUNT(lnk.id)')
              ->from('HexaaStorageBundle:Link', 'lnk')
              ->where(':serv = lnk.service')
              ->setParameter(':serv', $s)
              ->getQuery()
              ->getSingleScalarResult();

            return array("item_number" => (int)$itemNumber, "items" => $items);
        } else {
            return $items;
        }
    }

    /**
     * Lists all links of an organization
     *
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
     *
     * @ApiDoc(
     *   section = "Organization",
     *   resource = false,
     *   description = "get all links of an organization",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   output="array<Hexaa\StorageBundle\Entity\Link>"
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher links
     * @param integer               $id           organization ID
     *
     * @return array
     */
    public function cgetOrganizationLinkAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called by ".$p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl, true);

        $items = $this->em->createQueryBuilder()
          ->select('lnk')
          ->from('HexaaStorageBundle:Link', 'lnk')
          ->where('lnk.organization = :org')
          ->setParameter(':org', $o)
          ->setFirstResult($paramFetcher->get('offset'))
          ->setMaxResults($paramFetcher->get('limit'))
          ->getQuery()
          ->getResult();

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $this->em->createQueryBuilder()
              ->select('COUNT(lnk.id)')
              ->from('HexaaStorageBundle:Link', 'lnk')
              ->where('lnk.organization = :org')
              ->setParameter(':org', $o)
              ->getQuery()
              ->getSingleScalarResult();

            return array("item_number" => (int)$itemNumber, "items" => $items);
        } else {
            return $items;
        }
    }

    /**
     * get link details
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
     *   section = "Link",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4", "service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="link id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher links
     * @param integer               $id           Link id
     *
     * @return Link
     */
    public function getLinkAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."called with id=".$id." by ".$p->getFedid());

        $sd = $this->eh->get('Link', $id, $loglbl);

        return $sd;
    }

    /**
     * Edit link preferences
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
     *
     * @ApiDoc(
     *   section = "Link",
     *   resource = true,
     *   description = "edit link preferences",
     *   statusCodes = {
     *     204 = "Returned when links has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when link is not found"
     *   },
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="link id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   tags = {"organization manager" = "#4180B4", "service manager" = "#4180B4"},
     *   parameters = {
     *      {"name"="service","dataType"="integer","required"=true, "description"="identifier of the service to be linked"},
     *      {"name"="organization","dataType"="integer","required"=true, "description"="identifier of the organization to be linked"},
     *      {"name"="entitlements","dataType"="array","required"=false, "description"="array of IDs of entitlements to be linked"},
     *      {"name"="entitlement_packs","dataType"="array","required"=false, "description"="array of IDs of entitlement packs to be linked"}
     *   }
     * )
     *
     *
     * @InvokeHook({"attribute_change", "user_added"})
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher link
     * @param integer               $id           Link id
     *
     * @return null
     *
     */
    public function putLinkAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."called with id=".$id." by ".$p->getFedid());

        $sd = $this->eh->get('Link', $id, $loglbl);

        return $this->processForm($sd, $loglbl, $request, 'PUT', $p);
    }

    private function processForm(Link $link, $loglbl, Request $request, $method = "PUT", Principal $p)
    {
        $statusCode = $link->getId() == null ? 201 : 204;

        $entitlementsOfLink = $link->getEntitlements();

        foreach ($link->getEntitlementPacks() as $entitlementPack) {
            foreach ($entitlementPack->getEntitlements() as $entitlement) {
                if (!$entitlementsOfLink->contains($entitlement)) {
                    $entitlementsOfLink->add($entitlement);
                }
            }
        }

        $entitlementsOfLink = $entitlementsOfLink->toArray();

        $form = $this->createForm(new LinkType(), $link, array("method" => $method));
        $form->submit($request->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            if (201 === $statusCode) {
                $this->modlog->info($loglbl."created new Link with id=".$link->getId());
            } else {
                $this->modlog->info($loglbl."updated Link with id=".$link->getId());

                $newEntitlementsOfLink = $link->getEntitlements();

                foreach ($link->getEntitlementPacks() as $entitlementPack) {
                    foreach ($entitlementPack->getEntitlements() as $entitlement) {
                        if (!$newEntitlementsOfLink->contains($entitlement)) {
                            $newEntitlementsOfLink->add($entitlement);
                        }
                    }
                }

                foreach ($entitlementsOfLink as $e) {
                    if (!$newEntitlementsOfLink->contains($e)) {
                        /** @var Role $r */
                        foreach ($link->getOrganization()->getRoles() as $r) {
                            if ($r->hasEntitlement($e)) {
                                $r->removeEntitlement($e);
                            }
                            $this->em->persist($r);
                        }
                    }
                }
            }
            $this->em->persist($link);

            //Create News object to notify the user
            $n = new News();
            $n->setService($link->getService());
            $n->setOrganization($link->getOrganization());
            $n->setTitle($statusCode === 201 ? 'Organization linked to service' : 'Link modified');
            $n->setMessage(
              $p->getFedid().' has '.($statusCode === 201 ? 'created a link ' : 'modified the link ')
              .'between service '.$link->getService()->getName().' and organization '.$link->getOrganization()->getName()
            );
            $n->setTag("organization_service");

            $this->em->persist($n);
            $this->em->flush();

            $this->modlog->info($loglbl."Created News object with id=".$n->getId()." about ".$n->getTitle());

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set(
                  'Location',
                  $this->generateUrl(
                    'get_link',
                    array('id' => $link->getId()),
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
     * Edit link
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
     *   section = "Link",
     *   resource = true,
     *   description = "edit link preferences",
     *   statusCodes = {
     *     204 = "Returned when link has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when link is not found"
     *   },
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="link id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   tags = {"organization manager" = "#4180B4", "service manager" = "#4180B4"},
     *   parameters = {
     *      {"name"="service","dataType"="integer","required"=true, "description"="identifier of the service to be linked"},
     *      {"name"="organization","dataType"="integer","required"=true, "description"="identifier of the organization to be linked"},
     *      {"name"="entitlements","dataType"="array","required"=false, "description"="array of IDs of entitlements to be linked"},
     *      {"name"="entitlement_packs","dataType"="array","required"=false, "description"="array of IDs of entitlement packs to be linked"}
     *   }
     * )
     *
     *
     * @InvokeHook({"attribute_change", "user_added"})
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher links
     * @param integer               $id           Link id
     *
     * @return null
     *
     */
    public function patchLinkAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."called with id=".$id." by ".$p->getFedid());

        $sd = $this->eh->get('Link', $id, $loglbl);

        return $this->processForm($sd, $loglbl, $request, 'PATCH', $p);
    }

    /**
     * Create new link
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
     *   section = "Link",
     *   resource = true,
     *   description = "create new link",
     *   statusCodes = {
     *     201 = "Returned when link has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when link is not found"
     *   },
     *   requirements = {
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   tags = {"organization manager" = "#4180B4", "service manager" = "#4180B4"},
     *   input = "Hexaa\StorageBundle\Form\LinkType"
     * )
     *
     * @InvokeHook({"attribute_change", "user_added"})
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher links
     *
     * @return null
     *
     */
    public function postLinkAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called by ".$p->getFedid());

        return $this->processForm(new Link(), $loglbl, $request, "POST", $p);
    }

    /**
     * Generate a new one-time link token
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
     *   section = "Link",
     *   description = "generate new link token",
     *   resource = false,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when link is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="link id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     * @Annotations\Get("/links/{id}/token", requirements={"id" = "\d+"})
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement pack
     * @param integer               $id           Link id
     *
     * @return LinkerToken
     */
    public function getLinkTokenAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        $link = $this->eh->get('Link', $id, $loglbl);

        $token = new LinkerToken($link);
        $this->em->persist($token);
        $this->em->flush();

        return $token;
    }

    /**
     * List unused tokens
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
     *   section = "Link",
     *   description = "list unused tokens",
     *   resource = false,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when link is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="link id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     * @Annotations\Get("/links/{id}/tokens", requirements={"id" = "\d+"})
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement pack
     * @param integer               $id           Link id
     *
     * @return Collection
     */
    public function cgetLinkTokensAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        /** @var Link $link */
        $link = $this->eh->get('Link', $id, $loglbl);

        return $link->getTokens();
    }

    /**
     * Delete a link between an organization and a service
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
     *   section = "Link",
     *   resource = true,
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when the link is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4", "service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="link id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher
     * @param integer               $id           Link id
     */
    public function deleteLinksAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller').'] ';
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl.'Called with id='.$id.' by '.$p->getFedid());
        /** @var Link $link */
        $link = $this->eh->get('Link', $id, $loglbl);

        // Set affected entity for Hook
        $request->attributes->set(
          '_attributeChangeAffectedEntity',
          array(
            "entity"    => "Organization",
            "id"        => array($link->getOrganization()->getId()),
            'serviceId' => $link->getService()->getId(),
          )
        );

        $entitlementsToRemove = $link->getEntitlements();

        foreach ($link->getEntitlementPacks() as $entitlementPack) {
            foreach ($entitlementPack->getEntitlements() as $entitlement) {
                if (!$entitlementsToRemove->contains($entitlement)) {
                    $entitlementsToRemove->add($entitlement);
                }
            }
        }

        foreach ($entitlementsToRemove as $e) {
            /** @var Role $r */
            foreach ($link->getOrganization()->getRoles() as $r) {
                if ($r->hasEntitlement($e)) {
                    $r->removeEntitlement($e);
                }
                $this->em->persist($r);
            }
        }

        $this->em->remove($link);


        //Create News object to notify the user
        $n = new News();
        $n->setOrganization($link->getOrganization());
        $n->setService($link->getService());
        $n->setTitle("Service unlinked");
        $n->setMessage(
          "A service named ".$link->getService()->getName()." has been unlinked from organization "
          .$link->getOrganization()->getName()
        );
        $n->setTag("organization_service");
        $this->em->persist($n);

        $this->modlog->info(
          $loglbl."Service (id=".$link->getService()->getId().") link with Organization (id=".$id.") was deleted"
        );
        $this->em->flush();
        $this->modlog->info($loglbl."Created News object with id=".$n->getId()." about ".$n->getTitle());
    }

    /**
     * link service to organization by token
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
     *   section = "Organization",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when successfully created new link",
     *     204 = "Returned when successfully modified link",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="token", "dataType"="string", "required"=true, "requirement"="\d+", "description"="link token"},
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
    public function putOrganizationsLinksTokenAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0,
      $token = "nullToken"
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." and token=".$token." by ".$p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);

        /** @var LinkerToken $linkerToken */
        $linkerToken = $this->em->getRepository('HexaaStorageBundle:LinkerToken')->findOneByToken($token);
        if (!$linkerToken) {
            $this->errorlog->error($loglbl."The requested linkerToken (".$token.") was not found");
            throw new HttpException(404, "Token not found");
        }

        /** @var Link $link */
        $link = $linkerToken->getLink();

        if (!$link->getService()->getIsEnabled()) {
            $this->errorlog->error($loglbl."Service ".$link->getService()->getName()." is not enabled, can't link it.");
            throw new HttpException(400, "Service ".$link->getService()->getName()." is not enabled.");
        }

        $link->setStatus("accepted");
        $statusCode = 201;
        $this->em->remove($linkerToken);
        $this->em->persist($link);

        //Create News object to notify the user
        $n = new News();
        $n->setOrganization($o);
        $n->setService($link->getService());
        $n->setTitle("Service linked");
        $n->setMessage("A service named ".$link->getService()->getName()." has been linked to organization ".$o->getName());
        $n->setTag("organization_service");
        $this->em->persist($n);
        $this->em->flush();
        $this->modlog->info($loglbl."Created News object with id=".$n->getId()." about ".$n->getTitle());

        $this->modlog->info(
          $loglbl."Service (id=".$link->getService()->getId(
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
                array('id' => $link->getId()),
                UrlGeneratorInterface::ABSOLUTE_URL // absolute
              )
            );
        }

        return $response;
    }

    /**
     * List entitlements of the link
     *
     * Note: this list will not include entitlements from the entitlement packs of the link
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
     *   section = "Link",
     *   description = "list entitlements of the link",
     *   resource = false,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when link is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4", "service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="link id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement pack
     * @param integer               $id           Link id
     *
     * @return array
     */
    public function cgetLinkEntitlementsAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        /** @var Link $link */
        $link = $this->eh->get('Link', $id, $loglbl, true);

        $items = $this->em->createQueryBuilder()
          ->select('entitlement')
          ->from('HexaaStorageBundle:Entitlement', 'entitlement')
          ->where(':link MEMBER OF entitlement.links')
          ->setParameter(':link', $link)
          ->setFirstResult($paramFetcher->get('offset'))
          ->setMaxResults($paramFetcher->get('limit'))
          ->getQuery()
          ->getResult();

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $this->em->createQueryBuilder()
              ->select('COUNT(entitlement.id)')
              ->from('HexaaStorageBundle:Entitlement', 'entitlement')
              ->where(':link MEMBER OF entitlement.links')
              ->setParameter(':link', $link)
              ->getQuery()
              ->getSingleScalarResult();

            return array('item_number' => $itemNumber, 'items' => $items);
        }

        return $items;
    }

    /**
     * List entitlement packs of the link
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
     *   section = "Link",
     *   description = "list entitlement packs of the link",
     *   resource = false,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when link is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4", "service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="link id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement pack
     * @param integer               $id           Link id
     *
     * @return array
     */
    public function cgetLinkEntitlementpacksAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        /** @var Link $link */

        /** @var Link $link */
        $link = $this->eh->get('Link', $id, $loglbl, true);

        $items = $this->em->createQueryBuilder()
          ->select('entitlementPack')
          ->from('HexaaStorageBundle:EntitlementPack', 'entitlementPack')
          ->where(':link MEMBER OF entitlementPack.links')
          ->setParameter(':link', $link)
          ->setFirstResult($paramFetcher->get('offset'))
          ->setMaxResults($paramFetcher->get('limit'))
          ->getQuery()
          ->getResult();

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $itemNumber = $this->em->createQueryBuilder()
              ->select('COUNT(entitlementPack.id)')
              ->from('HexaaStorageBundle:EntitlementPack', 'entitlementPack')
              ->where(':link MEMBER OF entitlementPack.links')
              ->setParameter(':link', $link)
              ->getQuery()
              ->getSingleScalarResult();

            return array('item_number' => $itemNumber, 'items' => $items);
        }

        return $items;
    }
}