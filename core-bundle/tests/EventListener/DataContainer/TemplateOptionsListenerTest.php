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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Database\Result;
use Contao\DataContainer;
use Contao\LegacyElement;
use Contao\LegacyModule;
use Contao\ModuleProxy;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class TemplateOptionsListenerTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $GLOBALS['TL_CTE'] = [
            'foobar' => [
                'fragment_element' => ContentProxy::class,
                'legacy_element' => LegacyElement::class,
            ],
        ];

        $GLOBALS['FE_MOD'] = [
            'foobar' => [
                'fragment_module' => ModuleProxy::class,
                'legacy_module' => LegacyModule::class,
            ],
        ];
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        unset($GLOBALS['TL_CTE']);
    }

    public function testReturnsTheDefaultElementTemplate(): void
    {
        $callback = new TemplateOptionsListener($this->getFramework(), new RequestStack(), [], 'ce_', ContentProxy::class);

        $this->assertSame(['' => 'ce_fragment_element'], $callback($this->mockDataContainer('tl_content', ['type' => 'fragment_element'])));
    }

    public function testReturnsTheDefaultModuleTemplate(): void
    {
        $callback = new TemplateOptionsListener($this->getFramework(), new RequestStack(), [], 'mod_', ModuleProxy::class);

        $this->assertSame(['' => 'mod_fragment_module'], $callback($this->mockDataContainer('tl_module', ['type' => 'fragment_module'])));
    }

    public function testReturnsTheCustomElementTemplate(): void
    {
        $callback = new TemplateOptionsListener($this->getFramework(), new RequestStack(), ['fragment_element' => 'ce_custom_fragment_template'], 'ce_', ContentProxy::class);

        $this->assertSame(['' => 'ce_custom_fragment_template'], $callback($this->mockDataContainer('tl_content', ['type' => 'fragment_element'])));
        $this->assertSame(['' => 'ce_custom_legacy_template'], $callback($this->mockDataContainer('tl_content', ['type' => 'legacy_element'])));
    }

    public function testReturnsTheCustomModuleTemplate(): void
    {
        $callback = new TemplateOptionsListener($this->getFramework(), new RequestStack(), ['fragment_module' => 'mod_custom_fragment_template'], 'mod_', ModuleProxy::class);

        $this->assertSame(['' => 'mod_custom_fragment_template'], $callback($this->mockDataContainer('tl_module', ['type' => 'fragment_module'])));
        $this->assertSame(['' => 'mod_custom_legacy_template'], $callback($this->mockDataContainer('tl_module', ['type' => 'legacy_module'])));
    }

    public function testReturnsAllElementTemplatesInOverrideAllMode(): void
    {
        $request = new Request(['act' => 'overrideAll']);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $callback = new TemplateOptionsListener($this->getFramework(), $requestStack, [], 'ce_', ContentProxy::class);

        $this->assertSame(['' => '-', 'ce_all' => 'ce_all'], $callback($this->mockDataContainer('tl_content')));
    }

    public function testReturnsAllModuleTemplatesInOverrideAllMode(): void
    {
        $request = new Request(['act' => 'overrideAll']);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $callback = new TemplateOptionsListener($this->getFramework(), $requestStack, [], 'mod_', ModuleProxy::class);

        $this->assertSame(['' => '-', 'mod_all' => 'mod_all'], $callback($this->mockDataContainer('tl_module')));
    }

    private function getFramework(array $adapters = []): ContaoFramework
    {
        $controllerAdapter = $this->mockAdapter(['getTemplateGroup']);
        $controllerAdapter
            ->method('getTemplateGroup')
            ->willReturnMap([
                ['ce_', ['ce_all' => 'ce_all']],
                ['ce_fragment_element_', [], 'ce_fragment_element', ['' => 'ce_fragment_element']],
                ['ce_custom_fragment_template_', [], 'ce_custom_fragment_template', ['' => 'ce_custom_fragment_template']],
                ['ce_custom_legacy_template_', [], 'ce_custom_legacy_template', ['' => 'ce_custom_legacy_template']],
                ['mod_', ['mod_all' => 'mod_all']],
                ['mod_fragment_module_', [], 'mod_fragment_module', ['' => 'mod_fragment_module']],
                ['mod_custom_fragment_template_', [], 'mod_custom_fragment_template', ['' => 'mod_custom_fragment_template']],
                ['mod_custom_legacy_template_', [], 'mod_custom_legacy_template', ['' => 'mod_custom_legacy_template']],
            ])
        ;

        return $this->mockContaoFramework(array_merge([Controller::class => $controllerAdapter], $adapters));
    }

    private function mockDataContainer(string $table, array $activeRecord = []): DataContainer
    {
        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DataContainer::class);
        $dc->table = $table;

        if (!empty($activeRecord)) {
            $dc->activeRecord = $this->mockClassWithProperties(Result::class, $activeRecord);
        }

        return $dc;
    }
}
