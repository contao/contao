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
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Nyholm\Psr7\Uri;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Component\HttpFoundation\RequestStack;
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

        $factory = new Factory($this->createMock(Connection::class), $this->mockContaoFramework(), new RequestStack());
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
        $rootPage
            ->method('getAbsoluteUrl')
            ->willReturn('https://contao.org')
        ;

        $pageModelAdapter = $this->mockAdapter(['findPublishedRootPages']);
        $pageModelAdapter
            ->method('findPublishedRootPages')
            ->willReturn([$rootPage])
        ;

        $factory = new Factory(
            $this->createMock(Connection::class),
            $this->mockContaoFramework([PageModel::class => $pageModelAdapter]),
            new RequestStack(),
            ['https://example.com']
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

        $factory = new Factory($this->createMock(Connection::class), $this->mockContaoFramework(), new RequestStack());
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

        $factory = new Factory($this->createMock(Connection::class), $this->mockContaoFramework(), new RequestStack());
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

    public function testScopesConfidentialHeadersAutomatically(): void
    {
        $expectedRequests = [
            function (string $method, string $url, array $options): MockResponse {
                $this->assertSame('GET', $method);
                $this->assertSame('https://contao.org/robots.txt', $url);
                $this->assertContains('Cookie: Confidential', $options['headers']);
                $this->assertContains('Authorization: Basic dXNlcm5hbWU6cGFzc3dvcmQ=', $options['headers']);

                return new MockResponse();
            },
            function (string $method, string $url, array $options): MockResponse {
                $this->assertSame('GET', $method);
                $this->assertSame('https://contao.de/robots.txt', $url);
                $this->assertContains('Cookie: Confidential', $options['headers']);
                $this->assertContains('Authorization: Basic dXNlcm5hbWU6cGFzc3dvcmQ=', $options['headers']);

                return new MockResponse();
            },
            function (string $method, string $url, array $options): MockResponse {
                $this->assertSame('GET', $method);
                $this->assertSame('https://www.foreign-domain.com/robots.txt', $url);
                $this->assertNotContains('Cookie: Confidential', $options['headers']);
                $this->assertNotContains('Authorization: Basic dXNlcm5hbWU6cGFzc3dvcmQ=', $options['headers']);

                return new MockResponse();
            },
        ];

        $mockClient = new MockHttpClient($expectedRequests);
        $clientFactory = static fn (array $defaultOptions) => $mockClient;

        $rootPage1 = $this->createMock(PageModel::class);
        $rootPage1
            ->method('getAbsoluteUrl')
            ->willReturn('https://contao.org')
        ;

        $rootPage2 = $this->createMock(PageModel::class);
        $rootPage2
            ->method('getAbsoluteUrl')
            ->willReturn('https://contao.de')
        ;

        $pageModelAdapter = $this->mockAdapter(['findPublishedRootPages']);
        $pageModelAdapter
            ->method('findPublishedRootPages')
            ->willReturn([$rootPage1, $rootPage2])
        ;

        $subscriber1 = $this->createMock(EscargotSubscriberInterface::class);
        $subscriber1
            ->method('getName')
            ->willReturn('subscriber-1')
        ;

        $factory = new Factory(
            $this->createMock(Connection::class),
            $this->mockContaoFramework([PageModel::class => $pageModelAdapter]),
            new RequestStack(),
            ['https://www.foreign-domain.com'],
            [
                'headers' => [
                    'Cookie' => 'Confidential',
                ],
                'auth_basic' => 'username:password',
            ],
            $clientFactory
        );

        $factory->addSubscriber($subscriber1);

        $escargot = $factory->create($factory->getCrawlUriCollection(), new InMemoryQueue(), ['subscriber-1']);
        $escargot->crawl();

        $this->assertSame(3, $mockClient->getRequestsCount());
        $this->assertInstanceOf(ScopingHttpClient::class, $escargot->getClient());
    }
}
