<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Tests\Messenger\MessageHandler;

use Contao\CoreBundle\Crawl\Escargot\Factory;
use Contao\CoreBundle\Job\Job;
use Contao\CoreBundle\Job\Jobs;
use Contao\CoreBundle\Job\Status;
use Contao\CoreBundle\Messenger\Message\CrawlMessage;
use Contao\CoreBundle\Messenger\MessageHandler\CrawlMessageHandler;
use Contao\CoreBundle\Tests\Job\AbstractJobsTestCase;
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Terminal42\Escargot\BaseUriCollection;
use Terminal42\Escargot\CrawlUri;
use Terminal42\Escargot\Escargot;
use Terminal42\Escargot\Queue\InMemoryQueue;
use Terminal42\Escargot\Queue\LazyQueue;
use Terminal42\Escargot\Subscriber\SubscriberInterface;

class CrawlMessageHandlerTest extends AbstractJobsTestCase
{
    private BaseUriCollection $baseUriCollection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseUriCollection = new BaseUriCollection([
            new Uri('https://contao.org'),
            new Uri('https://contao.org/about-us'),
        ]);
    }

    public function testDoesNothingIfJobNotFoundOrAlreadyCompleted(): void
    {
        $jobs = $this->getJobs();
        $handler = $this->createMessageHandler($jobs);
        $handler($this->createMessage());
        $this->assertSame([], $jobs->findMyNewOrPending());

        $job = $jobs->createJob('crawl');
        $job = $job->markCompleted();
        $jobs->persist($job);

        $handler($this->createMessage($job));
        $this->assertSame(Status::completed, $jobs->getByUuid($job->getUuid())->getStatus());
    }

    public function testCreatesANewEscargotJobAndFinishesCrawlingRightAway(): void
    {
        $lazyQueue = new LazyQueue(new InMemoryQueue(), new InMemoryQueue());
        $factory = $this->mockEscargotFactory($lazyQueue);
        $escargot = $this->createEscargot($lazyQueue);

        $factory
            ->expects($this->once())
            ->method('create')
            ->willReturn($escargot)
        ;

        $jobs = $this->getJobs();
        $job = $jobs->createJob('crawl');

        $handler = $this->createMessageHandler($jobs, $factory);
        $handler($this->createMessage($job));

        $job = $jobs->getByUuid($job->getUuid());
        $this->assertSame($escargot->getJobId(), $job->getMetadata()['escargotJobId']);
        $this->assertSame(Status::completed, $job->getStatus());
    }

    public function testCreatesANewEscargotJobCrawlsAndRedispatches(): void
    {
        $lazyQueue = new LazyQueue(new InMemoryQueue(), new InMemoryQueue());
        $factory = $this->mockEscargotFactory($lazyQueue);
        $escargot = $this->createEscargot($lazyQueue);

        // This will make sure Escargot doesn't finish in this round as we have 2 URLs in
        // our BaseUriCollection
        $escargot = $escargot->withMaxRequests(1);

        $factory
            ->expects($this->once())
            ->method('create')
            ->willReturn($escargot)
        ;

        $jobs = $this->getJobs();
        $job = $jobs->createJob('crawl');
        $message = $this->createMessage($job);

        $handler = $this->createMessageHandler($jobs, $factory, $this->mockMessageBusThatExpectsARedispatch($message));
        $handler($message);

        $job = $jobs->getByUuid($job->getUuid());
        $this->assertSame($escargot->getJobId(), $job->getMetadata()['escargotJobId']);
        $this->assertSame(Status::pending, $job->getStatus());
        $this->assertSame(50.0, $job->getProgress());
    }

    public function testContinuesFromExistingJobAndThenFinishes(): void
    {
        $lazyQueue = new LazyQueue(new InMemoryQueue(), new InMemoryQueue());
        $factory = $this->mockEscargotFactory($lazyQueue);
        $escargot = $this->createEscargot($lazyQueue);

        $factory
            ->expects($this->once())
            ->method('createFromJobId')
            ->willReturn($escargot)
        ;

        $jobs = $this->getJobs();
        $job = $jobs->createJob('crawl');
        $job = $job->withMetadata(['escargotJobId' => $escargot->getJobId()]);
        $jobs->persist($job);

        $message = $this->createMessage($job);

        $handler = $this->createMessageHandler($jobs, $factory);
        $handler($message);

        $job = $jobs->getByUuid($job->getUuid());
        $this->assertSame($escargot->getJobId(), $job->getMetadata()['escargotJobId']);
        $this->assertSame(Status::completed, $job->getStatus());
        $this->assertSame(100.0, $job->getProgress());
    }

    private function createEscargot(LazyQueue $lazyQueue): Escargot
    {
        $escargot = Escargot::create($this->baseUriCollection, $lazyQueue)->withHttpClient(new MockHttpClient());
        $escargot->addSubscriber(new class() implements SubscriberInterface {
            public function shouldRequest(CrawlUri $crawlUri): string
            {
                return self::DECISION_POSITIVE;
            }

            public function needsContent(CrawlUri $crawlUri, ResponseInterface $response, ChunkInterface $chunk): string
            {
                return self::DECISION_POSITIVE;
            }

            public function onLastChunk(CrawlUri $crawlUri, ResponseInterface $response, ChunkInterface $chunk): void
            {
                // noop
            }
        });

        return $escargot;
    }

    private function createMessage(Job|null $job = null): CrawlMessage
    {
        return (new CrawlMessage(['subscriber-a'], 3, []))->setJobId($job?->getUuid() ?? 'i-do-not-exist');
    }

    private function mockEscargotFactory(LazyQueue $lazyQueue): MockObject&Factory
    {
        $escargotFactory = $this->createMock(Factory::class);
        $escargotFactory
            ->expects($this->once())
            ->method('getCrawlUriCollection')
            ->willReturn($this->baseUriCollection)
        ;

        $escargotFactory
            ->method('createLazyQueue')
            ->willReturn($lazyQueue)
        ;

        return $escargotFactory;
    }

    private function mockMessageBusThatExpectsARedispatch(CrawlMessage $crawlMessage): MessageBusInterface&MockObject
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($crawlMessage)
            ->willReturn(new Envelope($crawlMessage))
        ;

        return $messageBus;
    }

    private function createMessageHandler(Jobs $jobs, Factory|null $factory = null, MessageBusInterface|null $messageBus = null): CrawlMessageHandler
    {
        if (null === $messageBus) {
            $messageBus = $this->createMock(MessageBusInterface::class);
            $messageBus
                ->expects($this->never())
                ->method('dispatch')
            ;
        }

        return new CrawlMessageHandler(
            $factory ?? $this->createMock(Factory::class),
            $jobs,
            $messageBus,
            'project-dir',
            5,
        );
    }
}
