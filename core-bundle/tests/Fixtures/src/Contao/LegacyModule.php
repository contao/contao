<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Fixtures\Contao;

use Contao\Module;

class LegacyModule extends Module
{
    protected $strTemplate = 'mod_custom_legacy_template';

    public function __construct()
    {
    }

    #[\Override]
    protected function compile(): void
    {
    }
}
