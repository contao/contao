<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\Matcher;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\Input;
use Contao\PageModel;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

class LegacyMatcher implements RequestMatcherInterface
{
    private ContaoFramework $framework;
    private RequestMatcherInterface $requestMatcher;
    private string $urlSuffix;
    private bool $prependLocale;

    /**
     * @internal Do not inherit from this class; decorate the "contao.routing.legacy_matcher" service instead
     */
    public function __construct(ContaoFramework $framework, RequestMatcherInterface $requestMatcher, string $urlSuffix, bool $prependLocale)
    {
        $this->framework = $framework;
        $this->requestMatcher = $requestMatcher;
        $this->urlSuffix = $urlSuffix;
        $this->prependLocale = $prependLocale;
    }

    public function matchRequest(Request $request): array
    {
        $this->framework->initialize(true);

        $pathInfo = rawurldecode($request->getPathInfo());

        if (
            '/' === $pathInfo
            || empty($GLOBALS['TL_HOOKS']['getPageIdFromUrl'])
            || !\is_array($GLOBALS['TL_HOOKS']['getPageIdFromUrl'])
            || ($this->prependLocale && preg_match('@^/([a-z]{2}(-[A-Z]{2})?)/$@', $pathInfo))
        ) {
            return $this->requestMatcher->matchRequest($request);
        }

        $locale = null;
        $fragments = null;

        try {
            $match = $this->requestMatcher->matchRequest($request);
            $fragments = $this->createFragmentsFromMatch($match);
            $locale = isset($match['_locale']) ? LocaleUtil::formatAsLanguageTag($match['_locale']) : null;
        } catch (ResourceNotFoundException $e) {
            // continue and parse fragments from path
        }

        if (null === $fragments) {
            $pathInfo = $this->parseSuffixAndLanguage($pathInfo, $locale);
            $fragments = $this->createFragmentsFromPath($pathInfo);
        }

        if ($this->prependLocale) {
            if (null === $locale) {
                throw new ResourceNotFoundException('Locale is missing');
            }

            $input = $this->framework->getAdapter(Input::class);
            $input->setGet('language', $locale);
        }

        trigger_deprecation('contao/core-bundle', '4.0', 'Using the "getPageIdFromUrl" hook has been deprecated and will no longer work in Contao 5.0.');

        $fragments = $this->executeLegacyHook($fragments);
        $pathInfo = $this->createPathFromFragments($fragments, $locale);

        return $this->requestMatcher->matchRequest($this->rebuildRequest($pathInfo, $request));
    }

    private function createFragmentsFromMatch(array $match): array
    {
        $page = $match['pageModel'] ?? null;
        $parameters = $match['parameters'] ?? '';

        if (!$page instanceof PageModel) {
            throw new ResourceNotFoundException('Resource not found');
        }

        if ('' === $parameters) {
            return [$page->alias ?: $page->id];
        }

        $config = $this->framework->getAdapter(Config::class);
        $fragments = [...[$page->alias ?: $page->id], ...explode('/', substr($parameters, 1))];

        // Add the second fragment as auto_item if the number of fragments is even
        if ($config->get('useAutoItem') && 0 === \count($fragments) % 2) {
            array_splice($fragments, 1, 0, ['auto_item']);
        }

        return $fragments;
    }

    private function createFragmentsFromPath(string $pathInfo): array
    {
        $config = $this->framework->getAdapter(Config::class);
        $fragments = explode('/', $pathInfo);

        // Add the second fragment as auto_item if the number of fragments is even
        if ($config->get('useAutoItem') && 0 === \count($fragments) % 2) {
            array_splice($fragments, 1, 0, ['auto_item']);
        }

        return $fragments;
    }

    private function executeLegacyHook(array $fragments): array
    {
        $system = $this->framework->getAdapter(System::class);

        foreach ($GLOBALS['TL_HOOKS']['getPageIdFromUrl'] as $callback) {
            $fragments = $system->importStatic($callback[0])->{$callback[1]}($fragments);
        }

        // Return if the alias is empty (see #4702 and #4972)
        if ('' === $fragments[0]) {
            throw new ResourceNotFoundException('Page alias is empty');
        }

        return $fragments;
    }

    private function createPathFromFragments(array $fragments, ?string $locale): string
    {
        $config = $this->framework->getAdapter(Config::class);

        if (isset($fragments[1]) && 'auto_item' === $fragments[1] && $config->get('useAutoItem')) {
            unset($fragments[1]);
        }

        $pathInfo = implode('/', $fragments).$this->urlSuffix;

        if ($this->prependLocale) {
            $pathInfo = $locale.'/'.$pathInfo;
        }

        return '/'.$pathInfo;
    }

    private function parseSuffixAndLanguage(string $pathInfo, ?string &$locale): string
    {
        $suffixLength = \strlen($this->urlSuffix);

        if (0 !== $suffixLength) {
            if (substr($pathInfo, -$suffixLength) !== $this->urlSuffix) {
                throw new ResourceNotFoundException('URL suffix does not match');
            }

            $pathInfo = substr($pathInfo, 0, -$suffixLength);
        }

        if (0 === strncmp($pathInfo, '/', 1)) {
            $pathInfo = substr($pathInfo, 1);
        }

        if ($this->prependLocale) {
            $matches = [];

            if (!preg_match('@^([a-z]{2}(-[A-Z]{2})?)/(.+)$@', $pathInfo, $matches)) {
                throw new ResourceNotFoundException('Locale does not match');
            }

            [, $locale,, $pathInfo] = $matches;
        }

        return $pathInfo;
    }

    /**
     * @see ChainRouter::rebuildRequest()
     */
    private function rebuildRequest(string $pathinfo, Request $request): Request
    {
        $uri = $pathinfo;
        $server = [];

        if ($request->getBaseUrl()) {
            $uri = $request->getBaseUrl().$pathinfo;
            $server['SCRIPT_FILENAME'] = $request->getBaseUrl();
            $server['PHP_SELF'] = $request->getBaseUrl();
        }

        $host = $request->getHttpHost() ?: 'localhost';
        $scheme = $request->getScheme() ?: 'http';
        $uri = $scheme.'://'.$host.$uri.'?'.$request->getQueryString();

        return Request::create($uri, $request->getMethod(), [], [], [], $server);
    }
}
