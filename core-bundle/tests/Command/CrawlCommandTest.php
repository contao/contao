<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Command;

use Contao\CoreBundle\Command\CrawlCommand;
use Contao\CoreBundle\Crawl\Escargot\Factory;
use Contao\CoreBundle\Tests\TestCase;
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Terminal42\Escargot\BaseUriCollection;
use Terminal42\Escargot\Escargot;
use Terminal42\Escargot\Exception\InvalidJobIdException;
use Terminal42\Escargot\Queue\InMemoryQueue;
use Terminal42\Escargot\Queue\LazyQueue;

class CrawlCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([ProgressBar::class, Terminal::class]);

        parent::tearDown();
    }

    public function testAbortsWithInvalidJobId(): void
    {
        $escargotFactory = $this->mockInvalidEscargotFactory(new InvalidJobIdException(), true);
        $command = new CrawlCommand($escargotFactory, new Filesystem());

        $tester = new CommandTester($command);
        $code = $tester->execute(['job' => 'i-do-not-exist']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('[ERROR] Could not find the given job ID.', $tester->getDisplay());
    }

    public function testAbortsIfEscargotCouldNotBeInstantiated(): void
    {
        $escargotFactory = $this->mockInvalidEscargotFactory(new InvalidArgumentException('Something went wrong!'));
        $command = new CrawlCommand($escargotFactory, new Filesystem());

        $tester = new CommandTester($command);
        $code = $tester->execute([]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('[ERROR] Something went wrong!', $tester->getDisplay());
    }

    public function testOptionsConfigureEscargotCorrectly(): void
    {
        // Make sure we never execute real requests
        $client = new MockHttpClient();

        // Test defaults
        $escargot = Escargot::create($this->getBaseUriCollection(), new InMemoryQueue())->withHttpClient($client);
        $escargotFactory = $this->mockValidEscargotFactory($escargot);
        $command = new CrawlCommand($escargotFactory, new Filesystem());

        $tester = new CommandTester($command);
        $code = $tester->execute([]);

        $this->assertSame(0, $code);
        $this->assertSame(10, $command->getEscargot()->getConcurrency());
        $this->assertSame(0, $command->getEscargot()->getRequestDelay());
        $this->assertSame(0, $command->getEscargot()->getMaxRequests());
        $this->assertSame(10, $command->getEscargot()->getMaxDepth());

        // Test options
        $escargot = Escargot::create($this->getBaseUriCollection(), new InMemoryQueue())->withHttpClient($client);
        $escargotFactory = $this->mockValidEscargotFactory($escargot);
        $command = new CrawlCommand($escargotFactory, new Filesystem());

        $tester = new CommandTester($command);
        $code = $tester->execute(['-c' => 20, '--delay' => 20, '--max-requests' => 20, '--max-depth' => 20]);

        $this->assertSame(0, $code);
        $this->assertSame(20, $command->getEscargot()->getConcurrency());
        $this->assertSame(20, $command->getEscargot()->getRequestDelay());
        $this->assertSame(20, $command->getEscargot()->getMaxRequests());
        $this->assertSame(20, $command->getEscargot()->getMaxDepth());
    }

    public function testSubsequentCrawlCommandsWithDoctrineQueue(): void
    {
        $client = new MockHttpClient();
        $queue = new InMemoryQueue();
        $jobId = null;

        $escargotFactory = $this->createMock(Factory::class);
        $escargotFactory
            ->expects($this->exactly(2))
            ->method('getCrawlUriCollection')
            ->willReturn($this->getBaseUriCollection())
        ;

        $escargotFactory
            ->expects($this->once())
            ->method('create')
            ->willReturnCallback(
                function () use ($client, $queue, &$jobId) {
                    $escargot = Escargot::create($this->getBaseUriCollection(), $queue)->withHttpClient($client);
                    $jobId = $escargot->getJobId();

                    return $escargot;
                }
            )
        ;

        $escargotFactory
            ->expects($this->exactly(2))
            ->method('createLazyQueue')
            ->willReturn(new LazyQueue(new InMemoryQueue(), $queue))
        ;

        $escargotFactory
            ->expects($this->once())
            ->method('createFromJobId')
            ->willReturnCallback(static fn (string $jobId) => Escargot::createFromJobId($jobId, $queue)->withHttpClient($client))
        ;

        $command = new CrawlCommand($escargotFactory, new Filesystem());

        $tester = new CommandTester($command);
        $tester->execute(['--queue' => 'doctrine']);

        $expectCli = sprintf('[Job ID: %s]', $jobId);

        $this->assertStringContainsString($expectCli, $tester->getDisplay(true));

        $tester->execute(['--queue' => 'doctrine', 'job' => $jobId]);

        $this->assertStringContainsString($expectCli, $tester->getDisplay(true));
    }

    public function testEmitsWarningIfLocalhostIsInCollection(): void
    {
        // Make sure we never execute real requests
        $client = new MockHttpClient();

        $baseUriCollection = new BaseUriCollection([new Uri('http://localhost')]);

        $escargot = Escargot::create($baseUriCollection, new InMemoryQueue())->withHttpClient($client);
        $escargotFactory = $this->mockValidEscargotFactory($escargot, $baseUriCollection);
        $command = new CrawlCommand($escargotFactory, new Filesystem());

        $tester = new CommandTester($command);
        $code = $tester->execute([]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('[WARNING] You are going to crawl localhost URIs.', $tester->getDisplay(true));
    }

    private function getBaseUriCollection(): BaseUriCollection
    {
        return new BaseUriCollection([new Uri('https://contao.org')]);
    }

    /**
     * @return Factory&MockObject
     */
    private function mockEscargotFactory(BaseUriCollection|null $baseUriCollection = null): Factory
    {
        $baseUriCollection ??= $this->getBaseUriCollection();

        $escargotFactory = $this->createMock(Factory::class);
        $escargotFactory
            ->expects($this->once())
            ->method('getCrawlUriCollection')
            ->willReturn($baseUriCollection)
        ;

        return $escargotFactory;
    }

    /**
     * @return Factory&MockObject
     */
    private function mockValidEscargotFactory(Escargot $escargot, BaseUriCollection|null $baseUriCollection = null): Factory
    {
        $escargotFactory = $this->mockEscargotFactory($baseUriCollection);
        $escargotFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($escargot)
        ;

        $escargotFactory
            ->expects($this->never())
            ->method('createFromJobId')
        ;

        return $escargotFactory;
    }

    /**
     * @return Factory&MockObject
     */
    private function mockInvalidEscargotFactory(\Exception $exception, bool $withExistingJobId = false): Factory
    {
        $escargotFactory = $this->mockEscargotFactory();

        if ($withExistingJobId) {
            $escargotFactory
                ->expects($this->once())
                ->method('createFromJobId')
                ->willThrowException($exception)
            ;

            $escargotFactory
                ->expects($this->never())
                ->method('create')
            ;

            $escargotFactory
                ->expects($this->never())
                ->method('createLazyQueue')
            ;

            return $escargotFactory;
        }

        $escargotFactory
            ->expects($this->once())
            ->method('create')
            ->willThrowException($exception)
        ;

        $escargotFactory
            ->expects($this->never())
            ->method('createFromJobId')
        ;

        return $escargotFactory;
    }
}
