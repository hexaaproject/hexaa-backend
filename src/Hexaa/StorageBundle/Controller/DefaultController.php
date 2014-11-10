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

}
