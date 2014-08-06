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
use Hexaa\StorageBundle\Form\OrganizationType;
use Hexaa\StorageBundle\Entity\Organization;
use Hexaa\StorageBundle\Entity\OrganizationPage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class OrganizationController extends FOSRestController implements ClassResourceInterface {

    /**
     * Lists all organization, where the user is at least a member.
     * Lists all organizations if the user is a HEXAA admin
     *
     *
     * @ApiDoc(
     *   section = "Organization",
     *   description = "list organization where user is at least a member",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     204 = "Returned when no organization is connected to the user",
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
     * @param ParamFetcherInterface $paramFetcher param fetcher organization
     *
     * @return Organization
     */
    public function cgetAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[cgetOrganizations] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $accesslog->info($loglbl . "called");

        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $os = $em->getRepository('HexaaStorageBundle:Organization')->findAll();
        if (in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
            return $os;
        } else {

            $reto = array();
            foreach ($os as $o) {
                if ($o->hasPrincipal($p)) {
                    $reto[] = $o;
                }
            }
            $reto = array_filter($reto);
            //if (count($reto)<1) throw new HttpException(204, "No organization is linked to the user");
            return $reto;
        }
    }

    /**
     * get organizations where the user is at least a member
     *
     *
     * @ApiDoc(
     *   section = "Organization",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when organization is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher organization
     *
     * @return Organization
     */
    public function getAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $loglbl = "[getOrganization] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $accesslog->info($loglbl . "called with id=" . $id);

        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        if (!$o) {
            $errorlog->error($loglbl . "the requested Organization with id=" . $id . " was not found");
            throw new HttpException(404, "Organization not found.");
            return;
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasPrincipal($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        return $o;
    }

    private function processForm(Organization $o) {
        $em = $this->getDoctrine()->getManager();
        $statusCode = $o->getId() == null ? 201 : 204;

        $form = $this->createForm(new OrganizationType(), $o);
        $form->bind($this->getRequest());

        if ($form->isValid()) {
            if (201 === $statusCode) {
                $usr = $this->get('security.context')->getToken()->getUser();
                $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
                $o->addManager($p);
            }
            $em->persist($o);
            $em->flush();

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_organization', array('id' => $o->getId()), true // absolute
                        )
                );
            }

            return $response;
        }

        return View::create($form, 400);
    }

    /**
     * create new organization
     *
     *
     * @ApiDoc(
     *   section = "Organization",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when organization has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when organization is not found"
     *   },
     *   requirements = {
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="name","dataType"="string","required"=true,"description"="displayable name of the organization"},
     *      {"name"="default_role","dataType"="integer","required"=false,"description"="id of the default role"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"}
     *   }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher organization
     *
     * 
     */
    public function postAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[postOrganization] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $accesslog->info($loglbl . "called");

        /* $em = $this->getDoctrine()->getManager();
          $s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
          if (!$s) throw new HttpException(404, "Resource not found."); */

        return $this->processForm(new Organization());
    }

    /**
     * edit organization preferences
     *
     *
     * @ApiDoc(
     *   section = "Organization",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when organization has been edited successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when organization is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   parameters = {
     *      {"name"="name","dataType"="string","required"=true,"description"="displayable name of the organization"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"}
     *   }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher organization
     *
     * 
     */
    public function putAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $loglbl = "[putOrganization] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $accesslog->info($loglbl . "called with id=" . $id);

        $em = $this->getDoctrine()->getManager();
        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        if (!$o) {
            $errorlog->error($loglbl . "the requested Organization with id=" . $id . " was not found");
            throw new HttpException(404, "Organization not found.");
        }
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
        }
        return $this->processForm($o);
    }

    /**
     * delete organization
     *
     *
     * @ApiDoc(
     *   section = "Organization",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when organization has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when organization is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="organization id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher organization
     *
     * 
     */
    public function deleteAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $loglbl = "[deleteOrganization] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $accesslog->info($loglbl . "called with id=" . $id);

        $em = $this->getDoctrine()->getManager();
        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        if (!$o) {
            $errorlog->error($loglbl . "the requested Organization with id=" . $id . " was not found");
            throw new HttpException(404, "Organization not found.");
        }
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
        } else {
            if ($o->getDefaultRole() != null) {
                $o->setDefaultRole(null);
            }
            $em->persist($o);
            $em->flush();
            $em->remove($o);
            $em->flush();
        }
    }

}
