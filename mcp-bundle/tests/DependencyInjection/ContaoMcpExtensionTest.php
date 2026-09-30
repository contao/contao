<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\Tests\DependencyInjection;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use Contao\ApiBundle\Http\ApiRequestFactory;
use Contao\CoreBundle\File\UploadSizeProvider;
use Contao\CoreBundle\Search\Backend\BackendSearch;
use Contao\CoreBundle\Twig\Inspector\Inspector;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Studio\TemplateSnapshots;
use Contao\McpBundle\ContaoMcpBundle;
use Contao\McpBundle\Controller\SerializedMcpController;
use Contao\McpBundle\Tool\TemplateSnapshotTools;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Server\Builder;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\AI\McpBundle\McpBundle;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Yaml\Yaml;
use Twig\Environment;

final class ContaoMcpExtensionTest extends TestCase
{
    public function testConfiguresTheMaximumBinaryPayloadSize(): void
    {
        $container = $this->getContainerBuilder(maximumBinaryPayloadSize: 1234);

        $this->assertSame(1234, $container->getParameter('contao.mcp.max_binary_payload_size'));
    }

    public function testRegistersTemplateToolsThroughTheBundleConfiguration(): void
    {
        $container = $this->getContainerBuilder();
        $config = Yaml::parseFile(\dirname(__DIR__, 2).'/config/mcp.yaml')['mcp'];

        $bundle = new McpBundle();
        $bundle->getContainerExtension()->load([$config], $container);
        $bundle->build($container);

        $container->getDefinition('mcp.server.contao_backend.builder')->setPublic(true);
        $container->compile();

        $this->assertInstanceOf(SerializedMcpController::class, $container->get('mcp.server.contao_backend.controller'));

        $builder = $container->get('mcp.server.contao_backend.builder');
        $this->assertInstanceOf(Builder::class, $builder);
        $builder->build();

        $tools = [];

        foreach ($container->getDefinition('mcp.server.contao_backend.builder')->getMethodCalls() as [$method, $arguments]) {
            if ('addTool' === $method) {
                $tools[] = $arguments[1];
            }
        }

        $this->assertSame(
            [
                'contao_api_discover',
                'contao_api_describe',
                'contao_api_execute',
                'contao_template_list_themes',
                'contao_template_discover',
                'contao_template_read',
                'contao_template_validate',
                'contao_template_analyze_impact',
                'contao_template_create_override',
                'contao_template_save',
                'contao_template_delete_override',
                'contao_template_execute_operation',
                'contao_template_snapshot',
                'contao_template_snapshots',
                'contao_template_diff',
                'contao_template_rollback',
            ],
            $tools,
        );

        $resources = [];

        foreach ($container->getDefinition('mcp.server.contao_backend.builder')->getMethodCalls() as [$method, $arguments]) {
            if ('addResource' === $method) {
                $resources[] = $arguments[1];
            }
        }

        $this->assertSame(['contao://template-guidance', 'contao://twig/html-attributes'], $resources);
    }

    public function testKeepsBackendToolsOutOfASecondServer(): void
    {
        $container = $this->getContainerBuilder();
        $config = Yaml::parseFile(\dirname(__DIR__, 2).'/config/mcp.yaml')['mcp'];

        $config['servers']['frontend_example'] = [
            'name' => 'Frontend example',
            'registry' => ['tools' => ['frontend_example']],
        ];

        $bundle = new McpBundle();
        $bundle->getContainerExtension()->load([$config], $container);
        $bundle->build($container);

        $container->getDefinition('mcp.server.frontend_example.builder')->setPublic(true);
        $container->compile();

        $tools = [];

        foreach ($container->getDefinition('mcp.server.frontend_example.builder')->getMethodCalls() as [$method, $arguments]) {
            if ('addTool' === $method) {
                $tools[] = $arguments[1];
            }
        }

        $this->assertSame(['frontend_example'], $tools);
    }

    public function testDoesNotRegisterSnapshotToolsWithoutTemplateStudio(): void
    {
        $container = $this->getContainerBuilder(withSnapshots: false);
        $config = Yaml::parseFile(\dirname(__DIR__, 2).'/config/mcp.yaml')['mcp'];

        $bundle = new McpBundle();
        $bundle->getContainerExtension()->load([$config], $container);
        $bundle->build($container);

        $container->compile();

        $this->assertFalse($container->hasDefinition(TemplateSnapshotTools::class));
    }

