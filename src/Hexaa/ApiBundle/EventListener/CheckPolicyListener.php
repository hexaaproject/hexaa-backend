<?php

namespace Acme\DemoBundle\EventListener;

use Acme\DemoBundle\Controller\TokenAuthenticatedController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class CheckPolicyListener {
    private $em;

    public function __construct($em)
    {
        $this->em = $em;
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
            
            //Actual code here
        }
    }
}
