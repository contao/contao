<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Messenger\MessageHandler\BackendSearch;

use Contao\CoreBundle\Messenger\Message\BackendSearch\ReindexMessage;
use Contao\CoreBundle\Messenger\Message\ScopeAwareMessageInterface;
use Contao\CoreBundle\Messenger\MessageHandler\BackendSearch\ReindexMessageHandler;
use Contao\CoreBundle\Search\Backend\BackendSearch;
use Contao\CoreBundle\Search\Backend\GroupedDocumentIds;
use Contao\CoreBundle\Search\Backend\ReindexConfig;
use PHPUnit\Framework\TestCase;

class ReindexMessageHandlerTest extends TestCase
{
    public function testReindex(): void
    {
        $reindexConfig = (new ReindexConfig())
            ->limitToDocumentIds(new GroupedDocumentIds(['foo' => ['bar']]))
            ->limitToDocumentsNewerThan(new \DateTimeImmutable('2024-01-01T00:00:00+00:00'))
        ;

        $message = new ReindexMessage($reindexConfig);
        $message->setScope(ScopeAwareMessageInterface::SCOPE_CLI);

        $backendSearch = $this->createMock(BackendSearch::class);
        $backendSearch
            ->expects($this->once())
            ->method('reindex')
            ->with(
                $this->callback(static fn (ReindexConfig $config): bool => '2024-01-01T00:00:00+00:00' === $config->getUpdateSince()->format(\DateTimeInterface::ATOM) && ['foo' => ['bar']] === $config->getLimitedDocumentIds()->toArray()),
                false,
            )
        ;

        $messageHandler = new ReindexMessageHandler($backendSearch);
        $messageHandler($message);
    }
}
