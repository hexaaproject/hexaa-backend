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
use Hexaa\StorageBundle\Entity\News;
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
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default="10", description="How many items to return.")
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
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\Organization>"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return Organization
     */
    public function cgetAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[cgetOrganizations] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());


        if (in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
            $os = $em->getRepository('HexaaStorageBundle:Organization')->findBy(array(), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset'));
        } else {
            $os = $em->createQueryBuilder()
                    ->select('o')
                    ->from('HexaaStorageBundle:Organization', 'o')
                    ->where(':p MEMBER OF o.principals')
                    ->setParameter('p', $p)
                    ->setFirstResult($paramFetcher->get('offset'))
                    ->setMaxResults($paramFetcher->get('limit'))
                    ->getQuery()
                    ->getResult()
            ;
        }
        return $os;
    }

    /**
     * get organization where the user is at least a member
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
     *  },
     *   output="Hexaa\StorageBundle\Entity\Organization"
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
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

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

    private function processForm(Organization $o, $loglbl, $method = "PUT") {
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $statusCode = $o->getId() == null ? 201 : 204;

        $form = $this->createForm(new OrganizationType(), $o, array("method" => $method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            if (201 === $statusCode) {
                $usr = $this->get('security.context')->getToken()->getUser();
                $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
                $o->addManager($p);
            }
            $em->persist($o);

            //Create News object to notify the user
            $n = new News();
            $n->setOrganization($o);
            $n->setPrincipal($p);
            if ($method == "POST") {
                $n->setTitle("New Organization created");
                $n->setMessage("A new organization named " . $o->getName() . " has been created");
            } else {
                $n->setTitle("Organization modified");
                $n->setMessage("Organization named " . $o->getName() . " has been modified");
            }
            $n->setTag("organization");
            $em->persist($n);
            $em->flush();
            $modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());


            if (201 === $statusCode) {
                $modlog->info($loglbl . "New Organization created with id=" . $o->getId());
            } else {
                $modlog->info($loglbl . "Organization edited with id=" . $o->getId());
            }


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
        $errorlog->error($loglbl . "Validation error");
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
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        return $this->processForm(new Organization(), $loglbl, "POST");
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
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        if (!$o) {
            $errorlog->error($loglbl . "the requested Organization with id=" . $id . " was not found");
            throw new HttpException(404, "Organization not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
        }
        return $this->processForm($o, $loglbl, "PUT");
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
    public function patchAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $loglbl = "[patchOrganization] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        if (!$o) {
            $errorlog->error($loglbl . "the requested Organization with id=" . $id . " was not found");
            throw new HttpException(404, "Organization not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$o->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
        }
        return $this->processForm($o, $loglbl, "PATCH");
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
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $o = $em->getRepository('HexaaStorageBundle:Organization')->find($id);
        if (!$o) {
            $errorlog->error($loglbl . "the requested Organization with id=" . $id . " was not found");
            throw new HttpException(404, "Organization not found.");
        }
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
            $modlog->info($loglbl . "Organization with id=" . $id . " deleted");
        }
    }

}
