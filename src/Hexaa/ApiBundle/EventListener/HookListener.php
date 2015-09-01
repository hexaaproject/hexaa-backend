<?php
/**
 * Created by PhpStorm.
 * User: baloo
 * Date: 8/19/15
 * Time: 11:06 AM
 */

namespace Hexaa\ApiBundle\EventListener;


use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Class HookListener
 *
 * This class contains two EventListeners:
 *
 * kernel.controller event: onKernelController
 * Checks if the Controller has InvokeHook annotation. If so, then places the type(s) into the request.
 *
 * kernel.response event: onKernelResponse
 * If the request has hook types and is successful, this event creates a seperate Process
 * to dispatch the required hook by invoking a cli command of the bundle.
 *
 * @package Hexaa\ApiBundle\EventListener
 */
class HookListener {

    protected $reader;

    public function __construct(Reader $reader = null) {
        $this->reader = $reader;
    }

    public function onKernelController(FilterControllerEvent $event) {
        /*
         * $controller passed can be either a class or a Closure. This is not usual in Symfony but it may happen.
         * If it is a class, it comes in array format
         */
        if (!is_array($controller = $event->getController())) {
            return;
        }

        $className = class_exists('Doctrine\Common\Util\ClassUtils') ? ClassUtils::getClass($controller[0]) : get_class($controller[0]);
        $object = new \ReflectionClass($className);
        $method = $object->getMethod($controller[1]);

        $methodAnnotation = $this->reader->getMethodAnnotation($method, 'Hexaa\ApiBundle\Annotations\InvokeHook');

        $request = $event->getRequest();

        if ($methodAnnotation) {
            $request->attributes->set("_invokeHookTypes", $methodAnnotation->getTypes());

        }
    }

    public function onKernelResponse(FilterResponseEvent $event) {
        if ($event->getRequest()->attributes->has("_invokeHookTypes")) {
            $types = $event->getRequest()->attributes->get("_invokeHookTypes");
            $options = array();
            foreach($types as $type) {
                $options["type"] = $type;
                switch($type) {
                    case"attribute_change":
                        $options["_attributeChangeAffectedEntity"] =
                            $event->getRequest()->attributes->get("_attributeChangeAffectedEntity");
                        break;
                }
            }

            // ToDo: magic (invoke cli command)
        }
    }

}