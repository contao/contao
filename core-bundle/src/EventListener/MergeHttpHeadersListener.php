<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Adds HTTP headers sent by Contao to the Symfony response.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class MergeHttpHeadersListener
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $contaoFramework;

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var array
     */
    private $multiHeaders = [
        'set-cookie',
        'link',
        'vary',
        'pragma',
        'cache-control',
    ];

    /**
     * Constructor.
     *
     * @param ContaoFrameworkInterface $contaoFramework
     */
    public function __construct(ContaoFrameworkInterface $contaoFramework)
    {
        $this->contaoFramework = $contaoFramework;
        $this->setHeaders(headers_list());
    }

    /**
     * Returns the headers.
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
     * Returns the multi-value headers.
     *
     * @return array
     */
    public function getMultiHeaders()
    {
        return array_values($this->multiHeaders);
    }

    /**
     * Sets the multi-value headers.
     *
     * @param array $headers
     */
    public function setMultiHeader(array $headers)
    {
        $this->multiHeaders = $headers;
    }

    /**
     * Adds a multi-value header.
     *
     * @param string $name
     */
    public function addMultiHeader($name)
    {
        $uniqueKey = $this->getUniqueKey($name);

        if (!in_array($uniqueKey, $this->multiHeaders)) {
            $this->multiHeaders[] = $uniqueKey;
        }
    }

    /**
     * Removes a multi-value header.
     *
     * @param string $name
     */
    public function removeMultiHeader($name)
    {
        if (false !== ($i = array_search($this->getUniqueKey($name), $this->multiHeaders))) {
            unset($this->multiHeaders[$i]);
        }
    }

    /**
     * Adds the Contao headers to the Symfony response.
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$this->contaoFramework->isInitialized()) {
            return;
        }

        $event->setResponse($this->mergeHttpHeaders($event->getResponse()));
    }

    /**
     * Merges the HTTP headers.
     *
     * @param Response $response
     *
     * @return Response
     */
    private function mergeHttpHeaders(Response $response)
    {
        foreach ($this->getHeaders() as $header) {
            list($name, $content) = explode(':', $header, 2);

            if ('cli' !== PHP_SAPI) {
                header_remove($name);
            }

            $uniqueKey = $this->getUniqueKey($name);

            if (in_array($uniqueKey, $this->multiHeaders)) {
                $response->headers->set($uniqueKey, trim($content), false);
            } elseif (!$response->headers->has($uniqueKey)) {
                $response->headers->set($uniqueKey, trim($content));
            }
        }

        return $response;
    }

    /**
     * Returns the unique header key.
     *
     * @param string $name
     *
     * @return string
     */
    private function getUniqueKey($name)
    {
        return str_replace('_', '-', strtolower($name));
    }
}
