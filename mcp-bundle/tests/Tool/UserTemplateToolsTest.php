<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\Tests\Tool;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Contao\ApiBundle\Http\ApiRequestFactory;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\McpBundle\Response\ApiResponseConverter;
use Contao\McpBundle\Tool\UserTemplateTools;
use Contao\McpBundle\UserTemplate\UserTemplateValidator;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class UserTemplateToolsTest extends TestCase
{
    public function testValidatesWithoutDispatchingThroughTheApi(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $kernel
            ->expects($this->never())
            ->method('handle')
        ;
        $loader = $this->createMock(ContaoFilesystemLoader::class);
        $loader
            ->expects($this->once())
            ->method('getFirst')
            ->with('content_element/text', 'demo')
            ->willReturn('@Contao_Test/content_element/text.html.twig')
        ;

        $result = new UserTemplateTools(
            $this->createStub(ResourceMetadataCollectionFactoryInterface::class),
            $kernel,
            new ApiRequestFactory($this->createStub(UrlGeneratorInterface::class)),
            new RequestStack(),
            new ApiResponseConverter(),
            new UserTemplateValidator(new Environment(new ArrayLoader()), $loader),
        )->validate('content_element/text', '{{ value }}', 'demo');

        $this->assertSame(['identifier' => 'content_element/text', 'valid' => true, 'errors' => []], $result);
    }

    public function testRejectsArbitraryOperationNames(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Only advertised create_* and rename_* operations');

        $metadata = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $tools = new UserTemplateTools(
            $metadata,
            $this->createStub(HttpKernelInterface::class),
            new ApiRequestFactory($this->createStub(UrlGeneratorInterface::class)),
            new RequestStack([Request::create('/')]),
            new ApiResponseConverter(),
            new UserTemplateValidator(new Environment(new ArrayLoader()), $this->createStub(ContaoFilesystemLoader::class)),
        );
        $tools->executeOperation('save', 'content_element/text');
    }
}
