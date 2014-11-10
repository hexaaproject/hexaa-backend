<?php

/*
 * Copyright 2014 MTA-SZTAKI.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Hexaa\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\RouteRedirectView;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Hexaa\StorageBundle\Form\InvitationType;
use Hexaa\StorageBundle\Entity\Invitation;
use Hexaa\StorageBundle\Entity\RolePrincipal;
use Hexaa\StorageBundle\Entity\News;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class InvitationController extends HexaaController implements PersonalAuthenticatedController {

    /**
     * get invitation details
     *
     *
     * @ApiDoc(
     *   section = "Invitation",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   tags = {"service manager" = "#4180B4", "organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="invitation id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   output="Hexaa\StorageBundle\Entity\Invitation"
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
    public function getInvitationAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $i = $this->eh->get('Invitation', $id, $loglbl);
        return $i;
    }

    /**
     * resend pending invitations
     *
     *
     * @ApiDoc(
     *   section = "Invitation",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   tags = {"service manager" = "#4180B4", "organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="invitation id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   output="Hexaa\StorageBundle\Entity\Invitation"
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
    public function getInvitationResendAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $i = $this->eh->get('Invitation', $id, $loglbl);

        $i->setLastReinviteAt(new \DateTime());
        $i->setReinviteCount($i->getReinviteCount() + 1);
        $this->em->persist($i);
        $this->em->flush();

        $this->sendInvitationEmail($i, $loglbl);

        return $i;
    }

    private function sendInvitationEmail(Invitation $i, $loglbl) {
        $maillog = $this->get('monolog.logger.email');
        $baseUrl = $this->getRequest()->getHttpHost() . $this->getRequest()->getBasePath();
        $this->getRequest()->setLocale($i->getLocale());
        $names = $i->getDisplayNames();
        $statuses = $i->getStatuses();

        foreach ($i->getEmails() as $email) {
            if ($statuses[$email] == "pending") {
                $message = \Swift_Message::newInstance()
                        ->setSubject('[hexaa] ' . $this->get('translator')->trans('Invitation'))
                        ->setFrom('hexaa@' . $baseUrl)
                        ->setBody(
                        $this->renderView(
                                'HexaaApiBundle:Default:Invite.html.twig', array(
                            'inviter' => $i->getInviter(),
                            'message' => $i->getMessage(),
                            'service' => $i->getService(),
                            'role' => $i->getRole(),
                            'organization' => $i->getOrganization(),
                            'asManager' => $i->getAsManager(),
                            'url' => $this->container->getParameter('hexaa_ui_url') . "/invitation.php",
                            'token' => $i->getToken(),
                            'mail' => $email
                                )
                        ), "text/html"
                );
                if ($names[$email] != "") {
                    $message->setTo(array($email => $names[$email]));
                } else {
                    $message->setTo($email);
                }
                $this->get('mailer')->send($message);
                $maillog->info($loglbl . "E-mail sent to " . $email);
            }
        }
    }

    private function processForm(Invitation $i, $loglbl, $method = "PUT") {
        $statusCode = $i->getId() == null ? 201 : 204;

        if ($this->getRequest()->request->has('emails')) {
            $emails = $this->getRequest()->request->get('emails');

            if (!is_array($emails)) {
                $this->errorlog->error($loglbl . "Emails must be an array");
                throw new HttpException(400, "emails must be an array.");
                return;
            }
            $mails = array();
            $names = array();
            foreach ($emails as &$email) {
                $email = trim($email);
                if (preg_match('/^".*".<[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})>$/', $email)) {
                    $email = str_replace('\"', '"', $email);
                    $name = substr($email, strpos($email, '"') + 1, strrpos($email, '"') - strpos($email, '"'));
                    $mail = substr($email, strpos($email, '<') + 1, strrpos($email, '>') - 1 - strpos($email, '<'));
                    $mails[] = $mail;
                    $names[$mail] = trim($name);
                } else {
                    $mails[] = $email;
                    $names[$email] = null;
                }
            }

            $this->getRequest()->request->set('emails', $mails);
        }

        $form = $this->createForm(new InvitationType(), $i, array("method" => $method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();

            $data = $form->getData();
            if (201 === $statusCode) {
                $i->setInviter($p);
                $i->generateToken();
                if ($this->getRequest()->request->get('limit') == null && count(array_filter($i->getEmails())) >= 1) {
                    $i->setLimit(count(array_filter($i->getEmails())));
                }
            }

            if ($this->getRequest()->request->has('emails')) {
                $i->setDisplayNames($names);
            }

            $this->em->persist($i);


            $n = new News();
            $n->setPrincipal($p);
            $n->setTitle("New invitation");
            $action = $method === "POST" ? "created a new" : "modified an";
            if ($i->getOrganization() != null) {
                $n->setMessage($p->getFedid() . " has " . $action . " invitation to Organization " . $i->getOrganization()->getName());
                $n->setOrganization($i->getOrganization());
            }
            if ($i->getService() != null) {
                $n->setMessage($p->getFedid() . " has " . $action . " invitation to Service " . $i->getService()->getName());
                $n->setService($i->getService());
            }
            $n->setTag("invitation");
            $this->em->persist($n);
            $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());


            $this->em->flush();

            if (201 === $statusCode) {
                $this->modlog->info($loglbl . "New Invitation created with id=" . $i->getId());
            } else {
                $this->modlog->info($loglbl . "Invitation edited with id=" . $i->getId());
            }

            $response = new Response();
            $response->setStatusCode($statusCode);

// set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_invitation', array('id' => $i->getId()), true // absolute
                        )
                );
            }

            if ($method == "POST") {
                $this->sendInvitationEmail($i, $loglbl);
            }

            return $response;
        }
        $this->errorlog->error($loglbl . "Validation error");
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
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   tags = {"service manager" = "#4180B4", "organization manager" = "#4180B4"},
     *   requirements ={
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *     {"name"="emails", "dataType"="array", "required"=false, "description"="e-mail address"},
     *     {"name"="landing_url", "dataType"="string", "required"=false, "description"="url to show the invitee, or to redirect the invitee to"},
     *     {"name"="do_redirect", "dataType"="boolean", "required"=false, "description"="sets wether to redirect the invitee to langing_url or not"},
     *     {"name"="as_manager", "dataType"="boolean", "required"=false, "description"="if set, the user will be invited as a manager (organization only)"},
     *     {"name"="message", "dataType"="text", "required"=true, "description"="the body of the e-mail sent"},
     *     {"name"="start_date", "dataType"="datetime", "required"=false, "description"="start of accept period"},
     *     {"name"="end_date", "dataType"="datetime", "required"=false, "description"="end of accept period"},
     *     {"name"="limit", "dataType"="datetime", "required"=false, "description"="limit the number of acceptions permitted (empty = indefinite)"},
     *     {"name"="locale", "dataType"="text", "required"=false, "format"="en|hu", "description"="the locale of the invitation e-mail"},
     *     {"name"="role", "dataType"="integer", "required"=false, "format"="\d+", "description"="if set and valid, the invitee will be a member of this role"},
     *     {"name"="organization", "dataType"="integer", "required"=false, "format"="\d+", "description"="if set and valid, the invitee will be a member of this organization"},
     *     {"name"="service", "dataType"="integer", "required"=false, "format"="\d+", "description"="if set and valid, the invitee will be a member of this service"},
     *   }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     *
     */
    public function postInvitationAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();

        $this->accesslog->info($loglbl . "Called by " . $p->getFedid());

        return $this->processForm(new Invitation(), $loglbl, "POST");
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
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   tags = {"service manager" = "#4180B4", "organization manager" = "#4180B4"},
     *   requirements ={
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *     {"name"="emails", "dataType"="array", "required"=false, "description"="e-mail address"},
     *     {"name"="landing_url", "dataType"="string", "required"=false, "description"="url to show the invitee, or to redirect the invitee to"},
     *     {"name"="do_redirect", "dataType"="boolean", "required"=false, "description"="sets wether to redirect the invitee to langing_url or not"},
     *     {"name"="as_manager", "dataType"="boolean", "required"=false, "description"="if set, the user will be invited as a manager (organization only)"},
     *     {"name"="message", "dataType"="text", "required"=true, "description"="the body of the e-mail sent"},
     *     {"name"="start_date", "dataType"="datetime", "required"=false, "description"="start of accept period"},
     *     {"name"="end_date", "dataType"="datetime", "required"=false, "description"="end of accept period"},
     *     {"name"="limit", "dataType"="datetime", "required"=false, "description"="limit the number of acceptions permitted (empty = indefinite)"},
     *     {"name"="locale", "dataType"="text", "required"=false, "format"="en|hu", "description"="the locale of the invitation e-mail"},
     *     {"name"="role", "dataType"="integer", "required"=false, "format"="\d+", "description"="if set and valid, the invitee will be a member of this role"},
     *     {"name"="organization", "dataType"="integer", "required"=false, "format"="\d+", "description"="if set and valid, the invitee will be a member of this organization"},
     *     {"name"="service", "dataType"="integer", "required"=false, "format"="\d+", "description"="if set and valid, the invitee will be a member of this service"},
     *   }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     *
     */
    public function putInvitationAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();

        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $i = $this->eh->get('Invitation', $id, $loglbl);
        return $this->processForm($i, $loglbl, "PUT");
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
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   tags = {"service manager" = "#4180B4", "organization manager" = "#4180B4"},
     *   requirements ={
     *     {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *     {"name"="emails", "dataType"="array", "required"=false, "description"="e-mail address"},
     *     {"name"="landing_url", "dataType"="string", "required"=false, "description"="url to show the invitee, or to redirect the invitee to"},
     *     {"name"="do_redirect", "dataType"="boolean", "required"=false, "description"="sets wether to redirect the invitee to langing_url or not"},
     *     {"name"="as_manager", "dataType"="boolean", "required"=false, "description"="if set, the user will be invited as a manager (organization only)"},
     *     {"name"="message", "dataType"="text", "required"=true, "description"="the body of the e-mail sent"},
     *     {"name"="start_date", "dataType"="datetime", "required"=false, "description"="start of accept period"},
     *     {"name"="end_date", "dataType"="datetime", "required"=false, "description"="end of accept period"},
     *     {"name"="limit", "dataType"="datetime", "required"=false, "description"="limit the number of acceptions permitted (empty = indefinite)"},
     *     {"name"="locale", "dataType"="text", "required"=false, "format"="en|hu", "description"="the locale of the invitation e-mail"},
     *     {"name"="role", "dataType"="integer", "required"=false, "format"="\d+", "description"="if set and valid, the invitee will be a member of this role"},
     *     {"name"="organization", "dataType"="integer", "required"=false, "format"="\d+", "description"="if set and valid, the invitee will be a member of this organization"},
     *     {"name"="service", "dataType"="integer", "required"=false, "format"="\d+", "description"="if set and valid, the invitee will be a member of this service"},
     *   }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     *
     */
    public function patchInvitationAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $i = $this->eh->get('Invitation', $id, $loglbl);
        return $this->processForm($i, $loglbl, "PATCH");
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
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     *   tags = {"service manager" = "#4180B4", "organization manager" = "#4180B4"},
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="invitation id"},
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
     */
    public function deleteInvitationAction(Request $request, ParamFetcherInterface $paramFetcher, $id = 0) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $i = $this->eh->get('Invitation', $id, $loglbl);
        $this->em->remove($i);
        $this->em->flush();

        $this->modlog->info($loglbl . "Invitation with id=" . $id . " has been deleted");
    }

    /**
     * accept invitation with e-mail address
     *
     *
     * @ApiDoc(
     *   section = "Invitation",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="token", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="invitation token"},
     *      {"name"="email", "dataType"="string", "required"=true, "description"="e-mail address of the invitee"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     */
    public function getInvitationAcceptEmailAction(Request $request, ParamFetcherInterface $paramFetcher, $token, $email) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();

        $this->accesslog->info($loglbl . "Called with token=" . $token . " and email=" . $email . " by " . $p->getFedid());

        $i = $this->em->getRepository('HexaaStorageBundle:Invitation')->findOneByToken($token);
        if ($request->getMethod() == "GET" && !$i) {
            $this->errorlog->error($loglbl . "the requested Invitation with token=" . $token . " was not found");
            throw new HttpException(404, 'Invitation not found.');
        }
        $statuses = $i->getStatuses();
        if ($statuses[$email] == "accepted") {
            $this->errorlog->error($loglbl . "This e-mail has already accepted this invitation (id=" . $i->getId() . ")");
            throw new HttpException(400, 'This e-mail has already accepted this invitation.');
            return;
        }
        if (!in_array($email, $i->getEmails())) {
            $this->errorlog->error($loglbl . 'E-mail "' . $email . '" not found in Invitation with id=' . $i->getId());
            throw new HttpException(400, 'E-mail not found in invitation.');
            return;
        }

        $now = new \DateTime();
        $valid = true;

        if (($i->getLimit() !== null) && !($i->getLimit() >= $i->getCounter()))
            $valid = false;
        if (($i->getStartDate() !== null) && !($i->getStartDate() <= $now))
            $valid = false;
        if (($i->getEndDate() !== null) && !($i->getEndDate() > $now))
            $valid = false;

        if ($valid) {
            $i->setCounter($i->getCounter() + 1);
            $names = $i->getDisplayNames();
            if ($names[$email] != "" && $p->getDisplayName() == NULL) {
                $p->setDisplayName($names[$email]);
            }
            $i->setEmail($email, "accepted");
            if (($i->getService() !== null)) {
                $s = $i->getService();
                if (!$s->hasManager($p)) {
                    $s->addManager($p);
                    $this->em->persist($s);
                    $this->modlog->info($loglbl . "E-mail " . $email . " removed from Invitation (id=" . $i->getId() . "), invitee set as a manager of Service with id=" . $s->getId());
                }
            }
            if (($i->getOrganization() !== null)) {
                $o = $i->getOrganization();
                if ($i->getAsManager()) {
                    if (!$o->hasManager($p)) {
                        $o->addManager($p);
                        $this->em->persist($o);
                        $this->modlog->info($loglbl . "E-mail " . $email . " removed from Invitation (id=" . $i->getId() . "), invitee set as a manager of Organization with id=" . $o->getId());
                    }
                } else {
                    if (!$o->hasPrincipal($p)) {
                        $o->addPrincipal($p);
                        $this->em->persist($o);
                        $this->modlog->info($loglbl . "E-mail " . $email . " removed from Invitation (id=" . $i->getId() . "), invitee set as a member of Organization with id=" . $o->getId());
                    }
                }
                if (($i->getRole() !== null)) {
                    $rp = $this->em->getRepository('HexaaStorageBundle:RolePrincipal')->findOneBy(array(
                        "principal" => $p,
                        "role" => $i->getRole()
                    ));
                    if (!$rp)
                        $rp = new RolePrincipal();
                    $rp->setPrincipal($p);
                    $rp->setRole($i->getRole());
                    $this->em->persist($rp);
                    $this->modlog->info($loglbl . "Invitee of Invitation (id=" . $i->getId() . ") set as a member of Role with id=" . $i->getRole()->getId());
                }
            }


            $n = new News();
            $n->setPrincipal($p);
            $n->setTitle("Accepted invitation");
            if ($i->getOrganization() != null) {
                $n->setMessage($p->getFedid() . " has accepted an invitation to Organization " . $i->getOrganization()->getName());
                $n->setOrganization($i->getOrganization());
            }
            if ($i->getService() != null) {
                $n->setMessage($p->getFedid() . " has accepted an invitation to Service " . $i->getService()->getName());
                $n->setService($i->getService());
            }
            $n->setTag("invitation");
            $this->em->persist($n);
            $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());

            $this->em->persist($i);
            $this->em->flush();

            if (($i->getLandingUrl() !== null)) {
                $redirUrl = $i->getLandingUrl();
            } else {
                $redirUrl = $this->container->getParameter('hexaa_ui_url');
            }

            return $this->redirect($redirUrl);
        } else {
            $this->errorlog->error($loglbl . "Invitation (id=" . $i->getId() . " limit reached or not between start and end date.");
            throw new HttpException(400, 'Limit reached or not between start and end date.');
            return;
        }
    }

    /**
     * accept invitation with only token
     *
     *
     * @ApiDoc(
     *   section = "Invitation",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="token", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="invitation token"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     */
    public function getInvitationAcceptTokenAction(Request $request, ParamFetcherInterface $paramFetcher, $token = "nullToken") {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with token=" . $token . " by " . $p->getFedid());

        $i = $this->em->getRepository('HexaaStorageBundle:Invitation')->findOneByToken($token);
        if (!$i) {
            $this->errorlog->error($loglbl . "the requested Invitation with token=" . $token . " was not found");
            throw new HttpException(404, 'Invitation not found.');
        }
        $now = new \DateTime();
        $valid = true;

        if (($i->getLimit() !== null) && !($i->getLimit() >= $i->getCounter()))
            $valid = false;
        if (($i->getStartDate() !== null) && !($i->getStartDate() <= $now))
            $valid = false;
        if (($i->getEndDate() !== null) && !($i->getEndDate() > $now))
            $valid = false;

        if ($valid) {
            $i->setCounter($i->getCounter() + 1);
            if (($i->getService() !== null)) {
                $s = $i->getService();
                if (!$s->hasManager($p)) {
                    $s->addManager($p);
                    $this->modlog->info($loglbl . "Invitee of Invitation (id=" . $i->getId() . ") set as a manager of Service with id=" . $s->getId() . " after accept by token");
                    $this->em->persist($s);
                }
            }
            if (($i->getOrganization() !== null)) {
                $o = $i->getOrganization();
                if ($i->getAsManager()) {
                    if (!$o->hasManager($p)) {
                        $o->addManager($p);
                        $this->modlog->info($loglbl . "Invitee of Invitation (id=" . $i->getId() . ") set as a manager of Organization with id=" . $o->getId() . " after accept by token");
                        $this->em->persist($o);
                    }
                } else {
                    if (!$o->hasPrincipal($p)) {
                        $o->addPrincipal($p);
                        $this->modlog->info($loglbl . "Invitee of Invitation (id=" . $i->getId() . ") set as a member of Organization with id=" . $o->getId() . " after accept by token");
                        $this->em->persist($o);
                    }
                }
                if (($i->getRole() !== null)) {
                    $rp = $this->em->getRepository('HexaaStorageBundle:RolePrincipal')->findOneBy(array(
                        "principal" => $p,
                        "role" => $i->getRole()
                    ));
                    if (!$rp)
                        $rp = new RolePrincipal();
                    $rp->setPrincipal($p);
                    $rp->setRole($i->getRole());
                    $this->em->persist($rp);
                    $this->modlog->info($loglbl . "Invitee of Invitation (id=" . $i->getId() . ") set as a member of Role with id=" . $i->getRole()->getId() . " after accept by token");
                }
            }

            $n = new News();
            $n->setPrincipal($p);
            $n->setTitle("Accepted invitation");
            if ($i->getOrganization() != null) {
                $n->setMessage($p->getFedid() . "has accepted an invitation to Organization " . $i->getOrganization()->getName());
                $n->setOrganization($i->getOrganization());
            }
            if ($i->getService() != null) {
                $n->setMessage($p->getFedid() . "has accepted an invitation to Service " . $i->getService()->getName());
                $n->setService($i->getService());
            }
            $n->setTag("invitation");
            $this->em->persist($n);
            $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());

            $this->em->persist($i);
            $this->em->flush();

            if (($i->getLandingUrl() !== null)) {
                $redirUrl = $i->getLandingUrl();
            } else {
                $redirUrl = $this->container->getParameter('hexaa_ui_url');
            }

            return $this->redirect($redirUrl);
        } else {
            $this->errorlog->error($loglbl . "Invitation (id=" . $i->getId() . " limit reached or not between start and end date.");
            throw new HttpException(400, 'Limit reached or not between start and end date.');
            return;
        }
    }

    /**
     * reject invitation with e-mail address
     *
     *
     * @ApiDoc(
     *   section = "Invitation",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired or invalid",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="token", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="invitation token"},
     *      {"name"="email", "dataType"="string", "required"=true, "description"="e-mail address of the invitee"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     */
    public function getInvitationRejectEmailAction(Request $request, ParamFetcherInterface $paramFetcher, $token = "nullToken", $email) {
        $loglbl = "[" . $request->attributes->get('_controller') . "] ";
        $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        $this->accesslog->info($loglbl . "Called with token=" . $token . " and email=" . $email . " by " . $p->getFedid());

        $i = $this->em->getRepository('HexaaStorageBundle:Invitation')->findOneByToken($token);
        if (!$i) {
            $this->errorlog->error($loglbl . "the requested Invitation with token=" . $token . " was not found");
            throw new HttpException(404, 'Invitation not found.');
        }
        if (!array_key_exists($email, $i->getEmails())) {
            $this->errorlog->error($loglbl . "E-mail not found in Invitation with id=" . $i->getId());
            throw new HttpException(400, 'E-mail not found in invitation.');
            return;
        }

        $now = new \DateTime();
        $valid = true;

        if (($i->getLimit() !== null) && !($i->getLimit() >= $i->getCounter()))
            $valid = false;
        if (($i->getStartDate() !== null) && !($i->getStartDate() <= $now))
            $valid = false;
        if (($i->getEndDate() !== null) && !($i->getEndDate() > $now))
            $valid = false;

        if ($valid) {
            $i->setEmail($email, "rejected");
            $names = $i->getDisplayNames();
            if ($names[$email] != "" && $p->getDisplayName() == NULL) {
                $p->setDisplayName($names[$email]);
            }

            $n = new News();
            $n->setPrincipal($p);
            $n->setTitle("Rejected invitation");
            if ($i->getOrganization() != null) {
                $n->setMessage($p->getFedid() . "has rejected an invitation to Organization " . $i->getOrganization()->getName());
                $n->setOrganization($i->getOrganization());
            }
            if ($i->getService() != null) {
                $n->setMessage($p->getFedid() . "has rejected an invitation to Service " . $i->getService()->getName());
                $n->setService($i->getService());
            }
            $n->setTag("invitation");
            $this->em->persist($n);
            $this->modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());

            $this->em->persist($i);
            $this->em->flush();

            $this->modlog->info($loglbl . "Invitation (id=" . $i->getId() . ") was rejected by " . $email);
        } else {
            $this->errorlog->error($loglbl . "Invitation (id=" . $i->getId() . " limit reached or not between start and end date.");
            throw new HttpException(400, 'Limit reached or not between start and end date.');
            return;
        }
    }

}
