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
use Contao\CoreBundle\Twig\LayoutTemplate;
use Symfony\Component\HttpFoundation\Response;

class LayoutTemplateTest extends TestCase
{
    public function testSetAndGetValues(): void
    {
        $template = $this->getLayoutTemplate();
        $this->assertSame('layout/default', $template->getName());

        $template->setName('layout/foobar');
        $this->assertSame('layout/foobar', $template->getName());

        $template->setData(['foobar' => 'foobar']);
        $template->set('foo', 'f');
        $template->set('bar', 42);
        $template->setSlot('baz', 'slot');

        $this->assertSame(
            [
                'foobar' => 'foobar',
                'foo' => 'f',
                'bar' => 42,
                '_slots' => [
                    'baz' => 'slot',
                ],
            ],
            $template->getData(),
        );

        $this->assertSame('f', $template->get('foo'));
        $this->assertTrue($template->has('bar'));
        $this->assertFalse($template->has('x'));

        $this->assertSame('slot', $template->getSlot('baz'));
        $this->assertTrue($template->hasSlot('baz'));
        $this->assertFalse($template->hasSlot('foo'));
    }

    public function testDelegatesGetResponseCall(): void
    {
        $returnedResponse = new Response();
        $preBuiltResponse = new Response();

        $callback = function (LayoutTemplate $reference, Response|null $response) use ($preBuiltResponse, $returnedResponse): Response {
            $this->assertSame('layout/default', $reference->getName());
            $this->assertSame($preBuiltResponse, $response);

            return $returnedResponse;
        };

        $template = $this->getLayoutTemplate($callback);
        $this->assertSame($returnedResponse, $template->getResponse($preBuiltResponse));
    }

    private function getLayoutTemplate(\Closure|null $callback = null): LayoutTemplate
    {
        $callback ??= static fn () => new Response();

        return new LayoutTemplate('layout/default', $callback);
    }
}
