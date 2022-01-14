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

use Contao\DataContainer;

class DC_NewsTableStub extends DataContainer
{
    protected $strTable = 'tl_news';

    public function __construct()
    {
    }

    public function getPalette(): void
    {
    }

    protected function save($varValue): void
    {
    }
}
