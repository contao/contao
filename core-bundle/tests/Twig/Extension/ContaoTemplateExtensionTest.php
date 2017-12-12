<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Twig;

use Contao\BackendCustom;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoTemplateExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class ContaoTemplateExtensionTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $extension = $this->mockExtension();

        $this->assertInstanceOf('Contao\CoreBundle\Twig\Extension\ContaoTemplateExtension', $extension);
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

        $extension = $this->mockExtension($framework);
        $extension->renderContaoBackendTemplate(['a' => 'a', 'b' => 'b', 'c' => 'c']);

        $this->assertSame('a', $template->a);
        $this->assertSame('b', $template->b);
        $this->assertSame('c', $template->c);
    }

    public function testAddsTheRenderContaoBackEndTemplateFunction(): void
    {
        $functions = $this->mockExtension()->getFunctions();

        $renderBaseTemplateFunction = array_filter(
            $functions,
            function (\Twig_SimpleFunction $function): bool {
                return 'render_contao_backend_template' === $function->getName();
            }
        );

        $this->assertCount(1, $renderBaseTemplateFunction);
    }

    public function testDoesNotRenderTheBackEndTemplateIfNotInBackEndScope(): void
    {
        $this->assertEmpty($this->mockExtension(null, 'frontend')->renderContaoBackendTemplate());
    }

    /**
     * Mocks a Contao template extension with an optional scope.
     *
     * @param ContaoFrameworkInterface|null $framework
     * @param string                        $scope
     *
     * @return ContaoTemplateExtension
     */
    private function mockExtension(ContaoFrameworkInterface $framework = null, string $scope = 'backend'): ContaoTemplateExtension
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
