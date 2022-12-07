<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Cron;

use Contao\CoreBundle\Cron\ProcessCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class ProcessCollectionTest extends TestCase
{
    public function testCollection(): void
    {
        $collection = new ProcessCollection();
        $collection->add(new Process([]), 'process-1');
        $collection->add(new Process([]), 'process-2');

        $this->assertCount(2, $collection->all());
        $this->assertSame(['process-1', 'process-2'], array_keys($collection->all()));
    }
}
