<?php

namespace Hexaa\StorageBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;


class DefaultController extends Controller
{

    /**
     * @Route("/index")
     */
    public function indexAction()
    {
        return $this->render(
          '@HexaaStorage/Default/index.html.twig',
          array("ui_url" => $this->container->getParameter('hexaa_ui_url'))
        );
    }

}
