<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;

/**
 * The priority must be lower than 0 (see #3255).
 *
 * @internal
 */
#[AsHook('loadDataContainer', priority: -16)]
class DataContainerCallbackListener
{
    private const SINGLETONS = [
        'button_callback',
        'child_record_callback',
        'default',
        'group_callback',
        'header_callback',
        'input_field_callback',
        'label_callback',
        'options_callback',
        'panel_callback',
        'paste_button_callback',
        'title_tag_callback',
        'url_callback',
    ];

    // These "callbacks" do not support array notation, so they are wrapped with a closure
    private const CLOSURES = [
        'default',
    ];

    private array $callbacks = [];

    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function setCallbacks(array $callbacks): void
    {
        $this->callbacks = $callbacks;
    }

    public function onLoadDataContainer(string $table): void
    {
        if (!isset($this->callbacks[$table])) {
            return;
        }

        foreach ($this->callbacks[$table] as $target => $callbacks) {
            $keys = explode('.', (string) $target);
            $dcaRef = &$this->getDcaReference($table, $keys);

            if (\in_array(end($keys), self::CLOSURES, true)) {
                $systemAdapter = $this->framework->getAdapter(System::class);

                foreach ($callbacks as $priority => $pCallbacks) {
                    foreach ($pCallbacks as $k => $callback) {
                        $callbacks[$priority][$k] = static fn (...$args) => $systemAdapter->importStatic($callback[0])->{$callback[1]}(...$args);
                    }
                }
            }

            if ((isset($keys[2]) && 'panel_callback' === $keys[2]) || \in_array(end($keys), self::SINGLETONS, true)) {
                $this->updateSingleton($dcaRef, $callbacks);
            } else {
                $this->addCallbacks($dcaRef, $callbacks);
            }
        }
    }

    private function &getDcaReference(string $table, array $keys): array|callable|null
    {
        $dcaRef = &$GLOBALS['TL_DCA'][$table];

        foreach ($keys as $key) {
            $dcaRef = &$dcaRef[$key];
        }

        return $dcaRef;
    }

    private function updateSingleton(array|callable|null &$dcaRef, array $callbacks): void
    {
        krsort($callbacks, SORT_NUMERIC);

        if (empty($dcaRef) || array_keys($callbacks)[0] >= 0) {
            $callbacks = array_shift($callbacks);
            $dcaRef = array_shift($callbacks);
        }
    }

    /**
     * @param-out array|callable $dcaRef
     */
    private function addCallbacks(array|callable|null &$dcaRef, array $callbacks): void
    {
        if (null === $dcaRef) {
            $dcaRef = [];
        }

        krsort($callbacks, SORT_NUMERIC);

        $preCallbacks = array_merge(
            [],
            ...array_filter($callbacks, static fn ($priority) => $priority > 0, ARRAY_FILTER_USE_KEY),
        );

        $postCallbacks = array_merge(
            [],
            ...array_filter($callbacks, static fn ($priority) => $priority <= 0, ARRAY_FILTER_USE_KEY),
        );

        if ($preCallbacks) {
            array_unshift($dcaRef, ...$preCallbacks);
        }

        if ($postCallbacks) {
            array_push($dcaRef, ...$postCallbacks);
        }
    }
}
