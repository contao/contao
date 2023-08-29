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
use Contao\ManagerBundle\ContaoManager\ApiCommand\GenerateJwtCookieCommand;
use Contao\ManagerBundle\HttpKernel\JwtManager;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\Cookie;

class GenerateJwtCookieCommandTest extends ContaoTestCase
{
    private JwtManager&MockObject $jwtManager;

    private GenerateJwtCookieCommand $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtManager = $this->createMock(JwtManager::class);

        $application = $this->createMock(Application::class);
        $application
            ->method('getProjectDir')
            ->willReturn($this->getTempDir())
        ;

        $this->command = new GenerateJwtCookieCommand($application, $this->jwtManager);
    }

    public function testHasCorrectName(): void
    {
        $this->assertSame('jwt-cookie:generate', $this->command->getName());
    }

    public function testGeneratesCookieWithDebugEnabled(): void
    {
        $cookie = Cookie::create('contao_settings', 'foobar');

        $this->jwtManager
            ->expects($this->once())
            ->method('createCookie')
            ->with(['debug' => true])
            ->willReturn($cookie)
        ;

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['--debug' => true]);

        $this->assertSame((string) $cookie, $commandTester->getDisplay());
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testGeneratesCookieWithDebugDisabled(): void
    {
        $cookie = Cookie::create('contao_settings', 'foobar');

        $this->jwtManager
            ->expects($this->once())
            ->method('createCookie')
            ->with(['debug' => false])
            ->willReturn($cookie)
        ;

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $this->assertSame((string) $cookie, $commandTester->getDisplay());
        $this->assertSame(0, $commandTester->getStatusCode());
    }
}
