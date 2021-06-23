<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Fixtures\Migration;

use Contao\CoreBundle\Migration\AbstractRecordedMigration;
use Contao\CoreBundle\Migration\MigrationResult;

class FooRecordedMigration extends AbstractRecordedMigration
{
    public function run(): MigrationResult
    {
        return $this->createResult(true);
    }
}
