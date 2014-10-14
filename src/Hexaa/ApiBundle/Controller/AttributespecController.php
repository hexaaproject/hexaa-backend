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
use Hexaa\StorageBundle\Form\AttributeSpecType;
use Hexaa\StorageBundle\Entity\AttributeSpec;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class AttributespecController extends FOSRestController implements ClassResourceInterface, PersonalAuthenticatedController {

    /**
     * Lists all attribute specifications
     * 
     *
     * 
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
     * 
     * @ApiDoc(
     *   section = "AttributeSpec",
     *   resource = true,
     *   description = "get all attribute specifications",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   output="array<Hexaa\StorageBundle\Entity\AttributeSpec>"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     *
     * @return array
     */
    public function cgetAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[cgetAttributeSpec] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        $as = $em->getRepository('HexaaStorageBundle:AttributeSpec')->findBy(array(), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset'));
        return $as;
    }

    /**
     * get attribute specification details
     *
     *
     * @ApiDoc(
     *   section = "AttributeSpec",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute specification id"},
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
     * @return AttributeSpec
     */
    public function getAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[getAttributeSpec] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "called with id=" . $id . " by " . $p->getFedid());

        $as = $em->getRepository('HexaaStorageBundle:AttributeSpec')->find($id);
        if ($request->getMethod() == "GET" && !$as) {
            $errorlog->error($loglbl . "the requested attributeSpec with id=" . $id . " was not found");
            throw new HttpException(404, "Resource not found.");
        }
        return $as;
    }

    /**
     * Edit attribute specification preferences<br>
     * Note: admins only!
     *
     *
     * 
     * @ApiDoc(
     *   section = "AttributeSpec",
     *   resource = false,
     *   description = "edit attribute specification preferences",
     *   statusCodes = {
     *     204 = "Returned when attribute specification has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when attribute specification is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute specification id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  tags = {"admins"},
     *  parameters = {
     *      {"name"="oid","dataType"="string","required"=true,"description"="oid of attribute specification"},
     *      {"name"="friendly_name","dataType"="string","required"=true,"description"="displayable name of the attribute specification"},
     *      {"name"="maintainer","dataType"="enum","required"=true, "format"="user|manager", "description"="maintainer of the attribute"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"},
     *      {"name"="syntax","dataType"="string","required"=true,"description"="data type of connected values"},
     *      {"name"="is_multivalue","dataType"="boolean","required"=true,"format"="true|false","description"=""}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     *
     * 
     */
    public function putAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[putAttributeSpec] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "called with id=" . $id . " by " . $p->getFedid());

        $as = $em->getRepository('HexaaStorageBundle:AttributeSpec')->find($id);
        if ($request->getMethod() == "PUT" && !$as) {
            $errorlog->error($loglbl . "the requested attributeSpec with id=" . $id . " was not found");
            throw new HttpException(404, "Resource not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        return $this->processForm($as, $loglbl, 'PUT');
    }

    /**
     * Edit attribute specification<br>
     * Note: admins only!
     *
     *
     * @ApiDoc(
     *   section = "AttributeSpec",
     *   resource = false,
     *   description = "edit attribute specification preferences",
     *   statusCodes = {
     *     204 = "Returned when attribute specification has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when attribute specification is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute specification id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  tags = {"admins"},
     *  parameters = {
     *      {"name"="oid","dataType"="string","required"=true,"description"="oid of attribute specification"},
     *      {"name"="friendly_name","dataType"="string","required"=true,"description"="displayable name of the attribute specification"},
     *      {"name"="maintainer","dataType"="enum","required"=true, "format"="user|manager", "description"="maintainer of the attribute"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"},
     *      {"name"="syntax","dataType"="string","required"=true,"description"="data type of connected values"},
     *      {"name"="is_multivalue","dataType"="boolean","required"=true,"format"="true|false","description"=""}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     *
     * 
     */
    public function patchAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[patchAttributeSpec] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "called with id=" . $id . " by " . $p->getFedid());

        $as = $em->getRepository('HexaaStorageBundle:AttributeSpec')->find($id);
        if ($request->getMethod() == "PATCH" && !$as) {
            $errorlog->error($loglbl . "the requested attributeSpec with id=" . $id . " was not found");
            throw new HttpException(404, "Resource not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        return $this->processForm($as, $loglbl, 'PATCH');
    }

    /**
     * Create new attribute specification<br>
     * Note: admins only!
     *
     *
     * @ApiDoc(
     *   section = "AttributeSpec",
     *   resource = false,
     *   description = "create new attribute specification",
     *   statusCodes = {
     *     201 = "Returned when attribute specification has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when attribute specification is not found"
     *   },
     *   requirements = {
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *  tags = {"admins"},
     *  parameters = {
     *      {"name"="oid","dataType"="string","required"=true,"description"="oid of attribute specification"},
     *      {"name"="friendly_name","dataType"="string","required"=true,"description"="displayable name of the attribute specification"},
     *      {"name"="maintainer","dataType"="enum","required"=true, "format"="user|manager", "description"="maintainer of the attribute"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"},
     *      {"name"="syntax","dataType"="string","required"=true,"description"="data type of connected values"},
     *      {"name"="is_multivalue","dataType"="boolean","required"=true,"format"="true|false","description"=""}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     *
     * 
     */
    public function postAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[postAttributeSpec] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        return $this->processForm(new AttributeSpec(), $loglbl, "POST");
    }

    private function processForm(AttributeSpec $as, $loglbl, $method = "PUT") {
        $modlog = $this->get('monolog.logger.modification');
        $errorlog = $this->get('monolog.logger.error');

        $em = $this->getDoctrine()->getManager();
        $statusCode = $as->getId() == null ? 201 : 204;

        $form = $this->createForm(new AttributeSpecType(), $as, array("method" => $method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            if (201 === $statusCode) {
                $modlog->info($loglbl . "created new attributeSpec with id=" . $as->getId());
            }
            $modlog->info($loglbl . "updated attributeSpec with id=" . $as->getId());
            $em->persist($as);
            $em->flush();

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_attributespec', array('id' => $as->getId()), true // absolute
                        )
                );
            }

            return $response;
        }
        $errorlog->error($loglbl . "Validation error");
        return View::create($form, 400);
    }

    /**
     * Delete attribute an specification<br>
     * Note: admins only!
     *
     *
     * @ApiDoc(
     *   section = "AttributeSpec",
     *   resource = false,
     *   description = "delete attribute specification",
     *   statusCodes = {
     *     204 = "Returned when attribute specification has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when attribute specification is not found"
     *   },
     *   tags = {"admins"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute specification id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     * 
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     *
     * 
     */
    public function deleteAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[deleteAttributeSpec] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "called with id=" . $id . " by " . $p->getFedid());

        $as = $em->getRepository('HexaaStorageBundle:AttributeSpec')->find($id);
        if ($request->getMethod() == "DELETE" && !$as) {
            $errorlog->error($loglbl . "the requested attributeSpec with id=" . $id . " was not found");
            throw new HttpException(404, "Resource not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        } else {
            $modlog->info($loglbl . "deleted attributeSpec with id=" . $id);
            $em->remove($as);
            $em->flush();
        }
    }

    /**
     * get connected services of the specified attribute specification
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
     * @ApiDoc(
     *   section = "AttributeSpec",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute specification id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     *
     * @return array
     */
    public function getServiceAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[getAttributeSpecPerService] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "called with id=" . $id . " by " . $p->getFedid());

        $as = $em->getRepository('HexaaStorageBundle:AttributeSpec')->find($id);
        if ($request->getMethod() == "GET" && !$as) {
            $errorlog->error($loglbl . "the requested attributeSpec with id=" . $id . " was not found");
            throw new HttpException(404, "Resource not found.");
        }

        $sas = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findBy(array("attributeSpec" => $as), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset'));

        return $sas;
    }

}
