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
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\EventListener\DataContainer\CustomTemplateOptionsListener;
use Contao\CoreBundle\Fragment\FragmentConfig;
use Contao\CoreBundle\Fragment\FragmentRegistry;
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Contao\CoreBundle\Fragment\Reference\FrontendModuleReference;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Database\Result;
use Contao\DataContainer;
use Contao\LegacyElement;
use Contao\LegacyModule;
use Contao\ModuleProxy;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CustomTemplateOptionsListenerTest extends TestCase
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

    public function testReturnsTheDefaultTemplate(): void
    {
        $callback = new CustomTemplateOptionsListener($this->getFramework(), new RequestStack(), $this->getFragmentRegistry());
        $callback->setContainer($this->getContainer());

        $this->assertSame(['' => 'mod_article'], $callback->onArticle($this->mockDataContainer('tl_article')));
        $this->assertSame(['' => 'ce_foobar'], $callback->onContent($this->mockDataContainer('tl_content', ['type' => 'foobar'])));
        $this->assertSame(['' => 'form_wrapper'], $callback->onForm($this->mockDataContainer('tl_form')));
        $this->assertSame(['' => 'form_foobar'], $callback->onFormField($this->mockDataContainer('tl_form_field', ['type' => 'foobar'])));
        $this->assertSame(['' => 'mod_foobar'], $callback->onModule($this->mockDataContainer('tl_module', ['type' => 'foobar'])));
    }

    public function testReturnsTheCustomTemplate(): void
    {
        $callback = new CustomTemplateOptionsListener($this->getFramework(), new RequestStack(), $this->getFragmentRegistry());
        $callback->setContainer($this->getContainer());

        $this->assertSame(['' => 'ce_custom_fragment_template'], $callback->onContent($this->mockDataContainer('tl_content', ['type' => 'fragment_element'])));
        $this->assertSame(['' => 'ce_custom_legacy_template'], $callback->onContent($this->mockDataContainer('tl_content', ['type' => 'legacy_element'])));
        $this->assertSame(['' => 'mod_custom_fragment_template'], $callback->onModule($this->mockDataContainer('tl_module', ['type' => 'fragment_module'])));
        $this->assertSame(['' => 'mod_custom_legacy_template'], $callback->onModule($this->mockDataContainer('tl_module', ['type' => 'legacy_module'])));
    }

    public function testReturnsAllTemplatesInOverrideAllMode(): void
    {
        $request = new Request(['act' => 'overrideAll']);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $callback = new CustomTemplateOptionsListener($this->getFramework(), $requestStack, $this->getFragmentRegistry());
        $callback->setContainer($this->getContainer());

        $this->assertSame(['' => '-', 'ce_all' => 'ce_all'], $callback->onContent($this->mockDataContainer('tl_content')));
        $this->assertSame(['' => '-', 'mod_all' => 'mod_all'], $callback->onModule($this->mockDataContainer('tl_module')));
        $this->assertSame(['' => '-', 'form_all' => 'form_all'], $callback->onFormField($this->mockDataContainer('tl_form_field')));
    }

    private function getFramework(array $adapters = []): ContaoFramework
    {
        $controllerAdapter = $this->mockAdapter(['getTemplateGroup']);
        $controllerAdapter
            ->method('getTemplateGroup')
            ->willReturnMap([
                ['ce_', ['ce_all' => 'ce_all']],
                ['ce_foobar_', [], 'ce_foobar', ['' => 'ce_foobar']],
                ['ce_custom_fragment_template_', [], 'ce_custom_fragment_template', ['' => 'ce_custom_fragment_template']],
                ['ce_custom_legacy_template_', [], 'ce_custom_legacy_template', ['' => 'ce_custom_legacy_template']],
                ['mod_', ['mod_all' => 'mod_all']],
                ['mod_foobar_', [], 'mod_foobar', ['' => 'mod_foobar']],
                ['mod_custom_fragment_template_', [], 'mod_custom_fragment_template', ['' => 'mod_custom_fragment_template']],
                ['mod_custom_legacy_template_', [], 'mod_custom_legacy_template', ['' => 'mod_custom_legacy_template']],
                ['mod_article_', [], 'mod_article', ['' => 'mod_article']],
                ['form_', ['form_all' => 'form_all']],
                ['form_wrapper_', [], 'form_wrapper', ['' => 'form_wrapper']],
                ['form_foobar_', [], 'form_foobar', ['' => 'form_foobar']],
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

    private function getContainer(): ContainerInterface
    {
        $container = new ContainerBuilder();

        $foobarElementController = $this->getMockForAbstractClass(AbstractContentElementController::class);
        $foobarElementController->setFragmentOptions(['template' => 'ce_custom_fragment_template']);

        $foobarModuleController = $this->getMockForAbstractClass(AbstractFrontendModuleController::class);
        $foobarModuleController->setFragmentOptions(['template' => 'mod_custom_fragment_template']);

        $container->set('foobar_element_controller', $foobarElementController);
        $container->set('foobar_module_controller', $foobarModuleController);

        return $container;
    }

    private function getFragmentRegistry(): FragmentRegistry
    {
        $fragmentRegistry = new FragmentRegistry();
        $fragmentRegistry->add(ContentElementReference::TAG_NAME.'.fragment_element', new FragmentConfig('foobar_element_controller'));
        $fragmentRegistry->add(FrontendModuleReference::TAG_NAME.'.fragment_module', new FragmentConfig('foobar_module_controller'));

        return $fragmentRegistry;
    }
}
