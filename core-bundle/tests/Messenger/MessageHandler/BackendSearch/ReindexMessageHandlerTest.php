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
use Contao\CoreBundle\Messenger\MessageHandler\BackendSearch\ReindexMessageHandler;
use Contao\CoreBundle\Search\Backend\BackendSearch;
use Contao\CoreBundle\Search\Backend\ReindexConfig;
use PHPUnit\Framework\TestCase;

class ReindexMessageHandlerTest extends TestCase
{
    public function testReindex(): void
    {
        $updateSince = new \DateTimeImmutable();
        $message = new ReindexMessage($updateSince->format(\DateTimeInterface::ATOM));

        $backendSearch = $this->createMock(BackendSearch::class);
        $backendSearch
            ->expects($this->once())
            ->method('reindex')
            ->with(
                $this->callback(static fn (ReindexConfig $config): bool => $config->getUpdateSince()->format(\DateTimeInterface::ATOM) === $updateSince->format(\DateTimeInterface::ATOM)),
                false,
            )
        ;

        $messageHandler = new ReindexMessageHandler($backendSearch);
        $messageHandler($message);
    }
}
