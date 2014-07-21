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
use Hexaa\StorageBundle\Form\InvitationType;
use \Hexaa\StorageBundle\Entity\Invitation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class InvitationController extends FOSRestController {

    /**
     * get invitation details
     *
     *
     * @ApiDoc(
     *   section = "Invitation",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="invitation id"},
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
     * @return Invitation
     */
    public function getInvitationAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $i = $em->getRepository('HexaaStorageBundle:Invitation')->find($id);
        if (!$i)
            throw new HttpException(404, 'Invitation not found.');
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && $i->getInviter() !== $p) {
            throw new HttpException(403, 'Forbidden.');
            return;
        }
        return $i;
    }

    private function processForm(Invitation $i) {
        $em = $this->getDoctrine()->getManager();
        $statusCode = $i->getId() == null ? 201 : 204;

        $form = $this->createForm(new InvitationType(), $i);
        $form->bind($this->getRequest());

        if ($form->isValid()) {
            $usr = $this->get('security.context')->getToken()->getUser();
            $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
            $data = $form->getData();
            if (array_key_exists("service", $data) && isset($data['service'])) {
                if (!$data['service']->hasManager($p)) {
                    throw new HttpException(403, "You are not a manager of the service.");
                    return;
                }
            } else if (array_key_exists("organization", $data) && isset($data['organization'])) {
                if (!$data['organization']->hasManager($p)) {
                    throw new HttpException(403, "You are not a manager of the organization.");
                    return;
                }
            }
            if (201 === $statusCode) {
                $i->setInviter($p);
                $i->setToken(uniqid());
            }
            $em->persist($i);
            $em->flush();

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_invitation', array('id' => $i->getId()), true // absolute
                        )
                );
            }

            return $response;
        }

        return View::create($form, 400);
    }

    /**
     * send new invitation
     *
     *
     * @ApiDoc(
     *   section = "Invitation",
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
     *   {"name"="emails", "dataType"="string", "required"=false, "description"="e-mail address"},
     *   {"name"="landing_url", "dataType"="string", "required"=false, "description"="url to show the invitee, or to redirect the invitee to"},
     *   {"name"="do_redirect", "dataType"="boolean", "required"=false, "description"="sets wether to redirect the invitee to langing_url or not"},
     *   {"name"="as_manager", "dataType"="boolean", "required"=false, "description"="if set, the user will be invited as a manager (organization only)"},
     *   {"name"="message", "dataType"="text", "required"=true, "description"="the body of the e-mail sent"},
     *   {"name"="start_date", "dataType"="datetime", "required"=false, "description"="start of accept period"},
     *   {"name"="end_date", "dataType"="datetime", "required"=false, "description"="end of accept period"},
     *   {"name"="limit", "dataType"="datetime", "required"=false, "description"="limit the number of acceptions permitted (empty = indefinite)"},
     *   {"name"="role", "dataType"="integer", "required"=false, "format"="\d+", "description"="if set and valid, the invitee will be a member of this role"},
     *   {"name"="organization", "dataType"="integer", "required"=false, "format"="\d+", "description"="if set and valid, the invitee will be a member of this organization"},
     *   {"name"="service", "dataType"="integer", "required"=false, "format"="\d+", "description"="if set and valid, the invitee will be a member of this service"},
     *   
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
     * @return Invitation
     */
    public function postInvitationAction(Request $request, ParamFetcherInterface $paramFetcher) {
        return $this->processForm(new Invitation());
        //throw new HttpException(400, "not implemented, yet!");
    }

    /**
     * edit invitation
     *
     *
     * @ApiDoc(
     *   section = "Invitation",
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
     *   {"name"="emails", "dataType"="string", "required"=false, "description"="e-mail address"},
     *   {"name"="landing_url", "dataType"="string", "required"=false, "description"="url to show the invitee, or to redirect the invitee to"},
     *   {"name"="do_redirect", "dataType"="boolean", "required"=false, "description"="sets wether to redirect the invitee to langing_url or not"},
     *   {"name"="as_manager", "dataType"="boolean", "required"=false, "description"="if set, the user will be invited as a manager (organization only)"},
     *   {"name"="message", "dataType"="text", "required"=true, "description"="the body of the e-mail sent"},
     *   {"name"="start_date", "dataType"="datetime", "required"=false, "description"="start of accept period"},
     *   {"name"="end_date", "dataType"="datetime", "required"=false, "description"="end of accept period"},
     *   {"name"="limit", "dataType"="datetime", "required"=false, "description"="limit the number of acceptions permitted (empty = indefinite)"},
     *   {"name"="role", "dataType"="integer", "required"=false, "format"="\d+", "description"="if set and valid, the invitee will be a member of this role"},
     *   {"name"="organization", "dataType"="integer", "required"=false, "format"="\d+", "description"="if set and valid, the invitee will be a member of this organization"},
     *   {"name"="service", "dataType"="integer", "required"=false, "format"="\d+", "description"="if set and valid, the invitee will be a member of this service"},
     *   
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
     * @return Invitation
     */
    public function putInvitationAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $i = $em->getRepository('HexaaStorageBundle:Invitation')->find($id);
        if (!$i)
            throw new HttpException(404, 'Invitation not found.');
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && $i->getInviter() !== $p) {
            throw new HttpException(403, 'Forbidden.');
            return;
        }
        return $this->processForm($i);
    }

    /**
     * delete invitation
     *
     *
     * @ApiDoc(
     *   section = "Invitation",
     *   resource = true,
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="invitation id"},
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
     * @return Invitation
     */
    public function deleteInvitationAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $i = $em->getRepository('HexaaStorageBundle:Invitation')->find($id);
        if (!$i)
            throw new HttpException(404, 'Invitation not found.');
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && $i->getInviter() !== $p) {
            throw new HttpException(403, 'Forbidden.');
            return;
        }
        $em->remove($i);
        $em->flush();
    }

}
