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
use Monolog\Logger;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Process\Process;

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
    protected $hookLog;

    public function __construct(Reader $reader = null, Logger $hookLog) {
        $this->reader = $reader;
        $this->hookLog = $hookLog;
    }

    public function onKernelController(FilterControllerEvent $event) {
        $loglbl = "[HookKernelControllerEventListener] ";
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
            $this->hookLog->debug($loglbl . "Detected InvokeHook with the following types: "
                . implode(", ", $methodAnnotation->getTypes()) .
                ". on action " . $event->getRequest()->attributes->get('_controller'));
        }
    }

    public function onKernelResponse(FilterResponseEvent $event) {
        $statusCode = $event->getResponse()->getStatusCode();
        if ($statusCode == 200 || $statusCode == 201 || $statusCode == 204) {
            $loglbl = "[HookKernelResponseEventListener] ";
            if ($event->getRequest()->attributes->has("_invokeHookTypes")) {
                $types = $event->getRequest()->attributes->get("_invokeHookTypes");
                $options = array();
                foreach($types as $type) {
                    $hookStuff = array("type" => $type);
                    $doNotAdd = false;
                    switch($type) {
                        case"attribute_change":
                            if ($event->getRequest()->attributes->has("_attributeChangeAffectedEntity")) {
                                $hookStuff["_attributeChangeAffectedEntity"] =
                                    $event->getRequest()->attributes->get("_attributeChangeAffectedEntity");
                            } else {
                                $doNotAdd = true;
                            }
                            break;
                        case"user_removed":
                            if ($event->getRequest()->attributes->has("_attributeChangeAffectedEntity")) {
                                $hookStuff["_attributeChangeAffectedEntity"] =
                                    $event->getRequest()->attributes->get("_attributeChangeAffectedEntity");
                            } else {
                                $doNotAdd = true;
                            }
                            break;
                    }
                    if (!$doNotAdd) {
                        $options[] = $hookStuff;
                    }
                }

                if (count($options) != 0) {
                    $this->hookLog->info($loglbl . "Invoking hexaa:hook:dispatch");
                    $param = json_encode($options);
                    $this->hookLog->debug($loglbl . "Invoking hexaa:hook:dispatch with parameter: " . $param);

                    $process = new Process('php ../app/console hexaa:hook:dispatch ' . escapeshellarg($param));
                    $process->start();
                    $this->hookLog->info($loglbl . "hexaa:hook:dispatch started with pid: " . $process->getPid());
                }
            }
        }
    }

}