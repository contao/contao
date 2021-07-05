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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Runtime\LegacyTemplateFunctionsRuntime;
use Contao\FrontendTemplate;
use Twig\Error\RuntimeError;

class LegacyTemplateFunctionsRuntimeTest extends TestCase
{
    /**
     * @dataProvider provideMethodCalls
     */
    public function testDelegatesCalls(string $methodName, string $delegatedMethodName): void
    {
        $frontendTemplate = $this->createMock(FrontendTemplate::class);
        $frontendTemplate
            ->expects($this->once())
            ->method($delegatedMethodName)
            ->with('key', 'template')
            ->willReturnCallback(
                static function (): void {
                    echo 'output';
                }
            )
        ;

        $context = [
            'Template' => $frontendTemplate,
        ];

        $runtime = new LegacyTemplateFunctionsRuntime(
            $this->createMock(ContaoFramework::class)
        );

        $this->assertSame(
            'output',
            $runtime->$methodName(
                $context,
                'key',
                'template'
            )
        );
    }

    /**
     * @dataProvider provideMethodCalls
     */
    public function testThrowsIfTemplateNotInContext(string $methodName, string $delegatedMethodName): void
    {
        $runtime = new LegacyTemplateFunctionsRuntime(
            $this->createMock(ContaoFramework::class)
        );

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage("The \"contao_$delegatedMethodName\" function cannot be used in this template.");

        $runtime->$methodName([], 'foo');
    }

    public function provideMethodCalls(): \Generator
    {
        yield 'sections' => ['renderLayoutSections', 'sections'];

        yield 'section' => ['renderLayoutSection', 'section'];
    }
}
