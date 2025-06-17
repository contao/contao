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

use Contao\Backend;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * @internal
 */
abstract class AbstractDataContainerOperationsBuilder implements \Stringable
{
    /**
     * @var list<array{html: string, primary?: bool}|array{separator: true}|array{
     *     href: string,
     *     label: string,
     *     title?: string,
     *     attributes: HtmlAttributes,
     *     icon?: string,
     *     primary?: bool|null,
     * }>
     */
    protected array|null $operations = null;

    public function __construct(protected readonly ContaoFramework $framework)
    {
    }

    /**
     * @param array{html: string, primary?: bool}|array{separator: true}|array{
     *     href: string,
     *     title: string,
     *     label: string,
     *     attributes: HtmlAttributes,
     *     icon: string,
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
     *     href: string,
     *     title: string,
     *     label: string,
     *     attributes: HtmlAttributes,
     *     icon: string,
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

    protected function executeButtonCallback(DataContainerOperation $config, callable|null $legacyCallback): void
    {
        if (\is_array($operation['button_callback'] ?? null)) {
            $callback = $this->framework->getAdapter(System::class)->importStatic($operation['button_callback'][0]);

            if ($this->acceptsDataContainerOperation(new \ReflectionMethod($callback, $operation['button_callback'][1]))) {
                $callback->{$operation['button_callback'][1]}($config);
            } elseif ($legacyCallback) {
                $legacyCallback($config);
            } else {
                throw new \RuntimeException('Cannot handle legacy button_callback, provide the $legacyCallback');
            }
        } elseif (\is_callable($operation['button_callback'] ?? null)) {
            if ($this->acceptsDataContainerOperation(new \ReflectionFunction($operation['button_callback']))) {
                $operation['button_callback']($config);
            } elseif ($legacyCallback) {
                $legacyCallback($config);
            } else {
                throw new \RuntimeException('Cannot handle legacy button_callback, provide the $legacyCallback');
            }
        }
    }

    protected function acceptsDataContainerOperation(\ReflectionMethod|\ReflectionFunction $ref): bool
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
