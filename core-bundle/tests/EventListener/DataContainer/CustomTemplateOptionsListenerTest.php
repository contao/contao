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

use Contao\Controller;
use Contao\CoreBundle\EventListener\DataContainer\CustomTemplateOptionsListener;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Database\Result;
use Contao\DataContainer;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CustomTemplateOptionsListenerTest extends TestCase
{
    public function testReturnsTheDefaultTemplate(): void
    {
        $callback = new CustomTemplateOptionsListener($this->getFramework(), new RequestStack());

        $this->assertSame(['' => 'mod_article'], $callback->onArticle($this->mockDataContainer('tl_article')));
        $this->assertSame(['' => 'ce_default'], $callback->onContent($this->mockDataContainer('tl_content')));
        $this->assertSame(['' => 'form_wrapper'], $callback->onForm($this->mockDataContainer('tl_form')));
        $this->assertSame(['' => 'form_default'], $callback->onFormField($this->mockDataContainer('tl_form_field')));
        $this->assertSame(['' => 'mod_default'], $callback->onModule($this->mockDataContainer('tl_module')));
    }

    public function testReturnsTheCustomTemplate(): void
    {
        $callback = new CustomTemplateOptionsListener($this->getFramework(), new RequestStack());
        $callback->setFragmentTemplate('tl_content', 'default', 'ce_foo');
        $callback->setFragmentTemplate('tl_module', 'default', 'mod_foo');

        $this->assertSame(['' => 'ce_foo'], $callback->onContent($this->mockDataContainer('tl_content')));
        $this->assertSame(['' => 'mod_foo'], $callback->onModule($this->mockDataContainer('tl_module')));
    }

    public function testReturnsAllTemplatesInOverrideAllMode(): void
    {
        $request = new Request(['act' => 'overrideAll']);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $callback = new CustomTemplateOptionsListener($this->getFramework(), $requestStack);

        $this->assertSame(['' => '-', 'ce_custom' => 'ce_custom (global)'], $callback->onContent($this->mockDataContainer('tl_content')));
        $this->assertSame(['' => '-', 'mod_custom' => 'mod_custom (global)'], $callback->onModule($this->mockDataContainer('tl_module')));
        $this->assertSame(['' => '-', 'form_custom' => 'form_custom (global)'], $callback->onFormField($this->mockDataContainer('tl_form_field')));
    }

    private function getFramework(array $adapters = []): ContaoFramework
    {
        $controllerAdapter = $this->mockAdapter(['getTemplateGroup']);
        $controllerAdapter
            ->method('getTemplateGroup')
            ->willReturnMap([
                ['ce_default_', [], 'ce_default', ['' => 'ce_default']],
                ['ce_foo_', [], 'ce_foo', ['' => 'ce_foo']],
                ['ce_', ['ce_custom' => 'ce_custom (global)']],
                ['mod_default_', [], 'mod_default', ['' => 'mod_default']],
                ['mod_foo_', [], 'mod_foo', ['' => 'mod_foo']],
                ['mod_', ['mod_custom' => 'mod_custom (global)']],
                ['mod_article_', [], 'mod_article', ['' => 'mod_article']],
                ['form_wrapper_', [], 'form_wrapper', ['' => 'form_wrapper']],
                ['form_default_', [], 'form_default', ['' => 'form_default']],
                ['form_', ['form_custom' => 'form_custom (global)']],
            ])
        ;

        return $this->mockContaoFramework(array_merge([Controller::class => $controllerAdapter], $adapters));
    }

    private function mockDataContainer(string $table): DataContainer
    {
        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DataContainer::class);
        $dc->table = $table;

        if (\in_array($table, ['tl_content', 'tl_module', 'tl_form_field'], true)) {
            /** @var Result&MockObject $activeRecord */
            $activeRecord = $this->mockClassWithProperties(Result::class);
            $activeRecord->type = 'default';

            $dc->activeRecord = $activeRecord;
        }

        return $dc;
    }
}
