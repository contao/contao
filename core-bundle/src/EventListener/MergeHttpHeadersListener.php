<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\HttpKernel\Header\HeaderStorageInterface;
use Contao\CoreBundle\HttpKernel\Header\NativeHeaderStorage;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal
 */
class MergeHttpHeadersListener implements ResetInterface
{
    private HeaderStorageInterface $headerStorage;
    private array $headers = [];

    private array $multiHeaders = [
        'set-cookie',
        'link',
        'vary',
        'pragma',
        'cache-control',
    ];

    public function __construct(private ContaoFramework $framework, HeaderStorageInterface|null $headerStorage = null)
    {
        $this->headerStorage = $headerStorage ?: new NativeHeaderStorage();
    }

    /**
     * Adds the Contao headers to the Symfony response.
     */
    public function __invoke(ResponseEvent $event): void
    {
        if (!$this->framework->isInitialized()) {
            return;
        }

        // Fetch remaining headers and add them to the response
        $this->fetchHttpHeaders();
        $this->setResponseHeaders($event->getResponse());
    }

    /**
     * @return array<string>
     */
    public function getMultiHeaders(): array
    {
        return array_values($this->multiHeaders);
    }

    public function setMultiHeader(array $headers): void
    {
        $this->multiHeaders = $headers;
    }

    public function addMultiHeader(string $name): void
    {
        $uniqueKey = $this->getUniqueKey($name);

        if (!\in_array($uniqueKey, $this->multiHeaders, true)) {
            $this->multiHeaders[] = $uniqueKey;
        }
    }

    public function removeMultiHeader(string $name): void
    {
        if (false !== ($i = array_search($this->getUniqueKey($name), $this->multiHeaders, true))) {
            unset($this->multiHeaders[$i]);
        }
    }

    public function reset(): void
    {
        $this->headers = [];
    }

    /**
     * Fetches and stores HTTP headers from PHP.
     */
    private function fetchHttpHeaders(): void
    {
        $this->headers = array_merge($this->headers, $this->headerStorage->all());
        $this->headerStorage->clear();
    }

    private function setResponseHeaders(Response $response): void
    {
        $allowOverrides = [];

        foreach ($this->headers as $header) {
            if (preg_match('/^HTTP\/[^ ]+ (\d{3})( (.+))?$/i', (string) $header, $matches)) {
                $response->setStatusCode((int) $matches[1], $matches[3] ?? '');
                continue;
            }

            [$name, $content] = explode(':', (string) $header, 2);

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

    private function getUniqueKey(string $name): string
    {
        return str_replace('_', '-', strtolower($name));
    }
}
