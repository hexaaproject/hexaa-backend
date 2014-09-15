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
use Hexaa\StorageBundle\Form\EntitlementPackType;
use Hexaa\StorageBundle\Entity\EntitlementPack;
use Hexaa\StorageBundle\Form\EntitlementType;
use Hexaa\StorageBundle\Entity\Entitlement;
use Hexaa\StorageBundle\Entity\News;
use Hexaa\StorageBundle\Form\ServiceAttributeSpecType;
use Hexaa\StorageBundle\Entity\ServiceAttributeSpec;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rest controller for HEXAA
 *
 * @package Hexaa\ApiBundle\Controller
 * @author Soltész Balázs <solazs@sztaki.hu>
 */
class ServiceChildController extends FOSRestController {

    /**
     * get managers of service
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
     * 
     * @ApiDoc(
     *   section = "Service",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\Principal>"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher 
     *
     * @return array
     */
    public function cgetManagersAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $loglbl = "[cgetServiceManagers] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
        if ($request->getMethod() == "GET" && !$s) {
            $errorlog->error($loglbl . "the requested Service with id=" . $id . " was not found");
            throw new HttpException(404, "Service not found.");
        }
        $retarr = array_slice($s->getManagers()->toArray(), $paramFetcher->get('offset'), $paramFetcher->get('limit'));
        return $retarr;
    }

    /**
     * get number of service managers
     *
     * 
     * @ApiDoc(
     *   section = "Service",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher 
     *
     * @return array
     */
    public function getManagerCountAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $loglbl = "[getServiceManagerCount] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
        if ($request->getMethod() == "GET" && !$s) {
            $errorlog->error($loglbl . "the requested Service with id=" . $id . " was not found");
            throw new HttpException(404, "Service not found.");
        }
        $retarr = array("count"=> count($s->getManagers()->toArray()));
        return $retarr;
    }

    /**
     * get Attribute specifications linked to the service
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
     * 
     * @ApiDoc(
     *   section = "Service",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\AttributeSpec>"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher 
     *
     * @return array
     */
    public function cgetAttributespecsAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $loglbl = "[cgetServiceAttributeSpecs] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
        if ($request->getMethod() == "GET" && !$s) {
            $errorlog->error($loglbl . "the requested Service with id=" . $id . " was not found");
            throw new HttpException(404, "Service not found.");
        }
        $retarr = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->findBy(array("service" => $s), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset'));
        return $retarr;
    }

    /**
     * Get all EntitlementPack - Organization connections related to the service.
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
     * 
     * @ApiDoc(
     *   section = "Service",
     *   description = "get entitlementpack - organizations relations",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\Organization>"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher 
     *
     * @return array
     */
    public function cgetEntitlementpackRequestsAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $loglbl = "[cgetServiceOrganizations] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
        if ($request->getMethod() == "GET" && !$s) {
            $errorlog->error($loglbl . "the requested Service with id=" . $id . " was not found");
            throw new HttpException(404, "Service not found.");
        }

        $retarr = $em->createQueryBuilder()
                ->select('oep')
                ->from('HexaaStorageBundle:OrganizationEntitlementPack', 'oep')
                ->innerJoin('oep.organization', 'o')
                ->innerJoin('oep.entitlementPack', 'ep')
                ->where("oep.status = 'accepted'")
                ->andWhere('ep.service = :s')
                ->setFirstResult($paramFetcher->get('offset'))
                ->setMaxResults($paramFetcher->get('limit'))
                ->setParameters(array("s" => $s))
                ->getQuery()
                ->getResult()
        ;

        return $retarr;
    }

    /**
     * Get all Organization connected (through some EntitlementPacks) to the service.
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
     * 
     * @ApiDoc(
     *   section = "Service",
     *   description = "get organizations linked to the service",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\Organization>"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher 
     *
     * @return array
     */
    public function cgetOrganizationsAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $loglbl = "[cgetServiceOrganizations] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
        if ($request->getMethod() == "GET" && !$s) {
            $errorlog->error($loglbl . "the requested Service with id=" . $id . " was not found");
            throw new HttpException(404, "Service not found.");
        }

        $retarr = $em->createQueryBuilder()
                ->select('o')
                ->from('HexaaStorageBundle:Organization', 'o')
                ->innerJoin('HexaaStorageBundle:OrganizationEntitlementPack', 'oep', 'WITH', 'oep.organization = o')
                ->innerJoin('oep.entitlementPack', 'ep')
                ->where("oep.status = 'accepted'")
                ->andWhere('ep.service = :s')
                ->setFirstResult($paramFetcher->get('offset'))
                ->setMaxResults($paramFetcher->get('limit'))
                ->setParameters(array("s" => $s))
                ->getQuery()
                ->getResult()
        ;

        return $retarr;
    }

    /**
     * remove manager from service
     *
     *
     * @ApiDoc(
     *   section = "Service",
     *   resource = true,
     *   statusCodes = {
     * 	   204 = "Returned on success",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="pid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="principal id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher 
     *
     */
    public function deleteManagerAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $pid) {
        $loglbl = "[deleteServiceManager] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " and pid=" . $pid . " by " . $p->getFedid());

        $s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
        if ($request->getMethod() == "DELETE" && !$s) {
            $errorlog->error($loglbl . "the requested Service with id=" . $id . " was not found");
            throw new HttpException(404, "Service not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$s->hasManager($p) && $pid != $p->getId()) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        $p = $em->getRepository('HexaaStorageBundle:Principal')->find($pid);
        if ($request->getMethod() == "DELETE" && !$p) {
            $errorlog->error($loglbl . "the requested Principal with id=" . $pid . " was not found");
            throw new HttpException(404, "Principal not found.");
        }
        if ($s->hasManager($p)) {
            $s->removeManager($p);
            $em->persist($s);

            //Create News object to notify the user
            $n = new News();
            $n->setPrincipal($p);
            $n->setService($s);
            $n->setTitle("Service management changed");
            $n->setMessage($p->getFedid() . " is no longer a manager of service " . $s->getName());
            $n->setTag("service_manager");
            $em->persist($n);
            $em->flush();
            $modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());

            $modlog->info($loglbl . "Principal (id=" . $pid . ") removed from the managers of Service (id=" . $id . ")");
        }
    }

    /**
     * add manager to service
     *
     *
     * @ApiDoc(
     *   section = "Service",
     *   resource = true,
     *   statusCodes = {
     * 	   201 = "Returned on success",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="pid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="principal id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View(statusCode=201)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher 
     *
     */
    public function putManagerAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $pid) {
        $loglbl = "[putServiceManager] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " and pid=" . $pid . " by " . $p->getFedid());

        $s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
        if ($request->getMethod() == "PUT" && !$s) {
            $errorlog->error($loglbl . "the requested Service with id=" . $id . " was not found");
            throw new HttpException(404, "Service not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$s->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        $p = $em->getRepository('HexaaStorageBundle:Principal')->find($pid);
        if ($request->getMethod() == "PUT" && !$p) {
            $errorlog->error($loglbl . "the requested Principal with id=" . $pid . " was not found");
            throw new HttpException(404, "Principal not found.");
        }
        if (!$s->hasManager($p)) {
            $s->addManager($p);
            $em->persist($s);

            //Create News object to notify the user
            $n = new News();
            $n->setPrincipal($p);
            $n->setService($s);
            $n->setTitle("Service management changed");
            $n->setMessage($p->getFedid() . " is now a manager of service " . $s->getName());
            $n->setTag("service_manager");
            $em->persist($n);
            $em->flush();
            $modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());

            $modlog->info($loglbl . "Principal (id=" . $pid . ") added to the managers of Service (id=" . $id . ")");
        }
    }

    /**
     * remove attribute specification from service
     *
     *
     * @ApiDoc(
     *   section = "Service",
     *   resource = true,
     *   statusCodes = {
     * 	   204 = "Returned on success",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="asid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute specification id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  }
     * )
     *
     * 
     * @Annotations\View(statusCode=204)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher 
     *
     */
    public function deleteAttributespecAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $asid) {
        $loglbl = "[deleteServiceAttributeSpec] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " and asid=" . $asid . " by " . $p->getFedid());

        $s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
        if ($request->getMethod() == "DELETE" && !$s) {
            $errorlog->error($loglbl . "the requested Service with id=" . $id . " was not found");
            throw new HttpException(404, "Service not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$s->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        $as = $em->getRepository('HexaaStorageBundle:AttributeSpec')->find($asid);
        if (!$as) {
            $errorlog->error($loglbl . "the requested AttributeSpec with id=" . $asid . " was not found");
            throw new HttpException(404, "AttributeSpec not found");
        }
        try {
            $sas = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->createQueryBuilder('sas')
                    ->where('sas.service = :s')
                    ->andwhere('sas.attributeSpec = :as')
                    ->setParameters(array(':s' => $s, ':as' => $as))
                    ->getQuery()
                    ->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            $errorlog->error($loglbl . "No service attributeSpec link was not found");
            throw new HttpException(404, "Resource not found.");
        }
        $em->remove($sas);


        //Create News object to notify the user
        $n = new News();
        $n->setService($s);
        $n->setAdmin();
        $n->setTitle("Attribute specification removed from service");
        $n->setMessage($sas->getAttributeSpec()->getFriendlyName() . " has been unlinked from service " . $s->getName());
        $n->setTag("service_attribute_spec");
        $em->persist($n);
        $em->flush();
        $modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());

        $modlog->info($loglbl . "Attribute specification (id=" . $asid . ") removed from Service (id=" . $id . ")");
    }

    /**
     * add attribute specification to service
     *
     *
     * @ApiDoc(
     *   section = "Service",
     *   resource = true,
     *   statusCodes = {
     * 	   201 = "Returned on success",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when object is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="asid", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="attribute specification id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  parameters = {
     *      {"name"="is_public", "dataType"="boolean", "required"=true, "format"="true|false", "description"="Set wether to allow any or only connected users to set the attribute."}
     *  }
     * )
     *
     * 
     * @Annotations\View(statusCode=201)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher 
     *
     */
    public function putAttributespecAction(Request $request, ParamFetcherInterface $paramFetcher, $id, $asid) {
        $loglbl = "[putServiceAttributeSpec] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " and asid=" . $asid . " by " . $p->getFedid());

        $s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
        if ($request->getMethod() == "PUT" && !$s) {
            $errorlog->error($loglbl . "the requested Service with id=" . $id . " was not found");
            throw new HttpException(404, "Service not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$s->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }

        $as = $em->getRepository('HexaaStorageBundle:AttributeSpec')->find($asid);
        if (!$as) {
            $errorlog->error($loglbl . "the requested AttributeSpec with id=" . $asid . " was not found");
            throw new HttpException(404, "AttributeSpec not found.");
        }

        try {
            $sas = $em->getRepository('HexaaStorageBundle:ServiceAttributeSpec')->createQueryBuilder('sas')
                    ->where('sas.service = :s')
                    ->andwhere('sas.attributeSpec = :as')
                    ->setParameters(array(':s' => $s, ':as' => $as))
                    ->getQuery()
                    ->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            $sas = new ServiceAttributeSpec();
            $sas->setAttributeSpec($as);
            $sas->setService($s);
        }

        return $this->processSASForm($sas, $loglbl, "PUT");
    }

    private function processSASForm(ServiceAttributeSpec $sas, $loglbl, $method = "PUT") {
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $statusCode = $sas->getId() == null ? 201 : 204;

        $form = $this->createForm(new ServiceAttributeSpecType(), $sas, array("method" => $method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $em->persist($sas);

            //Create News object to notify the user
            $n = new News();
            $n->setService($sas->getService());
            $n->setAdmin();
            $n->setTitle("Attribute specification added to service");
            $n->setMessage($sas->getAttributeSpec()->getFriendlyName() . " has been linked to service " . $sas->getService()->getName());
            $n->setTag("service_attribute_spec");
            $em->persist($n);
            $em->flush();
            $modlog->info($loglbl . "Created News object with id=" . $n->getId() . " about " . $n->getTitle());

            if (201 === $statusCode) {
                $modlog->info($loglbl . "Attribute Spec (id=" . $sas->getAttributeSpec()->getId() . ") linked to Service (id=" . $sas->getService()->getId() . ")");
            } else {
                $modlog->info($loglbl . "Attribute Spec (id=" . $sas->getAttributeSpec()->getId() . ") is already linked to Service (id=" . $sas->getService()->getId() . ")");
            }

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_service', array('id' => $sas->getService()->getId()), true // absolute
                        )
                );
            }

            return $response;
        }
        $errorlog->error($loglbl . "Validation error");
        return View::create($form, 400);
    }

    /**
     * get entitlements of service
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
     * 
     * @ApiDoc(
     *   section = "Service",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement pack is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\Entitlement>"
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement pack
     *
     * @return array
     */
    public function cgetEntitlementsAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $loglbl = "[cgetServiceEntitlements] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
        if (!$s) {
            $errorlog->error($loglbl . "the requested Service with id=" . $id . " was not found");
            throw new HttpException(404, "Service not found.");
        }
        $es = $em->getRepository('HexaaStorageBundle:Entitlement')->findBy(array("service" => $s), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset'));
        return $es;
    }

    /**
     * get entitlement packs of service
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
     * 
     * @ApiDoc(
     *   section = "Service",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement pack is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\EntitlementPack>"
     * )
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement pack
     *
     * @return array
     */
    public function cgetEntitlementpacksAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $loglbl = "[cgetServiceEntitlementPacks] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
        if (!$s) {
            $errorlog->error($loglbl . "the requested Service with id=" . $id . " was not found");
            throw new HttpException(404, "Service not found.");
        }
        $ep = $em->getRepository('HexaaStorageBundle:EntitlementPack')->findBy(array("service" => $s), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset'));
        return $ep;
    }

    /**
     * create new entitlement pack
     *
     *
     * @ApiDoc(
     *   section = "EntitlementPack",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when entitlement pack has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement pack is not found"
     *   },
     *   requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *  parameters = {
     *      {"name"="name","dataType"="string","required"=true,"description"="Displayable name of the entitlement package"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"},
     *      {"name"="type","dataType"="string","required"=true,"format"="private|public","description"="Visibility of the entitlement package"},
     *  }
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement pack
     *
     * 
     */
    public function postEntitlementpackAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $loglbl = "[postServiceEntitlementPack] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
        if ($request->getMethod() == "POST" && !$s) {
            $errorlog->error($loglbl . "the requested Service with id=" . $id . " was not found");
            throw new HttpException(404, "Service not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$s->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }

        $ep = new EntitlementPack();
        $ep->setService($s);
        return $this->processForm($ep, $loglbl, "POST");
    }

    private function processForm(EntitlementPack $ep, $loglbl, $method = "PUT") {
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $statusCode = $ep->getId() == null ? 201 : 204;

        $form = $this->createForm(new EntitlementPackType(), $ep, array("method" => $method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $em->persist($ep);
            $em->flush();

            if (201 === $statusCode) {
                $modlog->info($loglbl . "New Entitlement Pack created with id=" . $ep->getId());
            } else {
                $modlog->info($loglbl . "Entitlement Pack edited with id=" . $ep->getId());
            }

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_entitlementpack', array('id' => $ep->getId()), true // absolute
                        )
                );
            }

            return $response;
        }
        $errorlog->error($loglbl . "Validation error");
        return View::create($form, 400);
    }

    /**
     * create new entitlement
     *
     *
     * @ApiDoc(
     *   section = "Entitlement",
     *   resource = false,
     *   statusCodes = {
     *     201 = "Returned when entitlement has been created successfully",
     *     400 = "Returned on validation error",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when entitlement is not found"
     *   },
     *   requirement = {
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}    
     *   },
     *  parameters = {
     *      {"name"="uri","dataType"="string","required"=true,"description"="URI of entitlement"},
     *      {"name"="name","dataType"="string","required"=true,"description"="displayable name of the entitlement"},
     *      {"name"="description","dataType"="string","required"=false,"description"="description"}
     *  }        
     * )
     *
     * 
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher entitlement
     *
     * 
     */
    public function postEntitlementAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $loglbl = "[postServiceEntitlements] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
        if ($request->getMethod() == "POST" && !$s) {
            $errorlog->error($loglbl . "the requested Service with id=" . $id . " was not found");
            throw new HttpException(404, "Service not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$s->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }

        $e = new Entitlement();
        $e->setService($s);

        return $this->processEForm($e, $loglbl, "POST");
    }

    private function processEForm(Entitlement $e, $loglbl, $method = "PUT") {
        $errorlog = $this->get('monolog.logger.error');
        $modlog = $this->get('monolog.logger.modification');
        $em = $this->getDoctrine()->getManager();
        $statusCode = $e->getId() == null ? 201 : 204;

        $form = $this->createForm(new EntitlementType(), $e, array("method" => $method));
        $form->submit($this->getRequest()->request->all(), 'PATCH' !== $method);

        if ($form->isValid()) {
            $em->persist($e);
            $em->flush();

            if (201 === $statusCode) {
                $modlog->info($loglbl . "New Entitlement created with id=" . $e->getId());
            } else {
                $modlog->info($loglbl . "Entitlement edited with id=" . $e->getId());
            }

            $response = new Response();
            $response->setStatusCode($statusCode);

            // set the `Location` header only when creating new resources
            if (201 === $statusCode) {
                $response->headers->set('Location', $this->generateUrl(
                                'get_entitlement', array('id' => $e->getId()), true // absolute
                        )
                );
            }

            return $response;
        }

        $errorlog->error($loglbl . "Validation error");
        return View::create($form, 400);
    }

    /**
     * list all pending and rejected invitations of the specified service
     *
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default=null, description="How many items to return.")
     * 
     * @ApiDoc(
     *   section = "Service",
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     401 = "Returned when token is expired",
     *     403 = "Returned when not permitted to query",
     *     404 = "Returned when resource is not found"
     *   },
     * requirements ={
     *      {"name"="id", "dataType"="integer", "required"=true, "requirement"="\d+", "description"="service id"},
     *      {"name"="_format", "requirement"="xml|json", "description"="response format"}
     *  },
     *   output="array<Hexaa\StorageBundle\Entity\Invitation>"
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
    public function cgetInvitationsAction(Request $request, ParamFetcherInterface $paramFetcher, $id) {
        $loglbl = "[cgetServiceInvitations] ";
        $accesslog = $this->get('monolog.logger.access');
        $errorlog = $this->get('monolog.logger.error');
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $p = $em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());
        $accesslog->info($loglbl . "Called with id=" . $id . " by " . $p->getFedid());

        $s = $em->getRepository('HexaaStorageBundle:Service')->find($id);
        if ($request->getMethod() == "GET" && !$s) {
            $errorlog->error($loglbl . "the requested Service with id=" . $id . " was not found");
            throw new HttpException(404, "Service not found.");
        }
        if (!in_array($p->getFedid(), $this->container->getParameter('hexaa_admins')) && !$s->hasManager($p)) {
            $errorlog->error($loglbl . "user " . $p->getFedid() . " has insufficent permissions");
            throw new HttpException(403, "Forbidden");
            return;
        }
        $is = $em->getRepository('HexaaStorageBundle:Invitation')->findBy(array("service" => $s), array(), $paramFetcher->get('limit'), $paramFetcher->get('offset'));
        return $is;
    }

}
