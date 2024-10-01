<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\ResponseContext\Csp;

use Nelmio\SecurityBundle\ContentSecurityPolicy\ContentSecurityPolicyParser;
use Nelmio\SecurityBundle\ContentSecurityPolicy\DirectiveSet;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CspHandler
{
    private bool $reportOnly = false;

    private string|null $nonce = null;

    private array $directiveNonces = [];

    /**
     * @var array<string, array<string>>
     */
    private array $signatures = [];

    private static array $validNonceDirectives = ['script-src', 'style-src', 'script-src-elem', 'style-src-elem'];

    private static array $validHashDirectives = ['script-src', 'script-src-elem', 'script-src-attr', 'style-src', 'style-src-elem', 'style-src-attr'];

    public function __construct(
        private DirectiveSet $directives,
        private readonly int $maxHeaderLength = 3072,
        private readonly LoggerInterface|null $logger = null,
    ) {
    }

    public function setDirectives(DirectiveSet $directives): self
    {
        $this->directives = $directives;

        return $this;
    }

    public function getDirectives(): DirectiveSet
    {
        return $this->directives;
    }

    public function setReportOnly(bool $reportOnly): self
    {
        $this->reportOnly = $reportOnly;

        return $this;
    }

    public function getReportOnly(): bool
    {
        return $this->reportOnly;
    }

    public function getNonce(string $directive): string|null
    {
        if (!\in_array($directive, self::$validNonceDirectives, true)) {
            throw new \InvalidArgumentException('Invalid directive');
        }

        if (!$this->getDirective($directive)) {
            return null;
        }

        if (!$this->nonce) {
            $this->nonce = base64_encode(random_bytes(18));
        }

        $this->directiveNonces[$directive] = $this->nonce;

        return $this->nonce;
    }

    public function addHash(string $directive, string $script, string $algorithm = 'sha256'): self
    {
        if (!\in_array($directive, self::$validHashDirectives, true)) {
            throw new \InvalidArgumentException('Invalid directive');
        }

        if (!$this->getDirective($directive)) {
            return $this;
        }

        $this->signatures[$directive][] = $algorithm.'-'.base64_encode(hash($algorithm, $script, true));
        $this->signatures[$directive] = array_unique($this->signatures[$directive]);

        return $this;
    }

    /**
     * Sets or appends a source for a directive, e.g. frame-src https://www.youtube.com/….
     *
     * @param string $directive   the directive for which the source should be added
     * @param string $source      the source for the directive
     * @param bool   $autoIgnore  does not add the source if no directive (or its fallback) is set yet
     * @param bool   $useFallback whether to automatically add to the fallback of the directive
     */
    public function addSource(string $directive, string $source, bool $autoIgnore = true, bool $useFallback = true): self
    {
        if (($sources = $this->getDirective($directive, $useFallback)) || !$autoIgnore) {
            $parser = new ContentSecurityPolicyParser();
            $existingValues = explode(' ', (string) $sources);
            $newValues = array_unique(array_merge($existingValues, explode(' ', $source)));
            $value = $parser->parseSourceList($newValues);

            $this->directives->setDirective($directive, $value);
        }

        return $this;
    }

    /**
     * Returns the sources of a directive if set.
     *
     * @param string $directive       the directive
     * @param bool   $includeFallback whether to automatically return the fallback of the directive
     */
    public function getDirective(string $directive, bool $includeFallback = true): string|null
    {
        if ($sources = $this->directives->getDirective($directive)) {
            return $sources;
        }

        // Only source directives can have a fallback
        if (!$includeFallback || DirectiveSet::TYPE_SRC_LIST !== (DirectiveSet::getNames()[$directive] ?? null)) {
            return null;
        }

        return match ($directive) {
            'script-src-attr', 'script-src-elem' => $this->getDirective('script-src', $includeFallback),
            'style-src-attr', 'style-src-elem' => $this->getDirective('style-src', $includeFallback),
            default => $this->getDirective('default-src', false),
        };
    }

    public function applyHeaders(Response $response, Request|null $request = null): void
    {
        $headerValue = $this->buildHeaderConsideringMaxlength($request);

        if (!$headerValue) {
            return;
        }

        $headerName = 'Content-Security-Policy'.($this->reportOnly ? '-Report-Only' : '');

        $response->headers->set($headerName, $headerValue);
    }

    private function buildHeaderConsideringMaxlength(Request|null $request = null): string
    {
        $headerValue = $this->buildHeaderValue($request);
        $headerLength = \strlen($headerValue);

        if ($headerLength < $this->maxHeaderLength) {
            return $headerValue;
        }

        // Now let's try to not cause a 500 Internal Server error by removing some
        // signatures. Let's remove some style-src signatures first because they likely
        // have the least impact.
        $removedStyleSrc = $this->reduceHashSignatures('style-src');
        $headerValue = $this->buildHeaderValue($request);

        if (\strlen($headerValue) < $this->maxHeaderLength) {
            $this->logCspHeaderExceeded($headerLength, $removedStyleSrc, []);

            return $headerValue;
        }

        // If we still exceed the limit, we have to reduce script-src now.
        $removedScriptSrc = $this->reduceHashSignatures('script-src');
        $headerValue = $this->buildHeaderValue($request);

        if (\strlen($headerValue) < $this->maxHeaderLength) {
            $this->logCspHeaderExceeded($headerLength, $removedStyleSrc, $removedScriptSrc);

            return $headerValue;
        }

        // Still couldn't make it - we have to throw an exception now.
        throw new \LogicException(\sprintf('The generated Content Security Policy header exceeds %d bytes. It is very unlikely that your web server will be able to handle such a big header value. Check the policy and ensure it stays below %d bytes: %s', $this->maxHeaderLength, $this->maxHeaderLength, $headerValue));
    }

    private function logCspHeaderExceeded(int $headerLength, array $removedStyleSrc, array $removedScriptSrc): void
    {
        $this->logger?->critical(\sprintf('Allowed CSP header size of %d bytes exceeded (tried to write %d bytes). Removed style-src hashes: %s. Removed script-src hashes: %s.',
            $this->maxHeaderLength,
            $headerLength,
            [] === $removedStyleSrc ? 'none' : implode(', ', $removedStyleSrc),
            [] === $removedScriptSrc ? 'none' : implode(', ', $removedScriptSrc),
        ));
    }

    private function buildHeaderValue(Request|null $request = null): string
    {
        $signatures = $this->signatures;

        foreach ($this->directiveNonces as $name => $nonce) {
            $signatures[$name][] = 'nonce-'.$nonce;
        }

        return trim($this->directives->buildHeaderValue($request ?? new Request(), $signatures));
    }

    /**
     * @return array<string> The removed hashes
     */
    private function reduceHashSignatures(string $source): array
    {
        if (!isset($this->signatures[$source])) {
            return [];
        }

        $removed = [];

        // First unset the ones added last. In case of style-src, that means that the top
        // inline styles would still work while towards the footer they might not work anymore.
        do {
            $removed[] = array_pop($this->signatures[$source]);
        } while (\strlen($this->buildHeaderValue()) > $this->maxHeaderLength && [] !== $this->signatures[$source]);

        return $removed;
    }
}
