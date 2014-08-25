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
use Hexaa\StorageBundle\Form\AttributeValuePrincipalType;
use Hexaa\StorageBundle\Entity\AttributeValuePrincipal;
use Hexaa\StorageBundle\Form\AttributeValueOrganizationType;
use Hexaa\StorageBundle\Entity\AttributeValueOrganization;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class AttributevalueController extends FOSRestController {

    /**
     * get attribute value (for principal) details
     *
     *
     * @ApiDoc(
     *   section = "Attribute value (for principal)",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value (for principal) id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     *
     * @return Role
     */
    public function getAttributevalueprincipalAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[getAttributeValuePrincipal] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $asp = $em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->find($id);
        if (!$asp) {
            $errorlog->error($loglbl . "the requested attributeValuePrincipal with id=" . $id . " was not found");
            throw new HttpException(404, "Resource not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && $asp->getPrincipal() != $p) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        return $asp;
    }

    private function processAVPForm(AttributeValuePrincipal $avp, $loglbl) {
        $modlog = $this->get('monolog.logger.modification');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $statusCode = $avp->getId() == null ? 201 : 204;
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());

        if ($this->getRequest()->request->has('principal') && $this->getRequest()->request->get('principal') !== $p && !in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
            $errorlog->error($loglbl . "User " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }

        if (!$this->getRequest()->request->has('principal') || $this->getRequest()->request->get('principal') == null)
            $this->getRequest()->request->set("principal", $p->getId());



        $form = $this->createForm(new AttributeValuePrincipalType(), $avp);
        $form->bind($this->getRequest());

        if ($form->isValid()) {
            $em->persist($avp);
            $em->flush();

            if (201 === $statusCode) {
                $modlog->info($loglbl . "New attribute value (for principal) was created with id=" . $avp->getId());
            } else {
                $modlog->info($loglbl . "Attribute value (for principal) was edited with id=" . $avp->getId());
            }

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_attributevalueprincipal', array('id' => $avp->getId()), true // absolute
                        )
                );
            }
            return $response;
        }

        $errorlog->error($loglbl . "Validation error");
        return View::create($form, 400);
    }

    /**
     * edit attribute value (for principal) details
     * 
     * note: only HEXAA admins are allowed to add attributes for other than themselves.
     *
     *
     * @ApiDoc(
     *   section = "Attribute value (for principal)",
     *   resource = true,
     *   description = "edit attribute value (for principal) details",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value (for principal) id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  parameters = {
     *      {"name"="services","dataType"="array", "required"=false, "description"="IDs of Services to give the value to. If empty, the value will be given to all services."},
     *      {"name"="value", "dataType"="string", "required"=true, "description"="assigned value"},
     *      {"name"="attribute_spec", "dataType"="integer", "required"=true, "description"="attribute specification id"},
     *      {"name"="principal", "dataType"="integer", "required"=false, "description"="ID of principal. If left blank, it will default to self."},
     * 
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     *
     * @return AttributeValuePrincipal
     */
    public function putAttributevalueprincipalAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[putAttributeValuePrincipal] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $avp = $em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->find($id);
        if (!$avp) {
            $errorlog->error($loglbl . "the requested attributeValuePrincipal with id=" . $id . " was not found");
            throw new HttpException(404, "Resource not found.");
        }
        return $this->processAVPForm($avp, $loglbl);
    }

    /**
     * create attribute value (for principal) details
     *
     *
     * @ApiDoc(
     *   section = "Attribute value (for principal)",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  parameters = {
     *      {"name"="services","dataType"="array", "required"=false, "description"="IDs of Services to give the value to. If empty, the value will be given to all services."},
     *      {"name"="value", "dataType"="string", "required"=true, "description"="assigned value"},
     *      {"name"="attribute_spec", "dataType"="integer", "required"=true, "description"="attribute specification id"},
     *      {"name"="principal", "dataType"="integer", "required"=false, "description"="ID of principal. If left blank, it will default to self."},
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     *
     * @return Role
     */
    public function postAttributevalueprincipalAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[postAttributeValuePrincipal] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());
        /*
          $as = $em->getRepository('HexaaStorageBundle:AttributeSpec')->find($asid);
          if (!$as) {
          $errorlog->error($loglbl . "the requested AttributeSpec with id=" . $asid . " was not found");
          throw new HttpException(404, 'AttributeSpec not found.');
          }/*
          if ($as->getMaintainer() != "user") {
          $errorlog->error($loglbl . "AttributeSpec id=" . $asid . " can not be linked to a principal");
          throw new HttpException(400, 'this AttributeSpec can not be linked to a principal');
          }/*
          $avp = $em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->findOneByAttributeSpec($as);
          if ($avp!=false && !$as->getIsMultivalue()) {
          $errorlog->error($loglbl." id=".$asid." can not be linked to a principal");
          } */
        $avp = new AttributeValuePrincipal();
        return $this->processAVPForm($avp, $loglbl);
    }

    /**
     * delete attribute value (for principal)
     *
     *
     * @ApiDoc(
     *   section = "Attribute value (for principal)",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when service has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
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
    public function deleteAttributevalueprincipalAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[deleteAttributeValuePrincipal] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $avp = $em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->find($id);
        if (!$avp) {
            $errorlog->error($loglbl . "the requested attributeValuePrincipal with id=" . $id . " was not found");
            throw new HttpException(404, "Resource not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && $avp->getPrincipal() != $p) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }

        $em->remove($avp);
        $em->flush();

        $modlog->info($loglbl . "Attribute value (for principal) was deleted with id=" . $id);
    }


    /**
     * get attribute value (for organization) details
     *
     *
     * @ApiDoc(
     *   section = "Attribute value (for organization)",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value (for organization) id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     *
     * @return Role
     */
    public function getAttributevalueorganizationAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[getAttributeValueOrganization] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $aso = $em->getRepository('HexaaStorageBundle:AttributeValueOrganization')->find($id);
        if (!$aso) {
            $errorlog->error($loglbl . "the requested attributeValueOrganization with id=" . $id . " was not found");
            throw new HttpException(404, "Resource not found.");
        }
        $o = $aso->getOrganization();
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !($o->hasManager($p) && $o->hasPrincipal($p))) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        return $aso;
    }

    private function processAVOForm(AttributeValueOrganization $avo, $loglbl) {
        $modlog = $this->get('monolog.logger.modification');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $statusCode = $avo->getId() == null ? 201 : 204;

        $form = $this->createForm(new AttributeValueOrganizationType(), $avo);
        $form->bind($this->getRequest());

        if ($form->isValid()) {
            $em->persist($avo);
            $em->flush();

            if (201 === $statusCode) {
                $modlog->info($loglbl . "New attribute value (for organization) was created with id=" . $avo->getId());
            } else {
                $modlog->info($loglbl . "Attribute value (for organization) was edited with id=" . $avo->getId());
            }

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_attributevalueorganization', array('id' => $avo->getId()), true // absolute
                        )
                );
            }
            return $response;
        }
        $errorlog->error($loglbl . "Validation error");
        return View::create($form, 400);
    }

    /**
     * edit attribute value (for organization) details
     *
     *
     * @ApiDoc(
     *   section = "Attribute value (for organization)",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value (for organization) id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  parameters = {
     *      {"name"="value", "dataType"="string", "required"=true, "description"="assigned value"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     *
     * @return AttributeValuePrincipal
     */
    public function putAttributevalueorganizationAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[putAttributeValueOrganization] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $avo = $em->getRepository('HexaaStorageBundle:AttributeValueOrganization')->find($id);
        if (!$avo) {
            $errorlog->error($loglbl . "the requested attributeValueOrganization with id=" . $id . " was not found");
            throw new HttpException(404, "Resource not found.");
        }
        $o = $avo->getOrganization();
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        return $this->processAVOForm($avo, $loglbl);
    }

    /**
     * delete attribute value (for organization)
     *
     *
     * @ApiDoc(
     *   section = "Attribute value (for organization)",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when service has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
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
    public function deleteAttributevalueorganizationAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[deleteAttributeValueOrganization] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $avo = $em->getRepository('HexaaStorageBundle:AttributeValueOrganization')->find($id);
        if (!$avo) {
            $errorlog->error($loglbl . "the requested attributeValueOrganization with id=" . $id . " was not found");
            throw new HttpException(404, "Resource not found.");
        }
        $o = $avo->getOrganization();
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }


        $em->remove($avo);
        $em->flush();

        $modlog->info($loglbl . "Attribute value (for organization) was removed with id=" . $id);
    }

    /**
     * get all consents for an attribute value (for organization)
     *
     *
     * @ApiDoc(
     *   section = "Attribute value (for organization)",
     *   resource = false,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
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
     *
     * 
     */
    public function cgetAttributevalueorganizationsConsentsAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[cgetAttributeValueOrganizationConsents] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $avo = $em->getRepository('HexaaStorageBundle:AttributeValueOrganization')->find($id);
        if (!$avo) {
            $errorlog->error($loglbl . "the requested attributeValueOrganization with id=" . $id . " was not found");
            throw new HttpException(404, "Attribute value not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$avo->getOrganization->hasPrincipal($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }

        $retarray = array();
        $retarray["attribute_value_organization_id"] = $id;
        $retarray["service_ids"] = array();
        foreach ($avo->getServices() as $s) {
            $retarray["service_ids"][] = $s->getId();
        }

        return $retarray;
    }

    /**
     * get attribute value (for organization) consent per service
     *
     *
     * @ApiDoc(
     *   section = "Attribute value (for organization)",
     *   resource = false,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
     *      {"name"="sid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
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
     * 
     */
    public function getAttributevalueorganizationServiceAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $sid) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[getAttributeValueOrganizationService] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " sid=" . $sid . " by " . $p->getFedid());

        $s = $em->getRepository('HexaaStorageBundle:Service')->find($sid);
        if (!$s) {
            $errorlog->error($loglbl . "the requested Service with id=" . $sid . " was not found");
            throw new HttpException(404, "Service not found.");
        }
        $avo = $em->getRepository('HexaaStorageBundle:AttributeValueOrganization')->find($id);
        if (!$avo) {
            $errorlog->error($loglbl . "the requested attributeValueOrganization with id=" . $id . " was not found");
            throw new HttpException(404, "Attribute value not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$avo->getOrganization()->hasPrincipal($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }

        if ($avo->hasService($s)) {
            $retarray = array();
            $retarray["attribute_value_id"] = $id;
            $retarray["service_id"] = $sid;
            $retarray["value"] = $avo->hasService($s);
            return $retarray;
        } else {
            return;
        }
    }

    /**
     * set attribute value (for organization) consent per service
     *
     *
     * @ApiDoc(
     *   section = "Attribute value (for organization)",
     *   resource = false,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
     *      {"name"="sid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
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
    public function putAttributevalueorganizationServiceAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $sid) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[putAttributeValueOrganizationService] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " sid=" . $sid . " by " . $p->getFedid());

        $s = $em->getRepository('HexaaStorageBundle:Service')->find($sid);
        if (!$s) {
            $errorlog->error($loglbl . "the requested Service with id=" . $sid . " was not found");
            throw new HttpException(404, "Service not found.");
        }
        $avo = $em->getRepository('HexaaStorageBundle:AttributeValueOrganization')->find($id);
        if (!$avo) {
            $errorlog->error($loglbl . "the requested attributeValueOrganization with id=" . $id . " was not found");
            throw new HttpException(404, "Attribute value not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$avo->getOrganization()->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }

        $sas = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findBy(array(
            "service" => $s,
            "attributeSpec" => $avo->getAttributeSpec()
        ));

        $valid = false;

        if (!$sas) {
            // no such attribute at the service... maybe it's public 
            $sass = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findBy(array(
                "isPublic" => true,
                "attributeSpec" => $avo->getAttributeSpec()
            ));
            if (!$sass) {
                // invalid -> 400 err
            } else
                $valid = true;  // ok, there is a public one
        } else
            $valid = true;

        if (!$valid) {
            $errorlog->error($loglbl . "Service (id=" . $sid . ") does not require this attribute (id=" . $id);
            throw new HttpError(400, "This service doesn't want this attribute.");
            return;
        }

        if (!$avo->hasService($s)) {
            $avo->addService($s);
            $em->persist($avo);
            $em->flush();

            $modlog->info($loglbl . "Release of attribute value (for organization) with id=" . $id . " to Service with id=" . $sid . " has been allowed");

            $response = new Response();
            $response->setStatusCode(201);


            $response->headers->set('Location', $this->generateUrl(
                            'get_attributevalueorganization', array('id' => $avo->getId()), true // absolute
                    )
            );

            return $response;
        }
    }

    /**
     * delete attribute value (for organization) consent per service
     *
     *
     * @ApiDoc(
     *   section = "Attribute value (for organization)",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned on successful delete",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
     *      {"name"="sid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
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
    public function deleteAttributevalueorganizationServiceAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $sid) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[deleteAttributeValueOrganizationService] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " sid=" . $sid . " by " . $p->getFedid());

        $s = $em->getRepository('HexaaStorageBundle:Service')->find($sid);
        if (!$s) {
            $errorlog->error($loglbl . "the requested Service with id=" . $sid . " was not found");
            throw new HttpException(404, "Service not found.");
        }
        $avo = $em->getRepository('HexaaStorageBundle:AttributeValueOrganization')->find($id);
        if (!$avo) {
            $errorlog->error($loglbl . "the requested attributeValueOrganization with id=" . $id . " was not found");
            throw new HttpException(404, "Attribute value not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$avo->getOrganization()->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }

        if ($avo->hasService($s)) {
            $avo->removeService($s);
            $em->persist($avo);
            $em->flush();

            $modlog->info($loglbl . "Release of attribute value (for organization) with id=" . $id . " to Service with id=" . $sid . " has been set to denied");
        }
    }

}