    public function testRegistersBackendSearchToolWhenBackendSearchIsConfigured(): void
    {
        $container = $this->getContainerBuilder(true);
        $config = Yaml::parseFile(\dirname(__DIR__, 2).'/config/mcp.yaml')['mcp'];

        $bundle = new McpBundle();
        $bundle->getContainerExtension()->load([$config], $container);
        $bundle->build($container);

        $container->getDefinition('mcp.server.contao_backend.builder')->setPublic(true);
        $container->compile();

        $tools = [];

        foreach ($container->getDefinition('mcp.server.contao_backend.builder')->getMethodCalls() as [$method, $arguments]) {
            if ('addTool' === $method) {
                $tools[] = $arguments[1];
            }
        }

        $this->assertContains('contao_backend_search', $tools);
        $this->assertCount(17, $tools);
    }

    public function testRegistersTheMcpEndpointAsOAuthProtectedResource(): void
    {
        $container = $this->getContainerBuilder();

        $extension = new ContaoMcpBundle()->getContainerExtension();
        $this->assertInstanceOf(PrependExtensionInterface::class, $extension);
        $extension->prepend($container);

        $this->assertSame(
            [
                [
                    'resource' => [
                        'route' => 'contao_mcp_backend',
                        'name' => 'Contao MCP',
                        'scopes' => ['mcp'],
                    ],
                    'cimd_trusted_domains' => [
                        'chatgpt.com',
                        'claude.ai',
                        'vscode.dev',
                    ],
                ],
            ],
            $container->getExtensionConfig('contao_oauth_server'),
        );
    }

    private function getContainerBuilder(bool $withBackendSearch = false, bool $withSnapshots = true, int|null $maximumBinaryPayloadSize = null): ContainerBuilder
    {
        $container = new ContainerBuilder(
            new ParameterBag([
                'kernel.project_dir' => \dirname(__DIR__, 3),
                'kernel.cache_dir' => sys_get_temp_dir(),
                'kernel.build_dir' => sys_get_temp_dir(),
                'kernel.environment' => 'test',
                'kernel.debug' => false,
                'contao.backend.route_prefix' => '/contao',
            ]),
        );

        foreach ([
            'http_kernel' => HttpKernelInterface::class,
            ApiRequestFactory::class => ApiRequestFactory::class,
            UploadSizeProvider::class => UploadSizeProvider::class,
            'request_stack' => RequestStack::class,
            'api_platform.metadata.resource.name_collection_factory' => ResourceNameCollectionFactoryInterface::class,
            'api_platform.metadata.resource.metadata_collection_factory' => ResourceMetadataCollectionFactoryInterface::class,
            'api_platform.openapi.factory' => OpenApiFactoryInterface::class,
            'serializer' => NormalizerInterface::class,
            'twig' => Environment::class,
            'contao.twig.filesystem_loader' => ContaoFilesystemLoader::class,
            'contao.twig.inspector' => Inspector::class,
            'security.helper' => Security::class,
        ] as $id => $class) {
            $container->register($id, $class)->setSynthetic(true)->setPublic(true);
        }

        if ($withSnapshots) {
            $container->register('contao.twig.studio.template_snapshots', TemplateSnapshots::class)->setSynthetic(true)->setPublic(true);
        }

        if ($withBackendSearch) {
            $container->register('contao.search.backend', BackendSearch::class)->setSynthetic(true)->setPublic(true);
        }

        $otherTool = new class() {
            #[McpTool(name: 'frontend_example')]
            public function __invoke(): string
            {
                return 'Frontend example';
            }
        };

        // Older DI versions cannot autoconfigure anonymous classes because their names
        // contain a null byte
        $container->register('frontend_example', $otherTool::class)->addTag('mcp.tool', ['method' => '__invoke']);
        $container->register('event_dispatcher', EventDispatcher::class);
        $container->register('logger', NullLogger::class);
        $container->register('lock.factory', LockFactory::class)->addArgument(new Definition(InMemoryStore::class));

        $extension = new ContaoMcpBundle()->getContainerExtension();
        $extension->load(null === $maximumBinaryPayloadSize ? [] : [['max_binary_payload_size' => $maximumBinaryPayloadSize]], $container);
        new ContaoMcpBundle()->build($container);

        return $container;
    }
}
