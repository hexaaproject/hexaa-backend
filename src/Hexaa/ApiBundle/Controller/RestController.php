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
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class RestController extends FOSRestController {

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
     * $apiKey = hash('sha256', $config->getValue('hexaa_master_secret').$stamp);<br />
     * You can obtain the master secret from the HEXAA admin.
     * </p><p>
     * NOTE: The mail and display name parameters are processed only when there is no such data in the database about the user. Otherwise these values are ignored.
     * </p>
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

        // Loggers & label
        static $postTokenLabel = "[postToken], ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $loginlog = $this->get('monolog.logger.login');

        // TODO Call login hook here

        /*
         * TODO implement ip filter
         *  
          $ip = $request->getClientIp();
          if (!in_array($ip, $this->container->getParameter('hexaa_get_token_ips'))){
          throw new HttpException(403, 'Forbidden');
          return ;
          } */

        if (!$request->request->has('fedid')) {
            $errorlog->error($postTokenLabel . "no fedid found");
            $accesslog->error($postTokenLabel . "called without fedid");
            throw new HttpException(400, 'no fedid found');
            return;
        }



        $fedid = urldecode($request->request->get('fedid'));
        $accesslog->info($postTokenLabel . "call with fedid=" . $fedid);

        $em = $this->getDoctrine()->getManager();
        $p = $em->getRepository('HexaaStorageBundle:Principal')
                ->findOneByFedid($fedid);
        if (!$p) {
            $p = new Principal();
            $p->setFedid($fedid);
            $modlog->info($postTokenLabel . "new principal created with fedid=" . $fedid);
        }

        if ($p->getEmail() == null) {
            if ($request->request->has('email')) {
                
                // Validate e-mail
                $email = $request->request->get('email');
                $emailConstraint = new EmailConstraint();
                $errors = $this->get('validator')->validateValue(
                        $email, $emailConstraint
                );
                
                if (!empty($errors)){
                    $errorlog->error($postTokenLabel . $errors);
                    throw new HttpException(400, $errors);
                } else {

                $modlog->info($postTokenLabel . "principal's email has been set to email=" . $request->request->get('email') . " with fedid=" . $fedid);
                $p->setEmail($email);
                $em->persist($p);
                $em->flush();
                }
            } else {
                $errorlog->error($postTokenLabel . "no mail found, but user has no mail, yet");
                throw new HttpException(400, 'no mail found');
            }
        }

        if ($request->request->has('display_name') && ($p->getDisplayName() == null)) {
            $modlog->info($postTokenLabel . "principal's display name has been set to display_name=" . $request->request->get('display_name') . " with fedid=" . $fedid);
            $p->setDisplayName($request->request->get('display_name'));
            $em->persist($p);
            $em->flush();
        }

        $date = new \DateTime();
        date_timezone_set($date, new \DateTimeZone("UTC"));
        if (!$p->getTokenExpire()) {
            $tokenExp = new \DateTime();
            date_timezone_set($tokenExp, new \DateTimeZone("UTC"));
            $tokenExp->modify('-2 hour');
        } else {
            $tokenExp = $p->getTokenExpire();
        }
        $diff = $tokenExp->diff($date, true);
        if ((!$p->getToken()) || (strlen($p->getToken()) < 2) || (($date < $tokenExp) && ($diff->h > 1))) {
            $date->modify('+1 hour');
            $p->setToken(hash('sha256', $p->getFedid() . $date->format('Y-m-d H:i:s')));
            $p->setTokenExpire($date);

            $modlog->info($postTokenLabel . "generated new token for principal with fedid=" . $fedid);
            $em->persist($p);
            $em->flush();
        }
        $loginlog->info($postTokenLabel . "served token for principal with fedid=" . $fedid);
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

        // Loggers & label
        static $attrLabel = "[attribute release], ";
        $accesslog = $this->get('monolog.logger.access');
        $modlog = $this->get('monolog.logger.modification');
        $releaselog = $this->get('monolog.logger.release');

        if (!$request->request->has('fedid')) {
            $accesslog->error($attrLabel . "no fedid found");
            throw new HttpException(400, 'no fedid found');
            return;
        }
        if (!$request->request->has("soid")) {
            $accesslog->error($attrLabel . "no entityid found");
            throw new HttpException(400, 'no entityid found');
            return;
        }


        $soid = urldecode($request->request->get('soid'));

        $entityidConstraint = new ValidEntityid();
        $errorList = $this->get('validator')->validateValue(
                $soid, $entityidConstraint
        );

        if (count($errorList) != 0) {
            $accesslog->error($attrLabel . "entityid validation error");
            return View::create($errorList, 400);
        }

        $fedid = urldecode($request->request->get('fedid'));

        $accesslog->info($attrLabel . "called with fedid=" . $fedid . " entityid=" . $request->request->get('soid'));

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
        $releaselog->info($attrLabel . "released attributes with parameters: fedid=" . $fedid . " entityid=" . $request->request->get('soid'));

        return $retarr;
    }

}
