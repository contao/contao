<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Search\Backend\Seal;

use CmsIg\Seal\Reindex\ReindexConfig as SealReindexConfig;
use Contao\CoreBundle\Event\BackendSearch\IndexDocumentEvent;
use Contao\CoreBundle\Search\Backend\BackendSearch;
use Contao\CoreBundle\Search\Backend\Document;
use Contao\CoreBundle\Search\Backend\Provider\ProviderInterface;
use Contao\CoreBundle\Search\Backend\Seal\SealReindexProvider;
use Contao\CoreBundle\Search\Backend\Security\DocumentAllowedGroupsResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SealReindexProviderTest extends TestCase
{
    public function testTotalReturnsNull(): void
    {
        $provider = new SealReindexProvider(
            [$this->createStub(ProviderInterface::class)],
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(DocumentAllowedGroupsResolver::class),
        );

        $this->assertNull($provider->total());
    }

    public function testProvideSkipsNonMatchingIndex(): void
    {
        $internalProvider = $this->createMock(ProviderInterface::class);
        $internalProvider
            ->expects($this->never())
            ->method('updateIndex')
        ;

        $reindexConfig = new SealReindexConfig()->withIndex('non_matching_index');

        $provider = new SealReindexProvider(
            [$internalProvider],
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(DocumentAllowedGroupsResolver::class),
        );

        $provider->provide($reindexConfig);
    }

    public function testProvideProcessesDocuments(): void
    {
        $document = new Document('42', 'type', 'searchable');

        $internalProvider = $this->createMock(ProviderInterface::class);
        $internalProvider
            ->expects($this->once())
            ->method('updateIndex')
            ->willReturn(new \ArrayIterator([$document]))
        ;

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(IndexDocumentEvent::class))
            ->willReturnCallback(
                function (IndexDocumentEvent $event) use ($document) {
                    $this->assertSame($document, $event->getDocument());

                    return $event;
                },
            )
        ;

        $allowedGroupsResolver = $this->createMock(DocumentAllowedGroupsResolver::class);
        $allowedGroupsResolver
            ->expects($this->once())
            ->method('resolveAllowedGroups')
            ->with($internalProvider, $document)
            ->willReturn([1, 2])
        ;

        $provider = new SealReindexProvider(
            [$internalProvider],
            $eventDispatcher,
            $allowedGroupsResolver,
        );

        $result = iterator_to_array($provider->provide(new SealReindexConfig()));

        $this->assertCount(1, $result);
        $this->assertSame('type__42', $result[0]['id']);
        $this->assertSame('type', $result[0]['type']);
        $this->assertSame('searchable', $result[0]['searchableContent']);
        $this->assertSame([1, 2], $result[0]['allowedGroups']);
    }

    public function testGetIndexReturnsCorrectValue(): void
    {
        $this->assertSame(BackendSearch::SEAL_INTERNAL_INDEX_NAME, SealReindexProvider::getIndex());
    }
}
