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

use Contao\BackendCustom;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Runtime\LegacyTemplateFunctionsRuntime;
use Contao\FrontendTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
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

        $runtime = $this->getRuntime();

        $this->assertSame('output', $runtime->$methodName($context, 'key', 'template'));
    }

    /**
     * @dataProvider provideMethodCalls
     */
    public function testThrowsIfTemplateNotInContext(string $methodName, string $delegatedMethodName): void
    {
        $runtime = $this->getRuntime();

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage("The \"contao_$delegatedMethodName\" function cannot be used in this template.");

        $runtime->$methodName([], 'foo');
    }

    public function provideMethodCalls(): \Generator
    {
        yield 'sections' => ['renderLayoutSections', 'sections'];

        yield 'section' => ['renderLayoutSection', 'section'];
    }

    public function testRendersTheContaoBackendTemplate(): void
    {
        $template = new \stdClass();

        $backendRoute = $this->createMock(BackendCustom::class);
        $backendRoute
            ->expects($this->once())
            ->method('getTemplateObject')
            ->willReturn($template)
        ;

        $backendRoute
            ->expects($this->once())
            ->method('run')
            ->willReturn(new Response())
        ;

        $framework = $this->mockContaoFramework();
        $framework
            ->method('createInstance')
            ->with(BackendCustom::class)
            ->willReturn($backendRoute)
        ;

        $runtime = $this->getRuntime($framework);
        $runtime->renderContaoBackendTemplate(['a' => 'a', 'b' => 'b', 'c' => 'c']);

        $this->assertSame('a', $template->a);
        $this->assertSame('b', $template->b);
        $this->assertSame('c', $template->c);
    }

    public function testDoesNotRenderTheBackEndTemplateIfNotInBackEndScope(): void
    {
        $this->assertEmpty($this->getRuntime(null, 'frontend')->renderContaoBackendTemplate());
    }

    private function getRuntime(?ContaoFramework $framework = null, string $scope = 'backend'): LegacyTemplateFunctionsRuntime
    {
        $request = new Request();
        $request->attributes->set('_scope', $scope);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $framework ??= $this->mockContaoFramework();

        return new LegacyTemplateFunctionsRuntime($requestStack, $framework, $this->mockScopeMatcher());
    }
}
