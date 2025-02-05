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
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal
 */
#[AsEventListener(priority: 256)]
class MergeHttpHeadersListener implements ResetInterface
{
    private readonly HeaderStorageInterface $headerStorage;

    private array $headers = [];

    private array $multiHeaders = [
        'set-cookie',
        'link',
        'vary',
        'pragma',
        'cache-control',
    ];

    public function __construct(
        private readonly ContaoFramework $framework,
        HeaderStorageInterface|null $headerStorage = null,
    ) {
        $this->headerStorage = $headerStorage ?: new NativeHeaderStorage();
    }

    /**
     * Adds the Contao headers to the Symfony response.
     */
    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->framework->isInitialized()) {
            return;
        }

        // Fetch remaining headers and add them to the response
        $this->fetchHttpHeaders($event->getRequest());
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
    private function fetchHttpHeaders(Request $request): void
    {
        $headers = $this->headerStorage->all();
        $session = $request->hasSession() ? $request->getSession() : null;

        $deprectatedHeaders = array_filter(
            $headers,
            static function ($header) use ($session): bool {
                // Ignore Set-Cookie header set by PHP when using NativeSessionStorage
                if ($session && str_starts_with($header, "Set-Cookie: {$session->getName()}=")) {
                    return false;
                }

                // Ignore X-Powered-By header
                return !str_starts_with($header, 'X-Powered-By:');
            },
        );

        if ([] !== $deprectatedHeaders) {
            trigger_deprecation('contao/core-bundle', '5.3', 'Using the PHP header() function to set HTTP headers has been deprecated and will no longer work in Contao 6. Use the response object instead. Headers used: %s', implode(', ', $deprectatedHeaders));
        }

        $this->headers = [...$this->headers, ...$headers];
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
