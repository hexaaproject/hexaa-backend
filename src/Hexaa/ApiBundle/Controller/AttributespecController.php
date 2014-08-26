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
class AttributespecController extends FOSRestController implements ClassResourceInterface {
    /*

    public function getPrincipalsAttributeSpecs() {

        $usr = $this->getContext()->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $ss = $em->getRepository('HexaaStorageBundle:Service')->findAll();
        $os = $em->getRepository('HexaaStorageBundle:Organization')->findAll();

        // Collect Organizations where user is a member
        $psos = array();
        foreach ($os as $o) {
            if ($o->hasPrincipal($p)) {
                $psos[] = $o;
            }
        }

        // Collect connected entitlement packs
        $eps = array();
        foreach ($psos as $o) {
            $oeps = $this->em->getRepository('HexaaStorageBundle:OrganizationEntitlementPack')->findByOrganization($o);
            foreach ($oeps as $oep) {
                $ep = $oep->getEntitlementPack();
                if ($oep->getStatus() == "accepted" && !in_array($ep, $eps, true)) {
                    $eps[] = $ep;
                }
            }
        }

        // Collect connected services
        $css = array();
        foreach ($eps as $ep) {
            $s = $ep->getService();
            if (!in_array($s, $css, true)) {
                $css[] = $s;
            }
        }


        $ss = array_filter($ss);

        $ass = array();
        foreach ($ss as $s) {
            $sass = $this->em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findByService($s);
            if (in_array($s, $css, true)) {
                foreach ($sass as $sas) {
                    if (!in_array($sas->getAttributeSpec(), $ass, true)) {
                        if ($sas->getAttributeSpec()->getMaintainer() == "user") {
                            $ass[] = $sas->getAttributeSpec();
                        }
                    }
                }
            }
        }

        $sass = $this->em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findByIsPublic(true);
        foreach ($sass as $sas) {
            if ((!in_array($sas->getAttributeSpec(), $ass, true)) && ($sas->getIsPublic() == true)) {
                if ($sas->getAttributeSpec()->getMaintainer() == "user") {
                    $ass[] = $sas->getAttributeSpec();
                }
            }
        }

        //So we've got the user's attributeSpecs
        $ass = array_filter($ass);
        
        return $ass;
    }
     */

    /**
     * get all attribute specifications
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

        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        $as = $em->getRepository('HexaaStorageBundle:AttributeSpec')->findAll();
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
     * edit attribute specification preferences
     *
     *
     * @ApiDoc(
     *   section = "AttributeSpec",
     *   resource = false,
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
     * edit attribute specification preferences
     *
     *
     * @ApiDoc(
     *   section = "AttributeSpec",
     *   resource = false,
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
        if ($request->getMethod() == "PUT" && !$as) {
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
     * create new attribute specification
     *
     *
     * @ApiDoc(
     *   section = "AttributeSpec",
     *   resource = false,
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

        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
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

        $form = $this->createForm(new AttributeSpecType(), $as, array("method"=>$method));
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
     * delete attribute specification
     *
     *
     * @ApiDoc(
     *   section = "AttributeSpec",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when attribute specification has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when attribute specification is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute specification id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
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

        $sas = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findByAttributeSpec($as);

        return $sas;
    }

}
