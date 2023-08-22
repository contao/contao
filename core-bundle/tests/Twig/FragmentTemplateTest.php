<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Response;

class FragmentTemplateTest extends TestCase
{
    public function testSetAndGetValues(): void
    {
        $template = $this->getFragmentTemplate();
        $this->assertSame('content_element/text', $template->getName());

        $template->setName('content_element/foobar');
        $this->assertSame('content_element/foobar', $template->getName());

        $template->setData(['foobar' => 'foobar']);
        $template->set('foo', 'f');
        $template->set('bar', 42);

        $template->baz = true;

        $this->assertSame(
            ['foobar' => 'foobar', 'foo' => 'f', 'bar' => 42, 'baz' => true],
            $template->getData()
        );

        $this->assertSame('f', $template->get('foo'));
        $this->assertSame('f', $template->foo);

        $this->assertTrue($template->has('bar'));
        $this->assertTrue(isset($template->bar));

        $this->assertFalse($template->has('x'));
        $this->assertFalse(isset($template->x));
    }

    public function testDelegatesGetResponseCall(): void
    {
        $returnedResponse = new Response();
        $preBuiltResponse = new Response();

        $callback = function (FragmentTemplate $reference, Response|null $response) use ($preBuiltResponse, $returnedResponse): Response {
            $this->assertSame('content_element/text', $reference->getName());
            $this->assertSame($preBuiltResponse, $response);

            return $returnedResponse;
        };

        $template = $this->getFragmentTemplate($callback);
        $this->assertSame($returnedResponse, $template->getResponse($preBuiltResponse));
    }

    /**
     * @dataProvider provideIllegalParentMethods
     */
    public function testDisallowsAccessOfParentMethods(string $method, array $args): void
    {
        $template = $this->getFragmentTemplate();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('Calling the "%s()" function on a FragmentTemplate is not allowed. Set template data instead and optionally output it with getResponse().', $method));

        $template->$method(...$args);
    }

    public function provideIllegalParentMethods(): \Generator
    {
        $excluded = ['__construct', '__set', '__get', '__isset', 'setData', 'getData', 'setName', 'getName', 'getResponse'];

        if (!$parent = (new \ReflectionClass(FragmentTemplate::class))->getParentClass()) {
            return;
        }

        foreach ($parent->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (\in_array($name = $method->getName(), $excluded, true)) {
                continue;
            }

            $args = array_map(
                function (\ReflectionParameter $parameter) {
                    $type = $parameter->getType();

                    if (!$type instanceof \ReflectionNamedType) {
                        return null;
                    }

                    return match ($name = $type->getName()) {
                        'bool' => false,
                        'string' => '',
                        'array' => [],
                        /** @phpstan-ignore-next-line because mocked type cannot be inferred */
                        default => $this->createMock($name)
                    };
                },
                $method->getParameters()
            );

            yield "accessing $name()" => [$name, $args];
        }
    }

    private function getFragmentTemplate(\Closure|null $callback = null): FragmentTemplate
    {
        $callback ??= static fn () => new Response();

        return new FragmentTemplate('content_element/text', $callback);
    }
}
