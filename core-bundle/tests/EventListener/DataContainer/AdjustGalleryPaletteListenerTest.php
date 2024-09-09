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

use Contao\ContentGallery;
use Contao\ContentProxy;
use Contao\CoreBundle\EventListener\DataContainer\AdjustGalleryPaletteListener;
use Contao\CoreBundle\Tests\TestCase;

class AdjustGalleryPaletteListenerTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['palettes']['gallery'] = 'customTpl';

        parent::setUp();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_CTE'], $GLOBALS['TL_DCA']);

        parent::tearDown();
    }

    public function testAddsTheGalleryTplFieldIfLegacyElementIsInUse(): void
    {
        $GLOBALS['TL_CTE']['media']['gallery'] = ContentGallery::class;

        (new AdjustGalleryPaletteListener())();

        $this->assertSame('galleryTpl,customTpl', $GLOBALS['TL_DCA']['tl_content']['palettes']['gallery']);
    }

    public function testDoesNotAddTheGalleryTplFieldIfLegacyElementIsNotInUse(): void
    {
        $GLOBALS['TL_CTE']['media']['gallery'] = ContentProxy::class;

        (new AdjustGalleryPaletteListener())();

        $this->assertSame('customTpl', $GLOBALS['TL_DCA']['tl_content']['palettes']['gallery']);
    }
}
