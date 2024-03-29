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
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Hexaa\ApiBundle\Annotations\InvokeHook;
use Hexaa\StorageBundle\Entity\EntitlementPack;
use Hexaa\StorageBundle\Entity\Link;
use Hexaa\StorageBundle\Entity\News;
use Hexaa\StorageBundle\Entity\Principal;
use Hexaa\StorageBundle\Entity\Role;
use Hexaa\StorageBundle\Form\EntitlementPackEntitlementType;
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
class EntitlementpackEntitlementController extends HexaaController implements PersonalAuthenticatedController
{

    /**
     * get entitlements of entitlement pack
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
     *   section = "EntitlementPack",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement is not found"
     *   },
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement pack id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           EntitlementPack id
     *
     * @return array
     */
    public function cgetEntitlementsAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        $ep = $this->eh->get('EntitlementPack', $id, $loglbl);

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $e = array_slice(
              $ep->getEntitlements()->toArray(),
              $paramFetcher->get('offset'),
              $paramFetcher->get('limit')
            );

            return array("item_number" => (int)count($ep->getEntitlements()), "items" => $e);
        } else {
            return $ep->getEntitlements();
        }
    }

    /**
     * remove entitlement from entitlement pack
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
     *     entity="Entitlement",
     *     id="eid",
     *     source="attributes"
     *     )
     *
     * @ApiDoc(
     *   section = "EntitlementPack",
     *   resource = true,
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement pack id"},
     *      {"name"="eid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           EntitlementPack id
     * @param integer               $eid          Entitlement id
     *
     */
    public function deleteEntitlementAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0,
      $eid = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." and eid=".$eid." by ".$p->getFedid());

        $ep = $this->eh->get('EntitlementPack', $id, $loglbl);
        $e = $this->eh->get('Entitlement', $eid, $loglbl);

        if ($ep->hasEntitlement($e)) {
            // get affected entity for hook
            $request->attributes->set(
              '_attributeChangeAffectedEntity',
              array(
                "entity"    => "Organization",
                "id"        => $this->em->getRepository('HexaaStorageBundle:Organization')->getIdsByEntitlementPack($ep),
                'serviceId' => $e->getServiceId(),
              )
            );


            $links = $this->em->createQueryBuilder()
              ->select('link')
              ->from('HexaaStorageBundle:Link', 'link')
              ->where(':ep MEMBER OF link.entitlementPacks')
              ->andWhere("link.status = 'accepted'")
              ->setParameter(':ep', $ep)
              ->getQuery()
              ->getResult();

            /** @var Link $link */
            foreach ($links as $link) {
                if (!$link->hasEntitlement($e, $ep)) {
                    // Link loses the entitlement
                    $roles = $this->em->createQueryBuilder()
                      ->select('r')
                      ->from('HexaaStorageBundle:Role', 'r')
                      ->where(':e MEMBER OF r.entitlements')
                      ->andWhere('r.organization = :o')
                      ->setParameters(array(":e" => $e, ":o" => $link->getOrganization()))
                      ->getQuery()
                      ->getResult();

                    /** @var Role $r */
                    foreach ($roles as $r) {
                        $r->removeEntitlement($e);
                        $this->em->persist($r);
                    }
                }
            }


            $ep->removeEntitlement($e);
            $this->em->persist($ep);
            $this->em->flush();

            $this->modlog->info($loglbl."Entitlement (id=".$eid.") has been removed from Entitlement Pack with id=".$id);
        }
    }

    /**
     * add entitlement to entitlement pack
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
     *     entity="Entitlement",
     *     id="eid",
     *     source="attributes"
     *     )
     *
     * @ApiDoc(
     *   section = "EntitlementPack",
     *   resource = true,
     *   statusCodes = {
     *     201 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement pack id"},
     *      {"name"="eid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     *
     * @Annotations\View(statusCode=201)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           EntitlementPack id
     * @param integer               $eid          Entitlement id
     *
     */
    public function putEntitlementsAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0,
      $eid = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." and eid=".$eid." by ".$p->getFedid());

        $ep = $this->eh->get('EntitlementPack', $id, $loglbl);
        $e = $this->eh->get('Entitlement', $eid, $loglbl);
        if (!$ep->hasEntitlement($e)) {
            $ep->addEntitlement($e);
            $this->em->persist($ep);
            $this->em->flush();

            $this->modlog->info($loglbl."Entitlement (id=".$eid.") has been added to Entitlement Pack with id=".$id);
        }
    }

    /**
     * set entitlements of an entitlement package
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
     *     entity="EntitlementPack",
     *     id="id",
     *     source="attributes"
     *     )
     *
     * @ApiDoc(
     *   section = "EntitlementPack",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when successful",
     *     204 = "Returned when entitlements are already added",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when role is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *     {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement pack id"},
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   input = "Hexaa\StorageBundle\Form\EntitlementPackEntitlementType"
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           EntitlementPack id
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

        $ep = $this->eh->get('EntitlementPack', $id, $loglbl);

        return $this->processEPEForm($ep, $loglbl, $request, "PUT");
    }

    private function processEPEForm(EntitlementPack $ep, $loglbl, Request $request, $method = "PUT")
    {
        $store = $ep->getEntitlements()->toArray();

        $form = $this->createForm(EntitlementPackEntitlementType::class, $ep, array("method" => $method));
        $form->submit($request->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $statusCode = $store === $ep->getEntitlements()->toArray() ? 204 : 201;
            //Create News object to notify the user
            $removed = array_diff($store, $ep->getEntitlements()->toArray());
            $added = array_diff($ep->getEntitlements()->toArray(), $store);
            if (count($added) > 0) {
                $msg = "New entitlements added: ";
                foreach ($added as $addedE) {
                    $msg = $msg.$addedE->getName().", ";
                }
            } else {
                $msg = "No new entitlements added, ";
            }
            if (count($removed) > 0) {
                $msg = $msg."entitlements removed: ";
                foreach ($removed as $removedE) {
                    $msg = $msg.$removedE->getName().', ';
                }


                //Remove entitlements from roles if necessary
                $links = $this->em->createQueryBuilder()
                  ->select('link')
                  ->from('HexaaStorageBundle:Link', 'link')
                  ->where(':ep MEMBER OF link.entitlementPacks')
                  ->andWhere("link.status = 'accepted'")
                  ->setParameter(':ep', $ep)
                  ->getQuery()
                  ->getResult();

                /** @var Link $link */
                foreach ($links as $link) {
                    foreach ($removed as $removedEntitlement) {
                        if (!$link->hasEntitlement($removedEntitlement, $ep)) {
                            // Link loses the removedEntitlement
                            $roles = $this->em->createQueryBuilder()
                              ->select('r')
                              ->from('HexaaStorageBundle:Role', 'r')
                              ->where(':e MEMBER OF r.entitlements')
                              ->andWhere('r.organization = :o')
                              ->setParameters(array(":e" => $removedEntitlement, ":o" => $link->getOrganization()))
                              ->getQuery()
                              ->getResult();

                            /** @var Role $r */
                            foreach ($roles as $r) {
                                $r->removeEntitlement($removedEntitlement);
                                $this->em->persist($r);
                            }
                        }
                    }
                }
            } else {
                $msg = $msg."no entitlements removed. ";
            }
            $msg[strlen($msg) - 2] = '.';

            $n = new News();
            $n->setService($ep->getService());
            $n->setTitle("EntitlementPack entitlements changed");
            $n->setMessage($ep->getScopedName().': '.$msg);
            $n->setTag("service_manager");
            $this->em->persist($n);
            $this->em->persist($ep);

            $this->em->flush();

            $this->modlog->info($loglbl."Created News object with id=".$n->getId()." about ".$n->getTitle());

            $ids = "[ ";
            foreach ($ep->getEntitlements() as $e) {
                $ids = $ids.$e->getId().", ";
            }
            $ids = substr($ids, 0, strlen($ids) - 2)." ]";
            $this->modlog->info($loglbl."Entitlements of EntitlementPack with id=".$ep->getId()." has been set to ".$ids);

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set(
                  'Location',
                  $this->generateUrl(
                    'get_entitlementpack',
                    array('id' => $ep->getId()),
                    UrlGeneratorInterface::ABSOLUTE_URL // absolute
                  )
                );
            }

            return $response;
        }
        $this->errorlog->error(
          $loglbl."Validation error: \n".$this->get('jms_serializer')->serialize(
            $form->getErrors(
              false,
              true
            ),
            "json"
          )
        );

        return View::create($form, 400);
    }

}
