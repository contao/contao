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

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;

/**
 * @Callback(table="tl_module", target="fields.menuAlias.save")
 *
 * @internal
 */
class NavigationModuleAliasListener
{
    public function __invoke(string $value, DataContainer $dc): string
    {
        if ('' !== $value) {
            return $value;
        }

        return $dc->activeRecord->type.'_'.$dc->id;
    }
}
