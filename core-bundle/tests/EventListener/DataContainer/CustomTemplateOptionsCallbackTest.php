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
use Contao\CoreBundle\EventListener\DataContainer\CustomTemplateOptionsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Database\Result;
use Contao\DataContainer;
use Symfony\Component\HttpFoundation\RequestStack;

class CustomTemplateOptionsCallbackTest extends TestCase
{
    public function testReturnsDefaultTemplate(): void
    {
        $callback = new CustomTemplateOptionsCallback($this->getFramework(), new RequestStack());

        $this->assertSame(['' => 'ce_default'], $callback($this->mockDataContainer()));
    }

    public function testReturnsCustomTemplate(): void
    {
        $callback = new CustomTemplateOptionsCallback($this->getFramework(), new RequestStack());
        $callback->setFragmentTemplate('tl_content', 'default', 'ce_foo');

        $this->assertSame(['' => 'ce_foo'], $callback($this->mockDataContainer()));
    }

    private function getFramework(array $adapters = []): ContaoFramework
    {
        $controllerAdapter = $this->mockAdapter(['getTemplateGroup']);
        $controllerAdapter
            ->method('getTemplateGroup')
            ->willReturnMap([
                ['ce_default_', [], 'ce_default', ['' => 'ce_default']],
                ['ce_foo_', [], 'ce_foo', ['' => 'ce_foo']],
            ])
        ;

        return $this->mockContaoFramework(array_merge([Controller::class => $controllerAdapter], $adapters));
    }

    private function mockDataContainer(): DataContainer
    {
        /** @var Result&MockObject $activeRecord */
        $activeRecord = $this->mockClassWithProperties(Result::class);
        $activeRecord->type = 'default';

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DataContainer::class);
        $dc->table = 'tl_content';
        $dc->activeRecord = $activeRecord;

        return $dc;
    }
}
