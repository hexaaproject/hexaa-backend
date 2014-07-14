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
        $em = $this->getDoctrine()->getManager();
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
    public function getPrincipalAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
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
        $em = $this->getDoctrine()->getManager();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->find($id);
        if (!$p)
            throw new HttpException(404, "Principal not found");
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
        $em = $this->getDoctrine()->getManager();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($fedid);
        if ($request->getMethod() == "GET" && !$p)
            throw new HttpException(404, "Principal not found");
        return $p;
    }

    /**
     * list all email invitations of the current principal
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
    public function cgetPrincipalEmailinvitationsAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $eis = $em->getRepository('HexaaStorageBundle:EmailInvitation')->findAll();
        return $eis;
    }

    /**
     * list all mass invitations of the current principal
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
    public function cgetPrincipalUrlinvitationsAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $uis = $em->getRepository('HexaaStorageBundle:UrlInvitation')->findAll();
        return $uis;
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
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
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
                if ($oep->getStatus() == "accepted" && !in_array($ep, $eps)) {
                    $eps[] = $ep;
                }
            }
        }

        // Collect connected services
        $css = array();
        foreach ($eps as $ep) {
            $s = $ep->getService();
            if (!in_array($s, $css)) {
                $css[] = $s;
            }
        }


        $ss = array_filter($ss);
        if (count($ss) < 1)
            throw new HttpException(404, "Resource not found.");
        $retarr = array();
        foreach ($ss as $s) {
            $sass = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findByService($s);
            if (in_array($s, $css, true)) {
                foreach ($sass as $sas) {
                    if (!in_array($sas, $retarr, true)) {
                        $retarr[] = $sas;
                    }
                }
            } else {
                foreach ($sass as $sas) {
                    if ((!in_array($sas, $retarr, true)) && ($sas->getIsPublic() == true)) {
                        $retarr[] = $sas;
                    }
                }
            }
        }

        $retarr = array_filter($retarr);
        //if (count($retarr)<1) throw new HttpException(404, "Resource not found.");
        return $retarr;
    }

    /**
     * list available attribute values per principal and attribute specification
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
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        /*
          $ss = $em->getRepository('HexaaStorageBundle:Service')->findAll();
          $os = $em->getRepository('HexaaStorageBundle:Organization')->findAll();

          // Collect Organizations where user is a member
          $psos = array();
          foreach ($os as $o) {
          if ($o->hasPrincipal($p)){
          $psos[] = $o;
          }
          }

          // Collect connected entitlement packs
          $eps = array();
          foreach ($psos as $o){
          $oeps = $em->getRepository('HexaaStorageBundle:OrganizationEntitlementPack')->findByOrganization($o);
          foreach ($oeps as $oep) {
          $ep = $oep->getEntitlementPack();
          if ($oep->getStatus() == "accepted" && !in_array($ep,$eps)){
          $eps[] = $ep;
          }
          }
          }

          // Collect connected services
          $css = array();
          foreach ($eps as $ep){
          $s = $ep->getService();
          if(!in_array($s, $css)){
          $css[] = $s;
          }
          }


          $ss = array_filter($ss);
          if ($request->getMethod()=="GET" && count($ss)<1) throw new HttpException(404, "Service not found.");
          $ass = array();
          foreach($ss as $s){
          $sass = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findByService($s);
          if (in_array($s, $css, true)) {
          foreach ($sass as $sas){
          if (!in_array($sas, $ass, true)){
          $ass[]=$sas;
          }
          }
          } else {
          foreach ($sass as $sas){
          if ((!in_array($sas, $ass, true)) && ($sas->getIsPublic()==true)){
          $ass[]=$sas;
          }
          }
          }
          }

          $ass = array_filter($ass);
          //if (count($retarr)<1) throw new HttpException(404, "Resource not found.");
         */
        $as = $em->getRepository('HexaaStorageBundle:AttributeSpec')->find($asid);
        if ($request->getMethod()=="GET" && !$as)
            throw new HttpException(404, "AttributeSpec not found.");
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
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
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
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
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
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
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
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
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

    private function processForm(Principal $p) {
        $em = $this->getDoctrine()->getManager();
        $statusCode = $p->getId() == null ? 201 : 204;

        $form = $this->createForm(new PrincipalType(), $p);
        $form->bind($this->getRequest());

        if ($form->isValid()) {
            if (201 === $statusCode) {
                
            }
            $em->persist($p);
            $em->flush();

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_principal', array('id' => $p->getId()), true // absolute
                        )
                );
            }

            return $response;
        }

        return View::create($form, 400);
    }

    /**
     * create new principal
     *
     *
     * @ApiDoc(
     *   section = "Principal",
     *   tags = {"dev-only"},
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
     *      {"name"="fedid","dataType"="string","required"=true,"description"="Federal ID of principal"}
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
        /* $em = $this->getDoctrine()->getManager();
          $s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
          if (!$s) throw new HttpException(404, "Resource not found."); */

        return $this->processForm(new Principal());
    }

}
