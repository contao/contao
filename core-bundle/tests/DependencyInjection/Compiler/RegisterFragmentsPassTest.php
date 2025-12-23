<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DependencyInjection\Compiler;

use Contao\ContentProxy;
use Contao\CoreBundle\Controller\FrontendModule\TwoFactorController;
use Contao\CoreBundle\DependencyInjection\Compiler\RegisterFragmentsPass;
use Contao\CoreBundle\EventListener\GlobalsMapListener;
use Contao\CoreBundle\Fragment\FragmentCompositor;
use Contao\CoreBundle\Fragment\FragmentPreHandlerInterface;
use Contao\CoreBundle\Fragment\FragmentRegistry;
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Contao\CoreBundle\Fragment\Reference\FrontendModuleReference;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Container\ContainerInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\ResolveClassPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class RegisterFragmentsPassTest extends TestCase
{
    public function testCreatesChildDefinitionForFragmentsWithOnlyOneTag(): void
    {
        $elementController = new Definition('App\Fragments\Text');
        $elementController->addTag('contao.content_element');

        $moduleController = new Definition('App\Fragments\LoginController');
        $moduleController->addTag('contao.frontend_module', ['renderer' => 'esi']);

        $container = $this->getContainerWithFragmentServices();
        $container->setDefinition('app.fragments.content_controller', $elementController);
        $container->setDefinition('app.fragments.module_controller', $moduleController);

        (new ResolveClassPass())->process($container);

        $pass = new RegisterFragmentsPass(ContentElementReference::TAG_NAME);
        $pass->process($container);

        $pass = new RegisterFragmentsPass(FrontendModuleReference::TAG_NAME);
        $pass->process($container);

        $methodCalls = $container->getDefinition('contao.fragment.registry')->getMethodCalls();
        [$element, $module] = $methodCalls;

        // Test a content element
        $this->assertSame('add', $element[0]);
        $this->assertSame('contao.content_element.text', $element[1][0]);
        $this->assertMatchesRegularExpression('/^contao.fragment._config_/', (string) $element[1][1]);

        $arguments = $container->getDefinition((string) $element[1][1])->getArguments();
        $this->assertSame('forward', $arguments[1]);

        // Test a front end module
        $this->assertSame('add', $module[0]);
        $this->assertSame('contao.frontend_module.login', $module[1][0]);
        $this->assertMatchesRegularExpression('/^contao.fragment._config_/', (string) $module[1][1]);

        $arguments = $container->getDefinition((string) $module[1][1])->getArguments();
        $this->assertSame('esi', $arguments[1]);
    }

    public function testCreatesChildDefinitionForFragmentsWithMultipleTags(): void
    {
        $elementController = new Definition('App\Fragments\Text');
        $elementController->addTag('contao.content_element', ['type' => 'text_1']);
        $elementController->addTag('contao.content_element', ['type' => 'text_2']);

        $moduleController = new Definition('App\Fragments\LoginController');
        $moduleController->addTag('contao.frontend_module', ['type' => 'login_1', 'renderer' => 'esi']);
        $moduleController->addTag('contao.frontend_module', ['type' => 'login_2', 'renderer' => 'esi']);

        $container = $this->getContainerWithFragmentServices();
        $container->setDefinition('app.fragments.content_controller', $elementController);
        $container->setDefinition('app.fragments.module_controller', $moduleController);

        (new ResolveClassPass())->process($container);

        $pass = new RegisterFragmentsPass(ContentElementReference::TAG_NAME);
        $pass->process($container);

        $pass = new RegisterFragmentsPass(FrontendModuleReference::TAG_NAME);
        $pass->process($container);

        $methodCalls = $container->getDefinition('contao.fragment.registry')->getMethodCalls();
        [$element1, $element2, $module1, $module2] = $methodCalls;

        // Test content elements
        $this->assertSame('add', $element1[0]);
        $this->assertSame('contao.content_element.text_1', $element1[1][0]);
        $this->assertMatchesRegularExpression('/^contao.fragment._config_/', (string) $element1[1][1]);

        $arguments = $container->getDefinition((string) $element1[1][1])->getArguments();
        $this->assertSame('forward', $arguments[1]);

        $definition = $container->getDefinition($arguments[0]);
        $this->assertInstanceOf(ChildDefinition::class, $definition);
        $this->assertSame('app.fragments.content_controller', $definition->getParent());

        $this->assertSame('add', $element2[0]);
        $this->assertSame('contao.content_element.text_2', $element2[1][0]);
        $this->assertMatchesRegularExpression('/^contao.fragment._config_/', (string) $element2[1][1]);

        $arguments = $container->getDefinition((string) $element2[1][1])->getArguments();
        $this->assertSame('forward', $arguments[1]);

        $definition = $container->getDefinition($arguments[0]);
        $this->assertInstanceOf(ChildDefinition::class, $definition);
        $this->assertSame('app.fragments.content_controller', $definition->getParent());

        // Test front end modules
        $this->assertSame('add', $module1[0]);
        $this->assertSame('contao.frontend_module.login_1', $module1[1][0]);
        $this->assertMatchesRegularExpression('/^contao.fragment._config_/', (string) $module1[1][1]);

        $arguments = $container->getDefinition((string) $module1[1][1])->getArguments();
        $this->assertSame('esi', $arguments[1]);

        $definition = $container->getDefinition($arguments[0]);
        $this->assertInstanceOf(ChildDefinition::class, $definition);
        $this->assertSame('app.fragments.module_controller', $definition->getParent());

        $this->assertSame('add', $module2[0]);
        $this->assertSame('contao.frontend_module.login_2', $module2[1][0]);
        $this->assertMatchesRegularExpression('/^contao.fragment._config_/', (string) $module2[1][1]);

        $arguments = $container->getDefinition((string) $module2[1][1])->getArguments();
        $this->assertSame('esi', $arguments[1]);

        $definition = $container->getDefinition($arguments[0]);
        $this->assertInstanceOf(ChildDefinition::class, $definition);
        $this->assertSame('app.fragments.module_controller', $definition->getParent());
    }

    public function testUsesTheGivenAttributes(): void
    {
        $attributes = [
            'type' => 'foo',
            'renderer' => 'esi',
            'method' => 'bar',
        ];

        $contentController = new Definition('App\Fragments\Text');
        $contentController->addTag('contao.content_element', $attributes);

        $container = $this->getContainerWithFragmentServices();
        $container->setDefinition('app.fragments.content_controller', $contentController);

        (new ResolveClassPass())->process($container);
        $pass = new RegisterFragmentsPass(ContentElementReference::TAG_NAME);
        $pass->process($container);

        $methodCalls = $container->getDefinition('contao.fragment.registry')->getMethodCalls();

        $this->assertSame('add', $methodCalls[0][0]);
        $this->assertSame('contao.content_element.foo', $methodCalls[0][1][0]);
        $this->assertStringMatchesFormat('contao.fragment._config_%S', (string) $methodCalls[0][1][1]);

        $arguments = $container->getDefinition((string) $methodCalls[0][1][1])->getArguments();

        $this->assertSame('app.fragments.content_controller::bar', $arguments[0]);
        $this->assertSame('esi', $arguments[1]);
    }

    public function testDoesNotRedefineExistingServices(): void
    {
        $contentController = new Definition('App\Fragments\Text');
        $contentController->addTag('contao.content_element', ['type' => 'text']);

        $duplicateContentController = new Definition('App\Fragments\EnhancedText');
        $duplicateContentController->addTag('contao.content_element', ['type' => 'text']);

        $container = $this->getContainerWithFragmentServices();
        $container->setDefinition('app.fragments.content_controller.enhanced_text', $duplicateContentController);
        $container->setDefinition('app.fragments.content_controller.text', $contentController);

        (new ResolveClassPass())->process($container);
        $pass = new RegisterFragmentsPass(ContentElementReference::TAG_NAME);
        $pass->process($container);

        $this->assertSame([], $container->getDefinition('app.fragments.content_controller.text')->getTags());
        $this->assertSame(['contao.content_element' => [['type' => 'text']]], $container->getDefinition('app.fragments.content_controller.enhanced_text')->getTags());
    }

    public function testMakesFragmentServicesPublic(): void
    {
        $contentController = new Definition('App\Fragments\Text');
        $contentController->setPublic(false);
        $contentController->addTag('contao.content_element');

        $container = $this->getContainerWithFragmentServices();
        $container->setDefinition('app.fragments.content_controller', $contentController);

        (new ResolveClassPass())->process($container);

        $this->assertFalse($container->findDefinition('app.fragments.content_controller')->isPublic());

        $pass = new RegisterFragmentsPass(ContentElementReference::TAG_NAME);
        $pass->process($container);

        $this->assertTrue($container->findDefinition('app.fragments.content_controller')->isPublic());
    }

    public function testAddsContainerCallIfClassExtendsSymfonyAbstractController(): void
    {
        $definition = new Definition(TwoFactorController::class);
        $definition->addTag('contao.frontend_module');

        $container = $this->getContainerWithFragmentServices();
        $container->setDefinition('app.fragments.two_factor', $definition);

        (new ResolveClassPass())->process($container);

        $pass = new RegisterFragmentsPass(FrontendModuleReference::TAG_NAME);
        $pass->process($container);

        $definition = $container->findDefinition('app.fragments.two_factor');
        $calls = $definition->getMethodCalls();

        $this->assertCount(2, $calls);
        $this->assertSame('setContainer', $calls[1][0]);
        $this->assertInstanceOf(Reference::class, $calls[1][1][0]);
        $this->assertSame(ContainerInterface::class, (string) $calls[1][1][0]);
    }

    public function testCopiesTagsToChildDefinitions(): void
    {
        $contentController = new Definition('App\Fragments\Text');
        $contentController->setPublic(false);
        $contentController->addTag('contao.content_element', ['type' => 'text_1']);
        $contentController->addTag('contao.content_element', ['type' => 'text_2']);
        $contentController->addTag('foo.bar');

        $container = $this->getContainerWithFragmentServices();
        $container->setDefinition('app.fragments.content_controller', $contentController);

        (new ResolveClassPass())->process($container);

        $pass = new RegisterFragmentsPass(ContentElementReference::TAG_NAME);
        $pass->process($container);

        $definition = $container->findDefinition('contao.fragment._contao.content_element.text_1');

        $this->assertInstanceOf(ChildDefinition::class, $definition);
        $this->assertSame('app.fragments.content_controller', $definition->getParent());
        $this->assertSame(['foo.bar' => [[]]], $definition->getTags());

        $definition = $container->findDefinition('contao.fragment._contao.content_element.text_2');

        $this->assertInstanceOf(ChildDefinition::class, $definition);
        $this->assertSame('app.fragments.content_controller', $definition->getParent());
        $this->assertSame(['foo.bar' => [[]]], $definition->getTags());
    }

    public function testRegistersThePreHandlers(): void
    {
        $contentController = new Definition(FragmentPreHandlerInterface::class);
        $contentController->addTag('contao.content_element');

        $container = $this->getContainerWithFragmentServices();
        $container->setDefinition('app.fragments.content_controller', $contentController);

        (new ResolveClassPass())->process($container);

        $pass = new RegisterFragmentsPass(ContentElementReference::TAG_NAME);
        $pass->process($container);

        $arguments = $container->getDefinition('contao.fragment.pre_handlers')->getArguments();

        $this->assertArrayHasKey('contao.content_element.fragment_pre_handler_interface', $arguments[0]);

        $this->assertSame(
            'app.fragments.content_controller',
            (string) $arguments[0]['contao.content_element.fragment_pre_handler_interface'],
        );
    }

    public function testFailsIfThereIsNoPreHandlersDefinition(): void
    {
        $contentController = new Definition('App\Fragments\Text');
        $contentController->addTag('contao.content_element');

        $container = new ContainerBuilder();
        $container->setDefinition('contao.fragment.registry', new Definition());
        $container->setDefinition('contao.command.debug_fragments', new Definition());
        $container->setDefinition('contao.fragment.compositor', new Definition());
        $container->setDefinition('app.fragments.content_controller', $contentController);

        (new ResolveClassPass())->process($container);

        $pass = new RegisterFragmentsPass(ContentElementReference::TAG_NAME);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Missing service definition for "contao.fragment.pre_handlers"');

        $pass->process($container);
    }

    public function testRegistersTheGlobalsMapListener(): void
    {
        $contentController = new Definition('App\Fragments\Text');
        $contentController->addTag('contao.content_element', ['category' => 'content']);

        $container = $this->getContainerWithFragmentServices();
        $container->setDefinition('app.fragments.content_controller', $contentController);

        (new ResolveClassPass())->process($container);

        $pass = new RegisterFragmentsPass(ContentElementReference::TAG_NAME, 'TL_CTE', ContentProxy::class);
        $pass->process($container);

        $definition = null;

        foreach ($container->getDefinitions() as $def) {
            if (GlobalsMapListener::class === $def->getClass()) {
                $definition = $def;
                break;
            }
        }

        $this->assertNotNull($definition);

        $this->assertSame(
            [
                'contao.hook' => [
                    [
                        'hook' => 'initializeSystem',
                        'priority' => 255,
                    ],
                ],
            ],
            $definition->getTags(),
        );

        $this->assertSame(
            [
                'TL_CTE' => [[
                    'content' => [
                        'text' => ContentProxy::class,
                    ],
                ]],
            ],
            $definition->getArguments()[0],
        );

        $this->assertTrue($definition->isPublic());
    }

    public function testFailsToRegisterGlobalsMapListenerIfCategoryIsMissing(): void
    {
        $contentController = new Definition('App\Fragments\Text');
        $contentController->addTag('contao.content_element');

        $container = $this->getContainerWithFragmentServices();
        $container->setDefinition('app.fragments.content_controller', $contentController);

        (new ResolveClassPass())->process($container);

        $pass = new RegisterFragmentsPass(ContentElementReference::TAG_NAME, 'TL_CTE', ContentProxy::class);

        $this->expectException(InvalidConfigurationException::class);

        $pass->process($container);
    }

    #[DataProvider('provideTemplateNames')]
    public function testSetsTemplatesInTemplatesOptionsListener(string|null $template, array $expectedCustomTemplates): void
    {
        $contentController = new Definition('App\Controller\TextController');
        $contentController->addTag('contao.content_element', array_filter(['template' => $template]));

        $container = $this->getContainerWithFragmentServices();
        $container->setDefinition('app.fragments.content_controller', $contentController);
        $container->setDefinition('contao.listener.data_container.template_options', $templateOptionsListener = new Definition());

        (new ResolveClassPass())->process($container);

        $pass = new RegisterFragmentsPass(
            ContentElementReference::TAG_NAME,
            templateOptionsListener: 'contao.listener.data_container.template_options',
            dca: 'tl_content',
        );

        $pass->process($container);

        $this->assertCount(1, $calls = $templateOptionsListener->getMethodCalls());
        $this->assertSame('setDefaultIdentifiersByType', $calls[0][0]);
        $this->assertSame(['tl_content', $expectedCustomTemplates], $calls[0][1]);
    }

    public static function provideTemplateNames(): iterable
    {
        yield 'legacy template' => [
            'ce_text',
            ['text' => 'ce_text'],
        ];

        yield 'legacy template, alternative name' => [
            'ce_foo',
            ['text' => 'ce_foo'],
        ];

        yield 'template inferred from type' => [
            null,
            ['text' => 'content_element/text'],
        ];

        yield 'modern template' => [
            'content_element/text',
            ['text' => 'content_element/text'],
        ];

        yield 'modern template, alternative name' => [
            'content_element/foobar',
            ['text' => 'content_element/foobar'],
        ];
    }

    public function testDoesNothingIfThereIsNoFragmentRegistry(): void
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->expects($this->never())
            ->method('findDefinition')
        ;

        $pass = new RegisterFragmentsPass(ContentElementReference::TAG_NAME);
        $pass->process($container);
    }

    private function getContainerWithFragmentServices(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition('contao.fragment.registry', new Definition(FragmentRegistry::class));
        $container->setDefinition('contao.fragment.pre_handlers', new Definition(ServiceLocator::class, [[]]));
        $container->setDefinition('contao.fragment.compositor', new Definition(FragmentCompositor::class, [[]]));

        return $container;
    }
}
