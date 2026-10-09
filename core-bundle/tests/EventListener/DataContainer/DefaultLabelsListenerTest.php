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

use Contao\CoreBundle\EventListener\DataContainer\DefaultLabelsListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\System;

class DefaultLabelsListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA'], $GLOBALS['TL_LANG']);
        $this->resetStaticProperties([System::class]);

        parent::tearDown();
    }

    public function testDoesNotReplaceAConfiguredLabelReferencingAMissingTranslation(): void
    {
        $GLOBALS['TL_DCA']['tl_test']['fields']['size']['label'] = &$GLOBALS['TL_LANG']['MSC']['imgSize'];

        new \ReflectionClass(System::class)->setStaticPropertyValue('arrLanguageFiles', ['tl_test' => ['en' => 'en']]);

        $listener = new DefaultLabelsListener();
        $listener('tl_test');

        $GLOBALS['TL_LANG']['MSC']['imgSize'] = ['Image size', 'Help text'];

        $this->assertSame(['Image size', 'Help text'], $GLOBALS['TL_DCA']['tl_test']['fields']['size']['label']);
    }

    public function testDoesNotReplaceAConfiguredOperationLabelPartReferencingAMissingTranslation(): void
    {
        $GLOBALS['TL_LANG']['DCA']['edit'] = ['Fallback label'];

        $GLOBALS['TL_DCA']['tl_test']['list']['operations']['edit']['label'] = [
            &$GLOBALS['TL_LANG']['tl_test']['edit'][0],
            'Description',
        ];

        new \ReflectionClass(System::class)->setStaticPropertyValue('arrLanguageFiles', ['tl_test' => ['en' => 'en']]);

        $listener = new DefaultLabelsListener();
        $listener('tl_test');

        $GLOBALS['TL_LANG']['tl_test']['edit'][0] = 'Edit';

        $this->assertSame(['Edit', 'Description'], $GLOBALS['TL_DCA']['tl_test']['list']['operations']['edit']['label']);
    }

    public function testAddsAFallbackToAnOperationLabelWithoutAFirstPart(): void
    {
        $GLOBALS['TL_LANG']['DCA']['edit'] = ['Fallback label'];
        $GLOBALS['TL_DCA']['tl_test']['list']['operations']['edit']['label'] = [1 => 'Description'];

        new \ReflectionClass(System::class)->setStaticPropertyValue('arrLanguageFiles', ['tl_test' => ['en' => 'en']]);

        $listener = new DefaultLabelsListener();
        $listener('tl_test');

        $this->assertSame([1 => 'Description', 0 => 'Fallback label'], $GLOBALS['TL_DCA']['tl_test']['list']['operations']['edit']['label']);
    }
}
