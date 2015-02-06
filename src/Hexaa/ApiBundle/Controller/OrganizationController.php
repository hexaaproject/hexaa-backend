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


use FOS\RestBundle\Routing\ClassResourceInterface;

use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Request\ParamFetcherInterface;

use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Hexaa\StorageBundle\Form\OrganizationType;
use Hexaa\StorageBundle\Entity\Organization;
use Hexaa\StorageBundle\Entity\News;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class OrganizationController extends HexaaController implements ClassResourceInterface, PersonalAuthenticatedController {

    /**
     * Lists all organization, where the user is at least a member.
     * Lists all organizations if the user is a HEXAA admin
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
     * 
     * @ApiDoc(
     *   section = "Organization",
     *   description = "list organization where user is at least a member",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     204 = "Returned when no organization is connected to the user",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\Organization>"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return Organization
     */
    public function cgetAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());


        if (in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
            $os = $this->em->getRepository('HexaaStorageBundle:Organization')->findBy(array(), array('name' => 'ASC'), $paramFetcher->get('limit'), $paramFetcher->get('offset'));

            $itemNumber = $this->em->createQueryBuilder()
                ->select("COUNT(o.id)")
                ->from("HexaaStorageBundle:Organization", "o")
                ->getQuery()
                ->getSingleScalarResult();
        } else {
            $os = $this->em->createQueryBuilder()
                    ->select('o')
                    ->from('HexaaStorageBundle:Organization', 'o')
                    ->where(':p MEMBER OF o.principals')
                    ->setParameter('p', $p)
                    ->setFirstResult($paramFetcher->get('offset'))
                    ->setMaxResults($paramFetcher->get('limit'))
                    ->orderBy("o.name", "ASC")
                    ->getQuery()
                    ->getResult()
            ;

            $itemNumber = $this->em->createQueryBuilder()
                ->select("COUNT(o.id)")
                ->from("HexaaStorageBundle:Organization", "o")
                ->where(':p MEMBER OF o.principals')
                ->setParameter('p', $p)
                ->getQuery()
                ->getSingleScalarResult();
        }
        return array("item_number" => $itemNumber, "items" => $os);
    }

    /**
     * get organization where the user is at least a member
     *
     *
     * @ApiDoc(
     *   section = "Organization",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when organization is not found"
     *   },
     *   tags = {"organization member" = "#5BA578", "service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   output="Hexaa\StorageBundle\Entity\Organization"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer $id Organization id
     *
     * @return Organization
     */
    public function getAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                              ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);
        return $o;
    }

    private function processForm(Organization $o, $loglbl, Request $request, $method = "PUT") {
        $statusCode = $o->getId() == null ? 201 : 204;
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();


        $form = $this->createForm(new OrganizationType(), $o, array("method" => $method));
        $form->submit($request->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            if (201 === $statusCode) {
                $o->addManager($p);
            } else {
                $uow = $this->em->getUnitOfWork();
                $uow->computeChangeSets(); // do not compute changes if inside a listener
                $changeSet = $uow->getEntityChangeSet($o);
            }
            $this->em->persist($o);

            //Create News object to notify the user
            $n = new News();
            $n->setOrganization($o);
            $n->setPrincipal($p);
            if ($method == "POST") {
                $n->setTitle("New Organization created");
                $n->setMessage($p->getFedid() . " has created a new organization named " . $o->getName());
            } else {
                $changedFields = "";
                foreach (array_keys($changeSet) as $fieldName) {
                    if ($changedFields == "") {
                        $changedFields = $fieldName;
                    } else {
                        $changedFields = $changedFields . ", " . $fieldName;
                    }
                }
                $n->setTitle("Organization modified");
                $n->setMessage($p->getFedid() . " has modified organization named " . $o->getName() . ". Changed fields: " . $changedFields . ".");
            }
            $n->setTag("organization");
            $this->em->persist($n);
            $this->em->flush();
            $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());


            if (201 === $statusCode) {
                $this->modlog->info($loglbl . "New Organization created with id=" . $o->getId());
            } else {
                $this->modlog->info($loglbl . "Organization edited with id=" . $o->getId() . ", changed fields: " . $changedFields . ".");
            }


            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_organization', array('id' => $o->getId()), true // absolute
                        )
                );
            }

            return $response;
        }
        $this->errorlog->error($loglbl . "Validation error: \n" . $this->get("serializer")->serialize($form->getErrors(false, true), "json"));
        return View::create($form, 400);
    }

    /**
     * create new organization
     *
     *
     * @ApiDoc(
     *   section = "Organization",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when organization has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when organization is not found"
     *   },
     *   requirements = {
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="name","dataType"="string","required"=true,"description"="displayable name of the organization"},
     *      {"name"="url","dataType"="string","required"=false,"description"="URL of VO web page"},
     *      {"name"="default_role","dataType"="integer","required"=false,"description"="id of the default role"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"},
     *      {"name"="tags", "dataType"="array", "required"=false, "description"="array of tags to append to service"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request $request the request object
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

        return $this->processForm(new Organization(), $loglbl, $request, "POST");
    }

    /**
     * edit organization preferences
     *
     *
     * @ApiDoc(
     *   section = "Organization",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when organization has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when organization is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="name","dataType"="string","required"=true,"description"="displayable name of the organization"},
     *      {"name"="url","dataType"="string","required"=false,"description"="URL of VO web page"},
     *      {"name"="default_role","dataType"="integer","required"=false,"description"="id of the default role"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request $request the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer $id Organization id
     *
     *
     * @return View|Response
     */
    public function putAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                              ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);
        return $this->processForm($o, $loglbl, $request, "PUT");
    }

    /**
     * edit organization preferences
     *
     *
     * @ApiDoc(
     *   section = "Organization",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when organization has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when organization is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="name","dataType"="string","required"=true,"description"="displayable name of the organization"},
     *      {"name"="url","dataType"="string","required"=false,"description"="URL of VO web page"},
     *      {"name"="default_role","dataType"="integer","required"=false,"description"="id of the default role"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request $request the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer $id Organization id
     *
     *
     * @return View|Response
     */
    public function patchAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);
        return $this->processForm($o, $loglbl, $request, "PATCH");
    }

    /**
     * delete organization
     *
     *
     * @ApiDoc(
     *   section = "Organization",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when organization has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when organization is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     * 
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer $id Organization id
     *
     * 
     */
    public function deleteAction(Request $request, /** @noinspection PhpUnusedParameterInspection */
                                 ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);

        // Create News objects to notify members
        foreach ($o->getPrincipals() as $member) {
            $n = new News();
            $n->setPrincipal($member);
            $n->setTitle("Organization deleted");
            $n->setMessage($p->getFedid() . " has deleted organization " . $o->getName() . " that you were a member of. ");
            $n->setTag("organization");
            $this->em->persist($n);

            $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());
        }


        if ($o->getDefaultRole() != null) {
            $o->setDefaultRole(null);
        }
        $this->em->persist($o);
        $this->em->flush();
        $this->em->remove($o);
        $this->em->flush();
        $this->modlog->info($loglbl . "Organization with id=" . $id . " deleted");
    }

}
