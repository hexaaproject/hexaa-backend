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
use Hexaa\StorageBundle\Form\ConsentType;
use Hexaa\StorageBundle\Entity\Consent;
use Hexaa\StorageBundle\Entity\News;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class ConsentController extends FOSRestController implements ClassResourceInterface {

    /**
     * get consents of the current user
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
     * @ApiDoc(
     *   section = "Consents",
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
     *   output="array<Hexaa\StorageBundle\Entity\Consent>"
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
        $loglbl = "[getConsents] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        $cs = $em->getRepository('HexaaStorageBundle:Consent')->findBy(array("principal" => $p), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset'));
        return $cs;
    }

    /**
     * get a consent of the current user
     *
     *
     * @ApiDoc(
     *   section = "Consents",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="consent id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   output="Hexaa\StorageBundle\Entity\Consent"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     *
     * @return Consent
     */
    public function getAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[getConsent] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $c = $em->getRepository('HexaaStorageBundle:Consent')->find($id);
        return $c;
    }

    /**
     * get consent of the current user for a specific service
     *
     *
     * @ApiDoc(
     *   section = "Consents",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="sid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   output="Hexaa\StorageBundle\Entity\Consent"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     *
     * @return Consent
     */
    public function getServiceAction(Request $request, ParamFetcherInterface $paramFetcher, $sid = 0) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[getConsent] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $sid . " by " . $p->getFedid());


        $s = $em->getRepository('HexaaStorageBundle:Service')->find($sid);
        if (!$s) {
            $errorlog->error($loglbl . "the requested Service with id=" . $sid . " was not found");
            throw new HttpException(404, "Service not found.");
        }
        $c = $em->getRepository('HexaaStorageBundle:Consent')->findOneBy(array(
            "principal" => $p,
            "service" => $s
        ));
        if (!$c) {
            $c = new Consent();
            $c->setPrincipal($p);
            $c->setService($s);
            $em->persist($c);
            $em->flush();
        }
        return $c;
    }

    private function processForm(Consent $c, $loglbl, $method = "PUT") {
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $statusCode = $c->getId() == null ? 201 : 204;

        if ($this->getRequest()->request->has('principal') && $this->getRequest()->request->get('principal') !== $p && !in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
            $errorlog->error($loglbl . "User " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }

        if (!$this->getRequest()->request->has('principal') || $this->getRequest()->request->get('principal') == null)
            $this->getRequest()->request->set("principal", $p->getId());

        $form = $this->createForm(new ConsentType(), $c, array("method" => $method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            if (201 === $statusCode) {
                
            }
            $em->persist($c);

            //Create News object to notify the user
            $n = new News();
            $n->setPrincipal($p);
            $n->setTitle("You consented to the release of your data");
            $releaseable = "";
            foreach ($c->getEnabledAttributeSpecs() as $as) {
                $releaseable = $releaseable . $as->getFriendlyName() . ", ";
            }
            if ($c->getEnableEntitlements()) {
                $releaseable = $releaseable . "eduPersonEntitlement";
            } else {
                $releaseable = substr($releaseable, 0, strlen($releaseable) - 2);
            }
            $n->setMessage("You gave HEXAA permission to release the following attributes to service " . $c->getService()->getName() . ": " . $releaseable);
            $n->setTag("organization_manager");
            $em->persist($n);
            $em->flush();
            $modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());

            if (201 === $statusCode) {
                $modlog->info($loglbl . "New Consent created with id=" . $c->getId());
            } else {
                $modlog->info($loglbl . "Consent edited with id=" . $c->getId());
            }

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_principal_consent', array('id' => $c->getId()), true // absolute
                        )
                );
            }

            return $response;
        }
        $errorlog->error($loglbl . "Validation error");
        return View::create($form, 400);
    }

    /**
     * Create a new consent.<br>
     * Note: Consents are idetified by principal-service pairs, which must be unique. If the requested new consent already exists, error 400 will be returned.
     * 
     *
     *
     * @ApiDoc(
     *   section = "Consents",
     *   resource = false,
     *   description = "create new consent",
     *   statusCodes = {
     *     201 = "Returned when consent has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     *   requirements = {
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *   {"name"="enable_entitlements", "dataType"="boolean", "required"=true, "description"="sets the release consent of entitlements"},
     *   {"name"="enabled_attribute_specs", "dataType"="array", "required"=true, "description"="array of the releasable attribute specifications"},
     *   {"name"="principal", "dataType"="integer", "format"="\d+", "required"=false, "description"="principal id, defaults to self if left blank"},
     *   {"name"="service", "dataType"="integer", "format"="\d+", "required"=true, "description"="service ID"},
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
    public function postAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[postConsent] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        if ($request->request->has("service") && $request->request->get('service') != null) {
            $s = $em->getRepository('HexaaStorageBundle:Service')->find($request->request->get('service'));
            if (!$s) {
                // Oops, no such service... let the form handle it!
            } else {
                $c = $em->getRepository('HexaaStorageBundle:Consent')->findBy(array(
                    "principal" => $p,
                    "service" => $s
                ));
                $c = array_filter($c);
                if (count($c) > 0) {
                    $errorlog->error($loglbl . 'Duplicate constants are not allowed... You may want to use PUT instead');
                    throw new HttpException(400, 'A consent already exists with this principal and service, please use the PUT method!');
                }
            }
        }
        return $this->processForm(new Consent(), $loglbl, "POST");
    }

    /**
     * edit consent
     *
     *
     * @ApiDoc(
     *   section = "Consents",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when consent has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *   {"name"="enable_entitlements", "dataType"="boolean", "required"=true, "description"="sets the release consent of entitlements"},
     *   {"name"="enabled_attribute_specs", "dataType"="array", "required"=true, "description"="array of the releasable attribute specifications"},
     *   {"name"="principal", "dataType"="integer", "format"="\d+", "required"=false, "description"="principal id, defaults to self if left blank"},
     *   {"name"="service", "dataType"="integer", "format"="\d+", "required"=true, "description"="service ID"},
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
    public function putAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $loglbl = "[putConsent] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());



        $c = $em->getRepository('HexaaStorageBundle:Consent')->find($id);
        if (!$c) {
            $errorlog->error($loglbl . "the requested Consent with id=" . $id . " was not found");
            throw new HttpException(404, "Service not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$c->getPrincipal() != $p) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        return $this->processForm($c, $loglbl, "PUT");
    }

    /**
     * edit consent
     *
     *
     * @ApiDoc(
     *   section = "Consents",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when consent has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *   {"name"="enable_entitlements", "dataType"="boolean", "required"=true, "description"="sets the release consent of entitlements"},
     *   {"name"="enabled_attribute_specs", "dataType"="array", "required"=true, "description"="array of the releasable attribute specifications"},
     *   {"name"="principal", "dataType"="integer", "format"="\d+", "required"=false, "description"="principal id, defaults to self if left blank"},
     *   {"name"="service", "dataType"="integer", "format"="\d+", "required"=true, "description"="service ID"},
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
    public function patchAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $loglbl = "[patchConsent] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());



        $c = $em->getRepository('HexaaStorageBundle:Consent')->find($id);
        if (!$c) {
            $errorlog->error($loglbl . "the requested Consent with id=" . $id . " was not found");
            throw new HttpException(404, "Service not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$c->getPrincipal() != $p) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        return $this->processForm($c, $loglbl, "PATCH");
    }

    /**
     * delete consent
     *
     *
     * @ApiDoc(
     *   section = "Service",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when service has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
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
    public function deleteAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $loglbl = "[deleteConsent] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());


        throw new HttpException(400, 'Not implemented, yet!');
        /*
          $s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
          if (!$s) {
          $errorlog->error($loglbl . "the requested Service with id=" . $id . " was not found");
          throw new HttpException(404, "Service not found.");
          }
          if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$s->hasManager($p)) {
          $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
          throw new HttpException(403, "Forbidden");
          return;
          }
          $em->remove($s);
          $em->flush();
          $modlog->info($loglbl . "Service with id=" . $id . " deleted"); */
    }

}
