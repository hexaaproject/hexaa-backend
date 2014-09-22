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
use Hexaa\StorageBundle\Form\EntitlementType;
use Hexaa\StorageBundle\Form\EntitlementPackEntitlementType;
use Hexaa\StorageBundle\Entity\Entitlement;
use Hexaa\StorageBundle\Entity\EntitlementPack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class EntitlementpackEntitlementController extends FOSRestController {

    /**
     * get entitlements of entitlement pack
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
     * @ApiDoc(
     *   section = "EntitlementPack",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement pack id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\Entitlement>"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement
     *
     * @return array
     */
    public function cgetEntitlementsAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[getEntitlementPackEntitlements] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $ep = $em->getRepository('HexaaStorageBundle:EntitlementPack')->find($id);
        if ($request->getMethod() == "GET" && !$ep) {
            $errorlog->error($loglbl . "the requested EntitlementPack with id=" . $id . " was not found");
            throw new HttpException(404, "Resource not found.");
        }
        $e = array_slice($ep->getEntitlements()->toArray(), $paramFetcher->get('offset'), $paramFetcher->get('limit'));
        return $e;
    }

    /**
     * remove entitlement from entitlement pack
     *
     *
     * @ApiDoc(
     *   section = "EntitlementPack",
     *   resource = true,
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     401 = "Returned when token is expired",
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
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement
     *
     */
    public function deleteEntitlementAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $eid) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[deleteEntitlementPackEntitlements] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " and eid=" . $eid . " by " . $p->getFedid());

        $ep = $em->getRepository('HexaaStorageBundle:EntitlementPack')->find($id);
        if ($request->getMethod() == "DELETE" && !$ep) {
            $errorlog->error($loglbl . "the requested EntitlementPack with id=" . $id . " was not found");
            throw new HttpException(404, "Entitlement package not found.");
        }
        $s = $ep->getService();
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$s->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        $e = $em->getRepository('HexaaStorageBundle:Entitlement')->find($eid);
        if (!$e) {
            $errorlog->error($loglbl . "the requested Entitlement with id=" . $eid . " was not found");
            throw new HttpException(404, "Entitlement not found.");
        }
        if ($ep->hasEntitlement($e)) {
            $ep->removeEntitlement($e);
            $em->persist($ep);
            $em->flush();

            $modlog->info($loglbl . "Entitlement (id=" . $eid . ") has been removed from Entitlement Pack with id=" . $id);
        }
    }

    /**
     * add entitlement to entitlement pack
     *
     *
     * @ApiDoc(
     *   section = "EntitlementPack",
     *   resource = true,
     *   statusCodes = {
     *     201 = "Returned when successful",
     *     401 = "Returned when token is expired",
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
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement
     *
     */
    public function putEntitlementsAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $eid) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[putEntitlementPackEntitlements] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " and eid=" . $eid . " by " . $p->getFedid());

        $ep = $em->getRepository('HexaaStorageBundle:EntitlementPack')->find($id);
        if ($request->getMethod() == "PUT" && !$ep) {
            $errorlog->error($loglbl . "the requested EntitlementPack with id=" . $id . " was not found");
            throw new HttpException(404, "Entitlement package not found.");
        }
        $s = $ep->getService();
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$s->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        $e = $em->getRepository('HexaaStorageBundle:Entitlement')->find($eid);
        if (!$e) {
            $errorlog->error($loglbl . "the requested Entitlement with id=" . $eid . " was not found");
            throw new HttpException(404, "Entitlement not found.");
        }
        if (!$ep->hasEntitlement($e)) {
            $ep->addEntitlement($e);
            $em->persist($ep);
            $em->flush();

            $modlog->info($loglbl . "Entitlement (id=" . $eid . ") has been added to Entitlement Pack with id=" . $id);
        }
    }

    /**
     * set entitlements of an entitlement package
     *
     *
     * @ApiDoc(
     *   section = "EntitlementPack",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when successful",
     *     204 = "Returned when entitlements are already added",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
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
     * @param ParamFetcherInterface $paramFetcher param fetcher role
     *
     * 
     */
    public function putEntitlementAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $loglbl = "[putEntitlementPackEntitlement] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $ep = $em->getRepository('HexaaStorageBundle:EntitlementPack')->find($id);
        if ($request->getMethod() == "PUT" && !$ep) {
            $errorlog->error($loglbl . "the requested EntitlementPack with id=" . $id . " was not found");
            throw new HttpException(404, "EntitlementPack not found.");
        }
        $s = $ep->getService();
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$s->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }

        return $this->processEPEForm($ep, $loglbl, "PUT");
    }

    private function processEPEForm(EntitlementPack $ep, $loglbl, $method = "PUT") {
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $store = $ep->getEntitlements()->toArray();



        $form = $this->createForm(new EntitlementPackEntitlementType(), $ep, array("method" => $method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $statusCode = $store === $ep->getEntitlements()->toArray() ? 204 : 201;
            $em->persist($ep);
            $em->flush();
            $ids = "[ ";
            foreach ($ep->getEntitlements() as $e) {
                $ids = $ids . $e->getId() . ", ";
            }
            $ids = substr($ids, 0, strlen($ids) - 2) . " ]";
            $modlog->info($loglbl . "Entitlements of EntitlementPack with id=" . $ep->getId()) . " has been set to " . $ids;
            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_entitlement_pack', array('id' => $ep->getId()), true // absolute
                        )
                );
            }

            return $response;
        }
        $errorlog->error($loglbl . "Validation error");
        return View::create($form, 400);
    }

}
