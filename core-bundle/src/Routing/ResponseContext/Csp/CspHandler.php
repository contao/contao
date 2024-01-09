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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CspHandler
{
    private string|null $nonce = null;

    private array $directiveNonces = [];

    private array $signatures = [];

    private static array $validNonceDirectives = ['script-src', 'style-src', 'script-src-elem', 'style-src-elem'];

    private static array $validHashDirectives = ['script-src', 'script-src-elem', 'script-src-attr', 'style-src', 'style-src-elem', 'style-src-attr'];

    public function __construct(
        private DirectiveSet $directives,
        private bool $reportOnly = false,
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

    public function addHash(string $directive, string $script, string $algorithm = 'sha384'): self
    {
        if (!\in_array($directive, self::$validHashDirectives, true)) {
            throw new \InvalidArgumentException('Invalid directive');
        }

        if (!$this->getDirective($directive)) {
            return $this;
        }

        $hash = base64_encode(hash($algorithm, $script, true));

        $this->signatures[$directive][] = $algorithm.'-'.$hash;

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
        if (!$includeFallback || (DirectiveSet::getNames()[$directive] ?? null) !== DirectiveSet::TYPE_SRC_LIST) {
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
        $signatures = $this->signatures;

        foreach ($this->directiveNonces as $name => $nonce) {
            $signatures[$name][] = 'nonce-'.$nonce;
        }

        $headerValue = $this->directives->buildHeaderValue($request ?? new Request(), $signatures);

        if (!$headerValue) {
            return;
        }

        $headerName = 'Content-Security-Policy'.($this->reportOnly ? '-Report-Only' : '');

        $response->headers->set($headerName, $headerValue);
    }
}
