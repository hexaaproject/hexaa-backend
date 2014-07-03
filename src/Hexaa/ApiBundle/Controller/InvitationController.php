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

use Hexaa\StorageBundle\Form\EmailInvitationType;
use \Hexaa\StorageBundle\Entity\EmailInvitation;
use Hexaa\StorageBundle\Form\UrlInvitationType;
use \Hexaa\StorageBundle\Entity\UrlInvitation;

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
    public function getInvitationAction(Request $request, ParamFetcherInterface $paramFetcher, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $i = $em->getRepository('HexaaStorageBundle:EmailInvitation')->find($id);
        if (!$i) throw new HttpException(404, 'Invitation not found.');
        return $i;
    }
    
    private function processEIForm(EmailInvitation $i)
    {
	$em = $this->getDoctrine()->getManager();
        $statusCode = $i->getId()==null ? 201 : 204;

        $form = $this->createForm(new EmailInvitationType(), $i);
        $form->bind($this->getRequest());

        if ($form->isValid()) {
	    if (201 === $statusCode) {
                $usr= $this->get('security.context')->getToken()->getUser();
                $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
                $i->setCreatedAt(new \DateTime());
                $i->setInviter($p);
                $i->setStatus("pending");
	    }
            $em->persist($i);
            $em->flush();

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location',
                    $this->generateUrl(
                        'get_invitation', array('id' => $i->getId()),
                        true // absolute
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
     *   {"name"="email", "dataType"="string", "required"=true, "description"="e-mail address"},
     *   {"name"="landing_url", "dataType"="string", "required"=true, "description"="url to show the invitee, or to redirect the invitee to"},
     *   {"name"="do_redirect", "dataType"="boolean", "required"=false, "description"="sets wether to redirect the invitee to langing_url or not"},
     *   {"name"="as_manager", "dataType"="boolean", "required"=false, "description"="if set, the user will be invited as a manager (organization only)"},
     *   {"name"="message", "dataType"="text", "required"=true, "description"="the body of the e-mail sent"},
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
    public function postInvitationAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
        return $this->processEIForm(new EmailInvitation());
        //throw new HttpException(400, "not implemented, yet!");
    }
    
}