<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\E2eTests\Backend;

use Contao\E2eTests\AbstractContaoMonorepoE2ETestCase;

class LayoutTest extends AbstractContaoMonorepoE2ETestCase
{
    use BackendTestTrait;

    public function testSwitchesLayoutType(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();
        $path = $fixtures->interpolate('/contao?do=themes&table=tl_layout&act=edit&id={layout_editorial}');
        $backend = $this->login();
        $backend->visit($path);
        $backend->page()->locator('fieldset.collapsed:has(select[name="template"]) legend button')->click();
        $backend->select('template', 'fe_page');
        $backend->submitForm('Save');

        $backend->waitForNavigation(static fn () => $backend->select('type', 'modern'));
        $backend->page()->locator('input[name="cols"]')->first()->waitFor(['state' => 'detached']);

        $this->assertSame('modern', $backend->page()->locator('select[name="type"]')->inputValue());
        $this->assertSame('', $backend->page()->locator('select[name="template"]')->inputValue());
        $this->assertSame(0, $backend->page()->locator('form#tl_layout .tl_error')->count());

        $backend->waitForNavigation(static fn () => $backend->select('template', 'page/layout'));
        $backend->submitForm('Save');
        $backend->visit($path);

        $this->assertSame('modern', $backend->page()->locator('select[name="type"]')->inputValue());
        $this->assertSame('page/layout', $backend->page()->locator('select[name="template"]')->inputValue());

        $backend->waitForNavigation(static fn () => $backend->select('type', 'default'));
        $backend->waitFor('input[name="cols"]:checked');

        $this->assertSame('default', $backend->page()->locator('select[name="type"]')->inputValue());
        $this->assertSame('', $backend->page()->locator('select[name="template"]')->inputValue());
        $this->assertSame(0, $backend->page()->locator('form#tl_layout .tl_error')->count());

        $backend->select('template', 'fe_page');
        $backend->submitForm('Save');
        $backend->visit($path);

        $this->assertSame('default', $backend->page()->locator('select[name="type"]')->inputValue());
        $this->assertSame('fe_page', $backend->page()->locator('select[name="template"]')->inputValue());
    }
}
