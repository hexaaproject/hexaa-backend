<?php

namespace Hexaa\ApiBundle\EventListener;

use Hexaa\ApiBundle\Controller\PersonalAuthenticatedController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class CheckPolicyListener {
    private $em;
    private $loginlog;
    private $errorlog;

    public function __construct($em, $loginlog, $errorlog)
    {
        $this->em = $em;
        $this->loginlog = $loginlog;
        $this->errorlog = $errorlog;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        /*
         * $controller passed can be either a class or a Closure. This is not usual in Symfony but it may happen.
         * If it is a class, it comes in array format
         */
        if (!is_array($controller)) {
            return;
        }

        if ($controller[0] instanceof PersonalAuthenticatedController) {
            
            
            
            //$event->getRequest()->request->set('asd',$event->getRequest()->attributes->get('_controller'));
        }
    }
}
