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

use Contao\ManagerBundle\Command\MaintenanceModeCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

class MaintenanceModeCommandTest extends TestCase
{
    /**
     * @dataProvider enableProvider
     */
    public function testEnable(string $expectedTemplateName, array $expectedTemplateVars, string $customTemplateName = null, string $customTemplateVars = null): void
    {
        $twig = $this->getTwigMock();
        $twig
            ->expects($this->once())
            ->method('render')
            ->with($expectedTemplateName, $expectedTemplateVars)
            ->willReturn('parsed-template')
        ;

        $filesystem = $this->getFilesystemMock();
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

        $command = new MaintenanceModeCommand('/path/to/var/maintenance.html', $twig, $filesystem);

        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        $this->assertStringContainsString('[OK] Maintenance mode enabled', $commandTester->getDisplay(true));
    }

    public function testDisable(): void
    {
        $filesystem = $this->getFilesystemMock();
        $filesystem
            ->expects($this->once())
            ->method('remove')
            ->with('/path/to/var/maintenance.html')
        ;

        $command = new MaintenanceModeCommand('/path/to/var/maintenance.html', $this->getTwigMock(), $filesystem);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['state' => 'disable']);

        $this->assertStringContainsString('[OK] Maintenance mode disabled', $commandTester->getDisplay(true));
    }

    public function enableProvider(): \Generator
    {
        yield 'Test defaults' => [
            '@ContaoCore/Error/service_unavailable.html.twig',
            [
                'statusCode' => 503,
                'language' => 'en',
                'template' => '@ContaoCore/Error/service_unavailable.html.twig',
            ],
        ];

        yield 'Test custom template name' => [
            '@CustomBundle/maintenance.html.twig',
            [
                'statusCode' => 503,
                'language' => 'en',
                'template' => '@CustomBundle/maintenance.html.twig',
            ],
            '@CustomBundle/maintenance.html.twig',
        ];

        yield 'Test custom template name and template vars' => [
            '@CustomBundle/maintenance.html.twig',
            [
                'statusCode' => 503,
                'language' => 'de',
                'template' => '@CustomBundle/maintenance.html.twig',
                'foo' => 'bar',
            ],
            '@CustomBundle/maintenance.html.twig',
            '{"language":"de", "foo": "bar"}',
        ];
    }

    private function getFilesystemMock()
    {
        return $this->getMockBuilder(Filesystem::class)
            ->disableAutoReturnValueGeneration() // Ensure we don't call any other method than the ones we mock
            ->getMock()
        ;
    }

    private function getTwigMock()
    {
        return $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->disableAutoReturnValueGeneration() // Ensure we don't call any other method than the ones we mock
            ->getMock()
        ;
    }
}
