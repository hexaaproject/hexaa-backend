<?php

namespace Hexaa\FOSSamlBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class FOSSamlController extends Controller
{

   /**
    * @Route("/unauthorized")
    */
    public function unauthorizedAction()
    {
        return $this->render('FOSSamlBundle::unauthorized.html.twig',array('type'=>'Acesso Negado'));
    }
    
   /**
    * @Route("/logout")
    */
    public function logoutAction()
    {
	$auth = new \SimpleSAML_Auth_Simple('default-sp'); 
	$auth->requireAuth();

	if($auth->isAuthenticated()) {
	    $p = $this->get('security.context')->getToken()->getUser()->getPrincipal();
	    $p->setToken("");
	    $em = $this->get("doctrine")->getManager();
	    $em->persist($p);
	    $em->flush();
            $logoutUrl=$auth->getLogoutURL($this->generateUrl('_welcome'));
	}
	
	return $this->redirect($logoutUrl);
        //return $this->render('FOSSamlBundle::unauthorized.html.twig',array('type'=>'Acesso Negado'));
        
        //$user = $this->get('security.context')->getToken()->getUser();
        //var_dump($user);
        
        //$this->get('request')->getSession()->invalidate();
        
        //var_dump('controller logout');*/
        
    }
    
    /**
     * @Route("/login")
     * @Template("::base.html.twig")
     */
    public function loginAction()
    {
      	return array();
    }
}