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

class DataContainerCallbackListener
{
//    private const CALLBACKS = [
//        'config.onload',
//        'config.onsubmit',
//        'config.oncreate_version',
//        'config.onrestore_version',
//        'config.oncreate',
//        'config.oncut',
//        'config.oncopy',
//        'config.ondelete',
//        'config.onundo',
//        'panel.panel',
//        'edit.buttons',
//        'select.buttons',
//        'list.sorting.paste_button',
//        'list.sorting.child_record',
//        'list.label.label',
//        'list.label.header',
//        'list.label.group',
//        'operations.edit.button',
//        'global_operations.edit.button',
//        'field.title.options',
//        'field.title.load',
//        'field.title.save',
//        'field.title.xlabel',
//        'field.title.input_field',
//        'field.title.wizard',
//    ];

    private const SINGLETONS = [
        'panel_callback',
        'paste_button_callback',
        'button_callback',
        'label_callback',
        'header_callback',
        'child_record_callback',
        'input_field_callback',
        'options_callback',
    ];

    private $callbacks = [];

    public function setCallbacks(array $callbacks): void
    {
        $this->callbacks = $callbacks;
    }

    public function onLoadDataContainer(string $table): void
    {
        if (!isset($this->callbacks[$table])) {
            return;
        }

        $replaces = [];

        foreach ($this->callbacks[$table] as $target => $callbacks) {
            $keys = explode('.', $target);
            $current = $this->getFromDCA($table, $keys);

            if (\in_array(end($keys), self::SINGLETONS, true)) {
                $value = $this->getFirstByPriority($callbacks, $current);
            } else {
                $value = $this->getMergedByPriority($callbacks, $current);
            }

            foreach (array_reverse($keys) as $key) {
                $value = [$key => $value];
            }

            $replaces[] = $value;
        }

        $GLOBALS['TL_DCA'][$table] = array_replace_recursive($GLOBALS['TL_DCA'][$table], $replaces);
    }

    private function getFromDCA(string $table, array $keys)
    {
        if (!isset($GLOBALS['TL_DCA'][$table])) {
            return null;
        }

        $dca = $GLOBALS['TL_DCA'][$table];

        foreach ($keys as $key) {
            if (!isset($dca[$key])) {
                return null;
            }

            $dca = $dca[$key];
        }

        return $dca;
    }

    private function getFirstByPriority(array $callbacks, ?callable $current): callable
    {
        if (null !== $current && !isset($callbacks[0])) {
            $callbacks[0] = $current;
        }

        krsort($callbacks, SORT_NUMERIC);

        return array_shift($callbacks);
    }

    private function getMergedByPriority(array $callbacks, ?array $current): array
    {
        if (null !== $current) {
            if (!isset($callbacks[0])) {
                $callbacks[0] = [];
            }

            $callbacks[0] = array_merge($callbacks[0], $current);
        }

        krsort($callbacks, SORT_NUMERIC);

        return array_merge(...$callbacks);
    }
}
