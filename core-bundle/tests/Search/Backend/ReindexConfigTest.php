<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Search\Backend;

use Contao\CoreBundle\Search\Backend\ReindexConfig;
use PHPUnit\Framework\TestCase;

class ReindexConfigTest extends TestCase
{
    public function testIndexUpdateConfig(): void
    {
        $config = new ReindexConfig(null);
        $this->assertNull($config->getUpdateSince());

        $since = new \DateTimeImmutable();
        $config = new ReindexConfig($since);
        $this->assertSame($since, $config->getUpdateSince());
    }
}
