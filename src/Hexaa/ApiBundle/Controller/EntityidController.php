<?php

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
use Hexaa\StorageBundle\Form\EntityidRequestType;
use Hexaa\StorageBundle\Entity\EntityidRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class EntityidController extends FOSRestController {

    /**
     * List all existing and enabled service entityIDs from HEXAA config
     *
     *
     * @ApiDoc(
     *   section = "EntityID",
     *   description = "list service entityIDs",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
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
    public function cgetEntityidsAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[cgetEntityIDs] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        return $this->container->getParameter('hexaa_service_entityids');
    }

    /**
     * List all entityID requests for HEXAA admins, <br>
     * list all entityID requests of the current user for non-admins.
     *
     *
     * @ApiDoc(
     *   section = "EntityID",
     *   description = "list entityID requests",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
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
    public function cgetEntityidrequestsAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[cgetEntityIDrequests] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
            $er = $em->getRepository('HexaaStorageBundle:EntityidRequest')->findAll();
        } else {
            $er = $em->getRepository('HexaaStorageBundle:EntityidRequest')->findByRequester($p);
        }
        return $er;
    }

    /**
     * get entity request
     *
     *
     * @ApiDoc(
     *   section = "EntityID",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entityidRequest id"},
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
     * @return EntityidRequest
     */
    public function getEntityidrequestAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[getEntityIDrequest] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $er = $em->getRepository('HexaaStorageBundle:EntityidRequest')->find($id);
        if (!$er) {
            $errorlog->error($loglbl . "the requested EntityIDrequest with id=" . $id . " was not found");
            throw new HttpException(404, "EntityidRequest not found.");
            return;
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && $er->getRequester() !== $p) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        return $er;
    }

    private function processForm(EntityidRequest $er, $loglbl) {
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $statusCode = $er->getId() == null ? 201 : 204;

        $form = $this->createForm(new EntityidRequestType(), $er);
        $form->bind($this->getRequest());

        if ($form->isValid()) {
            if (201 === $statusCode) {
                $usr = $this->get('security.context')->getToken()->getUser();
                $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
                $er->setRequester($p);
            }
            $er->setStatus("pending");
            $em->persist($er);
            $em->flush();

            if (201 === $statusCode) {
                $modlog->info($loglbl . "New EntityID request has been created with id=" . $id);
            } else {
                $modlog->info($loglbl . "EntityID request has been edited with id=" . $id);
            }

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_organization', array('id' => $er->getId()), true // absolute
                        )
                );
            }

            return $response;
        }
        $errorlog->error($loglbl . "Validation error");
        return View::create($form, 400);
    }

    /**
     * create new entityid request
     *
     *
     * @ApiDoc(
     *   section = "EntityID",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when entityid request has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   requirements = {
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="entityid","dataType"="string","required"=true,"description"="entityID to be requested"},
     *      {"name"="message","dataType"="string","required"=false,"description"="message to the HEXAA admin (metadata, etc.)"}
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
    public function postEntityidrequestAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[postEntityIDrequest] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        /* $em = $this->getDoctrine()->getManager();
          $s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
          if (!$s) throw new HttpException(404, "Resource not found."); */

        return $this->processForm(new EntityidRequest(), $loglbl);
    }

    /**
     * edit entityid request preferences
     *
     *
     * @ApiDoc(
     *   section = "EntityID",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when entityid request has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entityid request id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   parameters = {
     *      {"name"="entityid","dataType"="string","required"=true,"description"="entityID to be requested"},
     *      {"name"="message","dataType"="string","required"=false,"description"="message to the HEXAA admin (metadata, etc.)"}
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
    public function putEntityidrequestAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[putEntityIDrequest] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $er = $em->getRepository('HexaaStorageBundle:EntityidRequest')->find($id);
        if (!$er) {
            $errorlog->error($loglbl . "the requested EntityIDrequest with id=" . $id . " was not found");
            throw new HttpException(404, "EntityidRequest not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$er->getRequester() !== $p) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        return $this->processForm($er, $loglbl);
    }

    /**
     * delete entityid request
     *
     *
     * @ApiDoc(
     *   section = "EntityID",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when entityid request has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entityid request id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
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
    public function deleteEntityidrequestAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[deleteEntityIDrequest] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $er = $em->getRepository('HexaaStorageBundle:EntityidRequest')->find($id);
        if (!$er) {
            $errorlog->error($loglbl . "the requested EntityIDrequest with id=" . $id . " was not found");
            throw new HttpException(404, "EntityidRequest not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$er->getRequester() !== $p) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
        } else {
            $em->remove($er);
            $em->flush();
            $modlog->info($loglbl . "EntityID request (id=" . $id . ") has been deleted");
        }
    }

    /**
     * Mark entityID request as accepted
     *
     *
     * @ApiDoc(
     *   section = "EntityID",
     *   description = "accept entity request",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entityidRequest id"},
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
     * @return EntityidRequest
     */
    public function getEntityidrequestAcceptAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[getEntityIDrequestAccept] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $er = $em->getRepository('HexaaStorageBundle:EntityidRequest')->find($id);
        if ($request->getMethod() == "GET" && !$er) {
            $errorlog->error($loglbl . "the requested EntityIDrequest with id=" . $id . " was not found");
            throw new HttpException(404, "EntityidRequest not found.");
            return;
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        $er->setStatus("accepted");
        $em->persist($er);
        $em->flush();
        $modlog->info($loglbl . "EntityID request (id=" . $id . ") has been marked as accepted");
        return $er;
    }

    /**
     * Mark entityID request as rejected
     *
     *
     * @ApiDoc(
     *   section = "EntityID",
     *   description = "reject entity request",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entityidRequest id"},
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
     * @return EntityidRequest
     */
    public function getEntityidrequestRejectAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[getEntityIDrequestReject] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $er = $em->getRepository('HexaaStorageBundle:EntityidRequest')->find($id);
        if ($request->getMethod() == "GET" && !$er) {
            $errorlog->error($loglbl . "the requested EntityIDrequest with id=" . $id . " was not found");
            throw new HttpException(404, "EntityidRequest not found.");
            return;
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        $er->setStatus("rejected");
        $em->persist($er);
        $em->flush();
        $modlog->info($loglbl . "EntityID request (id=" . $id . ") has been marked as rejected");
        return $er;
    }

}