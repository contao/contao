<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\HttpKernel\Header\HeaderStorageInterface;
use Contao\CoreBundle\HttpKernel\Header\NativeHeaderStorage;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Adds HTTP headers sent by Contao to the Symfony response.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 * @author Andreas Schempp <https://github.com/aschempp>
 */
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
     * Constructor.
     *
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

        if (!\in_array($uniqueKey, $this->multiHeaders, true)) {
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

        // Fetch remaining headers and add them to the response
        $this->fetchHttpHeaders();
        $this->setResponseHeaders($event->getResponse());
    }

    /**
     * Fetches and stores HTTP headers from PHP.
     */
    private function fetchHttpHeaders()
    {
        $this->headers = array_merge($this->headers, $this->headerStorage->all());
        $this->headerStorage->clear();
    }

    /**
     * Sets the response headers.
     *
     * @param Response $response
     */
    private function setResponseHeaders(Response $response)
    {
        $allowOverrides = [];

        foreach ($this->headers as $header) {
            if (preg_match('/^HTTP\/[^ ]* (\d{3}) (.*)$/i', $header, $matches)) {
                $response->setStatusCode($matches[1], $matches[2]);
                continue;
            }

            list($name, $content) = explode(':', $header, 2);

            $uniqueKey = $this->getUniqueKey($name);

            // Never merge cache-control headers (see #1246)
            if ('cache-control' === $uniqueKey) {
                continue;
            }

            if ('set-cookie' === $uniqueKey) {
                $cookie = Cookie::fromString($content);

                if (session_name() === $cookie->getName()) {
                    $this->headerStorage->add('Set-Cookie: '.$cookie);
                    continue;
                }
            }

            if (\in_array($uniqueKey, $this->multiHeaders, true)) {
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
    private function getUniqueKey($name)
    {
        return str_replace('_', '-', strtolower($name));
    }
}
