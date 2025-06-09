<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;

/**
 * Adds default labels to DCA operations and fields (see #509).
 *
 * @internal
 */
#[AsHook('loadDataContainer', priority: -255)]
class DefaultLabelsListener
{
    public function __invoke(string $table): void
    {
        // Operations
        foreach (['global_operations', 'operations'] as $key) {
            if (!isset($GLOBALS['TL_DCA'][$table]['list'][$key])) {
                continue;
            }

            foreach ($GLOBALS['TL_DCA'][$table]['list'][$key] as $k => &$v) {
                if (!\is_array($v) || \array_key_exists('label', $v)) {
                    continue;
                }

                if (isset($GLOBALS['TL_LANG'][$table][$k]) || !isset($GLOBALS['TL_LANG']['DCA'][$k])) {
                    $v['label'] = &$GLOBALS['TL_LANG'][$table][$k];
                } else {
                    $v['label'] = &$GLOBALS['TL_LANG']['DCA'][$k];
                }
            }

            unset($v);
        }

        // Fields
        if (isset($GLOBALS['TL_DCA'][$table]['fields'])) {
            foreach ($GLOBALS['TL_DCA'][$table]['fields'] as $k => &$v) {
                if (isset($v['label'])) {
                    continue;
                }

                $v['label'] = &$GLOBALS['TL_LANG'][$table][$k];
            }

            unset($v);
        }
    }
}
