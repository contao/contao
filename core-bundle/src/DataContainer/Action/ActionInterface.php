<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DataContainer\Action;

use Contao\DataContainer;

/**
 * @internal
 */
interface ActionInterface
{
    /**
     * Renders the content of the action.
     */
    public function render(DataContainer $dc): string;
}
