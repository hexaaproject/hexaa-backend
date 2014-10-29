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

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\RouteRedirectView;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Hexaa\StorageBundle\Form\ServiceType;
use Hexaa\StorageBundle\Form\ServiceLogoType;
use Hexaa\StorageBundle\Entity\Service;
use Hexaa\StorageBundle\Entity\News;
use Hexaa\StorageBundle\Entity\ServicePage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class ServiceController extends FOSRestController implements ClassResourceInterface, PersonalAuthenticatedController {

    /**
     * Lists all services, where the user is a manager.
     * Lists all services if the user is a HEXAA admin
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
     * 
     * @ApiDoc(
     *   section = "Service",
     *   description = "list services where the user is a manager",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     204 = "Returned when no service is connected to the user",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   output="array<Hexaa\StorageBundle\Entity\Service>"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return Service
     */
    public function cgetAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        if (in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
            $ss = $em->getRepository('HexaaStorageBundle:Service')->findBy(array(), array('name' => 'ASC'), $paramFetcher->get('limit'), $paramFetcher->get('offset'));
        } else {
            $ss = $em->createQueryBuilder()
                    ->select('s')
                    ->from('HexaaStorageBundle:Service', 's')
                    ->where(':p MEMBER OF s.managers')
                    ->setParameter('p', $p)
                    ->setFirstResult($paramFetcher->get('offset'))
                    ->setMaxResults($paramFetcher->get('limit'))
                    ->orderBy("s.name", "ASC")
                    ->getQuery()
                    ->getResult()
            ;
        }
        return $ss;
    }

    /**
     * get service preferences
     *
     *
     * @ApiDoc(
     *   section = "Service",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   output="Hexaa\StorageBundle\Entity\Service"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return Service
     */
    public function getAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $eh = $this->get('hexaa.handler.entity_handler');
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $eh->get('Service', $id, $loglbl);

        return $s;
    }

    private function processForm(Service $s, $loglbl, $method = "PUT") {
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $statusCode = $s->getId() == null ? 201 : 204;

        $form = $this->createForm(new ServiceType(), $s, array("method" => $method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            if (201 === $statusCode) {
                $usr = $this->get('security.context')->getToken()->getUser();
                $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
                $s->addManager($p);
            }
            $em->persist($s);

            //Create News object to notify the user
            $n = new News();
            $n->setService($s);
            $n->setPrincipal($p);
            if ($method == "POST") {
                $n->setTitle("New Service created");
                $n->setMessage("A new service named " . $s->getName() . " has been created");
            } else {
                $n->setTitle("Service modified");
                $n->setMessage("Service named " . $s->getName() . " has been modified");
            }
            $n->setTag("service");
            $em->persist($n);
            $em->flush();
            $modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());


            if (201 === $statusCode) {
                $modlog->info($loglbl . "New Service created with id=" . $s->getId());
            } else {
                $modlog->info($loglbl . "Service edited with id=" . $s->getId());
            }

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_service', array('id' => $s->getId()), true // absolute
                        )
                );
            }

            if (201 === $statusCode) {
                $this->sendNotifyAdminEmail($s, $loglbl);
            }

            return $response;
        }
        $errorlog->error($loglbl . "Validation error");
        return View::create($form, 400);
    }

    /**
     * create new service
     *
     *
     * @ApiDoc(
     *   section = "Service",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when service has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     *   requirements = {
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *     {"name"="name", "dataType"="string", "required"=true, "description"="service name"},
     *     {"name"="entityid", "dataType"="string", "required"=true, "description"="service entity id"},
     *     {"name"="url", "dataType"="string", "required"=false, "description"="service url"},
     *     {"name"="description", "dataType"="string", "required"=false, "description"="service description"},
     *     {"name"="org_name", "dataType"="string", "required"=false, "description"="name of the organization providing the service"},
     *     {"name"="org_short_name", "dataType"="string", "required"=false, "description"="short name of the organization providing the service"},
     *     {"name"="org_url", "dataType"="string", "required"=false, "description"="home page of the organization providing the service"},
     *     {"name"="org_description", "dataType"="string", "required"=false, "description"="description of the organization providing the service"},
     *     {"name"="priv_url", "dataType"="string", "required"=false, "description"="service privacy policy URL"},
     *     {"name"="priv_description", "dataType"="string", "required"=false, "description"="short abstract of the privacy policy"}
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
     */
    public function postAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        return $this->processForm(new Service(), $loglbl, "POST");
    }

    /**
     * edit service preferences
     *
     *
     * @ApiDoc(
     *   section = "Service",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when service has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *     {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *     {"name"="name", "dataType"="string", "required"=true, "description"="service name"},
     *     {"name"="entityid", "dataType"="string", "required"=true, "description"="service entity id"},
     *     {"name"="url", "dataType"="string", "required"=false, "description"="service url"},
     *     {"name"="description", "dataType"="string", "required"=false, "description"="service description"},
     *     {"name"="org_name", "dataType"="string", "required"=false, "description"="name of the organization providing the service"},
     *     {"name"="org_short_name", "dataType"="string", "required"=false, "description"="short name of the organization providing the service"},
     *     {"name"="org_url", "dataType"="string", "required"=false, "description"="home page of the organization providing the service"},
     *     {"name"="org_description", "dataType"="string", "required"=false, "description"="description of the organization providing the service"},
     *     {"name"="priv_url", "dataType"="string", "required"=false, "description"="service privacy policy URL"},
     *     {"name"="priv_description", "dataType"="string", "required"=false, "description"="short abstract of the privacy policy"}
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
     */
    public function putAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $eh = $this->get('hexaa.handler.entity_handler');
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $eh->get('Service', $id, $loglbl);
        return $this->processForm($s, $loglbl, "PUT");
    }

    /**
     * edit service preferences
     *
     *
     * @ApiDoc(
     *   section = "Service",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when service has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *     {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *     {"name"="name", "dataType"="string", "required"=true, "description"="service name"},
     *     {"name"="entityid", "dataType"="string", "required"=true, "description"="service entity id"},
     *     {"name"="url", "dataType"="string", "required"=false, "description"="service url"},
     *     {"name"="description", "dataType"="string", "required"=false, "description"="service description"},
     *     {"name"="org_name", "dataType"="string", "required"=false, "description"="name of the organization providing the service"},
     *     {"name"="org_short_name", "dataType"="string", "required"=false, "description"="short name of the organization providing the service"},
     *     {"name"="org_url", "dataType"="string", "required"=false, "description"="home page of the organization providing the service"},
     *     {"name"="org_description", "dataType"="string", "required"=false, "description"="description of the organization providing the service"},
     *     {"name"="priv_url", "dataType"="string", "required"=false, "description"="service privacy policy URL"},
     *     {"name"="priv_description", "dataType"="string", "required"=false, "description"="short abstract of the privacy policy"}
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
     */
    public function patchAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $eh = $this->get('hexaa.handler.entity_handler');
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $eh->get('Service', $id, $loglbl);
        return $this->processForm($s, $loglbl, "PATCH");
    }

    /**
     * delete service
     *
     *
     * @ApiDoc(
     *   section = "Service",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when service has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *     {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     * 
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * 
     */
    public function deleteAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $eh = $this->get('hexaa.handler.entity_handler');
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $eh->get('Service', $id, $loglbl);
        $em->remove($s);
        $em->flush();
        $modlog->info($loglbl . "Service with id=" . $id . " deleted");
    }

    /**
     * Upload a service logo<br><br>
     * 
     * The uploaded image must be less than 6MB, and its size must be between 150x150 and 400x400.
     *
     * @ApiDoc(
     *   section = "Service",
     *   resource = false,
     *   description = "put service logo",
     *   statusCodes = {
     *     204 = "Returned when service has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     *   tags = {"service manager" = "#4180B4"},
     *   requirements ={
     *     {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *     {"name"="logo", "dataType"="file", "required"=false, "description"="service provider logo"}
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
     */
    public function postLogoAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $eh = $this->get('hexaa.handler.entity_handler');
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $eh->get('Service', $id, $loglbl);
        return $this->processLogoForm($s, $loglbl, "POST");
    }

    private function processLogoForm(Service $s, $loglbl, $method = "PUT") {
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $statusCode = $s->getId() == null ? 201 : 204;

        $form = $this->createForm(new ServiceLogoType(), $s, array("method" => $method));
        $form->submit($this->getRequest()->files->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $em->persist($s);

            //Create News object to notify the user
            $n = new News();
            $n->setService($s);
            $n->setTitle("Service logo modified");
            $n->setMessage("Logo of Service named " . $s->getName() . " has been modified");
            $n->setTag("service");
            $em->persist($n);
            $em->flush();
            $modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());


            if (201 === $statusCode) {
                $modlog->info($loglbl . "New Service created with id=" . $s->getId());
            } else {
                $modlog->info($loglbl . "Service edited with id=" . $s->getId());
            }

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_service', array('id' => $s->getId()), true // absolute
                        )
                );
            }

            return $response;
        }
        $errorlog->error($loglbl . "Validation error");
        return View::create($form, 400);
    }

    private function sendNotifyAdminEmail(Service $s, $loglbl) {
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $maillog = $this->get('monolog.logger.email');
        $baseUrl = $this->getRequest()->getHttpHost() . $this->getRequest()->getBasePath();
        $entityids = $this->container->getParameter('hexaa_service_entityids');
        $mails = array_keys($entityids[$s->getEntityid()]);
        foreach ($mails as $email) {
            $message = \Swift_Message::newInstance()
                    ->setSubject('[hexaa] ' . $this->get('translator')->trans('Request for HEXAA Service approval'))
                    ->setFrom('hexaa@' . $baseUrl)
                    ->setBody(
                    $this->renderView(
                            'HexaaApiBundle:Default:ServiceNotify.html.twig', array(
                        'to' => $email,
                        'creator' => $p,
                        'url' => $this->container->getParameter('hexaa_ui_url') . "/enable_service.php",
                        'service' => $s,
                            )
                    ), "text/html"
            );
            $message->setTo($email);

            $this->get('mailer')->send($message);
            $maillog->info($loglbl . "E-mail sent to " . $email);
        }
    }

    /**
     * enable service
     *
     *
     * @ApiDoc(
     *   section = "Service",
     *   resource = true,
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     *   requirements ={
     *      {"name"="token", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service enable token"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   output="Hexaa\StorageBundle\Entity\Service"
     * )
     *
     * 
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return Service
     */
    public function getEnableAction(Request $request, ParamFetcherInterface $paramFetcher, $token = "nullToken") {
        $loglbl = $request->attributes->get('_controller');
        $accesslog = $this->get('monolog.logger.access');
        $modlog = $this->get('monolog.logger.modification');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with token=" . $token . " by " . $p->getFedid());

        $s = $em->getRepository('HexaaStorageBundle:Service')->findOneByEnableToken($token);
        if (!$s) {
            $errorlog->error($loglbl . "the requested Service with id=" . $id . " was not found");
            throw new HttpException(404, "Service not found.");
        }

        $s->setIsEnabled(true);

        $em->persist($s);
        $em->flush();
        $modlog->info($loglbl . 'Service with id=' . $s->getId() . ' has been enabled.');
    }

}
