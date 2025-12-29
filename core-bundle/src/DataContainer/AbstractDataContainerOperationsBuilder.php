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
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\Input;
use Contao\System;

/**
 * @phpstan-type HtmlOperation array{html: string, primary?: bool}
 * @phpstan-type ParametricOperation array{label: string, title?: string, attributes?: HtmlAttributes, listAttributes?: HtmlAttributes, icon?: string, iconAttributes?: HtmlAttributes, href?: string, method?: string, primary?: bool|null}
 * @phpstan-type Separator array{separator: true}
 * @phpstan-type Operation HtmlOperation|ParametricOperation|Separator
 *
 * @internal
 */
abstract class AbstractDataContainerOperationsBuilder implements \Stringable
{
    public const CREATE_NEW = 'create';

    public const CREATE_PASTE = 'paste';

    public const CREATE_AFTER = 'after';

    public const CREATE_INTO = 'into';

    public const CREATE_TOP = 'top';

    /**
     * @var list<Operation>
     */
    protected array|null $operations = null;

    public function __construct(protected readonly ContaoFramework $framework)
    {
    }

    /**
     * @param Operation $operation
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
     * @param Operation $operation
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
        $hasSeparator = true;
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

    /**
     * @return array{0: string, 1: string}
     */
    protected function getLabelAndTitle(string $table, string $key, int|string|null $id = null): array
    {
        $label = $GLOBALS['TL_LANG'][$table][$key] ?? $GLOBALS['TL_LANG']['DCA'][$key] ?? null;

        if (null === $label) {
            return [$key, ''];
        }

        if (\is_string($label)) {
            $label = [null, $label];
        }

        if (null !== $id && isset($label[1])) {
            $label[1] = \sprintf($label[1], $id);
        }

        if (!isset($label[0])) {
            if (\is_array($GLOBALS['TL_LANG']['DCA'][$key] ?? null) && isset($GLOBALS['TL_LANG']['DCA'][$key][0])) {
                $label[0] = $GLOBALS['TL_LANG']['DCA'][$key][0];
            } else {
                $label[0] = $label[1];
            }
        }

        return $label;
    }

    /**
     * @param self::CREATE_* $mode
     */
    protected function getNewHref(string $mode, int|null $pid = null, int|null $id = null): string
    {
        $url = match ($mode) {
            self::CREATE_NEW => 'act=create',
            self::CREATE_PASTE => 'act=paste&amp;mode=create',
            self::CREATE_AFTER => 'act=create&amp;mode=1',
            self::CREATE_TOP,
            self::CREATE_INTO => 'act=create&amp;mode=2',
        };

        if (null !== $pid) {
            $url .= '&amp;pid='.$pid;
        }

        if (null !== $id) {
            $url .= '&amp;id='.$id;
        }

        if ($this->framework->getAdapter(Input::class)->get('nb')) {
            $url .= '&amp;nc=1';
        }

        return $this->framework->getAdapter(Backend::class)->addToUrl($url, true, [], false);
    }
}
