<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Search\Backend\IndexUpdateConfig;

use Contao\CoreBundle\Search\Backend\IndexUpdateConfig\UpdateAllProvidersConfig;
use PHPUnit\Framework\TestCase;

class UpdateAllProvidersConfigTest extends TestCase
{
    public function testUpdateAllProvidersConfig(): void
    {
        $config = new UpdateAllProvidersConfig(null);
        $this->assertNull($config->getUpdateSince());

        $since = new \DateTimeImmutable();
        $config = new UpdateAllProvidersConfig($since);
        $this->assertSame($since, $config->getUpdateSince());
    }
}
