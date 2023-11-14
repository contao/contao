<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\Command;

use Contao\CoreBundle\Intl\Locales;
use Contao\ManagerBundle\Command\MaintenanceModeCommand;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class MaintenanceModeCommandTest extends ContaoTestCase
{
    use ExpectDeprecationTrait;

    #[\Override]
    protected function tearDown(): void
    {
        $this->resetStaticProperties([Terminal::class]);

        parent::tearDown();
    }

    /**
     * @dataProvider enableProvider
     */
    public function testEnable(string $expectedTemplateName, array $expectedTemplateVars, string|null $customTemplateName = null, string|null $customTemplateVars = null): void
    {
        $twig = $this->mockEnvironment();
        $twig
            ->expects($this->once())
            ->method('render')
            ->with($expectedTemplateName, $expectedTemplateVars)
            ->willReturn('parsed-template')
        ;

        $filesystem = $this->mockFilesystem();
        $filesystem
            ->expects($this->once())
            ->method('dumpFile')
            ->with('/path/to/var/maintenance.html', 'parsed-template')
        ;

        $params = ['state' => 'enable'];

        if ($customTemplateName) {
            $params['--template'] = $customTemplateName;
        }

        if ($customTemplateVars) {
            $params['--templateVars'] = $customTemplateVars;
        }

        $command = new MaintenanceModeCommand(
            '/path/to/var/maintenance.html',
            $twig,
            $this->createMock(Locales::class),
            $this->createMock(TranslatorInterface::class),
            $filesystem,
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        $this->assertStringContainsString('[OK] Maintenance mode enabled', $commandTester->getDisplay(true));
    }

    public function testDisable(): void
    {
        $filesystem = $this->mockFilesystem();
        $filesystem
            ->expects($this->once())
            ->method('remove')
            ->with('/path/to/var/maintenance.html')
        ;

        $command = new MaintenanceModeCommand(
            '/path/to/var/maintenance.html',
            $this->mockEnvironment(),
            $this->createMock(Locales::class),
            $this->createMock(TranslatorInterface::class),
            $filesystem,
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute(['state' => 'disable']);

        $this->assertStringContainsString('[OK] Maintenance mode disabled', $commandTester->getDisplay(true));
    }

    public function testOutputIfEnabled(): void
    {
        $filesystem = $this->mockFilesystem();
        $filesystem
            ->expects($this->once())
            ->method('exists')
            ->with('/path/to/var/maintenance.html')
            ->willReturn(true)
        ;

        $command = new MaintenanceModeCommand(
            '/path/to/var/maintenance.html',
            $this->mockEnvironment(),
            $this->createMock(Locales::class),
            $this->createMock(TranslatorInterface::class), $filesystem);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertStringContainsString(' ! [NOTE] Maintenance mode is enabled', $commandTester->getDisplay(true));
    }

    public function testOutputIfDisabled(): void
    {
        $filesystem = $this->mockFilesystem();
        $filesystem
            ->expects($this->once())
            ->method('exists')
            ->with('/path/to/var/maintenance.html')
            ->willReturn(false)
        ;

        $command = new MaintenanceModeCommand(
            '/path/to/var/maintenance.html',
            $this->mockEnvironment(),
            $this->createMock(Locales::class),
            $this->createMock(TranslatorInterface::class),
            $filesystem,
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertStringContainsString(' [INFO] Maintenance mode is disabled', $commandTester->getDisplay(true));
    }

    public function testOutputWithJsonFormat(): void
    {
        $filesystem = $this->mockFilesystem();
        $filesystem
            ->expects($this->once())
            ->method('exists')
            ->with('/path/to/var/maintenance.html')
            ->willReturn(false)
        ;

        $command = new MaintenanceModeCommand(
            '/path/to/var/maintenance.html',
            $this->mockEnvironment(),
            $this->createMock(Locales::class),
            $this->createMock(TranslatorInterface::class),
            $filesystem,
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute(['--format' => 'json']);

        $json = json_decode($commandTester->getDisplay(true), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(['enabled' => false, 'maintenanceFilePath' => '/path/to/var/maintenance.html'], $json);
    }

    public function enableProvider(): \Generator
    {
        yield 'Test defaults' => [
            '@ContaoCore/Error/service_unavailable.html.twig',
            [
                'statusCode' => 503,
                'language' => 'en',
                'template' => '@ContaoCore/Error/service_unavailable.html.twig',
                'defaultLabels' => [],
            ],
        ];

        yield 'Test custom template name' => [
            '@CustomBundle/maintenance.html.twig',
            [
                'statusCode' => 503,
                'language' => 'en',
                'template' => '@CustomBundle/maintenance.html.twig',
                'defaultLabels' => [],
            ],
            '@CustomBundle/maintenance.html.twig',
        ];

        yield 'Test custom template name and template vars' => [
            '@CustomBundle/maintenance.html.twig',
            [
                'statusCode' => 503,
                'language' => 'de',
                'template' => '@CustomBundle/maintenance.html.twig',
                'defaultLabels' => [],
                'foo' => 'bar',
            ],
            '@CustomBundle/maintenance.html.twig',
            '{"language":"de", "foo": "bar"}',
        ];
    }

    private function mockFilesystem(): Filesystem&MockObject
    {
        return $this
            ->getMockBuilder(Filesystem::class)
            ->disableAutoReturnValueGeneration() // Ensure we don't call any other method than the ones we mock
            ->getMock()
        ;
    }

    private function mockEnvironment(): Environment&MockObject
    {
        return $this
            ->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->disableAutoReturnValueGeneration() // Ensure we don't call any other method than the ones we mock
            ->getMock()
        ;
    }
}
