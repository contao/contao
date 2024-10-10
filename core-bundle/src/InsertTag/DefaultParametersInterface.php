<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\InsertTag;

interface DefaultParametersInterface
{
    /**
     * Use this interface to apply default values to an insert tag. You should always
     * use this interface rather than dynamically evaluate defaults in the controller.
     */
    public function applyDefaults(InsertTag $tag): Inserttag;
}
