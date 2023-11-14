<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\ContaoManager\ApiCommand;

use Contao\ManagerBundle\Api\Application;
use Contao\ManagerBundle\ContaoManager\ApiCommand\ParseJwtCookieCommand;
use Contao\ManagerBundle\HttpKernel\JwtManager;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;

class ParseJwtCookieCommandTest extends ContaoTestCase
{
    private JwtManager&MockObject $jwtManager;

    private ParseJwtCookieCommand $command;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtManager = $this->createMock(JwtManager::class);

        $application = $this->createMock(Application::class);
        $application
            ->method('getProjectDir')
            ->willReturn($this->getTempDir())
        ;

        $this->command = new ParseJwtCookieCommand($application, $this->jwtManager);
    }

    public function testHasCorrectName(): void
    {
        $this->assertSame('jwt-cookie:parse', $this->command->getName());
    }

    public function testParsesJwtCookie(): void
    {
        $this->jwtManager
            ->expects($this->once())
            ->method('parseCookie')
            ->with('foobar')
            ->willReturn(['foo' => 'bar'])
        ;

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['content' => 'foobar']);

        $this->assertSame('{"foo":"bar"}', $commandTester->getDisplay());
        $this->assertSame(0, $commandTester->getStatusCode());
    }
}
