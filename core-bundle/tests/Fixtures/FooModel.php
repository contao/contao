<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Fixtures;

use Contao\Model;

class FooModel extends Model
{
    public $id = 1;
    protected static $strTable = 'foo';

    public function __construct()
    {
    }

    public function onRegister($registry): void
    {
    }
}
