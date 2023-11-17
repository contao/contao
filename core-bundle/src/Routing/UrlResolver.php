<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing;

use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Util\UrlUtil;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RequestContext;

class UrlResolver
{
    public function __construct(
        private readonly InsertTagParser $insertTagParser,
        private readonly RequestContext $requestContext,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * Resolves insert tags in the given URL and converts it to an absolute or
     * path-absolute URL.
     *
     * @param bool $absoluteUrl If true the scheme and host will be included
     *                          (https://example.com/foo/bar) otherwise a
     *                          path-absolut URL will be returned (/foo/bar).
     */
    public function resolve(string $url, bool $absoluteUrl = false): string
    {
        return UrlUtil::makeAbsolute(
            $this->insertTagParser->replaceInline($url),
            $absoluteUrl ? $this->getBaseUrl() : '/',
        );
    }

    /**
     * Same as Environment::get('base').
     */
    public function getBaseUrl(): string
    {
        if ($request = $this->requestStack->getMainRequest()) {
            return $request->getSchemeAndHttpHost().$request->getBasePath().'/';
        }

        return $this->requestContext->getBaseUrl().'/';
    }
}
