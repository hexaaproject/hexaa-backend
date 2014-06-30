<?php

namespace Hexaa\StorageBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller
{
    /**
     * @Route("/index")
     * @Template("HexaaStorageBundle:Default:index.html.twig")
     */
    public function indexAction()
    {
        return array();
    }
    
    /**
     * @Route("/services")
     * @Template("HexaaStorageBundle:Default:services.html.twig")
     */
    public function serviceAction()
    {
	$p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        return array('token' => $p->getToken());
    }
    
    /**
     * @Route("/organizations")
     * @Template("HexaaStorageBundle:Default:organizations.html.twig")
     */
    public function organizationAction()
    {
	$p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        return array('token' => $p->getToken());
    }
    
    /**
     * @Route("/profile")
     * @Template("HexaaStorageBundle:Default:profile.html.twig")
     */
    public function profileAction()
    {
	$p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        return array('token' => $p->getToken());
    }
    
    /**
     * @Route("/devgettoken")
     * @Template("HexaaStorageBundle:Default:devgettoken.html.twig")
     */
    public function devgettokenAction()
    {
	$p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
        return array('p' => $p);
    }
}
