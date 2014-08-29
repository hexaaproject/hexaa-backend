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
use Hexaa\StorageBundle\Entity\News;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class NewsController extends FOSRestController implements ClassResourceInterface {

    /**
     * get news for the current user
     * Note: if tags, services and/or organizations are left empty, all of them will be returned.
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing news.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default="10", description="How many news to return.")
     * @Annotations\QueryParam(name="tags", array=true, default={}, description="Tags to filter the query")
     * @Annotations\QueryParam(name="services", array=true, default={}, description="Service IDs to filter the query")
     * @Annotations\QueryParam(name="organizations", array=true, default={}, description="Organization IDs to filter the query")
     * @ApiDoc(
     *   section = "News",
     *   resource = true,
     *   description = "get news for the current user",
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
     * @param ParamFetcherInterface $paramFetcher param fetcher attribute specification
     *
     * @return array
     */
    public function getAction(Request $request, ParamFetcherInterface $paramFetcher) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[getNews] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());


        $tags = $paramFetcher->get('tags');
        $services = $paramFetcher->get('services');
        $organizations = $paramFetcher->get('organizations');

        $qb = $em->createQueryBuilder();

        $qb
                ->select('n')
                ->from('HexaaStorageBundle:News', 'n')
                ->leftJoin('n.service', 's')
                ->leftJoin('n.organization', 'o')
                ->where('(:p MEMBER OF s.managers) OR (:p MEMBER OF o.principals) OR (:p = n.principal)');

        if (is_array($tags) && count($tags) > 0) {
            $qb->andWhere('n.tag IN(:tags)');
        }
        if (is_array($services) && count($services) > 0) {
            $qb->andWhere('s.id IN(:services)');
        }
        if (is_array($organizations) && count($organizations) > 0) {
            $qb->andWhere('o.id IN(:organizations)');
        }
        $qb->orderBy('n.createdAt')
                ->setFirstResult($paramFetcher->get('offset'))
                ->setMaxResults($paramFetcher->get('limit'))
                ->setParameter("p", $p);

        if (is_array($tags) && count($tags) > 0) {
            $qb->setParameter("tags", $tags);
        }
        if (is_array($services) && count($services) > 0) {
            $qb->setParameter("services", $services);
        }
        if (is_array($organizations) && count($organizations) > 0) {
            $qb->setParameter("organizations", $organizations);
        }
        $news = $qb->getQuery()
                ->getResult()
        ;
        return $news;
    }

    /**
     * get news for the specified user<br>
     * Note: Admins only!
     * Note: if tags, services and/or organizations are left empty, all of them will be returned.
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing news.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default="10", description="How many news to return.")
     * @Annotations\QueryParam(name="tags", array=true, default={}, description="Tags to filter the query")
     * @Annotations\QueryParam(name="services", array=true, default={}, description="Service IDs to filter the query")
     * @Annotations\QueryParam(name="organizations", array=true, default={}, description="Organization IDs to filter the query")
     * @ApiDoc(
     *   section = "News",
     *   resource = true,
     *   desctiption = "get news for the specified user",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="pid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="principal id"},
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
     * @return array
     */
    public function cgetAction(Request $request, ParamFetcherInterface $paramFetcher, $pid = 0) {
        $em = $this->getDoctrine()->getManager();
        $loglbl = "[getNews] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called by " . $p->getFedid());

        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins'))) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }

        $p = $em->getRepository('HexaaStorageBundle:Principal')->find($pid);
        if (!$p) {
            $errorlog->error($loglbl . "The requested princpal with id=" . $pid . " was not found.");
            throw new HttpException(404, "The requested princpal with id=" . $pid . " was not found.");
        }

        $tags = $paramFetcher->get('tags');
        $services = $paramFetcher->get('services');
        $organizations = $paramFetcher->get('organizations');

        $qb = $em->createQueryBuilder();

        $qb
                ->select('n')
                ->from('HexaaStorageBundle:News', 'n')
                ->leftJoin('n.service', 's')
                ->leftJoin('n.organization', 'o')
                ->where('(:p MEMBER OF s.managers) OR (:p MEMBER OF o.principals) OR (:p = n.principal)');

        if (is_array($tags) && count($tags) > 0) {
            $qb->andWhere('n.tag IN(:tags)');
        }
        if (is_array($services) && count($services) > 0) {
            $qb->andWhere('s.id IN(:services)');
        }
        if (is_array($organizations) && count($organizations) > 0) {
            $qb->andWhere('o.id IN(:organizations)');
        }
        $qb->orderBy('n.createdAt')
                ->setFirstResult($paramFetcher->get('offset'))
                ->setMaxResults($paramFetcher->get('limit'))
                ->setParameter("p", $p);

        if (is_array($tags) && count($tags) > 0) {
            $qb->setParameter("tags", $tags);
        }
        if (is_array($services) && count($services) > 0) {
            $qb->setParameter("services", $services);
        }
        if (is_array($organizations) && count($organizations) > 0) {
            $qb->setParameter("organizations", $organizations);
        }
        $news = $qb->getQuery()
                ->getResult()
        ;
        return $news;
    }

}
