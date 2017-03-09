<?php
/**
 * Created by PhpStorm.
 * User: baloo
 * Date: 1/30/15
 * Time: 4:46 PM
 */

namespace Hexaa\ApiBundle\View;


use FOS\RestBundle\View\ExceptionWrapperHandlerInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler as BaseViewHandler;
use JMS\Serializer\Exclusion\DepthExclusionStrategy;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewHandler extends BaseViewHandler
{
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
          ? $this->getRouter()->generate($route, (array)$view->getRouteParameters(), true)
          : $view->getLocation();

        if ($location) {
            return $this->createRedirectResponse($view, $location, $format);
        }

        $response = $this->initResponse($view, $format, $request);

        if (!$response->headers->has('Content-Type')) {
            $response->headers->set('Content-Type', $request->getMimeType($format));
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
            $serializer = $this->getSerializer($view);
            if ($serializer instanceof SerializerInterface) {
                $context = $this->getSerializationContext($view);
                if ($request->attributes->has('groups')) {
                    $context->setGroups($request->attributes->get('groups'));
                    if (in_array('expanded', $request->attributes->get('groups'))) {
                        $context->enableMaxDepthChecks();
                    }
                }
                $content = $serializer->serialize($data, $format, $context);
            } else {
                $content = $serializer->serialize($data, $format);
            }
        }

        $response = $view->getResponse();
        $response->setStatusCode($this->getStatusCode($view, $content));

        if (null !== $content) {
            $response->setContent($content);
        }

        return $response;
    }

    /**
     * Returns the data from a view. If the data is form with errors, it will return it wrapped in an ExceptionWrapper.
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

        if ($form->isValid() || !$form->isSubmitted()) {
            return $form;
        }

        /** @var ExceptionWrapperHandlerInterface $exceptionWrapperHandler */
        $exceptionWrapperHandler = $this->container->get('fos_rest.exception_handler');

        return $exceptionWrapperHandler->wrap(
          array(
            'status_code' => $this->failedValidationCode,
            'message'     => 'Validation Failed',
            'errors'      => $form,
          )
        );
    }

}