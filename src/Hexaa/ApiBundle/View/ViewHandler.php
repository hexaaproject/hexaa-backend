<?php

/**
 * Copyright 2014-2018 MTA SZTAKI, ugyeletes@sztaki.hu
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Hexaa\ApiBundle\View;


use FOS\RestBundle\Serializer\Serializer;
use FOS\RestBundle\View\ExceptionWrapperHandlerInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler as BaseViewHandler;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ViewHandler extends BaseViewHandler
{


    private $urlGenerator;
    private $serializer;
    private $templating;
    private $requestStack;

    /**
     * Constructor.
     *
     * @param UrlGeneratorInterface $urlGenerator         The URL generator
     * @param Serializer            $serializer
     * @param EngineInterface       $templating           The configured templating engine
     * @param RequestStack          $requestStack         The request stack
     * @param array                 $formats              the supported formats as keys and if the given formats uses templating is denoted by a true value
     * @param int                   $failedValidationCode The HTTP response status code for a failed validation
     * @param int                   $emptyContentCode     HTTP response status code when the view data is null
     * @param bool                  $serializeNull        Whether or not to serialize null view data
     * @param array                 $forceRedirects       If to force a redirect for the given key format, with value being the status code to use
     * @param string                $defaultEngine        default engine (twig, php ..)
     */
    public function __construct(
      UrlGeneratorInterface $urlGenerator,
      Serializer $serializer,
      EngineInterface $templating = null,
      RequestStack $requestStack,
      array $formats = null,
      $failedValidationCode = Response::HTTP_BAD_REQUEST,
      $emptyContentCode = Response::HTTP_NO_CONTENT,
      $serializeNull = false,
      array $forceRedirects = null,
      $defaultEngine = 'twig'
    ) {
        parent::__construct(
          $urlGenerator,
          $serializer,
          $templating,
          $requestStack,
          $formats,
          $failedValidationCode,
          $emptyContentCode,
          $serializeNull,
          $forceRedirects,
          $defaultEngine
        );
        $this->urlGenerator = $urlGenerator;
        $this->serializer = $serializer;
        $this->templating = $templating;
        $this->requestStack = $requestStack;
        $this->formats = (array)$formats;
        $this->failedValidationCode = $failedValidationCode;
        $this->emptyContentCode = $emptyContentCode;
        $this->serializeNull = $serializeNull;
        $this->forceRedirects = (array)$forceRedirects;
        $this->defaultEngine = $defaultEngine;
    }

    public function createResponseFake(\FOS\RestBundle\View\ViewHandler $viewHandler, View $view, Request $request, $format)
    {
        $this->emptyContentCode = $viewHandler->emptyContentCode;
        $this->failedValidationCode = $viewHandler->failedValidationCode;
        $this->serializeNull = $viewHandler->serializeNull;
        return $this->createResponse($view, $request, $format);
    }

    /**
     * Handles creation of a Response using either redirection or the templating/serializer service.
     *
     * @param View    $view
     * @param Request $request
     * @param string  $format
     *
     * @return Response
     */
    public function createResponse(View $view, Request $request, $format)
    {
        $route = $view->getRoute();

        $location = $route
          ? $this->urlGenerator->generate($route, (array)$view->getRouteParameters(), UrlGeneratorInterface::ABSOLUTE_URL)
          : $view->getLocation();

        if ($location) {
            return $this->createRedirectResponse($view, $location, $format);
        }

        $response = $this->initResponse($view, $format, $request);

        if (!$response->headers->has('Content-Type')) {
            $mimeType = $request->attributes->get('media_type');
            if (null === $mimeType) {
                $mimeType = $request->getMimeType($format);
            }

            $response->headers->set('Content-Type', $mimeType);
        }

        return $response;
    }

    /**
     * Initializes a response object that represents the view and holds the view's status code.
     *
     * @param View    $view
     * @param string  $format
     * @param Request $request
     * @return Response
     */
    private function initResponse(View $view, $format, Request $request)
    {
        $content = null;
        if ($this->isFormatTemplating($format)) {
            $content = $this->renderTemplate($view, $format);
        } elseif ($this->serializeNull || null !== $view->getData()) {
            $data = $this->getDataFromView($view);

            if ($data instanceof FormInterface && $data->isSubmitted() && !$data->isValid()) {
                $view->getContext()->setAttribute('status_code', $this->failedValidationCode);
            }
            $context = $this->getSerializationContext($view);
            $context->setAttribute('template_data', $view->getTemplateData());
            /** Quick and dirty fix for serializeNull not getting applied from the config. We need this true anyways. */
            $context->setSerializeNull(true);
            if ($request->attributes->has('groups')) {
                $context->setGroups($request->attributes->get('groups'));
                if (in_array('expanded', $request->attributes->get('groups'))) {
                    $context->enableMaxDepth();
                }
            }
            $content = $this->serializer->serialize($data, $format, $context);
        }

        $response = $view->getResponse();
        $response->setStatusCode($this->getStatusCode($view, $content));

        if (null !== $content) {
            $response->setContent($content);
        }

        return $response;
    }

    /**
     * Returns the data from a view.
     *
     * @param View $view
     *
     * @return mixed|null
     */
    private function getDataFromView(View $view)
    {
        $form = $this->getFormFromView($view);

        if (false === $form) {
            return $view->getData();
        }

        return $form;
    }

}