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

use Contao\ApiBundle\Http\ApiRequestFactory;
use Contao\ApiBundle\Resource\DataContainerResourceRegistry;
use Contao\McpBundle\ContaoMcpBundle;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Server\Builder;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\AI\McpBundle\Controller\McpController;
use Symfony\AI\McpBundle\McpBundle;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Yaml\Yaml;

final class ContaoMcpExtensionTest extends TestCase
{
    public function testRegistersExactlySevenToolsThroughTheBundleConfiguration(): void
    {
        $container = $this->getContainerBuilder();
        $config = Yaml::parseFile(\dirname(__DIR__, 2).'/skeleton/config/mcp.yaml')['mcp'];

        $bundle = new McpBundle();
        $bundle->getContainerExtension()->load([$config], $container);
        $bundle->build($container);

        $container->getDefinition('mcp.server.contao_backend.builder')->setPublic(true);
        $container->compile();

        $this->assertSame('/_mcp/backend', $container->getParameter('contao_mcp.backend_path'));
        $this->assertInstanceOf(McpController::class, $container->get('mcp.server.contao_backend.controller'));

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
                'contao_dc_discover_resources',
                'contao_dc_describe_resource',
                'contao_dc_list_records',
                'contao_dc_read_record',
                'contao_dc_create_record',
                'contao_dc_update_record',
                'contao_dc_delete_record',
            ],
            $tools,
        );
    }

    public function testKeepsBackendToolsOutOfASecondServer(): void
    {
        $container = $this->getContainerBuilder();
        $config = Yaml::parseFile(\dirname(__DIR__, 2).'/skeleton/config/mcp.yaml')['mcp'];

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

    public function testAllowsConfiguringTheBackendPath(): void
    {
        $container = $this->getContainerBuilder(['backend_path' => '/custom/backend']);

        $this->assertSame('/custom/backend', $container->getParameter('contao_mcp.backend_path'));
    }

    public function testPrependsBackendAccessBeforePublicRules(): void
    {
        $container = $this->getContainerBuilder();
        $container->registerExtension(new SecurityExtension());

        $publicRule = ['access_control' => [['path' => '^/', 'roles' => ['PUBLIC_ACCESS']]]];
        $container->loadFromExtension('security', $publicRule);

        $extension = new ContaoMcpBundle()->getContainerExtension();
        $this->assertInstanceOf(PrependExtensionInterface::class, $extension);
        $extension->prepend($container);

        $this->assertSame(
            [
                ['access_control' => [['route' => 'contao_mcp_backend', 'roles' => ['ROLE_USER']]]],
                $publicRule,
            ],
            $container->getExtensionConfig('security'),
        );
    }

    private function getContainerBuilder(array $config = []): ContainerBuilder
    {
        $container = new ContainerBuilder(
            new ParameterBag([
                'kernel.project_dir' => \dirname(__DIR__, 3),
                'kernel.cache_dir' => sys_get_temp_dir(),
                'kernel.build_dir' => sys_get_temp_dir(),
                'kernel.environment' => 'test',
                'kernel.debug' => false,
            ]),
        );

        foreach ([
            'http_kernel' => HttpKernelInterface::class,
            ApiRequestFactory::class => ApiRequestFactory::class,
            'request_stack' => RequestStack::class,
            DataContainerResourceRegistry::class => DataContainerResourceRegistry::class,
        ] as $id => $class) {
            $container->register($id, $class)->setSynthetic(true)->setPublic(true);
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

        $extension = new ContaoMcpBundle()->getContainerExtension();
        $extension->load([$config], $container);

        return $container;
    }
}
