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
use Contao\CoreBundle\Search\Escargot\Factory;
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Terminal42\Escargot\BaseUriCollection;
use Terminal42\Escargot\Escargot;
use Terminal42\Escargot\Exception\InvalidJobIdException;
use Terminal42\Escargot\Queue\InMemoryQueue;

class CrawlCommandTest extends TestCase
{
    public function testAbortsWithInvalidJobId(): void
    {
        $escargotFactory = $this->createInvalidEscargotFactory(new InvalidJobIdException(), true);
        $command = new CrawlCommand($escargotFactory);

        $tester = new CommandTester($command);
        $code = $tester->execute(['job' => 'i-do-not-exist']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('[ERROR] Could not find the given job ID.', $tester->getDisplay());
    }

    public function testAbortsIfEscargotCouldNotBeInstantiated(): void
    {
        $escargotFactory = $this->createInvalidEscargotFactory(new \InvalidArgumentException('Something went wrong!'));
        $command = new CrawlCommand($escargotFactory);

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
        $escargot = Escargot::create($this->createBaseUriCollection(), new InMemoryQueue(), $client);
        $escargotFactory = $this->createValidEscargotFactory($escargot);
        $command = new CrawlCommand($escargotFactory);

        $tester = new CommandTester($command);
        $code = $tester->execute([]);

        $this->assertSame(0, $code);
        $this->assertSame(10, $command->getEscargot()->getConcurrency());
        $this->assertSame(0, $command->getEscargot()->getRequestDelay());
        $this->assertSame(0, $command->getEscargot()->getMaxRequests());
        $this->assertSame(0, $command->getEscargot()->getMaxDepth());

        // Test options
        $escargot = Escargot::create($this->createBaseUriCollection(), new InMemoryQueue(), $client);
        $escargotFactory = $this->createValidEscargotFactory($escargot);
        $command = new CrawlCommand($escargotFactory);

        $tester = new CommandTester($command);
        $code = $tester->execute(['-c' => 20, '--delay' => 20, '--max-requests' => 20, '--max-depth' => 20]);

        $this->assertSame(0, $code);
        $this->assertSame(20, $command->getEscargot()->getConcurrency());
        $this->assertSame(20, $command->getEscargot()->getRequestDelay());
        $this->assertSame(20, $command->getEscargot()->getMaxRequests());
        $this->assertSame(20, $command->getEscargot()->getMaxDepth());
    }

    private function createBaseUriCollection(): BaseUriCollection
    {
        return new BaseUriCollection([new Uri('https://contao.org')]);
    }

    private function createEscargotFactory(): MockObject
    {
        $escargotFactory = $this->createMock(Factory::class);
        $escargotFactory
            ->expects($this->once())
            ->method('getSearchUriCollection')
            ->willReturn($this->createBaseUriCollection())
        ;

        return $escargotFactory;
    }

    private function createValidEscargotFactory(Escargot $escargot, bool $withExistingJobId = false): MockObject
    {
        $escargotFactory = $this->createEscargotFactory();
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

    private function createInvalidEscargotFactory(\Exception $exception, bool $withExistingJobId = false): MockObject
    {
        $escargotFactory = $this->createEscargotFactory();

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
