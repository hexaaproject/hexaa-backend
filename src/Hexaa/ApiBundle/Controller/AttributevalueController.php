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
     * Get attribute value (for principal) details<br>
     * Note: only admins may query values for other than themselves.
     *
     *
     * @ApiDoc(
     *   section = "Attribute value (for principal)",
     *   resource = true,
     *   description = "get attribute value (for principal) details",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value (for principal) id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   output = "Hexaa\StorageBundle\Entity\AttributeValuePrincipal"
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

    private function processAVPForm(AttributeValuePrincipal $avp, $loglbl, $method = "PUT") {
        $modlog = $this->get('monolog.logger.modification');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $statusCode = $avp->getId() == null ? 201 : 204;
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        
        if (!$this->getRequest()->request->has('principal') && $method !== "POST"){
            $this->getRequest()->request->set('principal', $p->getId());
        }

        if ($this->getRequest()->request->has('principal') && $this->getRequest()->request->get('principal') !== $p && !in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
            $errorlog->error($loglbl . "User " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }

        if (!$this->getRequest()->request->has('principal') || $this->getRequest()->request->get('principal') == null)
            $this->getRequest()->request->set("principal", $p->getId());



        $form = $this->createForm(new AttributeValuePrincipalType(), $avp, array("method"=>$method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

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
     * Edit attribute value (for principal) details<br><br>
     * 
     * Note: only HEXAA admins are allowed to add or edit attributes for other than themselves.
     *
     *
     * @ApiDoc(
     *   section = "Attribute value (for principal)",
     *   resource = true,
     *   description = "edit attribute value (for principal) details",
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value (for principal) id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  parameters = {
     *      {"name"="services","dataType"="array", "required"=true, "description"="IDs of Services to give the value to. If empty, the value will be given to all services."},
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
        return $this->processAVPForm($avp, $loglbl, "PUT");
    }

    /**
     * Edit attribute value (for principal) details<br><br>
     * 
     * Note: only HEXAA admins are allowed to add or edit attributes for other than themselves.
     *
     *
     * @ApiDoc(
     *   section = "Attribute value (for principal)",
     *   resource = true,
     *   description = "edit attribute value (for principal) details",
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value (for principal) id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  parameters = {
     *      {"name"="services","dataType"="array", "required"=true, "description"="IDs of Services to give the value to. If empty, the value will be given to all services."},
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
    public function patchAttributevalueprincipalAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[patchAttributeValuePrincipal] ";
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
        return $this->processAVPForm($avp, $loglbl, "PATCH");
    }

    /**
     * Create attribute value (for principal)<br><br>
     * 
     * Note: only HEXAA admins are allowed to add or edit attributes for other than themselves.
     *
     *
     * @ApiDoc(
     *   section = "Attribute value (for principal)",
     *   description = "create attribute value (for principal)",
     *   resource = true,
     *   statusCodes = {
     *     201 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  parameters = {
     *      {"name"="services","dataType"="array", "required"=true, "description"="IDs of Services to give the value to. If empty, the value will be given to all services."},
     *      {"name"="value", "dataType"="string", "required"=true, "description"="assigned value"},
     *      {"name"="attribute_spec", "dataType"="integer", "required"=true, "description"="attribute specification id"},
     *      {"name"="principal", "dataType"="integer", "required"=false, "description"="ID of principal. If left blank, it will default to self."}
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
        
        $avp = new AttributeValuePrincipal();
        return $this->processAVPForm($avp, $loglbl, "POST");
    }

    /**
     * Delete attribute value (for principal)<br>
     * Note: only admins may delete values for other than themselves.
     *
     *
     * @ApiDoc(
     *   section = "Attribute value (for principal)",
     *   resource = false,
     *   description = "delete attribute value (for principal)",
     *   statusCodes = {
     *     204 = "Returned when value has been deleted successfully",
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
     * get all services linked to the specified attribute value (for principal)<br>
     * Note: only admins may query values for other than themselves.
     *
     * 
     * @ApiDoc(
     *   section = "Attribute value (for principal)",
     *   resource = false,
     *   description = "get all services linked to the specified attribute value (for principal)",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
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
     *
     * 
     */
    public function cgetAttributevalueprincipalsServicesAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[cgetAttributeValuePrincipalServices] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $avp = $em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->find($id);
        if (!$avp) {
            $errorlog->error($loglbl . "the requested attributeValuePrincipal with id=" . $id . " was not found");
            throw new HttpException(404, "Attribute value not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && $avp->getPrincipal()!==$p) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }

        $retarray = array();
        $retarray["attribute_value_principal_id"] = $id;
        $retarray["service_ids"] = array();
        foreach ($avp->getServices() as $s) {
            $retarray["service_ids"][] = $s->getId();
        }

        return $retarray;
    }

    /**
     * Get if the specified attribute value (for principal) will be released to a specific service.<br>
     * Note: This doesn't check consents.<br>
     * Note: only admins may query values for other than themselves.
     *
     * @ApiDoc(
     *   section = "Attribute value (for principal)",
     *   description = "get if the specified attribute value (for principal) will be released to a specific service",
     *   resource = false,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
     *      {"name"="sid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
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
     * 
     */
    public function getAttributevalueprincipalsServiceAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $sid) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[getAttributeValuePrincipalService] ";
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
        $avp = $em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->find($id);
        if (!$avp) {
            $errorlog->error($loglbl . "the requested attributeValuePrincipal with id=" . $id . " was not found");
            throw new HttpException(404, "Attribute value not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && $avp->getPrincipal()!==$p) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }

        if ($avp->hasService($s) || $avp->getServices() == new \Doctrine\Common\Collections\ArrayCollection()) {
            return $s;
        } else {
            return;
        }
    }

    /**
     * Add service to attribute value (for principal) <br>
     * Note: only admins may query values for other than themselves.
     *
     *
     * @ApiDoc(
     *   section = "Attribute value (for principal)",
     *   resource = false,
     *   description = "add service to attribute value (for principal)",
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
    public function putAttributevalueprincipalsServiceAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $sid) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[putAttributeValuePrincipalService] ";
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
        $avp = $em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->find($id);
        if (!$avp) {
            $errorlog->error($loglbl . "the requested attributeValuePrincipal with id=" . $id . " was not found");
            throw new HttpException(404, "Attribute value not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && $avp->getPrincipal()!==$p) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }

        $sas = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findBy(array(
            "service" => $s,
            "attributeSpec" => $avp->getAttributeSpec()
        ));

        $valid = false;

        if (!$sas) {
            // no such attribute at the service... maybe it's public 
            $sass = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findBy(array(
                "isPublic" => true,
                "attributeSpec" => $avp->getAttributeSpec()
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

        if (!$avp->hasService($s)) {
            $avp->addService($s);
            $em->persist($avp);
            $em->flush();

            $modlog->info($loglbl . "Release of attribute value (for principal) with id=" . $id . " to Service with id=" . $sid . " has been allowed");

            $response = new Response();
            $response->setStatusCode(201);


            $response->headers->set('Location', $this->generateUrl(
                            'get_attributevalueprincipal', array('id' => $avp->getId()), true // absolute
                    )
            );

            return $response;
        }
    }

    /**
     * Remove service from attribute value (for principal)<br>
     * Note: only admins may query values for other than themselves.
     *
     *
     * @ApiDoc(
     *   section = "Attribute value (for principal)",
     *   resource = false,
     *   description = "remove service from attribute value (for principal)",
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
    public function deleteAttributevalueprincipalServiceAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $sid) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[deleteAttributeValuePrincipalService] ";
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
        $avp = $em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->find($id);
        if (!$avp) {
            $errorlog->error($loglbl . "the requested attributeValuePrincipal with id=" . $id . " was not found");
            throw new HttpException(404, "Attribute value not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && $avp->getPrincipal()!==$p) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }

        if ($avp->hasService($s)) {
            $avp->removeService($s);
            $em->persist($avp);
            $em->flush();

            $modlog->info($loglbl . "Release of attribute value (for principal) with id=" . $id . " to Service with id=" . $sid . " has been set to denied");
        }
    }
    

    /**
     * Get attribute value (for organization) details
     *
     *
     * @ApiDoc(
     *   section = "Attribute value (for organization)",
     *   resource = true,
     *   description = "get attribute value (for organization) details",
     *   tags = {"organization member" = "#5BA578"},
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value (for organization) id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *    },
     *    output="Hexaa\StorageBundle\Entity\AttributeValueOrganization"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     *
     * @return AttributeValueOrganization
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

    private function processAVOForm(AttributeValueOrganization $avo, $loglbl, $method = "PUT") {
        $modlog = $this->get('monolog.logger.modification');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $statusCode = $avo->getId() == null ? 201 : 204;
        
        if (!$this->getRequest()->request->has('organization') && $method!="POST"){
            $this->getRequest()->request->set('organization', $avo->getOrganization()->getId());
        }

        $form = $this->createForm(new AttributeValueOrganizationType(), $avo, array("method"=>$method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

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
     * Edit an attribute value (for organization)
     * Note: If services array is empty, the value will be released to all services.
     *
     *
     * @ApiDoc(
     *   section = "Attribute value (for organization)",
     *   resource = true,
     *   description = "edit attribute value (for organization) details",
     *   tags = {"organization manager" = "#4180B4"},
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value (for organization) id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  parameters = {
     *      {"name"="value", "dataType"="string", "required"=true, "description"="assigned value"},
     *      {"name"="services", "dataType"="array", "required"=true, "description"="array of service IDs to give the value to"},
     *      {"name"="attribute_spec", "dataType"="integer", "required"=true, "description"="attribute specification id"},
     *      {"name"="organization", "dataType"="integer", "required"=true, "description"="ID of the organization"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     *
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
        return $this->processAVOForm($avo, $loglbl, "PUT");
    }

    /**
     * Edit an attribute value (for organization)
     * Note: If services array is empty, the value will be released to all services.
     *
     *
     * @ApiDoc(
     *   section = "Attribute value (for organization)",
     *   resource = true,
     *   description = "edit attribute value (for organization) details",
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value (for organization) id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="value", "dataType"="string", "required"=true, "description"="assigned value"},
     *      {"name"="services", "dataType"="array", "required"=true, "description"="array of service IDs to give the value to"},
     *      {"name"="attribute_spec", "dataType"="integer", "required"=true, "description"="attribute specification id"},
     *      {"name"="organization", "dataType"="integer", "required"=true, "description"="ID of the organization"}
     *   }
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
    public function patchAttributevalueorganizationAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[patchAttributeValueOrganization] ";
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
        return $this->processAVOForm($avo, $loglbl, "PATCH");
    }

    /**
     * create attribute value (for organization) details
     *
     *
     * @ApiDoc(
     *   section = "Attribute value (for organization)",
     *   resource = true,
     *   statusCodes = {
     *     201 = "Returned when successful",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="value", "dataType"="string", "required"=true, "description"="assigned value"},
     *      {"name"="services", "dataType"="array", "required"=true, "description"="array of service IDs to give the value to"},
     *      {"name"="attribute_spec", "dataType"="integer", "required"=true, "description"="attribute specification id"},
     *      {"name"="organization", "dataType"="integer", "required"=true, "description"="ID of the organization"}
     *   }
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
    public function postAttributevalueorganizationAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[postOrganizationAttributeValueOrganization] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        if ($request->request->has('organization') && $request->request->get('organization') != null) {
            $o = $em->getRepository('HexaaStorageBundle:Organization')->find($request->request->get('organization'));

            if ($request->getMethod() == "POST" && !$o) {
                $errorlog->error($loglbl . "The requested Organization with id=" . $request->request->get('organization') . " was not found");
                throw new HttpException(404, "Organization not found.");
            }
            if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
                $errorlog->error($loglbl . "User " . $p->getFedid() . " has insufficent permissions");
                throw new HttpExcetion(403, "Forbidden");
                return;
            }
        }
        $avo = new AttributeValueOrganization();
        $avo->setOrganization($o);
        return $this->processAVOForm($avo, $loglbl, "POST");
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
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
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
     * get all services linked to the specified attribute value (for organization)
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
     *   tags = {"organization member" = "#5BA578"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
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
     * @return array
     *
     * 
     */
    public function cgetAttributevalueorganizationsServicesAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[cgetAttributeValueOrganizationServices] ";
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
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$avo->getOrganization()->hasPrincipal($p)) {
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
     * get if the specified attribute value (for organization) will be released to a specific service
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
     *   tags = {"organization member" = "#5BA578"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
     *      {"name"="sid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
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

        if ($avo->hasService($s) || $avo->getServices() == new \Doctrine\Common\Collections\ArrayCollection()) {
            return $s;
        } else {
            return;
        }
    }

    /**
     * add service to attribute value (for organization) 
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
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
     *      {"name"="sid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
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
     * remove service from attribute value (for organization)
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
     *   tags = {"organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute value id"},
     *      {"name"="sid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
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
