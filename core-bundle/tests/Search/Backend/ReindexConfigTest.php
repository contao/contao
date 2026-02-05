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

use Contao\CoreBundle\Search\Backend\GroupedDocumentIds;
use Contao\CoreBundle\Search\Backend\ReindexConfig;
use PHPUnit\Framework\TestCase;

class ReindexConfigTest extends TestCase
{
    public function testIndexUpdateConfig(): void
    {
        $config = new ReindexConfig();
        $this->assertNull($config->getUpdateSince());

        $since = new \DateTimeImmutable();
        $config = new ReindexConfig();
        $config = $config->limitToDocumentsNewerThan($since);
        $this->assertSame($since, $config->getUpdateSince());
    }

    public function testLimitToGroupedDocumentIds(): void
    {
        $config = new ReindexConfig();
        $this->assertTrue($config->getLimitedDocumentIds()->isEmpty());

        $config = new ReindexConfig();
        $config = $config->limitToDocumentIds(new GroupedDocumentIds(['foo' => ['42']]));
        $this->assertSame(['foo' => ['42']], $config->getLimitedDocumentIds()->toArray());
    }

    public function testRequiresJob(): void
    {
        $config = new ReindexConfig();
        $this->assertFalse($config->requiresJob());

        $config = $config->withRequireJob(true);
        $this->assertTrue($config->requiresJob());
    }
}
