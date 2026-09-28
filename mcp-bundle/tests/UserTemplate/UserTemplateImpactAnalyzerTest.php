<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\Tests\UserTemplate;

use Contao\CoreBundle\Twig\Inspector\Inspector;
use Contao\CoreBundle\Twig\Inspector\TemplateInformation;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\McpBundle\UserTemplate\UserTemplateImpactAnalyzer;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\TestCase;
use Twig\Source;

final class UserTemplateImpactAnalyzerTest extends TestCase
{
    public function testFindsDirectConsumersAndTheirRelationship(): void
    {
        $loader = $this->createMock(ContaoFilesystemLoader::class);
        $loader
            ->expects($this->once())
            ->method('getInheritanceChains')
            ->with('demo')
            ->willReturn([
                'component/_figure' => [
                    '/vendor/component/_figure.html.twig' => '@Contao_ContaoCoreBundle/component/_figure.html.twig',
                ],
                'content_element/image' => [
                    '/vendor/content_element/image.html.twig' => '@Contao/content_element/image.html.twig',
                ],
                'content_element/text' => [
                    '/vendor/content_element/text.html.twig' => '@Contao/content_element/text.html.twig',
                ],
            ])
        ;

        $inspector = $this->createMock(Inspector::class);
        $inspector
            ->method('inspectTemplate')
            ->willReturnMap([
                [
                    '@Contao/content_element/image.html.twig',
                    new TemplateInformation(
                        new Source('', '@Contao/content_element/image.html.twig'),
                        references: [[
                            'type' => 'use',
                            'name' => '@Contao/component/_figure.html.twig',
                            'line' => 2,
                            'dynamic' => false,
                        ]],
                    ),
                ],
                [
                    '@Contao/content_element/text.html.twig',
                    new TemplateInformation(
                        new Source('', '@Contao/content_element/text.html.twig'),
                        references: [[
                            'type' => 'extends',
                            'name' => '@Contao/content_element/_base.html.twig',
                            'line' => 1,
                            'dynamic' => false,
                        ]],
                    ),
                ],
            ])
        ;

        $result = new UserTemplateImpactAnalyzer($loader, $inspector)->analyze('component/_figure', 'demo');

        $this->assertSame(['@Contao_ContaoCoreBundle/component/_figure.html.twig'], $result['inheritanceChain']);
        $this->assertContains('@Contao/component/_figure.html.twig', $result['referenceNames']);
        $this->assertSame(1, $result['summary']['directConsumerCount']);
        $this->assertSame('content_element/image', $result['directConsumers'][0]['identifier']);
        $this->assertSame('@Contao/content_element/image.html.twig', $result['directConsumers'][0]['template']);
        $this->assertSame([['type' => 'use', 'line' => 2]], $result['directConsumers'][0]['references']);
        $this->assertNotEmpty($result['limitations']);
    }

    public function testRejectsUnknownTemplates(): void
    {
        $loader = $this->createStub(ContaoFilesystemLoader::class);
        $loader
            ->method('getInheritanceChains')
            ->willReturn([])
        ;

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('does not exist');

        new UserTemplateImpactAnalyzer($loader, $this->createStub(Inspector::class))->analyze('unknown', null);
    }
}
