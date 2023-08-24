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

use Contao\ArrayUtil;
use Nyholm\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Document
{
    private Crawler|null $crawler = null;
    private array|null $jsonLds = null;

    /**
     * The key is the header name in lowercase letters and the value is again
     * an array of header values.
     *
     * @param array<string, array> $headers
     */
    public function __construct(
        private UriInterface $uri,
        private int $statusCode,
        private array $headers,
        private string $body = '',
    ) {
        $this->headers = array_change_key_case($headers);
    }

    public function __serialize(): array
    {
        return [
            'uri' => $this->uri,
            'statusCode' => $this->statusCode,
            'headers' => $this->headers,
            'body' => $this->body,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->uri = $data['uri'];
        $this->statusCode = $data['statusCode'];
        $this->headers = $data['headers'];
        $this->body = $data['body'];
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

    public function getContentCrawler(): Crawler
    {
        return $this->crawler ??= new Crawler($this->body);
    }

    public function extractCanonicalUri(): UriInterface|null
    {
        foreach ($this->getHeaders() as $key => $values) {
            if ('link' === $key) {
                foreach ($values as $value) {
                    if (preg_match('@<(https?://(.+))>;\s*rel="canonical"@', (string) $value, $matches)) {
                        return new Uri($matches[1]);
                    }
                }
            }
        }

        $headCanonical = $this->getContentCrawler()
            ->filterXPath('//html/head/link[@rel="canonical"][starts-with(@href,"http")]')
            ->first()
        ;

        if ($headCanonical->count()) {
            return new Uri($headCanonical->attr('href'));
        }

        return null;
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

        $jsonLds = $this->getContentCrawler()
            ->filterXPath('descendant-or-self::script[@type = "application/ld+json"]')
            ->each(
                static function (Crawler $node) {
                    try {
                        return json_decode($node->text(), true, 512, JSON_THROW_ON_ERROR);
                    } catch (\JsonException) {
                        return null;
                    }
                }
            )
        ;

        // Filter invalid (null) and parse all values
        foreach (array_filter($jsonLds) as $jsonLd) {
            // If array has numeric keys, it likely contains multiple data inside it which should be
            // treated as if coming from separate sources, and thus moved to the root of an array.
            $jsonLdItems = ArrayUtil::isAssoc($jsonLd) ? [$jsonLd] : $jsonLd;

            // Parsed the grouped values under the @graph within the same context
            foreach ($jsonLdItems as $jsonLdItem) {
                if (\is_array($graphs = $jsonLdItem['@graph'] ?? null)) {
                    foreach ($graphs as $graph) {
                        $this->jsonLds[] = [...array_diff_key($jsonLdItem, ['@graph' => null]), ...$graph];
                    }
                } else {
                    $this->jsonLds[] = $jsonLdItem;
                }
            }
        }

        return $this->filterJsonLd($this->jsonLds, $context, $type);
    }

    public static function createFromRequestResponse(Request $request, Response $response): self
    {
        return new self(
            new Uri($request->getUri()),
            $response->getStatusCode(),
            $response->headers->all(),
            (string) $response->getContent(),
        );
    }

    private function filterJsonLd(array $jsonLds, string $context = '', string $type = ''): array
    {
        if ('' !== $context) {
            $context = rtrim($context, '/').'/';
        }

        $matching = [];

        foreach ($jsonLds as $data) {
            $data = $this->expandJsonLdContexts($data);

            if ('' !== $type && (!isset($data['@type']) || $data['@type'] !== $context.$type)) {
                continue;
            }

            if ($filtered = $this->filterJsonLdContexts($data, [$context])) {
                $matching[] = $filtered;
            }
        }

        return $matching;
    }

    private function expandJsonLdContexts(array $data): array
    {
        if (empty($data['@context'])) {
            return $data;
        }

        if (\is_string($data['@context'])) {
            $data['@context'] = rtrim($data['@context'], '/').'/';

            foreach ($data as $key => $value) {
                if ('@type' === $key) {
                    $data[$key] = $data['@context'].$value;
                    continue;
                }

                if ('@' !== $key[0]) {
                    unset($data[$key]);
                    $data[$data['@context'].$key] = $value;
                }
            }

            return $data;
        }

        if (\is_array($data['@context'])) {
            foreach ($data['@context'] as $prefix => $context) {
                if (isset($data['@type']) && 0 === strncmp($data['@type'], $prefix.':', \strlen((string) $prefix) + 1)) {
                    $data['@type'] = $context.substr($data['@type'], \strlen((string) $prefix) + 1);
                }

                foreach ($data as $key => $value) {
                    if (0 === strncmp($prefix.':', $key, \strlen((string) $prefix) + 1)) {
                        unset($data[$key]);
                        $data[$context.substr($key, \strlen((string) $prefix) + 1)] = $value;
                    }
                }
            }

            return $data;
        }

        throw new \RuntimeException('Unable to expand JSON-LD data');
    }

    private function filterJsonLdContexts(array $data, array $contexts): array
    {
        $newData = [];
        $found = false;

        foreach ($data as $key => $value) {
            foreach ($contexts as $context) {
                if ('@type' === $key) {
                    $newData[$key] = $value;

                    if (str_starts_with($value, $context)) {
                        $newData[$key] = substr($value, \strlen((string) $context));
                        $found = true;
                        break;
                    }
                }

                if (0 === strncmp($context, $key, \strlen((string) $context))) {
                    $newData[substr($key, \strlen((string) $context))] = $value;
                    $found = true;
                    break;
                }
            }
        }

        return $found ? $newData : [];
    }
}
