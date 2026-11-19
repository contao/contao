<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\DataContainer;

use Contao\DC_Table;
use Contao\Widget;
use Psr\Log\LoggerInterface;

final class DataContainerWidgetFactory
{
    /**
     * @var array<string, true>
     */
    private array $warned = [];

    public function __construct(private readonly LoggerInterface|null $logger = null)
    {
    }

    public function resolve(DataContainerFieldContext $field, DC_Table|null $dc = null): DataContainerFieldContext
    {
        /** @var class-string<Widget>|null $class */
        $class = $GLOBALS['BE_FFL'][$field->config['inputType'] ?? ''] ?? null;

        if (!\is_string($class) || !is_a($class, Widget::class, true)) {
            return $field;
        }

        if (!$dc) {
            $dc = new \ReflectionClass(DC_Table::class)->newInstanceWithoutConstructor();
            new \ReflectionProperty(DC_Table::class, 'strTable')->setValue($dc, $field->table);
        }

        $previous = $dc->field;
        $dc->field = $field->name;
        $config = $field->config;

        try {
            $config['eval'] = $class::getAttributesFromDca($config, $field->name ?? '', null, $field->name ?? '', $field->table ?? '', $dc);
        } catch (\Throwable $exception) {
            if (isset($config['options_callback'])) {
                // The schema is built several times per request, so only log the first failure
                if (!isset($this->warned[$key = $field->table.'.'.$field->name])) {
                    $this->warned[$key] = true;
                    $this->logger?->warning(\sprintf('The options_callback of field "%s" failed without a record, so the field is resolved without it.', $key), ['exception' => $exception]);
                }

                unset($config['options_callback']);

                return $this->resolve(new DataContainerFieldContext($config, $field->table, $field->name), $dc);
            }

            throw new \RuntimeException(\sprintf('Could not resolve the widget attributes for field "%s.%s". Make sure the DCA callbacks work statically without a record.', $field->table, $field->name), previous: $exception);
        } finally {
            $dc->field = $previous;
        }

        return new DataContainerFieldContext($config, $field->table, $field->name);
    }
}
