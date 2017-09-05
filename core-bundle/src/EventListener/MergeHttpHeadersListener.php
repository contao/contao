<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
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
    private $framework;

    /**
     * @var array|null
     */
    private $headers;

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
     * @param ContaoFrameworkInterface $framework
     * @param array|null               $headers   Meant for unit testing only!
     */
    public function __construct(ContaoFrameworkInterface $framework, array $headers = null)
    {
        $this->framework = $framework;
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

        if (!in_array($uniqueKey, $this->multiHeaders, true)) {
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
        if (false !== ($i = array_search($this->getUniqueKey($name), $this->multiHeaders, true))) {
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
        if (!$this->framework->isInitialized()) {
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

            if ('cli' !== PHP_SAPI && !headers_sent()) {
                header_remove($name);
            }

            $uniqueKey = $this->getUniqueKey($name);

            if (in_array($uniqueKey, $this->multiHeaders, true)) {
                $response->headers->set($uniqueKey, trim($content), false);
            } elseif (!$response->headers->has($uniqueKey)) {
                $response->headers->set($uniqueKey, trim($content));
            }
        }

        return $response;
    }

    /**
     * Returns the headers.
     *
     * @return array
     */
    private function getHeaders()
    {
        return $this->headers ?: headers_list();
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
