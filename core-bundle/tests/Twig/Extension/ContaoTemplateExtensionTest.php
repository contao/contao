<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Twig;

use Contao\BackendCustom;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoTemplateExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the ContaoTemplateExtension class.
 *
 * @author Jim Schmid <https://github.com/sheeep>
 */
class ContaoTemplateExtensionTest extends TestCase
{
    /**
     * Tests the renderContaoBackendTemplate() method.
     */
    public function testRenderContaoBackendTemplate()
    {
        $backendRoute = $this
            ->getMockBuilder(BackendCustom::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTemplateObject', 'run'])
            ->getMock()
        ;

        $template = new \stdClass();

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

        $request = new Request();
        $request->attributes->set('_scope', 'backend');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $framework = $this->mockContaoFramework(null, null, [], [
            BackendCustom::class => $backendRoute,
        ]);

        $scopeMatcher = $this->mockScopeMatcher();
        $extension = new ContaoTemplateExtension($requestStack, $framework, $scopeMatcher);

        $extension->renderContaoBackendTemplate([
            'a' => 'a',
            'b' => 'b',
            'c' => 'c',
        ]);

        $this->assertSame('a', $template->a);
        $this->assertSame('b', $template->b);
        $this->assertSame('c', $template->c);
    }

    /**
     * Tests the getFunctions() method.
     */
    public function testGetFunctions()
    {
        $request = new Request();
        $request->attributes->set('_scope', 'backend');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $framework = $this->mockContaoFramework(null, null, [], []);
        $scopeMatcher = $this->mockScopeMatcher();

        $extension = new ContaoTemplateExtension($requestStack, $framework, $scopeMatcher);
        $functions = $extension->getFunctions();

        $renderBaseTemplateFunction = array_filter($functions, function (\Twig_SimpleFunction $function) {
            return $function->getName() === 'render_contao_backend_template';
        });

        $this->assertCount(1, $renderBaseTemplateFunction);
    }

    /**
     * Tests the scope restriction.
     */
    public function testScopeRestriction()
    {
        $request = new Request();
        $request->attributes->set('_scope', 'frontend');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $scopeMatcher = $this->mockScopeMatcher();

        $framework = $this->mockContaoFramework(null, null, [], []);
        $extension = new ContaoTemplateExtension($requestStack, $framework, $scopeMatcher);

        $this->assertEmpty($extension->renderContaoBackendTemplate());
    }
}
