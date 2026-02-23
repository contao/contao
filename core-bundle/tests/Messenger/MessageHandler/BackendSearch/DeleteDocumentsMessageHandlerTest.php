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

use Contao\CoreBundle\Messenger\Message\BackendSearch\DeleteDocumentsMessage;
use Contao\CoreBundle\Messenger\Message\ScopeAwareMessageInterface;
use Contao\CoreBundle\Messenger\MessageHandler\BackendSearch\DeleteDocumentsMessageHandler;
use Contao\CoreBundle\Search\Backend\BackendSearch;
use Contao\CoreBundle\Search\Backend\GroupedDocumentIds;
use PHPUnit\Framework\TestCase;

class DeleteDocumentsMessageHandlerTest extends TestCase
{
    public function testDeleteDocuments(): void
    {
        $documentTypesAndIds = new GroupedDocumentIds([
            'test' => ['42'],
            'foobar' => ['42'],
        ]);

        $message = new DeleteDocumentsMessage($documentTypesAndIds);
        $message->setScope(ScopeAwareMessageInterface::SCOPE_CLI);

        $backendSearch = $this->createMock(BackendSearch::class);
        $backendSearch
            ->expects($this->once())
            ->method('deleteDocuments')
            ->with($documentTypesAndIds)
        ;

        $messageHandler = new DeleteDocumentsMessageHandler($backendSearch);
        $messageHandler($message);
    }
}
