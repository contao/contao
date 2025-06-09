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

use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Input;

/**
 * Sets the parent table for the current table, if enabled and not set.
 *
 * @internal
 */
#[AsHook('loadDataContainer', priority: 255)]
class DynamicPtableListener
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function __invoke(string $table): void
    {
        if (
            !($GLOBALS['TL_DCA'][$table]['config']['dynamicPtable'] ?? null)
            || !isset($GLOBALS['BE_MOD'])
            || isset($GLOBALS['TL_DCA'][$table]['config']['ptable'])
        ) {
            return;
        }

        if (!$do = $this->framework->getAdapter(Input::class)->get('do')) {
            return;
        }

        $controllerAdapter = $this->framework->getAdapter(Controller::class);

        foreach (array_merge(...array_values($GLOBALS['BE_MOD'])) as $key => $module) {
            if ($do !== $key || !isset($module['tables']) || !\is_array($module['tables'])) {
                continue;
            }

            foreach ($module['tables'] as $ptable) {
                $controllerAdapter->loadDataContainer($ptable);

                $ctable = $GLOBALS['TL_DCA'][$ptable]['config']['ctable'] ?? [];

                if (\in_array($table, $ctable, true)) {
                    $GLOBALS['TL_DCA'][$table]['config']['ptable'] = $ptable;

                    return;
                }
            }
        }
    }
}
