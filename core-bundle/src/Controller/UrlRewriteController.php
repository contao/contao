<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Controller;

use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class UrlRewriteController
{
    public const ATTRIBUTE_NAME = '_url_rewrite';

    public function __construct(private readonly InsertTagParser $insertTagParser)
    {
    }

    public function __invoke(Request $request): Response
    {
        if (!$request->attributes->has(self::ATTRIBUTE_NAME)) {
            throw new RouteNotFoundException(sprintf('The "%s" attribute is missing', self::ATTRIBUTE_NAME));
        }

        $rule = $request->attributes->get(self::ATTRIBUTE_NAME);
        $responseCode = $rule['responseCode'];

        if (410 === $responseCode) {
            throw new GoneHttpException();
        }

        if (null !== ($uri = $this->generateUri($request, $rule))) {
            return new RedirectResponse($uri, $responseCode);
        }

        throw new InternalServerErrorException();
    }

    /**
     * Generate the URI.
     */
    private function generateUri(Request $request, array $rule): ?string
    {
        if (empty($uri = $rule['responseUri'])) {
            return null;
        }

        $uri = $this->replaceWildcards($request, $uri);
        $uri = $this->replaceInsertTags($uri);

        // Replace the multiple slashes except the ones for protocol
        $uri = preg_replace('@(?<!http:|https:|^)/+@', '/', $uri);

        // Make the URL absolute if it's not yet already
        if (!preg_match('@^(https?:)?//@', $uri)) {
            $uri = $request->getSchemeAndHttpHost().$request->getBasePath().'/'.ltrim($uri, '/');
        }

        return $uri;
    }

    /**
     * Replace the wildcards.
     */
    private function replaceWildcards(Request $request, string $uri): string
    {
        $wildcards = [];

        // Get the route params wildcards
        foreach ($request->attributes->get('_route_params', []) as $k => $v) {
            $wildcards['{'.$k.'}'] = $v;
        }

        // Get the query wildcards
        foreach ($request->query->all() as $k => $v) {
            $wildcards['{'.$k.'}'] = $v;
        }

        return strtr($uri, $wildcards);
    }

    /**
     * Replace the insert tags.
     */
    private function replaceInsertTags(string $uri): string
    {
        if (!str_contains($uri, '{{')) {
            return $uri;
        }

        return $this->insertTagParser->replaceInline($uri);
    }
}
