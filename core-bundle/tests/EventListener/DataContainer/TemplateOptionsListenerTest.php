<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\ContentProxy;
use Contao\Controller;
use Contao\CoreBundle\EventListener\DataContainer\TemplateOptionsListener;
use Contao\CoreBundle\Fixtures\Contao\LegacyElement;
use Contao\CoreBundle\Fixtures\Contao\LegacyModule;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Finder\Finder;
use Contao\CoreBundle\Twig\Finder\FinderFactory;
use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchyInterface;
use Contao\CoreBundle\Twig\Loader\ThemeNamespace;
use Contao\DataContainer;
use Contao\ModuleProxy;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class TemplateOptionsListenerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TL_CTE'] = [
            'foobar' => [
                'legacy_element' => LegacyElement::class,
            ],
        ];

        $GLOBALS['FE_MOD'] = [
            'foobar' => [
                'legacy_module' => LegacyModule::class,
            ],
        ];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_CTE'], $GLOBALS['FE_MOD']);

        parent::tearDown();
    }

    public function testReturnsElementTemplates(): void
    {
        $callback = $this->getTemplateOptionsListener('ce_', ContentProxy::class);

        $this->assertSame(
            [
                '' => 'content_element/foo [App]',
                'content_element/foo/variant' => 'content_element/foo/variant [Global]',
            ],
            $callback($this->mockDataContainer('tl_content', ['type' => 'foo_element_type']))
        );

        $this->assertSame(
            [
                '' => 'ce_legacy_fragment_element',
                'ce_legacy_fragment_element_variant' => 'ce_legacy_fragment_element_variant',
            ],
            $callback($this->mockDataContainer('tl_content', ['type' => 'legacy_fragment_element']))
        );
    }

    public function testReturnsModuleTemplates(): void
    {
        $callback = $this->getTemplateOptionsListener('mod_', ModuleProxy::class);

        $this->assertSame(
            ['' => 'frontend_module/foo [App]'],
            $callback($this->mockDataContainer('tl_module', ['type' => 'foo_module_type']))
        );

        $this->assertSame(
            ['' => 'mod_legacy_fragment_module'],
            $callback($this->mockDataContainer('tl_module', ['type' => 'legacy_fragment_module']))
        );
    }

    /**
     * @dataProvider provideOverrideAllScenarios
     */
    public function testReturnsCommonElementTemplatesInOverrideAllMode(string|null $commonType, array $expectedOptions): void
    {
        $session = $this->mockSession();
        $session->replace(['CURRENT' => ['IDS' => [1, 2, 3]]]);

        $request = new Request(['act' => 'overrideAll']);
        $request->setSession($session);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $result = $this->createMock(Result::class);

        $result
            ->method('rowCount')
            ->willReturn(null !== $commonType ? 1 : 2)
        ;

        if (null !== $commonType) {
            $result
                ->method('fetchOne')
                ->willReturn($commonType)
            ;
        }

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('quoteIdentifier')
            ->willReturnArgument(0)
        ;

        $connection
            ->method('executeQuery')
            ->with(
                sprintf('SELECT type FROM %s WHERE id IN (?) GROUP BY type LIMIT 2', 'tl_foo'),
                [[1, 2, 3]],
                [Connection::PARAM_INT_ARRAY]
            )
            ->willReturn($result)
        ;

        $callback = $this->getTemplateOptionsListener('ce_', ContentProxy::class, $requestStack, $connection);

        $this->assertSame(
            $expectedOptions,
            $callback($this->mockDataContainer('tl_foo'))
        );
    }

    public function provideOverrideAllScenarios(): \Generator
    {
        yield 'selected items share a common type' => [
            'foo_element_type',
            [
                '' => 'content_element/foo [App]',
                'content_element/foo/variant' => 'content_element/foo/variant [Global]',
            ],
        ];

        yield 'selected legacy items share a common type' => [
            'legacy_fragment_element',
            [
                '' => 'ce_legacy_fragment_element',
                'ce_legacy_fragment_element_variant' => 'ce_legacy_fragment_element_variant',
            ],
        ];

        yield 'selected items have different types' => [
            null,
            ['' => '-'],
        ];
    }

    private function getTemplateOptionsListener(string $legacyTemplatePrefix, string $legacyProxyClass, RequestStack|null $requestStack = null, Connection|null $connection = null): TemplateOptionsListener
    {
        $hierarchy = $this->createMock(TemplateHierarchyInterface::class);
        $hierarchy
            ->method('getInheritanceChains')
            ->willReturn([
                'content_element/foo' => [
                    '/templates/content_element/foo.html.twig' => '@Contao_App/content_element/foo.html.twig',
                ],
                'content_element/foo/variant' => [
                    '/templates/content_element/foo/variant.html.twig' => '@Contao_Global/content_element/foo/variant.html.twig',
                ],
                'frontend_module/foo' => [
                    '/templates/frontend_module/foo.html.twig' => '@Contao_App/frontend_module/foo.html.twig',
                ],
            ])
        ;

        $finder = new Finder(
            $hierarchy,
            $this->createMock(ThemeNamespace::class),
            $this->createMock(TranslatorInterface::class)
        );

        $finderFactory = $this->createMock(FinderFactory::class);
        $finderFactory
            ->method('create')
            ->willReturn($finder)
        ;

        $listener = new TemplateOptionsListener(
            $finderFactory,
            $connection ?? $this->createMock(Connection::class),
            $this->mockFramework(),
            $requestStack ?? new RequestStack(),
            $legacyTemplatePrefix,
            $legacyProxyClass
        );

        $listener->setDefaultIdentifiersByType([
            'foo_element_type' => 'content_element/foo',
            'foo_module_type' => 'frontend_module/foo',
        ]);

        return $listener;
    }

    /**
     * @return ContaoFramework&MockObject
     */
    private function mockFramework(): ContaoFramework
    {
        $controllerAdapter = $this->mockAdapter(['getTemplateGroup']);
        $controllerAdapter
            ->method('getTemplateGroup')
            ->willReturnMap([
                [
                    'ce_legacy_fragment_element_', [], 'ce_legacy_fragment_element', [
                        '' => 'ce_legacy_fragment_element',
                        'ce_legacy_fragment_element_variant' => 'ce_legacy_fragment_element_variant',
                    ],
                ],
                [
                    'mod_legacy_fragment_module_', [], 'mod_legacy_fragment_module', [
                        '' => 'mod_legacy_fragment_module',
                    ],
                ],
            ])
        ;

        return $this->mockContaoFramework([Controller::class => $controllerAdapter]);
    }

    /**
     * @return DataContainer&MockObject
     */
    private function mockDataContainer(string $table, array $currentRecord = []): DataContainer
    {
        $dc = $this->mockClassWithProperties(DataContainer::class);
        $dc->table = $table;

        if (!empty($currentRecord)) {
            $dc
                ->method('getCurrentRecord')
                ->willReturn($currentRecord)
            ;
        }

        return $dc;
    }
}
