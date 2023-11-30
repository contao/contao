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

use Contao\CoreBundle\Tests\Fixtures\Twig\ChildClassWithMembersStub;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Interop\ContextFactory;
use Contao\Template;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Loader\ArrayLoader;

class ContextFactoryTest extends TestCase
{
    use ExpectDeprecationTrait;

    public function testCreateContextFromTemplate(): void
    {
        $object = new \stdClass();
        $object->x = 'y';

        // Work around https://github.com/phpstan/phpstan/issues/8078
        $closure = static fn (): string => 'evaluated Closure';

        $data = [
            'foo' => 'bar',
            'a' => [1, 2],
            'o' => $object,
            'lazy1' => static fn (): string => 'evaluated',
            'lazy2' => static fn (int $n = 0): string => "evaluated: $n",
            'lazy3' => static fn (): array => [1, 2],
            'lazy4' => $closure(...),
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

        $context = (new ContextFactory())->fromContaoTemplate($template);

        $this->assertSame($template, $context['Template']);

        $output = (new Environment(new ArrayLoader(['test.html.twig' => $content])))->render('test.html.twig', $context);

        $this->assertSame($expectedOutput, $output);
    }

    public function testCreatesContextFromData(): void
    {
        $data = [
            'foo' => 'a',
            'bar' => static fn () => 'b',
            'baz' => [
                'foobar' => static fn () => 'c',
            ],
        ];

        $context = (new ContextFactory())->fromData($data);

        $this->assertSame('a', $context['foo']);
        $this->assertSame('b', $context['bar']());
        $this->assertSame('c', (string) $context['baz']['foobar']);
    }

    /**
     * @group legacy
     */
    public function testCreateContextFromClass(): void
    {
        if (\PHP_VERSION_ID >= 80200) {
            $this->expectDeprecation('%sCreation of dynamic property %s is deprecated');
        }

        $object = new ChildClassWithMembersStub();
        $context = (new ContextFactory())->fromClass($object);

        $expectedFields = [
            'PROTECTED_CONSTANT' => 2,
            'PUBLIC_CONSTANT' => 3,
            'protectedField' => 'b',
            'publicField' => 'c',
            'protectedStaticField' => 'B',
            'publicStaticField' => 'C',
            'dynamic' => 'd',
            // Members from ParentClassWithMembersStub
            'PARENT_PROTECTED_CONSTANT' => 2,
            'PARENT_PUBLIC_CONSTANT' => 3,
            'parentProtectedField' => 'b',
            'parentPublicField' => 'c',
            'parentProtectedStaticField' => 'B',
            'parentPublicStaticField' => 'C',
            'parentDynamic' => 'd',
        ];

        $expectedFunctions = [
            'protectedDo',
            'publicDo',
            'protectedStaticDo',
            'publicStaticDo',
            // Functions from ParentClassWithMembersStub
            'parentProtectedDo',
            'parentPublicDo',
            'parentProtectedStaticDo',
            'parentPublicStaticDo',
        ];

        foreach ($expectedFields as $field => $value) {
            $this->assertArrayHasKey($field, $context, "field $field exists");
            $this->assertSame($context[$field], $value, "field $field holds correct value");
        }

        foreach ($expectedFunctions as $function) {
            $this->assertArrayHasKey($function, $context, "function $function exists");
            $this->assertSame($function, $context[$function](), "function call to $function without parameters succeeds");
            $this->assertSame("{$function}foo", $context[$function]('foo'), "function call to $function with parameters succeeds");
        }

        $this->assertArrayHasKey('this', $context);
        $this->assertSame($object, $context['this']);

        $this->assertCount(\count($expectedFields) + \count($expectedFunctions) + 1, $context);
    }

    public function testEnhancesErrorMessageInCallableWrappersIfStringAccessFails(): void
    {
        $template = $this->createMock(Template::class);
        $template
            ->method('getData')
            ->willReturn(['lazy' => static fn (): object => new \stdClass()])
        ;

        $content = '{{ lazy }}';
        $environment = new Environment(new ArrayLoader(['test.html.twig' => $content]));
        $context = (new ContextFactory())->fromContaoTemplate($template);

        $this->expectException(RuntimeError::class);

        $this->expectExceptionMessage(
            'An exception has been thrown during the rendering of a template ("'.
            'Object of class stdClass could not be converted to string'.
            '") in "test.html.twig" at line 1.'
        );

        $environment->render('test.html.twig', $context);
    }
}
