<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DataContainer;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\System;

/**
 * @internal
 */
abstract class AbstractDataContainerOperationsBuilder implements \Stringable
{
    public const CREATE_NEW = 'create';

    public const CREATE_PASTE = 'paste';

    public const CREATE_PASTE_AFTER = 'paste_after';

    public const CREATE_PASTE_INTO = 'paste_into';

    /**
     * @var list<array{html: string, primary?: bool}|array{separator: true}|array{
     *     label: string,
     *     title?: string,
     *     attributes?: HtmlAttributes,
     *     icon?: string,
     *     iconAttributes?: HtmlAttributes,
     *     href?: string,
     *     method?: string,
     *     primary?: bool|null,
     * }>
     */
    protected array|null $operations = null;

    public function __construct(protected readonly ContaoFramework $framework)
    {
    }

    /**
     * @param array{html: string, primary?: bool}|array{separator: true}|array{
     *     label: string,
     *     title?: string,
     *     attributes?: HtmlAttributes,
     *     icon?: string,
     *     iconAttributes?: HtmlAttributes,
     *     href?: string,
     *     method?: string,
     *     primary?: bool|null
     * } $operation
     */
    public function prepend(array $operation, bool $parseHtml = false): self
    {
        $this->ensureInitialized();

        if ($parseHtml) {
            array_unshift($this->operations, ...$this->parseOperationsHtml($operation));
        } else {
            array_unshift($this->operations, $operation);
        }

        return $this;
    }

    /**
     * @param array{html: string, primary?: bool}|array{separator: true}|array{
     *     label: string,
     *     title?: string,
     *     attributes?: HtmlAttributes,
     *     icon?: string,
     *     iconAttributes?: HtmlAttributes,
     *     href?: string,
     *     method?: string,
     *     primary?: bool|null
     * } $operation
     */
    public function append(array $operation, bool $parseHtml = false): self
    {
        $this->ensureInitialized();

        if ($parseHtml) {
            array_push($this->operations, ...$this->parseOperationsHtml($operation));
        } else {
            $this->operations[] = $operation;
        }

        return $this;
    }

    public function addSeparator(): self
    {
        $this->append([
            'separator' => true,
        ]);

        return $this;
    }

    protected function cleanOperations(): array
    {
        $hasSeparator = false;
        $operations = $this->operations;

        foreach ($operations as $k => $v) {
            if (isset($v['html']) && '' === trim($v['html'])) {
                unset($operations[$k]);
                continue;
            }

            if ($v['separator'] ?? false) {
                if ($hasSeparator) {
                    unset($operations[$k]);
                    continue;
                }

                $hasSeparator = true;
            } else {
                $hasSeparator = false;
            }
        }

        return array_values($operations);
    }

    /**
     * Generate multiple operations if the given operation is using HTML.
     */
    protected function parseOperationsHtml(array $operation): array
    {
        if (!isset($operation['html']) || '' === trim((string) $operation['html'])) {
            return [$operation];
        }

        $xml = new \DOMDocument();
        $xml->preserveWhiteSpace = false;
        $xml->loadHTML('<?xml encoding="UTF-8">'.$operation['html']);

        $body = $xml->getElementsByTagName('body')[0];

        if ($body->childNodes->length < 2) {
            return [$operation];
        }

        $operations = [];
        $current = null;

        foreach ($body->childNodes as $node) {
            if ($node instanceof \DOMText) {
                if ('' === trim($html = $xml->saveHTML($node))) {
                    continue;
                }

                if ($current) {
                    $current['html'] .= $html;
                    continue;
                }
            }

            if ($current) {
                $operations[] = $current;
            }

            $current = $operation;
            $current['html'] = $xml->saveHTML($node);

            if ('a' === strtolower($node->nodeName)) {
                $operations[] = $current;
                $current = null;
            }
        }

        $operations[] = $current;

        return $operations;
    }

    protected function executeButtonCallback(array|callable|null $callback, DataContainerOperation $config, callable|null $legacyCallback): void
    {
        if (\is_array($callback)) {
            $instance = $this->framework->getAdapter(System::class)->importStatic($callback[0]);

            if ($this->acceptsDataContainerOperation(new \ReflectionMethod($instance, $callback[1]))) {
                $instance->{$callback[1]}($config);
            } elseif ($legacyCallback) {
                $legacyCallback($config);
            } else {
                throw new \RuntimeException('Cannot handle legacy button_callback, provide the $legacyCallback');
            }
        } elseif (\is_callable($callback)) {
            if ($this->acceptsDataContainerOperation(new \ReflectionFunction($callback))) {
                $callback($config);
            } elseif ($legacyCallback) {
                $legacyCallback($config);
            } else {
                throw new \RuntimeException('Cannot handle legacy button_callback, provide the $legacyCallback');
            }
        }
    }

    protected function acceptsDataContainerOperation(\ReflectionFunction|\ReflectionMethod $ref): bool
    {
        return 1 === $ref->getNumberOfParameters()
            && ($type = $ref->getParameters()[0]->getType())
            && $type instanceof \ReflectionNamedType
            && DataContainerOperation::class === $type->getName();
    }

    protected function ensureInitialized(): void
    {
        if (null === $this->operations) {
            throw new \RuntimeException(static::class.' has not been initialized yet.');
        }
    }

    protected function ensureNotInitialized(): void
    {
        if (null !== $this->operations) {
            throw new \RuntimeException(static::class.' has already been initialized.');
        }
    }
}
