<?php

namespace Hexaa\ApiBundle\Controller;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\RouteRedirectView;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Hexaa\ApiBundle\Validator\Constraints\ValidEntityid;
use Hexaa\StorageBundle\Entity\Principal;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class RestController extends FOSRestController {

    /**
     * list service entityIds from config
     *
     *
     * @ApiDoc(
     *   section = "Other",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
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
    public function cgetEntityidsAction(Request $request, ParamFetcherInterface $paramFetcher) {
        return $this->container->getParameter('hexaa_service_entityids');
    }

    /**
     * <p>
     * Returns a user token to access HEXAA API with.
     * </p>
     * <p>
     * This API call uses master secret authentication<br />
     * To get your token you need to provide a one time api key and a federal ID.<br />
     * The API key is created by the following code:</p>
     * 
     * <p>
     * $time = new \DateTime();<br />
     * date_timezone_set($time, new \DateTimeZone('UTC'));<br />
     * $stamp = $time->format('Y-m-d H:i');<br />
     * $apiKey = hash('sha256', $config->getValue('hexaa_master_secret').$stamp);</p>
     * 
     * You can obtain the master secret from the HEXAA admin.
     *
     *
     * @ApiDoc(
     *   description = "get a token for the API",
     *   section = "Other",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     400 = "Returned on bad request",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     * requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  parameters = {
     *      {"name"="fedid", "dataType"="string", "required"=true, "description"="Federal ID of principal"},
     *      {"name"="email", "dataType"="string", "required"=false, "description"="Contact e-mail of principal"},
     *      {"name"="display_name", "dataType"="string", "required"=false, "description"="Displayable name of principal"},
     *      {"name"="apikey", "dataType"="string", "required"=true, "description"="API key generated from master secret"}
     *  }
     * )
     *
     * 
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return String
     */
    public function postTokenAction(Request $request, ParamFetcherInterface $paramFetcher) {

        // TODO Login hook caller ide, amíg nincs, így biztosítjuk, hogy Principal objektuma a usernek

        /* $ip = $request->getClientIp();
          if (!in_array($ip, $this->container->getParameter('hexaa_get_token_ips'))){
          throw new HttpException(403, 'Forbidden');
          return ;
          } */
        if (!$request->request->has('fedid')) {
            throw new HttpException(400, 'no fedid found');
            return;
        }


        $fedid = urldecode($request->request->get('fedid'));


        $em = $this->getDoctrine()->getManager();
        $p = $em->getRepository('HexaaStorageBundle:Principal')
                ->findOneByFedid($fedid);
        if (!$p) {
            $p = new Principal();
            $p->setFedid($fedid);
        }

        if ($request->request->has('email')) {
            $p->setEmail($request->request->get('email'));
        }

        if ($request->request->has('display_name')) {
            $p->setDisplayName($request->request->get('display_name'));
        }


        $date = new \DateTime();
        date_timezone_set($date, new \DateTimeZone("UTC"));
        if (!$p->getTokenExpire()) {
            $tokenExp = new \DateTime();
            $tokenExp->modify('-2 hour');
        } else {
            $tokenExp = $p->getTokenExpire();
        }
        $diff = $tokenExp->diff($date, true);
        if ((!$p->getToken()) || (strlen($p->getToken()) < 2) || ($date > $tokenExp) || ($diff->format("H") > 1)) {
            $date->modify('+1 hour');
            $p->setToken(hash('sha256', $p->getFedid() . $date->format('Y-m-d H:i:s')));
            $p->setTokenExpire($date);
            $em->persist($p);
            $em->flush();
        }

        return array("fedid" => $p->getFedid(), "token" => $p->getToken());
    }

    /**
     * <p>
     * Returns an associative array containing all attributes and entitlements.<br />
     * Used mainly by simplesamlphp to get attributes.
     * </p>
     * <p>
     * This API call uses master secret authentication<br />
     * To get your token you need to provide a one time api key and a federal ID.<br />
     * The API key is created by the following code:</p>
     * 
     * <p>
     * $time = new \DateTime();<br />
     * date_timezone_set($time, new \DateTimeZone('UTC'));<br />
     * $stamp = $time->format('Y-m-d H:i');<br />
     * $apiKey = hash('sha256', $config->getValue('hexaa_master_secret').$stamp);</p>
     * 
     * You can obtain the master secret from the HEXAA admin.
     * 
     *
     *
     * @ApiDoc(
     *   description = "get all attributes (including entitlements) for a principal per service",
     *   section = "Other",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     400 = "Returned on bad request",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when service is not found"
     *   },
     * requirements ={
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  parameters = {
     *      {"name"="fedid", "dataType"="string", "required"=true, "description"="Federal ID of principal"},
     *      {"name"="soid", "dataType"="string", "required"=true, "description"="Entityid of a service"},
     *      {"name"="apikey", "dataType"="string", "required"=true, "description"="API key generated from master secret"}
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
    public function postAttributesAction(Request $request) {
        if (!$request->request->has('fedid')) {
            throw new HttpException(400, 'no fedid found');
            return;
        }
        if (!$request->request->has("soid")) {
            throw new HttpException(400, 'no entityid found');
            return;
        }
        $soid = urldecode($request->request->get('soid'));
        $entityidConstraint = new ValidEntityid();
        $errorList = $this->get('validator')->validateValue(
                $soid, $entityidConstraint
        );

        if (count($errorList) != 0) {
            return View::create($errorList, 400);
        }

        $fedid = urldecode($request->request->get('fedid'));

        $attrs = array();
        $retarr = array();
        $now = new \DateTime();
        $em = $this->container->get('doctrine')->getManager();

        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid(urldecode($fedid));
        $s = $em->getRepository("HexaaStorageBundle:Service")->findOneByEntityid($soid);

        // Get the attributes required by the Service
        $savps = $em->getRepository('HexaaStorageBundle:ServiceAttributeValuePrincipal')->findBy(array('service' => $s, 'isAllowed' => true));
        $ids = array();
        foreach ($savps as $savp) {
            $id = $savp->getAttributeValuePrincipal()->getId();
            if (!in_array($id, $ids, true)) {
                array_push($ids, $id);
            }
        }

        // Get the values by principal
        $avps = $em->createQuery('SELECT attvalp FROM HexaaStorageBundle:AttributeValuePrincipal attvalp WHERE attvalp.principal=(:p) AND attvalp.id in (:ids)')
                        ->setParameters(array('ids' => $ids, 'p' => $p))->getResult();

        // Place the attributes in the return array
        foreach ($avps as $avp) {
            $retarr[$avp->getAttributeSpec()->getOid()] = array();
        }

        foreach ($avps as $avp) {
            array_push($retarr[$avp->getAttributeSpec()->getOid()], $avp->getValue());
        }

        // Get the values by organization
        $avos = $em->getRepository('HexaaStorageBundle:AttributeValueOrganization')->findAll();
        foreach ($avos as $avo) {
            if ($avo->hasService($s)) {
                if (!array_key_exists($avo->getAttributeSpec()->getOid(), $retarr)) {
                    $retarr[$avo->getAttributeSpec()->getOid()] = array();
                }
                array_push($retarr[$avo->getAttributeSpec()->getOid()], $avo->getValue());
            }
        }

        // Collect the entitlements of the service
        $eps = $em->getRepository('HexaaStorageBundle:EntitlementPack')->findByService($s);
        $es = array();
        foreach ($eps as $ep) {
            foreach ($ep->getEntitlements() as $e) {
                if (!in_array($e, $es, true)) {
                    array_push($es, $e);
                }
            }
        }
        // Collect roles of principal
        $rps = $em->getRepository('HexaaStorageBundle:RolePrincipal')->findByPrincipal($p);

        $retarr['eduPersonEntitlement'] = array();

        // Cross reference entitlements with roles
        foreach ($rps as $rp) {
            foreach ($es as $e) {
                if (($rp->getRole()->hasEntitlement($e)) && (($rp->getRole()->getStartDate() == null) || ($rp->getRole()->getStartDate() < $now)) && (($rp->getRole()->getEndDate() == null) || ($rp->getRole()->getEndDate() > $now))) {
                    if (!in_array($e->getUri(), $retarr['eduPersonEntitlement'])) {
                        array_push($retarr['eduPersonEntitlement'], $e->getUri());
                    }
                }
            }
        }

        //$retarr['HexaaApiKey'] = $p->getToken();

        return $retarr;
    }

}
