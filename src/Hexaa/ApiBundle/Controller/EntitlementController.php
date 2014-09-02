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
use Hexaa\StorageBundle\Entity\Entitlement;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class EntitlementController extends FOSRestController implements ClassResourceInterface {

    /**
     * get entitlement details
     *
     *
     * @ApiDoc(
     *   section = "Entitlement",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement id"}
     *  },
     *   output="Hexaa\StorageBundle\Entity\Entitlement"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement
     *
     * @return Entitlement
     */
    public function getAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[getEntitlement] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $e = $em->getRepository('HexaaStorageBundle:Entitlement')->find($id);
        if (!$e) {
            $errorlog->error($loglbl . "the requested Entitlement with id=" . $id . " was not found");
            throw new HttpException(404, "Resource not found.");
        }
        $s = $e->getService();
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$s->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        return $e;
    }

    /**
     * edit entitlement preferences
     *
     *
     * @ApiDoc(
     *   section = "Entitlement",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when entitlement has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  parameters = {
     *      {"name"="uri","dataType"="string","required"=true,"description"="URI of entitlement"},
     *      {"name"="name","dataType"="string","required"=true,"description"="Displayable name of the entitlement"},
     *      {"name"="description","dataType"="string","required"=false,"description"="Description"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement
     *
     * 
     */
    public function putAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[putEntitlement] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $e = $em->getRepository('HexaaStorageBundle:Entitlement')->find($id);
        if (!$e) {
            $errorlog->error($loglbl . "the requested Entitlement with id=" . $id . " was not found");
            throw new HttpException(404, "Resource not found.");
        }
        $s = $e->getService();
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$s->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        return $this->processForm($e, $loglbl, "PUT");
    }

    /**
     * edit entitlement preferences
     *
     *
     * @ApiDoc(
     *   section = "Entitlement",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when entitlement has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  parameters = {
     *      {"name"="uri","dataType"="string","required"=true,"description"="URI of entitlement"},
     *      {"name"="name","dataType"="string","required"=true,"description"="Displayable name of the entitlement"},
     *      {"name"="description","dataType"="string","required"=false,"description"="Description"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement
     *
     * 
     */
    public function patchAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[patchEntitlement] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $e = $em->getRepository('HexaaStorageBundle:Entitlement')->find($id);
        if (!$e) {
            $errorlog->error($loglbl . "the requested Entitlement with id=" . $id . " was not found");
            throw new HttpException(404, "Resource not found.");
        }
        $s = $e->getService();
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$s->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        return $this->processForm($e, $loglbl, "PATCH");
    }

    private function processForm(Entitlement $e, $loglbl, $method="PUT") {
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $statusCode = $e->getId() == null ? 201 : 204;

        $form = $this->createForm(new EntitlementType(), $e, array("method"=>$method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $em->persist($e);
            $em->flush();
            if (201 === $statusCode) {
                $modlog->info($loglbl . "New Entitlement has been created with id=" . $e->getId());
            } else {
                $modlog->info($loglbl . "Entitlement has been edited with id=" . $e->getId());
            }

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_entitlement', array('id' => $e->getId()), true // absolute
                        )
                );
            }

            return $response;
        }
        $errorlog->error($loglbl . "Validation error");
        return View::create($form, 400);
    }

    /**
     * delete entitlement
     *
     *
     * @ApiDoc(
     *   section = "Entitlement",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when entitlement has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="entitlement id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement
     *
     * 
     */
    public function deleteAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[deleteEntitlement] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $e = $em->getRepository('HexaaStorageBundle:Entitlement')->find($id);
        if (!$e) {
            $errorlog->error($loglbl . "the requested Entitlement with id=" . $id . " was not found");
            throw new HttpException(404, "Resource not found.");
        }
        $s = $e->getService();
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$s->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        $em->remove($e);
        $em->flush();
        $modlog->info($loglbl . "Entitlement has been deleted with id=" . $id);
    }

}
