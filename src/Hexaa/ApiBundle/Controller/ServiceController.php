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
use Hexaa\StorageBundle\Form\ServiceType;
use Hexaa\StorageBundle\Entity\Service;
use Hexaa\StorageBundle\Entity\ServicePage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class ServiceController extends FOSRestController implements ClassResourceInterface {

    /**
     * Lists all services, where the user is a manager.
     * Lists all services if the user is a HEXAA admin
     *
     *
     * @ApiDoc(
     *   section = "Service",
     *   description = "list services where the user is a manager",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     204 = "Returned when no service is connected to the user",
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
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return Service
     */
    public function cgetAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[cgetServices] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        $ss = $em->getRepository('HexaaStorageBundle:Service')->findAll();
        if (in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
            return $ss;
        } else {
            $rets = array();
            foreach ($ss as $s) {
                if ($s->hasManager($p)) {
                    $rets[] = $s;
                }
            }
            $rets = array_filter($rets);
            //if (count($rets)<1) throw new HttpException(204, "No service is connected to the user.");
            return $rets;
        }
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
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return Service
     */
    public function getAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $loglbl = "[getService] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
        if (!$s) {
            $errorlog->error($loglbl . "the requested Service with id=" . $id . " was not found");
            throw new HttpException(404, "Service not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$s->hasManager($p)) {
            throw new HttpException(403, "Forbidden");
            return;
        }
        return $s;
    }

    private function processForm(Service $s, $loglbl) {
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $statusCode = $s->getId() == null ? 201 : 204;

        $form = $this->createForm(new ServiceType(), $s);
        $form->bind($this->getRequest());

        if ($form->isValid()) {
            if (201 === $statusCode) {
                $usr = $this->get('security.context')->getToken()->getUser();
                $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
                $s->addManager($p);
            }
            $em->persist($s);
            $em->flush();

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
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     *   requirements = {
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *   {"name"="name", "dataType"="string", "required"=true, "description"="service name"},
     *   {"name"="entityid", "dataType"="string", "required"=true, "description"="service entity id"},
     *   {"name"="url", "dataType"="string", "required"=false, "description"="service url"},
     *   {"name"="description", "dataType"="string", "required"=false, "description"="service description"},
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
        $loglbl = "[postService] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        /* $em = $this->getDoctrine()->getManager();
          $s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
          if (!$s) throw new HttpException(404, "Resource not found."); */
        return $this->processForm(new Service(), $loglbl);
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
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *   {"name"="name", "dataType"="string", "required"=true, "description"="service name"},
     *   {"name"="entityid", "dataType"="string", "required"=true, "description"="service entity id"},
     *   {"name"="url", "dataType"="string", "required"=false, "description"="service url"},
     *   {"name"="description", "dataType"="string", "required"=false, "description"="service description"},
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
        $loglbl = "[putService] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

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
        return $this->processForm($s, $loglbl);
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
        $loglbl = "[deleteService] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

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
        $modlog->info($loglbl . "Service with id=" . $id . " deleted");
    }

}
