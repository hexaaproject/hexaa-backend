<?php
/**
 * Created by PhpStorm.
 * User: baloo
 * Date: 8/19/15
 * Time: 11:06 AM
 */

namespace Hexaa\ApiBundle\EventListener;


use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Util\ClassUtils;
use Hexaa\ApiBundle\Annotations\InvokeHook;
use Hexaa\ApiBundle\Handler\AttributeCacheHandler;
use Hexaa\ApiBundle\Hook\HookHintResolver;
use Monolog\Logger;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
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
 * If the request has hook hint and is successful, this event creates a seperate Process
 * to dispatch the required hook by invoking a cli command of the bundle.
 *
 * @package Hexaa\ApiBundle\EventListener
 */
class HookListener
{

    protected $reader;
    protected $hookLog;
    protected $cacheHandler;
    protected $cache;
    protected $hookHintResolver;

    public function __construct(
      Reader $reader = null,
      Logger $hookLog,
      AttributeCacheHandler $cacheHandler,
      Cache $cache,
      HookHintResolver $hookHintResolver
    ) {
        $this->reader = $reader;
        $this->hookLog = $hookLog;
        $this->cacheHandler = $cacheHandler;
        $this->cache = $cache;
        $this->hookHintResolver = $hookHintResolver;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $loglbl = "[HookKernelControllerEventListener] ";
        /*
         * $controller passed can be either a class or a Closure. This is not usual in Symfony but it may happen.
         * If it is a class, it comes in array format
         */
        if (!is_array($controller = $event->getController())) {
            return;
        }

        $className = class_exists('Doctrine\Common\Util\ClassUtils') ? ClassUtils::getClass($controller[0]) : get_class(
          $controller[0]
        );
        $object = new \ReflectionClass($className);
        $method = $object->getMethod($controller[1]);

        /** @var InvokeHook $invokeHookAnnotation */
        $invokeHookAnnotation = $this->reader->getMethodAnnotation($method, 'Hexaa\ApiBundle\Annotations\InvokeHook');

        $request = $event->getRequest();

        if ($invokeHookAnnotation) {
            $request->attributes->set('_hookHints', $invokeHookAnnotation->getInfo());
            $this->hookHintResolver->setHint($invokeHookAnnotation->getInfo());
            $this->cacheHandler->setHints($this->hookHintResolver->resolve($request));
            $request->attributes->set("_invokeHookTypes", $invokeHookAnnotation->types);
            $request->attributes->set("_hooksDispatchNeeded", true);
            // ghetto cache id
            $cacheId = base64_encode(microtime()."hookdata".rand(1, 1000));
            // make sure cache id is unique
            while ($this->cache->contains($cacheId)) {
                $cacheId = base64_encode(microtime()."hookdata".rand(1, 1000));
            }
            $request->attributes->set('_hookCacheId', $cacheId);
            $this->hookLog->debug(
              $loglbl."Detected InvokeHook with the following types: "
              .implode(", ", $invokeHookAnnotation->types).
              ". on action ".$event->getRequest()->attributes->get('_controller')
            );
            $this->cacheHandler->setCacheId($cacheId);
            $request->attributes->set("_oldHooksData", $this->cacheHandler->computeData());
        }
    }

    public function onKernelTerminate(PostResponseEvent $event)
    {
        $request = $event->getRequest();
        $statusCode = $event->getResponse()->getStatusCode();
        if ($statusCode == 200 || $statusCode == 201 || $statusCode == 204) {
            $loglbl = "[HookKernelResponseEventListener] ";
            if ($request->attributes->has("_invokeHookTypes")) {
                if ($request->attributes->has("_hooksDispatchNeeded")
                  && $request->attributes->get("_hooksDispatchNeeded")
                ) {
                    $types = $request->attributes->get("_invokeHookTypes");
                    $cacheId = $request->attributes->get('_hookCacheId');
                    $this->cacheHandler->setCacheId($cacheId);
                    $this->hookHintResolver->setHint($request->attributes->get('_hookHints'));
                    $this->cacheHandler->setHints($this->hookHintResolver->resolve($request));
                    $options = array();
                    foreach ($types as $type) {
                        $hookStuff = array("type" => $type);
                        switch ($type) {
                            case 'attribute_change':
                            case 'user_removed':
                            case 'user_added':
                                $hookStuff['oldData'] = $request->get("_oldHooksData");
                                $this->cacheHandler->updateData();
                                break;
                        }
                        $options[] = $hookStuff;
                    }

                    if (count($options) != 0) {
                        $this->hookLog->info($loglbl."Invoking hexaa:hook:dispatch");

                        $this->cache->save($cacheId, $options);

                        $this->hookLog->debug($loglbl."Invoking hexaa:hook:dispatch with parameter: ".$cacheId);

                        // TODO: gotta fix this, it stops halfway through.
                        $process = new Process(
                          'nohup /usr/bin/php ../app/console hexaa:hook:dispatch '.escapeshellarg($cacheId)
                          .' > /dev/null 2>/dev/null &'
                        );
                        $process->start();
                        $this->hookLog->info($loglbl."hexaa:hook:dispatch started with pid: ".$process->getPid());
                    } else {
                        $this->hookLog->info($loglbl."hexaa:hook:dispatch was not called, because no hooks were detected.");
                    }
                    $request->attributes->set("_hooksDispatchNeeded", false);
                }
            }
        }
    }

}
