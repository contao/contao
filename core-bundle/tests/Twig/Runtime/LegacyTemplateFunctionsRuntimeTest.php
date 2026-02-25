<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Runtime;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Runtime\LegacyTemplateFunctionsRuntime;
use Contao\FrontendTemplate;
use PHPUnit\Framework\Attributes\DataProvider;
use Twig\Environment;

class LegacyTemplateFunctionsRuntimeTest extends TestCase
{
    #[DataProvider('provideLayoutSectionData')]
    public function testRenderLayoutSection(string|null $templateName, string $expectedRenderedTemplateName): void
    {
        $defaultData = [
            'positions' => [
                'after' => [
                    'prefooter' => [
                        'title' => 'prefooter',
                        'id' => 'prefooter',
                        'template' => 'block_foo',
                        'position' => 'after',
                    ],
                ],
            ],
            'sections' => [
                'prefooter' => '<content>',
            ],
        ];

        $frontendTemplate = (new \ReflectionClass(FrontendTemplate::class))->newInstanceWithoutConstructor();
        $frontendTemplate->setData($defaultData);

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with($expectedRenderedTemplateName, [...$defaultData, ...[
                'id' => 'prefooter',
                'content' => '<content>',
            ]])
            ->willReturn('<result>')
        ;

        $runtime = new LegacyTemplateFunctionsRuntime($twig);

        $this->assertSame(
            '<result>',
            $runtime->renderLayoutSection(['Template' => $frontendTemplate], 'prefooter', $templateName),
        );
    }

    public static function provideLayoutSectionData(): iterable
    {
        yield 'default template' => [
            null,
            '@Contao/block_foo.html.twig',
        ];

        yield 'custom template' => [
            'block_bar',
            '@Contao/block_bar.html.twig',
        ];
    }

    #[DataProvider('provideLayoutSectionsData')]
    public function testRenderLayoutSections(string|null $templateName, string $expectedRenderedTemplateName): void
    {
        $defaultData = [
            'positions' => [
                'after' => [
                    'prefooter' => [
                        'title' => 'prefooter',
                        'id' => 'prefooter',
                        'template' => 'block_foo',
                        'position' => 'after',
                    ],
                ],
            ],
            'sections' => [
                'prefooter' => '<content>',
            ],
        ];

        $frontendTemplate = (new \ReflectionClass(FrontendTemplate::class))->newInstanceWithoutConstructor();
        $frontendTemplate->setData($defaultData);

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with($expectedRenderedTemplateName, [...$defaultData, ...[
                'matches' => [
                    'prefooter' => [
                        'title' => 'prefooter',
                        'id' => 'prefooter',
                        'template' => 'block_foo',
                        'position' => 'after',
                        'content' => '<content>',
                    ],
                ],
            ]])
            ->willReturn('<result>')
        ;

        $runtime = new LegacyTemplateFunctionsRuntime($twig);

        $this->assertSame(
            '<result>',
            $runtime->renderLayoutSections(['Template' => $frontendTemplate], 'after', $templateName),
        );
    }

    public static function provideLayoutSectionsData(): iterable
    {
        yield 'default template' => [
            null,
            '@Contao/block_sections.html.twig',
        ];

        yield 'custom template' => [
            'block_sections_foo',
            '@Contao/block_sections_foo.html.twig',
        ];
    }
}
