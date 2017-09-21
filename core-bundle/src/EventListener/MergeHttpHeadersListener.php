<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\HttpKernel\Header\HeaderStorageInterface;
use Contao\CoreBundle\HttpKernel\Header\NativeHeaderStorage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class MergeHttpHeadersListener
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @var HeaderStorageInterface
     */
    private $headerStorage;

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
     * @param ContaoFrameworkInterface    $framework
     * @param HeaderStorageInterface|null $headerStorage
     */
    public function __construct(ContaoFrameworkInterface $framework, HeaderStorageInterface $headerStorage = null)
    {
        $this->framework = $framework;
        $this->headerStorage = $headerStorage ?: new NativeHeaderStorage();
    }

    /**
     * Returns the multi-value headers.
     *
     * @return array
     */
    public function getMultiHeaders(): array
    {
        return array_values($this->multiHeaders);
    }

    /**
     * Sets the multi-value headers.
     *
     * @param array $headers
     */
    public function setMultiHeader(array $headers): void
    {
        $this->multiHeaders = $headers;
    }

    /**
     * Adds a multi-value header.
     *
     * @param string $name
     */
    public function addMultiHeader(string $name): void
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
    public function removeMultiHeader(string $name): void
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
    public function onKernelResponse(FilterResponseEvent $event): void
    {
        if (!$this->framework->isInitialized()) {
            return;
        }

        // Fetch remaining headers and add them to the response
        $this->fetchHttpHeaders();
        $this->setResponseHeaders($event->getResponse());
    }

    /**
     * Fetches and stores HTTP headers from PHP.
     */
    private function fetchHttpHeaders(): void
    {
        $this->headers = array_merge($this->headers, $this->headerStorage->all());
        $this->headerStorage->clear();
    }

    /**
     * Sets the response headers.
     *
     * @param Response $response
     */
    private function setResponseHeaders(Response $response): void
    {
        $allowOverrides = [];

        foreach ($this->headers as $header) {
            list($name, $content) = explode(':', $header, 2);

            $uniqueKey = $this->getUniqueKey($name);

            if (in_array($uniqueKey, $this->multiHeaders, true)) {
                $response->headers->set($uniqueKey, trim($content), false);
            } elseif (isset($allowOverrides[$uniqueKey]) || !$response->headers->has($uniqueKey)) {
                $allowOverrides[$uniqueKey] = true;
                $response->headers->set($uniqueKey, trim($content));
            }
        }
    }

    /**
     * Returns the unique header key.
     *
     * @param string $name
     *
     * @return string
     */
    private function getUniqueKey(string $name): string
    {
        return str_replace('_', '-', strtolower($name));
    }
}
