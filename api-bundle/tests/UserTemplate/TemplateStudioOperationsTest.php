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

use ApiPlatform\Metadata\Post;
use Contao\ApiBundle\ApiPlatform\State\UserTemplateStateProcessor;
use Contao\ApiBundle\Dto\UserTemplateOperation;
use Contao\ApiBundle\UserTemplate\TemplateStudioClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TemplateStudioOperationsTest extends TestCase
{
    public function testProcessorDispatchesToTheStudioRoute(): void
    {
        $parent = Request::create('/contao/_api/user_template/content_element%2Ftest/save');
        $kernel = $this->createMock(HttpKernelInterface::class);
        $kernel
            ->expects($this->once())
            ->method('handle')
            ->willReturnCallback(
                function (Request $request, int $type): JsonResponse {
                    $this->assertSame(HttpKernelInterface::SUB_REQUEST, $type);
                    $this->assertSame('POST', $request->getMethod());
                    $this->assertSame('save', $request->query->get('operation'));
                    $this->assertSame(['code' => ''], $request->request->all());
                    $this->assertSame('text/vnd.turbo-stream.html', $request->headers->get('Accept'));
                    $bag = $request->getSession()->getBag('contao_backend');
                    $this->assertInstanceOf(AttributeBagInterface::class, $bag);
                    $this->assertSame('demo', $bag->get('template_studio_theme_slug'));
                    $this->assertTrue($request->attributes->getBoolean('_contao_api'));

                    return new JsonResponse(['identifier' => 'content_element/test']);
                },
            )
        ;
        $router = $this->createMock(UrlGeneratorInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('_contao_template_studio_operation.stream', ['operation' => 'save', 'identifier' => 'content_element/test'])
            ->willReturn('/contao/template-studio/resource/content_element/test?operation=save')
        ;
        $processor = new UserTemplateStateProcessor(new TemplateStudioClient($kernel, $router, new RequestStack([$parent])));
        $operation = new Post(extraProperties: ['template_studio_operation' => 'save']);
        $response = $processor->process(
            new UserTemplateOperation(['code' => ''], 'demo'),
            $operation,
            ['identifier' => 'content_element/test'],
        );

        $this->assertSame(['identifier' => 'content_element/test'], json_decode($response->getContent(), true));
    }

    public function testRejectsNonJsonStudioResponses(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $kernel
            ->expects($this->once())
            ->method('handle')
            ->willReturn(new Response('failure'))
        ;
        $router = $this->createStub(UrlGeneratorInterface::class);
        $router
            ->method('generate')
            ->willReturn('/studio')
        ;
        $client = new TemplateStudioClient($kernel, $router, new RequestStack([Request::create('/')]));

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('failure');
        $client->call('save', 'content_element/test', null, []);
    }
}
