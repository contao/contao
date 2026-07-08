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

        $this->assertSame(
            ['foobar' => 'foobar', 'foo' => 'f', 'bar' => 42],
            $template->getData(),
        );

        $this->assertSame('f', $template->get('foo'));

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

    private function getFragmentTemplate(\Closure|null $callback = null): FragmentTemplate
    {
        $callback ??= static fn () => new Response();

        return new FragmentTemplate('content_element/text', $callback);
    }
}
