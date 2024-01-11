<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Crawl\Escargot;

use Contao\CoreBundle\Crawl\Escargot\Factory;
use Contao\CoreBundle\Crawl\Escargot\Subscriber\EscargotSubscriberInterface;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Nyholm\Psr7\Uri;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Terminal42\Escargot\BaseUriCollection;
use Terminal42\Escargot\Queue\InMemoryQueue;

class FactoryTest extends TestCase
{
    public function testHandlesSubscribersCorrectly(): void
    {
        $subscriber1 = $this->createMock(EscargotSubscriberInterface::class);
        $subscriber1
            ->method('getName')
            ->willReturn('subscriber-1')
        ;

        $subscriber2 = $this->createMock(EscargotSubscriberInterface::class);
        $subscriber2
            ->method('getName')
            ->willReturn('subscriber-2')
        ;

        $factory = new Factory($this->createMock(Connection::class), $this->mockContaoFramework(), $this->createMock(ContentUrlGenerator::class));
        $factory->addSubscriber($subscriber1);
        $factory->addSubscriber($subscriber2);

        $this->assertCount(2, $factory->getSubscribers());
        $this->assertCount(2, $factory->getSubscribers(['subscriber-1', 'subscriber-2']));
        $this->assertCount(1, $factory->getSubscribers(['subscriber-1']));
        $this->assertCount(1, $factory->getSubscribers(['subscriber-2']));
        $this->assertSame(['subscriber-1', 'subscriber-2'], $factory->getSubscriberNames());
    }

    public function testBuildsUriCollectionsCorrectly(): void
    {
        $rootPage = $this->createMock(PageModel::class);

        $pageModelAdapter = $this->mockAdapter(['findPublishedRootPages']);
        $pageModelAdapter
            ->method('findPublishedRootPages')
            ->willReturn([$rootPage])
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->method('generate')
            ->with($rootPage, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://contao.org')
        ;

        $factory = new Factory(
            $this->createMock(Connection::class),
            $this->mockContaoFramework([PageModel::class => $pageModelAdapter]),
            $urlGenerator,
            ['https://example.com'],
        );

        $this->assertCount(1, $factory->getAdditionalCrawlUriCollection());
        $this->assertTrue($factory->getAdditionalCrawlUriCollection()->containsHost('example.com'));

        $this->assertCount(1, $factory->getRootPageUriCollection());
        $this->assertTrue($factory->getRootPageUriCollection()->containsHost('contao.org'));

        $this->assertCount(2, $factory->getCrawlUriCollection());
        $this->assertTrue($factory->getCrawlUriCollection()->containsHost('example.com'));
        $this->assertTrue($factory->getCrawlUriCollection()->containsHost('contao.org'));
    }

    public function testCreatesEscargotCorrectlyWithNewJobId(): void
    {
        $subscriber1 = $this->createMock(EscargotSubscriberInterface::class);
        $subscriber1
            ->method('getName')
            ->willReturn('subscriber-1')
        ;

        $factory = new Factory($this->createMock(Connection::class), $this->mockContaoFramework(), $this->createMock(ContentUrlGenerator::class));
        $factory->addSubscriber($subscriber1);

        $uriCollection = new BaseUriCollection([new Uri('https://contao.org')]);
        $escargot = $factory->create($uriCollection, new InMemoryQueue(), ['subscriber-1']);

        $this->assertCount(3, $escargot->getSubscribers());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('You have to specify at least one valid subscriber name. Valid subscribers are: subscriber-1');

        $escargot = $factory->create($uriCollection, new InMemoryQueue(), ['subscriber-8']);
        $this->assertSame(Factory::USER_AGENT, $escargot->getUserAgent());
    }

    public function testCreatesEscargotCorrectlyWithExistingJobId(): void
    {
        $subscriber1 = $this->createMock(EscargotSubscriberInterface::class);
        $subscriber1
            ->method('getName')
            ->willReturn('subscriber-1')
        ;

        $factory = new Factory($this->createMock(Connection::class), $this->mockContaoFramework(), $this->createMock(ContentUrlGenerator::class));
        $factory->addSubscriber($subscriber1);

        $queue = new InMemoryQueue();
        $jobId = $queue->createJobId(new BaseUriCollection([new Uri('https://contao.org')]));

        $escargot = $factory->createFromJobId($jobId, $queue, ['subscriber-1']);

        $this->assertCount(3, $escargot->getSubscribers());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('You have to specify at least one valid subscriber name. Valid subscribers are: subscriber-1');

        $escargot = $factory->createFromJobId($jobId, $queue, ['subscriber-8']);
        $this->assertSame(Factory::USER_AGENT, $escargot->getUserAgent());
    }
}
