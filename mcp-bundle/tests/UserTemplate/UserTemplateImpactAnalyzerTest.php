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

use Contao\CoreBundle\Twig\Inspector\BlockInformation;
use Contao\CoreBundle\Twig\Inspector\BlockType;
use Contao\CoreBundle\Twig\Inspector\Inspector;
use Contao\CoreBundle\Twig\Inspector\TemplateInformation;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\McpBundle\UserTemplate\UserTemplateImpactAnalyzer;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\TestCase;
use Twig\Source;

final class UserTemplateImpactAnalyzerTest extends TestCase
{
    public function testFindsDirectAndTransitiveConsumers(): void
    {
        $loader = $this->createMock(ContaoFilesystemLoader::class);
        $loader
            ->expects($this->once())
            ->method('getInheritanceChains')
            ->with('demo')
            ->willReturn([
                'component/_figure' => [
                    '/templates/component/_figure.html.twig' => '@Contao_User/component/_figure.html.twig',
                    '/vendor/component/_figure.html.twig' => '@Contao_ContaoCoreBundle/component/_figure.html.twig',
                ],
                'content_element/image' => [
                    '/templates/content_element/image.html.twig' => '@Contao_User/content_element/image.html.twig',
                    '/vendor/content_element/image.html.twig' => '@Contao_ContaoCoreBundle/content_element/image.html.twig',
                ],
                'content_element/gallery' => [
                    '/vendor/content_element/gallery.html.twig' => '@Contao_ContaoCoreBundle/content_element/gallery.html.twig',
                ],
            ])
        ;

        $inspector = $this->createMock(Inspector::class);
        $inspector
            ->method('inspectTemplate')
            ->willReturnCallback(
                static function (string $name): TemplateInformation {
                    $references = match ($name) {
                        '@Contao_User/component/_figure.html.twig' => [['type' => 'extends', 'name' => '@Contao/component/_figure.html.twig', 'line' => 1, 'dynamic' => false]],
                        '@Contao_User/content_element/image.html.twig' => [['type' => 'extends', 'name' => '@Contao/content_element/image.html.twig', 'line' => 1, 'dynamic' => false]],
                        '@Contao_ContaoCoreBundle/content_element/image.html.twig',
                        '@Contao_ContaoCoreBundle/content_element/gallery.html.twig' => [['type' => 'use', 'name' => '@Contao/component/_figure.html.twig', 'line' => 2, 'dynamic' => false]],
                        default => [],
                    };

                    return new TemplateInformation(new Source('', $name), references: $references);
                },
            )
        ;

        $inspector
            ->expects($this->once())
            ->method('getBlockHierarchy')
            ->with('@Contao_User/component/_figure.html.twig', 'figure_component')
            ->willReturn([
                new BlockInformation('@Contao_User/component/_figure.html.twig', 'figure_component', BlockType::enhance),
                new BlockInformation('@Contao_ContaoCoreBundle/component/_figure.html.twig', 'figure_component', BlockType::origin, true),
            ])
        ;

        $result = new UserTemplateImpactAnalyzer($loader, $inspector)->analyze('component/_figure', 'demo', 'figure_component');

        $this->assertSame(['@Contao_User/component/_figure.html.twig', '@Contao_ContaoCoreBundle/component/_figure.html.twig'], $result['inheritanceChain']);
        $this->assertContains('@Contao/component/_figure.html.twig', $result['referenceNames']);
        $this->assertSame(1, $result['summary']['directConsumerCount']);
        $this->assertSame(1, $result['summary']['transitiveConsumerCount']);
        $this->assertSame('content_element/gallery', $result['directConsumers'][0]['identifier']);
        $this->assertSame(1, $result['directConsumers'][0]['depth']);
        $this->assertSame('content_element/image', $result['transitiveConsumers'][0]['identifier']);
        $this->assertSame(2, $result['transitiveConsumers'][0]['depth']);
        $this->assertSame('extends', $result['transitiveConsumers'][0]['path'][0]['reference']['type']);
        $this->assertSame('use', $result['transitiveConsumers'][0]['path'][1]['reference']['type']);
        $this->assertSame('enhance', $result['blockImpact']['hierarchy'][0]['type']);
        $this->assertTrue($result['blockImpact']['hierarchy'][1]['prototype']);
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
