<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Interop;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Interop\ContextHelper;
use Contao\Template;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Loader\ArrayLoader;

class ContextHelperTest extends TestCase
{
    public function testAdjustsContaoTemplateContext(): void
    {
        $object = new \stdClass();
        $object->x = 'y';

        $data = [
            'foo' => 'bar',
            'a' => [1, 2],
            'o' => $object,
            'lazy1' => static fn (): string => 'evaluated',
            'lazy2' => static fn (int $n = 0): string => "evaluated: $n",
            'lazy3' => static fn (): array => [1, 2],
            'lazy4' => \Closure::fromCallable(
                static fn (): string => 'evaluated Closure'
            ),
            'value' => 'strtolower', // do not confuse with callable
        ];

        $template = $this->createMock(Template::class);
        $template
            ->method('getData')
            ->willReturn($data)
        ;

        $content =
            <<<'TEMPLATE'

                foo:   {{ foo }}
                a:     {{ a|join('|') }}
                o:     {{ o.x }}
                lazy1: {{ lazy1 }}
                lazy2: {{ lazy2 }}, {{ lazy2.invoke(5) }}
                lazy3: {{ lazy3.invoke()|join('|') }}
                lazy4: {{ lazy4 }}
                value: {{ value }}

                TEMPLATE;

        $expectedOutput =
            <<<'OUTPUT'

                foo:   bar
                a:     1|2
                o:     y
                lazy1: evaluated
                lazy2: evaluated: 0, evaluated: 5
                lazy3: 1|2
                lazy4: evaluated Closure
                value: strtolower

                OUTPUT;

        $output = (new Environment(new ArrayLoader(['test.html.twig' => $content])))->render(
            'test.html.twig',
            ContextHelper::fromContaoTemplate($template)
        );

        $this->assertSame($expectedOutput, $output);
    }

    public function testEnhancesErrorMessageIfStringAccessFails(): void
    {
        if (\PHP_VERSION_ID < 70400) {
            $this->markTestSkipped('This test requires at least PHP 7.4.');
        }

        $data = [
            'lazy' => static fn (): object => new \stdClass(),
        ];

        $template = $this->createMock(Template::class);
        $template
            ->method('getData')
            ->willReturn($data)
        ;

        $content = '{{ lazy }}';
        $environment = (new Environment(new ArrayLoader(['test.html.twig' => $content])));
        $context = ContextHelper::fromContaoTemplate($template);

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage(
            'An exception has been thrown during the rendering of a template ("'.
            'Error evaluating \'lazy\': Object of class stdClass could not be converted to string'.
            '") in "test.html.twig" at line 1.'
        );

        $environment->render('test.html.twig', $context);
    }
}
