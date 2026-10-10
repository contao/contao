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
use Contao\CoreBundle\Twig\Studio\Operation\AbstractOperation;
use Contao\McpBundle\Response\ApiResponseConverter;
use Contao\McpBundle\Tool\UserTemplateTools;
use Contao\McpBundle\UserTemplate\UserTemplateImpactAnalyzer;
use Contao\McpBundle\UserTemplate\UserTemplateValidator;
use Mcp\Capability\Discovery\DocBlockParser;
use Mcp\Capability\Discovery\SchemaGenerator;
use Mcp\Capability\Discovery\SchemaValidator;
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

    public function testExecuteOperationAcceptsGenericObjectParameters(): void
    {
        $schema = new SchemaGenerator(new DocBlockParser())->generate(new \ReflectionMethod(UserTemplateTools::class, 'executeOperation'));
        $parametersSchema = $schema['properties']['parameters'];

        $this->assertSame([], $parametersSchema['default']);
        $this->assertSame(
            [
                ['type' => 'object', 'additionalProperties' => true],
                ['type' => 'array', 'maxItems' => 0],
            ],
            $parametersSchema['anyOf'],
        );
        $this->assertSame(['operation', 'name'], $schema['required']);

        $validator = new SchemaValidator();

        $this->assertSame([], $validator->validateAgainstJsonSchema(['operation' => 'create_variant', 'name' => 'content_element/text', 'parameters' => []], $schema));
        $this->assertSame([], $validator->validateAgainstJsonSchema(['operation' => 'create_variant', 'name' => 'content_element/text', 'parameters' => ['custom_parameter' => 'value']], $schema));
        $this->assertNotSame([], $validator->validateAgainstJsonSchema(['operation' => 'create_variant', 'name' => 'content_element/text', 'parameters' => ['value']], $schema));
    }

    public function testForwardsOperationParameters(): void
    {
        $operation = $this->createStub(AbstractOperation::class);
        $operation
            ->method('getName')
            ->willReturn('create_variant')
        ;

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router
            ->expects($this->exactly(2))
            ->method('generate')
            ->with('contao_api_user_template_operation_create_variant', ['theme' => 'demo'])
            ->willReturn('/contao/api/user_template_operations/create_variant')
        ;

        $payloads = [];

        $kernel = $this->createMock(HttpKernelInterface::class);
        $kernel
            ->expects($this->exactly(2))
            ->method('handle')
            ->willReturnCallback(
                function (Request $request, int $type) use (&$payloads): Response {
                    $this->assertSame(HttpKernelInterface::SUB_REQUEST, $type);
                    $payloads[] = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);

                    return new Response('{}', 200);
                },
            )
        ;

        $stack = new RequestStack();
        $stack->push(Request::create('https://example.org/contao/mcp'));

        $tools = new UserTemplateTools(
            new UserTemplateResourceMetadataCollectionFactory($this->createStub(ResourceMetadataCollectionFactoryInterface::class), [$operation]),
            $kernel,
            new ApiRequestFactory($router),
            $stack,
            new ApiResponseConverter(),
            new UserTemplateValidator(new Environment(new ArrayLoader()), $this->createStub(ContaoFilesystemLoader::class)),
            new UserTemplateImpactAnalyzer($this->createStub(ContaoFilesystemLoader::class), $this->createStub(Inspector::class)),
            $this->createAdminSecurity(),
        );

        $tools->executeOperation('create_variant', 'content_element/text', theme: 'demo');
        $tools->executeOperation('create_variant', 'content_element/text', ['identifier_fragment' => 'compact', 'custom_parameter' => 'value'], 'demo');

        $this->assertSame(
            [
                ['name' => 'content_element/text', 'parameters' => []],
                ['name' => 'content_element/text', 'parameters' => ['identifier_fragment' => 'compact', 'custom_parameter' => 'value']],
            ],
            $payloads,
        );
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
