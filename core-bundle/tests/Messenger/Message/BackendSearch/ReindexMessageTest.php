<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Messenger\Message\BackendSearch;

use Contao\CoreBundle\Messenger\Message\BackendSearch\ReindexMessage;
use Contao\CoreBundle\Search\Backend\GroupedDocumentIds;
use Contao\CoreBundle\Search\Backend\ReindexConfig;
use PHPUnit\Framework\TestCase;

class ReindexMessageTest extends TestCase
{
    public function testGetter(): void
    {
        $reindexConfig = (new ReindexConfig())
            ->limitToDocumentIds(new GroupedDocumentIds(['foo' => ['bar']]))
            ->limitToDocumentsNewerThan(new \DateTimeImmutable('2024-01-01T00:00:00+00:00'))
        ;

        $message = new ReindexMessage($reindexConfig);
        $this->assertSame('2024-01-01T00:00:00+00:00', $message->getReindexConfig()->getUpdateSince()->format(\DateTimeInterface::ATOM));
        $this->assertSame(['foo' => ['bar']], $message->getReindexConfig()->getLimitedDocumentIds()->toArray());
    }
}
