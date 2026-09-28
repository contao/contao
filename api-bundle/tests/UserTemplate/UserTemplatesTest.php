<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\UserTemplate;

use ApiPlatform\Metadata\Get;
use Contao\ApiBundle\ApiPlatform\State\UserTemplateStateProvider;
use Contao\ApiBundle\UserTemplate\TemplateStudioClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserTemplatesTest extends TestCase
{
    #[DataProvider('studioEndpoints')]
    public function testReadsThroughTheStudioEndpoints(string $route, array $variables, string|null $theme): void
    {
        $parent = Request::create('/contao/_api/user_template', parameters: null === $theme ? [] : ['theme' => $theme]);
        $parent->setLocale('de');

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with($route, $variables)
            ->willReturn('/studio')
        ;
        $kernel = $this->createMock(HttpKernelInterface::class);
        $kernel
            ->expects($this->once())
            ->method('handle')
            ->willReturnCallback(
                function (Request $request, int $type) use ($parent, $theme): JsonResponse {
                    $this->assertSame('GET', $request->getMethod());
                    $this->assertSame(HttpKernelInterface::SUB_REQUEST, $type);
                    $this->assertSame('text/vnd.turbo-stream.html', $request->headers->get('Accept'));
                    $this->assertSame('de', $request->attributes->get('_locale'));
                    $this->assertNotSame($parent, $request);
                    $this->assertSame($theme, $request->getSession()->getBag('contao_backend')->get('template_studio_theme_slug'));
                    $this->assertSame([], $request->request->all());
                    $this->assertTrue($request->attributes->getBoolean('_contao_api'));

                    return new JsonResponse(['tree' => []]);
                },
            )
        ;
        $provider = new UserTemplateStateProvider(new TemplateStudioClient($kernel, $router, new RequestStack([$parent])));
        $response = $provider->provide(new Get(), $variables, ['request' => $parent]);

        $this->assertSame(['tree' => []], json_decode($response->getContent(), true));
    }

    public static function studioEndpoints(): iterable
    {
        yield ['_contao_template_studio_tree.stream', [], null];
        yield ['_contao_template_studio_tree.stream', [], 'demo'];
        yield ['_contao_template_studio_editor_tab.stream', ['identifier' => 'content_element/text'], null];
        yield ['_contao_template_studio_editor_tab.stream', ['identifier' => 'content_element/text'], 'demo'];
    }

    public function testRequiresARequestContext(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $kernel
            ->expects($this->never())
            ->method('handle')
        ;
        $client = new TemplateStudioClient($kernel, $this->createStub(UrlGeneratorInterface::class), new RequestStack());

        $this->expectException(\LogicException::class);
        $client->discover(null);
    }
}
