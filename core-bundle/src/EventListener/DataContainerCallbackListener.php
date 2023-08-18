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

/**
 * @internal
 */
class DataContainerCallbackListener
{
    private const SINGLETONS = [
        'panel_callback',
        'paste_button_callback',
        'button_callback',
        'label_callback',
        'header_callback',
        'child_record_callback',
        'input_field_callback',
        'options_callback',
        'group_callback',
        'url_callback',
        'title_tag_callback',
    ];

    private array $callbacks = [];

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

    private function addCallbacks(array|callable|null &$dcaRef, array $callbacks): void
    {
        if (null === $dcaRef) {
            $dcaRef = [];
        }

        krsort($callbacks, SORT_NUMERIC);

        $preCallbacks = array_merge(
            [],
            ...array_filter($callbacks, static fn ($priority) => $priority > 0, ARRAY_FILTER_USE_KEY)
        );

        $postCallbacks = array_merge(
            [],
            ...array_filter($callbacks, static fn ($priority) => $priority <= 0, ARRAY_FILTER_USE_KEY)
        );

        if ($preCallbacks) {
            array_unshift($dcaRef, ...$preCallbacks);
        }

        if ($postCallbacks) {
            array_push($dcaRef, ...$postCallbacks);
        }
    }
}
