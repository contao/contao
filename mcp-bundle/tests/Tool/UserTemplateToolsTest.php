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
use Contao\ApiBundle\ApiPlatform\Metadata\UserTemplateResourceMetadataCollectionFactory;
use Contao\ApiBundle\Http\ApiRequestFactory;
use Contao\CoreBundle\Twig\Inspector\Inspector;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\McpBundle\Response\ApiResponseConverter;
use Contao\McpBundle\Tool\UserTemplateTools;
use Contao\McpBundle\UserTemplate\UserTemplateImpactAnalyzer;
use Contao\McpBundle\UserTemplate\UserTemplateValidator;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class UserTemplateToolsTest extends TestCase
{
    public function testRequiresAdministratorForEveryTool(): void
    {
        $security = $this->createStub(Security::class);
        $security
            ->method('isGranted')
            ->willReturn(false)
        ;

        $tools = new UserTemplateTools(
            $this->createStub(ResourceMetadataCollectionFactoryInterface::class),
            $this->createStub(HttpKernelInterface::class),
            new ApiRequestFactory($this->createStub(UrlGeneratorInterface::class)),
            new RequestStack(),
            new ApiResponseConverter(),
            new UserTemplateValidator(new Environment(new ArrayLoader()), $this->createStub(ContaoFilesystemLoader::class)),
            new UserTemplateImpactAnalyzer($this->createStub(ContaoFilesystemLoader::class), $this->createStub(Inspector::class)),
            $security,
        );

        foreach ([
            'listThemes' => [],
            'discover' => [],
            'read' => ['content_element/text'],
            'validate' => ['content_element/text', '{{ value }}'],
            'analyzeImpact' => ['content_element/text'],
            'createOverride' => ['content_element/text'],
            'save' => ['content_element/text', '{{ value }}'],
            'deleteOverride' => ['content_element/text'],
            'executeOperation' => ['create_variant', 'content_element/text'],
        ] as $method => $arguments) {
            try {
                $tools->$method(...$arguments);
                $this->fail($method.' should require an administrator.');
            } catch (ToolCallException $exception) {
                $this->assertSame('Template Studio tools require administrator privileges.', $exception->getMessage());
            }
        }
    }

    public function testListsThemesThroughTheRegisteredApiOperation(): void
    {
        $router = $this->createMock(UrlGeneratorInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_api_user_template_theme_discover', [])
            ->willReturn('/contao/api/user_template_themes')
        ;

        $kernel = $this->createMock(HttpKernelInterface::class);
        $kernel
            ->expects($this->once())
            ->method('handle')
            ->willReturn(new Response('{"themes":[]}', 200))
        ;

        $stack = new RequestStack();
        $stack->push(Request::create('https://example.org/contao/mcp'));

        $tools = new UserTemplateTools(
            new UserTemplateResourceMetadataCollectionFactory($this->createStub(ResourceMetadataCollectionFactoryInterface::class), []),
            $kernel,
            new ApiRequestFactory($router),
            $stack,
            new ApiResponseConverter(),
            new UserTemplateValidator(new Environment(new ArrayLoader()), $this->createStub(ContaoFilesystemLoader::class)),
            new UserTemplateImpactAnalyzer($this->createStub(ContaoFilesystemLoader::class), $this->createStub(Inspector::class)),
            $this->createAdminSecurity(),
        );

        $result = $tools->listThemes();

        $this->assertFalse($result->isError);
        $this->assertSame(200, $result->structuredContent['status']);
    }

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
            new UserTemplateImpactAnalyzer($loader, $this->createStub(Inspector::class)),
            $this->createAdminSecurity(),
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
            new UserTemplateImpactAnalyzer($this->createStub(ContaoFilesystemLoader::class), $this->createStub(Inspector::class)),
            $this->createAdminSecurity(),
        );

        $tools->executeOperation('save', 'content_element/text');
    }

    private function createAdminSecurity(): Security
    {
        $security = $this->createStub(Security::class);
        $security
            ->method('isGranted')
            ->willReturn(true)
        ;

        return $security;
    }
}
