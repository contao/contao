<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Search;

use Nyholm\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Document
{
    /**
     * @var UriInterface
     */
    private $uri;

    /**
     * @var int
     */
    private $statusCode;

    /**
     * The key is the header name in lowercase letters and the value is again
     * an array of header values.
     *
     * @var array<string,array>
     */
    private $headers;

    /**
     * @var string
     */
    private $body;

    /**
     * @var array|null
     */
    private $jsonLds;

    public function __construct(UriInterface $uri, int $statusCode, array $headers = [], string $body = '')
    {
        $this->uri = $uri;
        $this->statusCode = $statusCode;
        $this->headers = array_change_key_case($headers);
        $this->body = $body;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Extracts all <script type="application/ld+json"> script tags and returns their contents as a JSON decoded
     * array. Optionally allows to restrict it to a given context and type.
     */
    public function extractJsonLdScripts(string $context = '', string $type = ''): array
    {
        if (null !== $this->jsonLds) {
            return $this->filterJsonLd($this->jsonLds, $context, $type);
        }

        $this->jsonLds = [];

        if ('' === $this->body) {
            return $this->jsonLds;
        }

        $crawler = new Crawler($this->body);

        $this->jsonLds = $crawler
            ->filterXPath('descendant-or-self::script[@type = "application/ld+json"]')
            ->each(
                static function (Crawler $node) {
                    $data = json_decode($node->text(), true);

                    if (JSON_ERROR_NONE !== json_last_error()) {
                        return null;
                    }

                    return $data;
                }
            )
        ;

        // Filter invalid (null) values
        $this->jsonLds = array_filter($this->jsonLds);

        return $this->filterJsonLd($this->jsonLds, $context, $type);
    }

    public static function createFromRequestResponse(Request $request, Response $response): self
    {
        return new self(
            new Uri($request->getUri()),
            $response->getStatusCode(),
            $response->headers->all(),
            $response->getContent()
        );
    }

    private function filterJsonLd(array $jsonLds, string $context = '', string $type = ''): array
    {
        $matching = [];

        foreach ($jsonLds as $data) {
            if ('' !== $context && (!isset($data['@context']) || $data['@context'] !== $context)) {
                continue;
            }

            if ('' !== $type && (!isset($data['@type']) || $data['@type'] !== $type)) {
                continue;
            }

            $matching[] = $data;
        }

        return $matching;
    }
}
