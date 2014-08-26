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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Hexaa\StorageBundle\Entity\Principal;
use Hexaa\StorageBundle\Form\PrincipalType;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PrincipalController
 *
 * @author baloo
 */
class PrincipalController extends FOSRestController {

    /**
     * get list of principals
     *
     *
     * @ApiDoc(
     *   section = "Principal",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query"
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
     * @return Service
     */
    public function cgetPrincipalsAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[getPrincipals] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        $p = $em->getRepository('HexaaStorageBundle:Principal')->findAll();
        return $p;
    }

    /**
     * get info about current principal 
     *
     *
     * @ApiDoc(
     *   section = "Principal",
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
     * @param ParamFetcherInterface $paramFetcher param fetcher organization
     *
     * @return Service
     */
    public function getPrincipalIsadminAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[getPrincipalIsAdmin] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
            return array("is_admin" => false);
        }
        return array("is_admin" => true);
    }

    /**
     * get info about current principal 
     *
     *
     * @ApiDoc(
     *   section = "Principal",
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
     * @param ParamFetcherInterface $paramFetcher param fetcher organization
     *
     * @return Service
     */
    public function getPrincipalSelfAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[getPrincipalSelf] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        return $p;
    }

    /**
     * get info about principal by id
     *
     *
     * @ApiDoc(
     *   section = "Principal",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "description"="id of principal"},
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
     * @return Service
     */
    public function getPrincipalIdAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $loglbl = "[getPrincipalId] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $p = $em->getRepository('HexaaStorageBundle:Principal')->find($id);
        if (!$p) {
            $errorlog->error($loglbl . "the requested Principal with id=" . $id . " was not found");
            throw new HttpException(404, "Principal not found");
        }
        return $p;
    }

    /**
     * get info about a principal by fedid
     *
     *
     * @ApiDoc(
     *   section = "Principal",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="fedid", "dataType"="string", "required"=true, "description"="Federal ID of principal"},
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
     * @return Service
     */
    public function getPrincipalFedidAction(Request $request, ParamFetcherInterface $paramFetcher, $fedid) {
        $loglbl = "[getPrincipalFedid] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with fedid=" . $fedid . " by " . $p->getFedid());

        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($fedid);
        if ($request->getMethod() == "GET" && !$p) {
            $errorlog->error($loglbl . "the requested Principal with fedid=" . $fedid . " was not found");
            throw new HttpException(404, "Principal not found");
        }
        return $p;
    }

    /**
     * list all invitations of the current principal
     *
     *
     * @ApiDoc(
     *   section = "Principal",
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
     * @param ParamFetcherInterface $paramFetcher param fetcher organization
     *
     * @return array
     */
    public function cgetPrincipalInvitationsAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[cgetPrincipalInvitations] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        $is = $em->getRepository('HexaaStorageBundle:Invitation')->findByInviter($p);
        return $is;
    }

    /**
     * list available attribute specifications
     *
     *
     * @ApiDoc(
     *   section = "Principal",
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
     * @param ParamFetcherInterface $paramFetcher param fetcher organization
     *
     * @return AttributeSpec
     */
    public function cgetPrincipalAttributespecsAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[cgetPrincipalAttributeSpecs] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

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
            $oeps = $em->getRepository('HexaaStorageBundle:OrganizationEntitlementPack')->findByOrganization($o);
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
            $sass = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findByService($s);
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

        $sass = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findByIsPublic(true);
        foreach ($sass as $sas) {
            if ((!in_array($sas->getAttributeSpec(), $ass, true)) && ($sas->getIsPublic() == true)) {
                if ($sas->getAttributeSpec()->getMaintainer() == "user") {
                    $ass[] = $sas->getAttributeSpec();
                }
            }
        }



        $ass = array_filter($ass);
        //if (count($retarr)<1) throw new HttpException(404, "Resource not found.");
        return $ass;
    }

    /**
     * list available attribute values of the current principal and the specified attribute specification
     *
     *
     * @ApiDoc(
     *   section = "Principal",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="asid", "dataType"="integer", "requirement"="\d+", "description"="attribute specification id"},
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
     * @return AttributeSpec
     */
    public function cgetPrincipalAttributespecsAttributevalueprincipalsAction(Request $request, ParamFetcherInterface $paramFetcher, $asid) {
        $loglbl = "[getPrincipalAttributeSpecsAttributeValuePrincipal] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with asid=" . $asid . " by " . $p->getFedid());

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
            $oeps = $em->getRepository('HexaaStorageBundle:OrganizationEntitlementPack')->findByOrganization($o);
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
        if ($request->getMethod() == "GET" && count($ss) < 1) {
            $errorlog->error($loglbl . "the requested Service was not found");
            throw new HttpException(404, "Service not found.");
        }
        $ass = array();
        foreach ($ss as $s) {
            $sass = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findByService($s);
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
        $sass = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findByIsPublic(true);
        foreach ($sass as $sas) {
            if ((!in_array($sas->getAttributeSpec(), $ass, true))) {
                if ($sas->getAttributeSpec()->getMaintainer() == "user") {
                    $ass[] = $sas->getAttributeSpec();
                }
            }
        }



        $ass = array_filter($ass);
        //if (count($retarr)<1) throw new HttpException(404, "Resource not found.");

        $as = $em->getRepository('HexaaStorageBundle:AttributeSpec')->find($asid);
        if ($request->getMethod() == "GET" && !$as) {
            $errorlog->error($loglbl . "the requested AttributeSpec with id=" . $asid . " was not found");
            throw new HttpException(404, "AttributeSpec not found.");
        }
        if ($request->getMethod() == "GET" && !in_array($as, $ass, true)) {
            throw new HttpException(400, "the Attribute specification is not visible to the user.");
        }
        $avps = $em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')
                ->findBy(array(
            "principal" => $p,
            "attributeSpec" => $as
                )
        );
        /*
          foreach ($avps as $avp){

          if ($avp->getAttributeSpec()!==$as) {
          if(($key = array_search($avp, $avps)) !== false) {
          unset($avps[$key]);
          }
          }


          } */
        return $avps;
    }

    /**
     * list all attribute values of the principal
     *
     *
     * @ApiDoc(
     *   section = "Principal",
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
     * @param ParamFetcherInterface $paramFetcher param fetcher principal
     *
     * @return array
     */
    public function cgetPrincipalAttributevalueprincipalAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[cgetPrincipalAttributeValuePrincipals] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        $avps = $em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->findByPrincipal($p);

        return $avps;
    }

    /**
     * list all services where the user is a manager
     *
     *
     * @ApiDoc(
     *   section = "Principal",
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
     * @param ParamFetcherInterface $paramFetcher param fetcher organization
     *
     * @return Service
     */
    public function cgetManagerServicesAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[cgetManagerService] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        $ss = $em->getRepository('HexaaStorageBundle:Service')->findAll();
        $rets = array();
        foreach ($ss as $s) {
            if ($s->hasManager($p)) {
                $rets[] = $s;
            }
        }
        $rets = array_filter($rets);
        //if (count($rets)<1) throw new HttpException(404, "Resource not found.");
        return $rets;
    }

    /**
     * list all organizations where the user is a manager
     *
     *
     * @ApiDoc(
     *   section = "Principal",
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
     * @param ParamFetcherInterface $paramFetcher param fetcher organization
     *
     * @return Organization
     */
    public function cgetManagerOrganizationsAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[cgetManagerOrganizations] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        $os = $em->getRepository('HexaaStorageBundle:Organization')->findAll();
        $reto = array();
        foreach ($os as $o) {
            if ($o->hasManager($p)) {
                $reto[] = $o;
            }
        }
        $reto = array_filter($reto);
        //if (count($reto)<1) throw new HttpException(404, "Resource not found.");
        return $reto;
    }

    /**
     * list all organizations where the user is a member
     *
     *
     * @ApiDoc(
     *   section = "Principal",
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
     * @param ParamFetcherInterface $paramFetcher param fetcher organization
     *
     * @return Organization
     */
    public function cgetMemberOrganizationsAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[cgetMemberOrganizations] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        $os = $em->getRepository('HexaaStorageBundle:Organization')->findAll();
        $reto = array();
        foreach ($os as $o) {
            if ($o->hasPrincipal($p)) {
                $reto[] = $o;
            }
        }
        $reto = array_filter($reto);
        //if (count($reto)<1) throw new HttpException(404, "Resource not found.");
        return $reto;
    }

    /**
     * list all entitlements of the user
     *
     *
     * @ApiDoc(
     *   section = "Principal",
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
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return array
     */
    public function cgetPrincipalEntitlementsAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[getPrincipalEntitlements] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        $rps = $em->getRepository('HexaaStorageBundle:RolePrincipal')->findByPrincipal($p);
        $es = array();
        foreach ($rps as $rp) {
            foreach ($rp->getRole()->getEntitlements() as $e) {
                if (!in_array($e, $es, true)) {
                    $es[] = $e;
                }
            }
        }
        return $es;
    }

    /**
     * list all roles of the user
     *
     *
     * @ApiDoc(
     *   section = "Principal",
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
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return array
     */
    public function cgetPrincipalRolesAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[getPrincipalRoles] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        $rps = $em->getRepository('HexaaStorageBundle:RolePrincipal')->findByPrincipal($p);
        $rs = array();
        foreach ($rps as $rp) {
            if (!in_array($rp->getRole(), $rs, true)) {
                $rs[] = $rp->getRole();
            }
        }
        return $rs;
    }

    private function processForm(Principal $p, $loglbl, $method = "PUT") {
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $statusCode = $p->getId() == null ? 201 : 204;

        $form = $this->createForm(new PrincipalType(), $p, array("method"=>$method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $em->persist($p);
            $em->flush();

            if (201 === $statusCode) {
                $modlog->info($loglbl . "New Principal created with id=" . $p->getId());
            } else {
                $modlog->info($loglbl . "Principal edited with id=" . $p->getId());
            }


            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_principal_id', array('id' => $p->getId()), true // absolute
                        )
                );
            }

            return $response;
        }

        $errorlog->error($loglbl . "Validation error");
        return View::create($form, 400);
    }

    /**
     * create new principal
     *
     *
     * @ApiDoc(
     *   section = "Principal",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when principal has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when organization is not found"
     *   },
     *   requirements = {
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="fedid","dataType"="string","required"=true,"description"="Federal ID of principal"},
     *      {"name"="email","dataType"="string","required"=true,"description"="Contact e-mail address of principal"},
     *      {"name"="display_name","dataType"="string","required"=true,"description"="Displayable name of principal"}
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
    public function postPrincipalAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[postPrincipal] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        return $this->processForm(new Principal(), $loglbl, "POST");
    }

    /**
     * principal edit by id
     *
     *
     * @ApiDoc(
     *   section = "Principal",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when principal has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when organization is not found"
     *   },
     *   requirements = {
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="principal id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="fedid","dataType"="string","required"=true,"description"="Federal ID of principal"},
     *      {"name"="email","dataType"="string","required"=true,"description"="Contact e-mail address of principal"},
     *      {"name"="display_name","dataType"="string","required"=true,"description"="Displayable name of principal"}
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
    public function putPrincipalAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $loglbl = "[putPrincipal] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
        } else {
            $toEdit = $em->getRepository('HexaaStorageBundle:Principal')->find($id);
            if ($request->getMethod() == "PUT" && !$toEdit) {
                $errorlog->error($loglbl . "the requested Principal with id=" . $id . " was not found");
                throw new HttpException(404, "Principal not found");
            }
            return $this->processForm($toEdit, $loglbl, "PUT");
        }
    }

    /**
     * principal edit by id
     *
     *
     * @ApiDoc(
     *   section = "Principal",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when principal has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when organization is not found"
     *   },
     *   requirements = {
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="principal id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *   },
     *   parameters = {
     *      {"name"="fedid","dataType"="string","required"=true,"description"="Federal ID of principal"},
     *      {"name"="email","dataType"="string","required"=true,"description"="Contact e-mail address of principal"},
     *      {"name"="display_name","dataType"="string","required"=true,"description"="Displayable name of principal"}
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
    public function patchPrincipalAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $loglbl = "[patchPrincipal] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
        } else {
            $toEdit = $em->getRepository('HexaaStorageBundle:Principal')->find($id);
            if ($request->getMethod() == "PUT" && !$toEdit) {
                $errorlog->error($loglbl . "the requested Principal with id=" . $id . " was not found");
                throw new HttpException(404, "Principal not found");
            }
            return $this->processForm($toEdit, $loglbl, "PATCH");
        }
    }

    /**
     * principal self delete
     *
     *
     * @ApiDoc(
     *   section = "Principal",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when principal has been deleted successfully",
     *     400 = "Returned on validation error",
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
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher organization
     *
     * 
     */
    public function deletePrincipalAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $loglbl = "[deletePrincipal] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        $em->remove($p);
        $em->flush();
        $modlog->info($loglbl . "Principal with id=" . $p->getId() . " deleted him/herself");
    }

    /**
     * delete principal by fedid
     *
     *
     * @ApiDoc(
     *   section = "Principal",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when principal has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="fedid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="federal ID of principal to delete"},
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
    public function deletePrincipalFedidAction(Request $request, ParamFetcherInterface $paramFetcher, $fedid) {
        $loglbl = "[deletePrincipalFedid] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with fedid=" . $fedid . " by " . $p->getFedid());

        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
        } else {
            $toDelete = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($fedid);
            if ($request->getMethod() == "DELETE" && !$toDelete) {
                $errorlog->error($loglbl . "the requested Principal with fedid=" . $fedid . " was not found");
                throw new HttpException(404, "Principal not found");
            }
            $em->remove($toDelete);
            $em->flush();
            $modlog->info($loglbl . "Principal with fedid=" . $fedid . " has been deleted");
        }
    }

    /**
     * delete principal by id
     *
     *
     * @ApiDoc(
     *   section = "Principal",
     *   resource = false,
     *   statusCodes = {
     *     204 = "Returned when principal has been deleted successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="principal id"},
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
    public function deletePrincipalIdAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $loglbl = "[deletePrincipalId] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
        } else {
            $toDelete = $em->getRepository('HexaaStorageBundle:Principal')->find($id);
            if ($request->getMethod() == "DELETE" && !$toDelete) {
                $errorlog->error($loglbl . "the requested Principal with id=" . $id . " was not found");
                throw new HttpException(404, "Principal not found");
            }
            $em->remove($toDelete);
            $em->flush();
            $modlog->info($loglbl . "Principal with id=" . $id . " has been deleted");
        }
    }

}
