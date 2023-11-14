<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\Config;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Model\Collection;
use Contao\PageModel;
use Contao\RootPageDependentSelect;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class RootPageDependentSelectTest extends TestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_MIME']);

        $this->resetStaticProperties([System::class, Config::class]);

        parent::tearDown();
    }

    public function testRendersMultipleSelects(): void
    {
        $rootPages = [
            $this->mockPageModel(['id' => 1, 'title' => 'Root Page 1', 'language' => 'en']),
            $this->mockPageModel(['id' => 2, 'title' => 'Root Page 2', 'language' => 'de']),
            $this->mockPageModel(['id' => 3, 'title' => 'Root Page 3', 'language' => 'fr']),
        ];

        $pageAdapter = $this->mockAdapter(['findByType']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByType')
            ->with('root', ['order' => 'sorting'])
            ->willReturn(new Collection($rootPages, 'tl_page'))
        ;

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->once())
            ->method('trans')
            ->with('tl_module.rootPageDependentModulesBlankOptionLabel', [], 'contao_tl_module')
            ->willReturn('Choose module for "%s"')
        ;

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $this->mockContaoFramework([PageModel::class => $pageAdapter]));
        $container->set('translator', $translator);
        $container->set('request_stack', $requestStack);

        System::setContainer($container);

        $fieldConfig = [
            'name' => 'rootPageDependentModules',
            'options' => [
                '10' => 'Module-10',
                '20' => 'Module-20',
                '30' => 'Module-30',
            ],
            'eval' => [
                'includeBlankOption' => true,
            ],
        ];

        $widget = new RootPageDependentSelect(RootPageDependentSelect::getAttributesFromDca($fieldConfig, $fieldConfig['name']));

        $expectedOutput =
            <<<'OUTPUT'
                <select
                    name="rootPageDependentModules[]"
                    id="ctrl_rootPageDependentModules-1"
                    class="tl_select tl_chosen"
                    onfocus="Backend.getScrollOffset()"
                >
                    <option value="">Choose module for "Root Page 1"</option>
                    <option value="10">Module-10</option>
                    <option value="20">Module-20</option>
                    <option value="30">Module-30</option>
                </select>
                <select
                    name="rootPageDependentModules[]"
                    id="ctrl_rootPageDependentModules-2"
                    class="tl_select tl_chosen"
                    onfocus="Backend.getScrollOffset()"
                >
                    <option value="">Choose module for "Root Page 2"</option>
                    <option value="10">Module-10</option>
                    <option value="20">Module-20</option>
                    <option value="30">Module-30</option>
                </select>
                <select
                    name="rootPageDependentModules[]"
                    id="ctrl_rootPageDependentModules-3"
                    class="tl_select tl_chosen"
                    onfocus="Backend.getScrollOffset()"
                >
                    <option value="">Choose module for "Root Page 3"</option>
                    <option value="10">Module-10</option>
                    <option value="20">Module-20</option>
                    <option value="30">Module-30</option>
                </select>
                OUTPUT;

        $minifiedExpectedOutput = preg_replace(['/\s\s|\n/', '/\s</'], ['', '<'], $expectedOutput);

        $this->assertSame($minifiedExpectedOutput, $widget->generate());
    }

    private function mockPageModel(array $properties): PageModel
    {
        $model = $this->mockClassWithProperties(PageModel::class);

        foreach ($properties as $key => $property) {
            $model->$key = $property;
        }

        return $model;
    }
}
