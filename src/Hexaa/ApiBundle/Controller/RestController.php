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

    /*
     * @Annotations\View()
     */

    public function getGetToken(Request $request, ParamFetcherInterface $paramFetcher, $fedid) {

        // TODO Login hook caller ide, amíg nincs, így biztosítjuk, hogy Principal objektuma a usernek



        $em = $this->getDoctrine()->getManager();
        $p = $em->getRepository('HexaaStorageBundle:Principal')
                ->findOneByFedid($fedid);
        if (!$p) {
            $p = new Principal();
            $p->setFedid($fedid);
        }
        $date = new \DateTime();
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
        
        return $p->getToken();
    }

}
