<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Extension;

use Contao\BackendCustom;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoTemplateExtension;
use Contao\CoreBundle\Twig\Runtime\LegacyTemplateFunctionsRuntime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Twig\Node\Node;

class ContaoTemplateExtensionTest extends TestCase
{
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

        $extension = $this->getExtension($framework);
        $extension->renderContaoBackendTemplate(['a' => 'a', 'b' => 'b', 'c' => 'c']);

        $this->assertSame('a', $template->a);
        $this->assertSame('b', $template->b);
        $this->assertSame('c', $template->c);
    }

    public function testAddsTheContaoTemplateFunctions(): void
    {
        $functions = $this->getExtension()->getFunctions();

        $this->assertCount(3, $functions);

        [$renderBaseTemplateFn, $layoutSectionsFn, $layoutSectionFn] = $functions;

        $node = $this->createMock(Node::class);

        $this->assertSame('render_contao_backend_template', $renderBaseTemplateFn->getName());
        $this->assertSame([], $renderBaseTemplateFn->getSafe($node));

        $this->assertSame('contao_sections', $layoutSectionsFn->getName());
        $this->assertSame([LegacyTemplateFunctionsRuntime::class, 'renderLayoutSections'], $layoutSectionsFn->getCallable());
        $this->assertSame(['html'], $layoutSectionsFn->getSafe($node));

        $this->assertSame('contao_section', $layoutSectionFn->getName());
        $this->assertSame([LegacyTemplateFunctionsRuntime::class, 'renderLayoutSection'], $layoutSectionFn->getCallable());
        $this->assertSame(['html'], $layoutSectionFn->getSafe($node));
    }

    public function testDoesNotRenderTheBackEndTemplateIfNotInBackEndScope(): void
    {
        $this->assertEmpty($this->getExtension(null, 'frontend')->renderContaoBackendTemplate());
    }

    private function getExtension(ContaoFramework $framework = null, string $scope = 'backend'): ContaoTemplateExtension
    {
        $request = new Request();
        $request->attributes->set('_scope', $scope);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        if (null === $framework) {
            $framework = $this->mockContaoFramework();
        }

        return new ContaoTemplateExtension($requestStack, $framework, $this->mockScopeMatcher());
    }
}
