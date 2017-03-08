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


use Doctrine\Common\Collections\ArrayCollection;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\View;
use Hexaa\ApiBundle\Annotations\InvokeHook;
use Hexaa\StorageBundle\Entity\News;
use Hexaa\StorageBundle\Entity\Organization;
use Hexaa\StorageBundle\Entity\Principal;
use Hexaa\StorageBundle\Entity\Tag;
use Hexaa\StorageBundle\Form\OrganizationType;
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
class OrganizationController extends HexaaController implements ClassResourceInterface, PersonalAuthenticatedController
{

    /**
     * Lists all organization, where the user is at least a member.
     * Lists all organizations if the user is a HEXAA admin
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
    public function cgetAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called by ".$p->getFedid());


        if ($request->attributes->has("_security.level") && $request->attributes->get("_security.level") === "admin") {
            $os = $this->em->getRepository('HexaaStorageBundle:Organization')->findBy(
              array(),
              array('name' => 'ASC'),
              $paramFetcher->get('limit'),
              $paramFetcher->get('offset')
            );

            $itemNumber = $this->em->createQueryBuilder()
              ->select("COUNT(o.id)")
              ->from("HexaaStorageBundle:Organization", "o")
              ->getQuery()
              ->getSingleScalarResult();
        } else {
            $os = $this->em->createQueryBuilder()
              ->select('o')
              ->from('HexaaStorageBundle:Organization', 'o')
              ->innerJoin('o.principals', 'm')
              ->where(':p MEMBER OF o.principals')
              ->setParameter(':p', $p)
              ->setFirstResult($paramFetcher->get('offset'))
              ->setMaxResults($paramFetcher->get('limit'))
              ->orderBy("o.name", "ASC")
              ->getQuery()
              ->getResult();

            $itemNumber = $this->em->createQueryBuilder()
              ->select("COUNT(o.id)")
              ->from("HexaaStorageBundle:Organization", "o")
              ->innerJoin('o.principals', 'm')
              ->where(':p MEMBER OF o.principals')
              ->setParameter(':p', $p)
              ->getQuery()
              ->getSingleScalarResult();
        }

        if ($request->query->has('limit') || $request->query->has('offset')) {
            return array("item_number" => (int)$itemNumber, "items" => $os);
        } else {
            return $os;
        }
    }

    /**
     * get organization where the user is at least a member
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
     * @param integer               $id           Organization id
     *
     * @return Organization
     */
    public function getAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);

        return $o;
    }

    /**
     * create new organization
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
     *      {"name"="isolate_members","dataType"="boolean","required"=false,"description"="sets wether Organization members can list the Organization members or not"},
     *      {"name"="isolate_role_members","dataType"="boolean","required"=false,"description"="sets wether the Role members of the Organization can list the members of their Role or not"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"},
     *      {"name"="tags", "dataType"="array", "required"=false, "description"="array of tags to append to service"}
     *   }
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
    public function postAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called by ".$p->getFedid());

        $o = new Organization();

        $sd = $this->em->getRepository('HexaaStorageBundle:SecurityDomain')->findOneBy(
          array("scopedKey" => $p->getToken()->getMasterKey())
        );
        if ($sd) {
            $o->addSecurityDomain($sd);
        }

        return $this->processForm($o, $loglbl, $request, "POST");
    }

    private function processForm(Organization $o, $loglbl, Request $request, $method = "PUT")
    {
        $statusCode = $o->getId() == null ? 201 : 204;
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();

        if ($request->request->has("tags")) {
            $tags = $request->request->get('tags');
            if (!is_array($tags)) {
                $this->errorlog->error($loglbl."Tags must be an array if given.");
                throw new HttpException(400, "Tags must be an array if given.");
            }
            $request->request->remove("tags");
        }

        $form = $this->createForm(new OrganizationType(), $o, array("method" => $method));
        $form->submit($request->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            if (isset($tags)) {
                $oldTags = $o->getTags()->toArray();
                /* @var $tag Tag
                 * Remove old tags (and delete them if they are not in use anymore)
                 */
                foreach ($oldTags as $tag) {
                    if (!in_array($tag->getName(), $tags)) {
                        $o->removeTag($tag);
                        if ($tag->getOrganizations()->isEmpty() && $tag->getServices()->isEmpty()) {
                            $this->em->remove($tag);
                        }
                    }
                }
                /* Add new tags (create them if necessary) */
                foreach ($tags as $tagName) {
                    $tag = $this->em->getRepository("HexaaStorageBundle:Tag")->findOneBy(array("name" => $tagName));
                    if ($tag == null) {
                        $tag = new Tag($tagName);
                        $this->em->persist($tag);
                    }
                    if (!$o->hasTag($tag)) {
                        $o->addTag($tag);
                    }
                }

            }
            if (201 === $statusCode) {
                $o->addManager($p);
            } else {
                $uow = $this->em->getUnitOfWork();
                $uow->computeChangeSets();
                $changeSet = $uow->getEntityChangeSet($o);
            }
            $this->em->persist($o);

            //Create News object to notify the user
            $n = new News();
            $n->setOrganization($o);
            $n->setPrincipal($p);
            if ($method == "POST") {
                $n->setTitle("New Organization created");
                $n->setMessage($p->getFedid()." has created a new organization named ".$o->getName());
            } else {
                $changedFields = "";
                /** @noinspection PhpUndefinedVariableInspection */
                foreach (array_keys($changeSet) as $fieldName) {
                    if ($changedFields == "") {
                        $changedFields = $fieldName;
                    } else {
                        $changedFields = $changedFields.", ".$fieldName;
                    }
                }
                $n->setTitle("Organization modified");
                $n->setMessage(
                  $p->getFedid()." has modified organization named ".$o->getName().". Changed fields: ".$changedFields."."
                );
            }
            $n->setTag("organization");
            $this->em->persist($n);
            $this->em->flush();
            $this->modlog->info($loglbl."Created News object with id=".$n->getId()." about ".$n->getTitle());


            if (201 === $statusCode) {
                $this->modlog->info($loglbl."New Organization created with id=".$o->getId());
            } else {
                $this->modlog->info($loglbl."Organization edited with id=".$o->getId().", changed fields: ".$changedFields.".");
            }


            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set(
                  'Location',
                  $this->generateUrl(
                    'get_organization',
                    array('id' => $o->getId()),
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
     * edit organization preferences
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
     *      {"name"="isolate_members","dataType"="boolean","required"=false,"description"="sets wether Organization members can list the Organization members or not"},
     *      {"name"="isolate_role_members","dataType"="boolean","required"=false,"description"="sets wether the Role members of the Organization can list the members of their Role or not"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"},
     *      {"name"="tags", "dataType"="array", "required"=false, "description"="array of tags to append to service"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Organization id
     *
     *
     * @return View|Response
     */
    public function putAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);

        return $this->processForm($o, $loglbl, $request, "PUT");
    }

    /**
     * edit organization preferences
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
     *      {"name"="isolate_members","dataType"="boolean","required"=false,"description"="sets wether Organization members can list the Organization members or not"},
     *      {"name"="isolate_role_members","dataType"="boolean","required"=false,"description"="sets wether the Role members of the Organization can list the members of their Role or not"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"},
     *      {"name"="tags", "dataType"="array", "required"=false, "description"="array of tags to append to service"}
     *   }
     * )
     *
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     * @param integer               $id           Organization id
     *
     *
     * @return View|Response
     */
    public function patchAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);

        return $this->processForm($o, $loglbl, $request, "PATCH");
    }

    /**
     * delete organization
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
     * @param integer               $id           Organization id
     *
     *
     */
    public function deleteAction(
      Request $request,
      /** @noinspection PhpUnusedParameterInspection */
      ParamFetcherInterface $paramFetcher,
      $id = 0
    ) {
        $loglbl = "[".$request->attributes->get('_controller')."] ";
        /** @var Principal $p */
        $p = $this->get('security.token_storage')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl."Called with id=".$id." by ".$p->getFedid());

        $o = $this->eh->get('Organization', $id, $loglbl);

        $pIds = array();

        // Create News objects to notify members
        foreach ($o->getPrincipals() as $member) {
            $n = new News();
            $n->setPrincipal($member);
            $n->setTitle("Organization deleted");
            $n->setMessage($p->getFedid()." has deleted organization ".$o->getName()." that you were a member of. ");
            $n->setTag("organization");
            $this->em->persist($n);

            $this->modlog->info($loglbl."Created News object with id=".$n->getId()." about ".$n->getTitle());

            $pIds[] = $member->getId();
        }


        // Set affected entity for Hook
        $request->attributes->set(
          '_attributeChangeAffectedEntity',
          array("entity" => "Principal", "id" => $pIds)
        );


        if ($o->getDefaultRole() != null) {
            $o->setDefaultRole(null);
        }
        $this->em->persist($o);
        $this->em->flush();
        $this->em->remove($o);
        $this->em->flush();
        $this->modlog->info($loglbl."Organization with id=".$id." deleted");
    }

}
