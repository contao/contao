<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\ContaoFrameworkInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Merges HTTP headers sent using PHP's header() method into the Symfony
 * Response so they are available for other listeners following later in the
 * application flow and relying on headers to be part of the response object.
 *
 * Although this could be useful in many applications we think that this should
 * not be supported in general and only serves as a layer to support legacy
 * code and therefore it's restricted to the Contao framework and part of the
 * Contao Core Bundle.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class MergeHttpHeadersListener
{
    /**
     * @var array
     */
    private $routeNames;

    /**
     * @var ContaoFrameworkInterface
     */
    private $contaoFramework;

    /**
     * Remove old headers or not
     * @var boolean
     */
    private $removeOldHeaders = true;

    /**
     * Headers
     * @var array
     */
    private $headers = [];

    /**
     * Constructor.
     *
     * @param array                    $routeNames
     * @param ContaoFrameworkInterface $contaoFramework
     */
    public function __construct(
        array $routeNames,
        ContaoFrameworkInterface $contaoFramework
    ) {
        $this->routeNames = $routeNames;
        $this->contaoFramework = $contaoFramework;

        $this->setHeaders(headers_list());
    }

    /**
     * Gets the headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Sets the headers.
     *
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * Gets whether old headers should be removed using header_remove()
     * or not.
     *
     * @return boolean
     */
    public function getRemoveOldHeaders()
    {
        return $this->removeOldHeaders;
    }

    /**
     * Sets whether old headers should be removed using header_remove()
     * or not.
     *
     * @param boolean $removeOldHeaders
     */
    public function setRemoveOldHeaders($removeOldHeaders)
    {
        $this->removeOldHeaders = (bool) $removeOldHeaders;
    }

    /**
     * Sets the default locale based on the request or session.
     *
     * @param GetResponseEvent $event The event object
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$this->contaoFramework->isInitialized()) {

            return;
        }

        $request = $event->getRequest();

        if (!$request->attributes->has('_route')
            || !in_array($request->attributes->get('_route'), $this->routeNames)
        ) {

            return;
        }

        $event->setResponse($this->mergeHttpHeaders($event->getResponse()));
    }

    /**
     * Merge the http headers. If one of the headers is already present in the
     * response, it will not be merged as the response object has a higher
     * priority.
     *
     * @param Response $response
     *
     * @return Response
     */
    private function mergeHttpHeaders(Response $response)
    {
        foreach ($this->getHeaders() as $header) {
            list($name, $content) = explode(':', $header, 2);

            if ($this->removeOldHeaders) {
                header_remove($name);
            }

            $content = trim($content);

            if ($response->headers->has($name)) {
                continue;
            }

            $response->headers->set($name, $content);
        }

        return $response;
    }
}
