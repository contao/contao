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

            $tables = $module['tables'];

            // Use the parent table if it has been set in the DCA file
            if (($ptable = $GLOBALS['TL_DCA'][$table]['config']['ptable'] ?? null) && \in_array($ptable, $tables, true)) {
                array_unshift($tables, $ptable);
            }

            // Use the ptable query parameter if there is another possible dynamic parent in
            // the back end module (see #10146)
            if (($ptable = $this->framework->getAdapter(Input::class)->get('ptable')) && \in_array($ptable, $tables, true)) {
                array_unshift($tables, $ptable);
            }

            foreach ($tables as $ptable) {
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
