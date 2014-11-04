<?php

namespace Hexaa\StorageBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DefaultController extends Controller {

    /**
     * @Route("/index")
     * @Template("HexaaStorageBundle:Default:index.html.twig")
     */
    public function indexAction() {
        return array("ui_url" => $this->container->getParameter('hexaa_ui_url'));
    }

    /**
     * enable service
     * @Route("/enableservice/{token}")
     * @Template("HexaaStorageBundle:Default:enableService.html.twig")
     */
    public function enableServiceAction($token = "nullToken") {
        /* No logging for now
        $loglbl = $request->attributes->get('_controller');
        $accesslog = $this->get('monolog.logger.access');
        $modlog = $this->get('monolog.logger.modification');
        $errorlog = $this->get('monolog.logger.error');
        $accesslog->info($loglbl . "Called with token=" . $token);*/
        $em = $this->getDoctrine()->getManager();

        $s = $em->getRepository('HexaaStorageBundle:Service')->findOneByEnableToken($token);
        if (!$s) {
            //$errorlog->error($loglbl . "the requested Service with id=" . $id . " was not found");
            throw new HttpException(403, "Invalid token.");
        }

        $s->setIsEnabled(true);

        $em->persist($s);
        $em->flush();
        //$modlog->info($loglbl . 'Service with id=' . $s->getId() . ' has been enabled.');
        return array(
          "service" => $s,
        );
    }

}
