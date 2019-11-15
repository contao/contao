<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Search\Escargot;

use Contao\CoreBundle\Search\Escargot\Factory;
use Contao\CoreBundle\Search\Escargot\Subscriber\EscargotSubscriber;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Nyholm\Psr7\Uri;
use Terminal42\Escargot\BaseUriCollection;
use Terminal42\Escargot\Escargot;
use Terminal42\Escargot\Queue\InMemoryQueue;
use Terminal42\Escargot\Queue\LazyQueue;

class FactoryTest extends TestCase
{
    public function testHandlesSubscribersCorrectly(): void
    {
        $factory = new Factory(
            $this->createMock(Connection::class),
            $this->mockContaoFramework()
        );

        $subscriber1 = $this->createMock(EscargotSubscriber::class);
        $subscriber1
            ->expects($this->any())
            ->method('getName')
            ->willReturn('subscriber-1')
        ;

        $subscriber2 = $this->createMock(EscargotSubscriber::class);
        $subscriber2
            ->expects($this->any())
            ->method('getName')
            ->willReturn('subscriber-2')
        ;

        $factory->addSubscriber($subscriber1);
        $factory->addSubscriber($subscriber2);

        $this->assertCount(2, $factory->getSubscribers());
        $this->assertCount(2, $factory->getSubscribers(['subscriber-1', 'subscriber-2']));
        $this->assertCount(1, $factory->getSubscribers(['subscriber-1']));
        $this->assertCount(1, $factory->getSubscribers(['subscriber-2']));

        $this->assertSame(['subscriber-1', 'subscriber-2'], $factory->getSubscriberNames());
    }

    public function testQueueIsInstantiatedCorrectly(): void
    {
        $factory = new Factory(
            $this->createMock(Connection::class),
            $this->mockContaoFramework()
        );

        $this->assertInstanceOf(LazyQueue::class, $factory->createLazyQueue());
    }

    public function testBuildsUriCollectionsCorrectly(): void
    {
        $rootPage = $this->createMock(PageModel::class);
        $rootPage
            ->expects($this->any())
            ->method('getAbsoluteUrl')
            ->willReturn('https://contao.org')
        ;

        $pageModelAdapter = $this->mockAdapter(['findPublishedRootPages']);
        $pageModelAdapter
            ->expects($this->any())
            ->method('findPublishedRootPages')
            ->willReturn([$rootPage])
        ;

        $factory = new Factory(
            $this->createMock(Connection::class),
            $this->mockContaoFramework([PageModel::class => $pageModelAdapter]),
            ['https://example.com']
        );

        $this->assertCount(1, $factory->getAdditionalSearchUriCollection());
        $this->assertTrue($factory->getAdditionalSearchUriCollection()->containsHost('example.com'));

        $this->assertCount(1, $factory->getRootPageUriCollection());
        $this->assertTrue($factory->getRootPageUriCollection()->containsHost('contao.org'));

        $this->assertCount(2, $factory->getSearchUriCollection());
        $this->assertTrue($factory->getSearchUriCollection()->containsHost('example.com'));
        $this->assertTrue($factory->getSearchUriCollection()->containsHost('contao.org'));
    }

    public function testCreatesEscargotCorrectlyWithNewJobId(): void
    {
        $factory = new Factory(
            $this->createMock(Connection::class),
            $this->mockContaoFramework()
        );

        $subscriber1 = $this->createMock(EscargotSubscriber::class);
        $subscriber1
            ->expects($this->any())
            ->method('getName')
            ->willReturn('subscriber-1')
        ;

        $factory->addSubscriber($subscriber1);

        $escargot = $factory->create(new BaseUriCollection([new Uri('https://contao.org')]), new InMemoryQueue(), ['subscriber-1']);

        $this->assertInstanceOf(Escargot::class, $escargot);
        $this->assertCount(3, $escargot->getSubscribers());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('You have to specify at least one valid subscriber name. Valid subscribers are: subscriber-1');
        $factory->create(new BaseUriCollection([new Uri('https://contao.org')]), new InMemoryQueue(), ['subscriber-8']);
    }

    public function testCreatesEscargotCorrectlyWithExistingJobId(): void
    {
        $factory = new Factory(
            $this->createMock(Connection::class),
            $this->mockContaoFramework(),
        );

        $subscriber1 = $this->createMock(EscargotSubscriber::class);
        $subscriber1
            ->expects($this->any())
            ->method('getName')
            ->willReturn('subscriber-1')
        ;

        $factory->addSubscriber($subscriber1);

        $queue = new InMemoryQueue();
        $jobId = $queue->createJobId(new BaseUriCollection([new Uri('https://contao.org')]));

        $escargot = $factory->createFromJobId($jobId, $queue, ['subscriber-1']);

        $this->assertInstanceOf(Escargot::class, $escargot);
        $this->assertCount(3, $escargot->getSubscribers());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('You have to specify at least one valid subscriber name. Valid subscribers are: subscriber-1');
        $factory->createFromJobId($jobId, $queue, ['subscriber-8']);
    }
}
